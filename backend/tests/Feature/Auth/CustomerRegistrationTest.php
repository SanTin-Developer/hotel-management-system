<?php

use App\Mail\RegistrationOtpMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(ThrottleRequests::class);
    RateLimiter::clear('registration-otp:customer@example.com');
    User::where('email', 'customer@example.com')->delete();
});

it('starts customer registration and sends an OTP without creating a user', function () {
    Mail::fake();

    $payload = [
        'full_name' => 'SanTin',
        'country' => 'Cambodia',
        'id_type' => 'national_id',
        'id_number' => '123456789',
        'email' => 'customer@example.com',
        'phone' => '012345678',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];

    $response = $this->postJson(
        '/api/v1/auth/register',
        $payload
    );

    // 1. API returns 202
    $response->assertCreated();

    // 2. Response contains verification information
    $response->assertJsonStructure([
        'message',
        'verification_id',
        'expires_at',
    ]);

    // 3. Temporary registration exists
    $this->assertDatabaseHas('registration_otps', [
        'email' => 'customer@example.com',
        'full_name' => 'SanTin',
        'country' => 'Cambodia',
        'id_type' => 'national_id',
        'id_number' => '123456789',
        'phone' => '012345678',
    ]);

    // 4. OTP email was sent
    Mail::assertSent(
        RegistrationOtpMail::class,
        function (RegistrationOtpMail $mail) {
            return $mail->fullName === 'SanTin'
                && $mail->otp !== '';
        }
    );

    // 5. User must NOT exist before OTP verification
    $this->assertDatabaseMissing('users', [
        'email' => 'customer@example.com',
    ]);

    // 6. Guest must NOT exist before OTP verification
    $this->assertDatabaseMissing('guests', [
        'email' => 'customer@example.com',
    ]);
});
