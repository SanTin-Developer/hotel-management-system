<?php

use App\Mail\RegistrationOtpMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

beforeEach(function () {
    Mail::fake();
    $this->withoutMiddleware(ThrottleRequests::class);
    foreach (['admin@example.com', 'nobody@example.com'] as $email) {
        RateLimiter::clear('password-reset:'.$email);
        cache()->forget('password_reset:v2:'.$email);
    }

    User::factory()->create([
        'name' => 'SanTin',
        'email' => 'admin@example.com',
        'password' => Hash::make('old-password'),
    ]);
});

it('sends a reset code to an existing email address', function () {
    $response = $this->postJson('/api/v1/auth/password/forgot', [
        'email' => 'admin@example.com',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('message', 'If the email exists, a reset code has been sent.');

    Mail::assertSent(
        RegistrationOtpMail::class,
        fn (RegistrationOtpMail $mail) => $mail->fullName === 'SanTin' && strlen($mail->otp) === 6
    );
});

it('does not reveal whether the email exists', function () {
    $response = $this->postJson('/api/v1/auth/password/forgot', [
        'email' => 'nobody@example.com',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('message', 'If the email exists, a reset code has been sent.');

    Mail::assertNothingSent();
});

it('resets the password with a valid OTP', function () {
    $this->postJson('/api/v1/auth/password/forgot', [
        'email' => 'admin@example.com',
    ])->assertOk();

    $otp = Mail::sent(RegistrationOtpMail::class)->first()->otp;

    $response = $this->postJson('/api/v1/auth/password/reset', [
        'email' => 'admin@example.com',
        'otp' => $otp,
        'password' => 'NewPassword123!',
        'password_confirmation' => 'NewPassword123!',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('message', 'Password has been reset successfully.');

    $user = User::where('email', 'admin@example.com')->first();

    expect(Hash::check('NewPassword123!', $user->password))->toBeTrue()
        ->and(Hash::check('old-password', $user->password))->toBeFalse();

    expect(cache()->get('password_reset:v2:admin@example.com'))->toBeNull();
});

it('rejects an incorrect OTP and records the failed attempt', function () {
    $this->postJson('/api/v1/auth/password/forgot', [
        'email' => 'admin@example.com',
    ])->assertOk();

    $response = $this->postJson('/api/v1/auth/password/reset', [
        'email' => 'admin@example.com',
        'otp' => '000000',
        'password' => 'NewPassword123!',
        'password_confirmation' => 'NewPassword123!',
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonPath('errors.otp.0', 'The provided code is incorrect.');

    expect(cache()->get('password_reset:v2:admin@example.com')['attempts'])->toBe(1);

    $user = User::where('email', 'admin@example.com')->first();

    expect(Hash::check('old-password', $user->password))->toBeTrue();
});

it('rejects an expired reset code', function () {
    $this->postJson('/api/v1/auth/password/forgot', [
        'email' => 'admin@example.com',
    ])->assertOk();

    $otp = Mail::sent(RegistrationOtpMail::class)->first()->otp;

    $data = cache()->get('password_reset:v2:admin@example.com');
    $data['expires_at'] = now()->subMinutes(1)->toIso8601String();
    cache()->put('password_reset:v2:admin@example.com', $data);

    $response = $this->postJson('/api/v1/auth/password/reset', [
        'email' => 'admin@example.com',
        'otp' => $otp,
        'password' => 'NewPassword123!',
        'password_confirmation' => 'NewPassword123!',
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonPath('errors.otp.0', 'The reset code has expired. Please request a new one.');
});

it('gracefully handles a corrupted cached reset payload', function () {
    cache()->put('password_reset:v2:admin@example.com', '__PHP_Incomplete_Class');

    $response = $this->postJson('/api/v1/auth/password/reset', [
        'email' => 'admin@example.com',
        'otp' => '123456',
        'password' => 'NewPassword123!',
        'password_confirmation' => 'NewPassword123!',
    ]);

    $response->assertStatus(422);
});

it('hashes and stores the reset code without leaking it back', function () {
    $this->postJson('/api/v1/auth/password/forgot', [
        'email' => 'admin@example.com',
    ])->assertOk();

    $otp = Mail::sent(RegistrationOtpMail::class)->first()->otp;

    $stored = cache()->get('password_reset:v2:admin@example.com');

    expect($stored)
        ->toBeArray()
        ->otp_hash->not->toBe($otp)
        ->and(Hash::check($otp, $stored['otp_hash']))->toBeTrue()
        ->and($stored['expires_at'])->toBeString()
        ->and($stored['attempts'])->toBeInt();
});

it('rejects a reset when no code was requested', function () {
    $response = $this->postJson('/api/v1/auth/password/reset', [
        'email' => 'admin@example.com',
        'otp' => '123456',
        'password' => 'NewPassword123!',
        'password_confirmation' => 'NewPassword123!',
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonPath('errors.email.0', 'No reset request found. Please request a new one.');
});
