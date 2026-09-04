<?php

use App\Mail\RegistrationOtpMail;
use App\Models\Guest;
use App\Models\RegistrationOtp;
use App\Models\User;
use App\Services\Auth\RegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Mail::fake();

    Role::create([
        'name' => 'customer',
        'guard_name' => 'web',
    ]);

});

// Verify OTP
it('verifies a valid OTP and creates the customer account', function () {
    $service = app(RegistrationService::class);

    $registration = $service->createRegistration([
        'full_name' => 'SanTin',
        'country' => 'Cambodia',
        'id_type' => 'national_id',
        'id_number' => '123456789',
        'email' => 'customer@example.com',
        'phone' => '012345678',
        'password' => 'password123',
    ]);

    Mail::assertSent(
        RegistrationOtpMail::class,
        fn (RegistrationOtpMail $mail) => $mail->fullName === 'SanTin'
    );

    $mail = Mail::sent(RegistrationOtpMail::class)->first();

    expect($mail)->not()->toBeNull();

    $otp = $mail->otp;

    $response = $this->postJson('/api/v1/auth/verify-otp', [
        'verification_id' => $registration->id,
        'otp' => $otp,
    ]);

    $response
        ->assertStatus(201)
        ->assertJsonStructure([
            'message',
            'user' => [
                'id',
                'name',
                'email',
                'roles',
            ],
            'token',
        ]);

    expect(
        User::where('email', 'customer@example.com')->exists()
    )->toBeTrue();

    expect(
        Guest::where('email', 'customer@example.com')->exists()
    )->toBeTrue();

    $user = User::where('email', 'customer@example.com')->first();

    expect($user->hasRole('customer'))->toBeTrue();

    expect(
        RegistrationOtp::where('id', $registration->id)->exists()
    )->toBeFalse();
});

// Wrong OTP
it('rejects an incorrect OTP', function () {
    $registration = app(RegistrationService::class)->createRegistration([
        'full_name' => 'SanTin',
        'country' => 'Cambodia',
        'id_type' => 'national_id',
        'id_number' => '123456789',
        'email' => 'wrong-otp@example.com',
        'phone' => '012345678',
        'password' => 'password123',
    ]);

    $response = $this->postJson('/api/v1/auth/verify-otp', [
        'verification_id' => $registration->id,
        'otp' => '000000',
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonPath('errors.otp.0', 'The provided OTP is incorrect.');

    expect($registration->fresh()->attempts)->toBe(1);

    expect(
        User::where('email', 'wrong-otp@example.com')->exists()
    )->toBeFalse();
});

// Expired OTP
it('rejects an expired OTP', function () {
    $registration = app(RegistrationService::class)->createRegistration([
        'full_name' => 'SanTin',
        'country' => 'Cambodia',
        'id_type' => 'national_id',
        'id_number' => '123456789',
        'email' => 'expired@example.com',
        'phone' => '012345678',
        'password' => 'password123',
    ]);

    $registration->update([
        'expires_at' => now()->subMinute(),
    ]);

    $response = $this->postJson('/api/v1/auth/verify-otp', [
        'verification_id' => $registration->id,
        'otp' => '123456',
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonPath(
            'errors.otp.0',
            'The OTP has expired. Please request a new OTP.'
        );

    expect(
        User::where('email', 'expired@example.com')->exists()
    )->toBeFalse();
});

// Too many Attempts
it('blocks verification after too many incorrect attempts', function () {
    $registration = app(RegistrationService::class)->createRegistration([
        'full_name' => 'SanTin',
        'country' => 'Cambodia',
        'id_type' => 'national_id',
        'id_number' => '123456789',
        'email' => 'attempts@example.com',
        'phone' => '012345678',
        'password' => 'password123',
    ]);

    $registration->update([
        'attempts' => 5,
    ]);

    $response = $this->postJson('/api/v1/auth/verify-otp', [
        'verification_id' => $registration->id,
        'otp' => '000000',
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonPath(
            'errors.otp.0',
            'Too many failed attempts. Please register again.'
        );
});

// Already verified
it('rejects an already verified registration', function () {
    $registration = app(RegistrationService::class)->createRegistration([
        'full_name' => 'SanTin',
        'country' => 'Cambodia',
        'id_type' => 'national_id',
        'id_number' => '123456789',
        'email' => 'verified@example.com',
        'phone' => '012345678',
        'password' => 'password123',
    ]);

    $registration->update([
        'verified_at' => now(),
    ]);

    $response = $this->postJson('/api/v1/auth/verify-otp', [
        'verification_id' => $registration->id,
        'otp' => '123456',
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonPath(
            'errors.verification_id.0',
            'This OTP has already been verified.'
        );
});
