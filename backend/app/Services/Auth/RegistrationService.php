<?php

namespace App\Services\Auth;

use App\Mail\RegistrationOtpMail;
use App\Models\Guest;
use App\Models\RegistrationOtp;
use App\Models\User;
use App\Services\Media\CloudinaryService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RegistrationService
{
    public function __construct(
        private readonly CloudinaryService $cloudinary
    ) {}

    public function createRegistration(array $data): RegistrationOtp
    {
        $email = Str::lower(trim($data['email']));
        $photo = $data['photo'] ?? null;

        $photoUrl = null;
        $photoPublicId = null;

        if ($photo instanceof UploadedFile) {
            $upload = $this->cloudinary->upload(
                $photo,
                'hotel/guests'
            );

            $photoUrl = $upload['secure_url'];
            $photoPublicId = $upload['public_id'];
        }

        // Allow only limited OTP generation attempts.
        RateLimiter::hit(
            $this->rateLimitKey($email),
            60
        );

        $opt = (string) random_int(100000, 999999);

        $registration = RegistrationOtp::updateOrCreate(
            [
                'email' => $email,
            ],
            [
                'full_name' => $data['full_name'],
                'country' => $data['country'],
                'id_type' => $data['id_type'] ?? null,
                'id_number' => $data['id_number'] ?? null,
                'email' => $email,
                'phone' => $data['phone'],

                // Store password Hash
                'password' => Hash::make($data['password']),

                'photo_url' => $photoUrl,
                'photo_public_id' => $photoPublicId,

                // Never store the raw OTP. *Important: The raw OTP is not Stored in the database for security reasons.*
                'otp_hash' => Hash::make($opt),

                'expires_at' => now()->addMinutes(10),

                'verified_at' => null,
                'attempts' => 0,
            ]
        );

        Mail::to($registration->email)->send(
            new RegistrationOtpMail(
                fullName: $registration->full_name,
                otp: $opt
            )
        );

        return $registration;
    }

    public function rateLimitKey(string $email): string
    {
        return 'registration-otp:'.$email;
    }

    // Verify the OTP and create a new user account
    public function verifyOtp(int $verificationId, string $otp): array
    {
        /*
        * Phase 1:
        * Validate the OTP without putting failed-attempt updates
        * inside the account-creation transaction.
        */
        $registration = RegistrationOtp::find($verificationId);

        if (! $registration) {
            throw ValidationException::withMessages([
                'verification_id' => 'Registration request not found.',
            ]);
        }

        if ($registration->verified_at) {
            throw ValidationException::withMessages([
                'verification_id' => 'This OTP has already been verified.',
            ]);
        }

        if ($registration->expires_at->isPast()) {
            throw ValidationException::withMessages([
                'otp' => 'The OTP has expired. Please request a new OTP.',
            ]);
        }

        if ($registration->attempts >= 5) {
            throw ValidationException::withMessages([
                'otp' => 'Too many failed attempts. Please register again.',
            ]);
        }

        /*
        * Wrong OTP:
        * Persist the failed attempt in its own transaction.
        */
        if (! Hash::check($otp, $registration->otp_hash)) {
            DB::transaction(function () use ($verificationId) {
                $lockedRegistration = RegistrationOtp::lockForUpdate()
                    ->find($verificationId);

                if (
                    $lockedRegistration &&
                    ! $lockedRegistration->verified_at &&
                    $lockedRegistration->attempts < 5
                ) {
                    $lockedRegistration->increment('attempts');
                }
            });

            throw ValidationException::withMessages([
                'otp' => 'The provided OTP is incorrect.',
            ]);
        }

        /*
        * Phase 2:
        * Correct OTP -> create the account atomically.
        */
        return DB::transaction(function () use ($verificationId, $otp) {
            $registration = RegistrationOtp::lockForUpdate()
                ->find($verificationId);

            if (! $registration) {
                throw ValidationException::withMessages([
                    'verification_id' => 'Registration request not found.',
                ]);
            }

            // Re-check after acquiring the row lock.
            if ($registration->verified_at) {
                throw ValidationException::withMessages([
                    'verification_id' => 'This OTP has already been verified.',
                ]);
            }

            if ($registration->expires_at->isPast()) {
                throw ValidationException::withMessages([
                    'otp' => 'The OTP has expired. Please request a new OTP.',
                ]);
            }

            if ($registration->attempts >= 5) {
                throw ValidationException::withMessages([
                    'otp' => 'Too many failed attempts. Please register again.',
                ]);
            }

            // Re-check OTP after locking the row.
            if (! Hash::check($otp, $registration->otp_hash)) {
                $registration->increment('attempts');

                throw ValidationException::withMessages([
                    'otp' => 'The provided OTP is incorrect.',
                ]);
            }

            $existingUser = User::where('email', $registration->email)
                ->lockForUpdate()
                ->first();

            if ($existingUser) {
                throw ValidationException::withMessages([
                    'email' => 'An account with this email already exists. Please login instead.',
                ]);
            }

            $user = User::create([
                'name' => $registration->full_name,
                'email' => $registration->email,
                'password' => $registration->password,
                'phone' => $registration->phone,
                'status' => 'active',
            ]);

            $user->assignRole('customer');

            Guest::create([
                'full_name' => $registration->full_name,
                'email' => $registration->email,
                'phone' => $registration->phone,
                'country' => $registration->country,
                'id_type' => $registration->id_type,
                'id_number' => $registration->id_number,
                'photo_url' => $registration->photo_url,
                'photo_public_id' => $registration->photo_public_id,
            ]);

            $registration->update([
                'verified_at' => now(),
            ]);

            $token = $user->createToken('customer_auth')->plainTextToken;

            $registration->delete();

            return [
                'user' => $user->load('roles'),
                'token' => $token,
            ];
        });
    }

    // Resend OTP for a given registration request
    public function resendOtp(int $verificationId): RegistrationOtp
    {
        $registration = RegistrationOtp::find($verificationId);

        if (! $registration) {
            throw ValidationException::withMessages([
                'verification_id' => 'Registration request not found.',
            ]);
        }

        if ($registration->verified_at) {
            throw ValidationException::withMessages([
                'verification_id' => 'This registration OTP has already been verified.',
            ]);
        }

        $key = 'resend-registration-otp:'.$registration->email;

        if (RateLimiter::tooManyAttempts($key, 3)) {

            $seconds = RateLimiter::availableIn($key);

            throw ValidationException::withMessages([
                'verification_id' => "Too many resend attempts. Please try again in {$seconds} seconds.",
            ]);
        }

        RateLimiter::hit($key, 600); // 600 seconds = 10 minutes

        $opt = (string) random_int(100000, 999999);

        $registration->update([
            'otp_hash' => Hash::make($opt),
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
        ]);

        Mail::to($registration->email)->send(
            new RegistrationOtpMail(
                fullName: $registration->full_name,
                otp: $opt
            )
        );

        return $registration->fresh();
    }
}
