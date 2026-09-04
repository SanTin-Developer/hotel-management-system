<?php

use App\Mail\RegistrationOtpMail;
use App\Services\Auth\RegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

beforeEach(function () {
    Mail::fake();

    // Clear route-level OTP throttle.
    RateLimiter::clear(request()->ip());

    // Clear registration OTP rate-limit buckets.
    RateLimiter::clear('registration-otp:resend@example.com');
    RateLimiter::clear('registration-otp:verified-resend@example.com');
    RateLimiter::clear('registration-otp:rate-limit@example.com');

    // Clear resend OTP rate-limit buckets.
    RateLimiter::clear('resend-registration-otp:resend@example.com');
    RateLimiter::clear('resend-registration-otp:verified-resend@example.com');
    RateLimiter::clear('resend-registration-otp:rate-limit@example.com');
});

it('resends a new OTP successfully', function () {

    $this->withoutMiddleware(ThrottleRequests::class);
    $email = 'resend@example.com';

    // Clear the resend OTP rate-limit bucket for this test.
    RateLimiter::clear('resend-registration-otp:'.$email);

    $registration = app(RegistrationService::class)->createRegistration([
        'full_name' => 'SanTin',
        'country' => 'Cambodia',
        'id_type' => 'national_id',
        'id_number' => '123456789',
        'email' => $email,
        'phone' => '012345678',
        'password' => 'password123',
    ]);

    $oldOtpHash = $registration->otp_hash;

    $registration->update([
        'attempts' => 2,
        'expires_at' => now()->subMinute(),
    ]);

    $response = $this->postJson('/api/v1/auth/resend-otp', [
        'verification_id' => $registration->id,
    ]);

    $response
        ->assertOk()
        ->assertJsonStructure([
            'message',
            'verification_id',
            'expires_at',
        ]);

    $registration->refresh();

    expect($registration->otp_hash)
        ->not->toBe($oldOtpHash);

    expect($registration->attempts)->toBe(0);

    expect($registration->expires_at->isFuture())->toBeTrue();

    Mail::assertSent(
        RegistrationOtpMail::class,
        fn (RegistrationOtpMail $mail) => $mail->fullName === 'SanTin'
    );
});

it('rejects resend for an already verified registration', function () {
    $this->withoutMiddleware(ThrottleRequests::class);
    $email = 'verified-resend@example.com';

    // Clear the resend OTP rate-limit bucket for this test.
    RateLimiter::clear('resend-registration-otp:'.$email);

    $registration = app(RegistrationService::class)->createRegistration([
        'full_name' => 'SanTin',
        'country' => 'Cambodia',
        'id_type' => 'national_id',
        'id_number' => '123456789',
        'email' => $email,
        'phone' => '012345678',
        'password' => 'password123',
    ]);

    $registration->update([
        'verified_at' => now(),
    ]);

    // Clear the OTP email that was sent during registration.
    Mail::fake();

    $response = $this->postJson('/api/v1/auth/resend-otp', [
        'verification_id' => $registration->id,
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonPath(
            'errors.verification_id.0',
            'This registration OTP has already been verified.'
        );

    Mail::assertNotSent(RegistrationOtpMail::class);
});

it('rate limits repeated resend requests', function () {

    $this->withoutMiddleware(ThrottleRequests::class);
    $email = 'rate-limit@example.com';

    // Clear both OTP rate-limit buckets for this test.
    RateLimiter::clear(request()->ip());
    RateLimiter::clear('registration-otp:'.$email);
    RateLimiter::clear('resend-registration-otp:'.$email);

    $registration = app(RegistrationService::class)->createRegistration([
        'full_name' => 'SanTin',
        'country' => 'Cambodia',
        'id_type' => 'national_id',
        'id_number' => '123456789',
        'email' => $email,
        'phone' => '012345678',
        'password' => 'password123',
    ]);

    // First resend
    $this->postJson('/api/v1/auth/resend-otp', [
        'verification_id' => $registration->id,
    ])->assertStatus(200);

    // Second resend
    $this->postJson('/api/v1/auth/resend-otp', [
        'verification_id' => $registration->id,
    ])->assertStatus(200);

    // Third resend
    $this->postJson('/api/v1/auth/resend-otp', [
        'verification_id' => $registration->id,
    ])->assertStatus(200);

    // Fourth resend should hit RegistrationService's 3-attempt limit
    $response = $this->postJson('/api/v1/auth/resend-otp', [
        'verification_id' => $registration->id,
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonStructure([
            'message',
            'errors' => [
                'verification_id',
            ],
        ])
        ->assertJsonPath(
            'errors.verification_id.0',
            fn ($message) => str_contains($message, 'Too many resend attempts.')
        );
});
