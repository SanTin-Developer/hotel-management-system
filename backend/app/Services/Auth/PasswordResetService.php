<?php

namespace App\Services\Auth;

use App\Mail\RegistrationOtpMail;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PasswordResetService
{
    public function sendResetLink(string $email): array
    {
        $email = Str::lower(trim($email));

        $key = 'password-reset:'.$email;

        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);

            throw ValidationException::withMessages([
                'email' => "Too many reset attempts. Please try again in {$seconds} seconds.",
            ]);
        }

        RateLimiter::hit($key, 600);

        $user = User::where('email', $email)->first();

        if (! $user) {
            return [
                'message' => 'If the email exists, a reset code has been sent.',
            ];
        }

        $otp = (string) random_int(100000, 999999);

        cache()->put(
            'password_reset:v2:'.$email,
            [
                'otp_hash' => Hash::make($otp),
                'expires_at' => now()->addMinutes(10)->toIso8601String(),
                'attempts' => 0,
            ],
            now()->addMinutes(10)
        );

        Mail::to($email)->send(
            new RegistrationOtpMail(
                fullName: $user->name,
                otp: $otp
            )
        );

        return [
            'message' => 'If the email exists, a reset code has been sent.',
        ];
    }

    public function resetPassword(string $email, string $otp, string $password, string $passwordConfirmation): array
    {
        $email = Str::lower(trim($email));

        $data = cache()->get('password_reset:v2:'.$email);

        if (! is_array($data) || ! isset($data['expires_at'], $data['otp_hash'])) {
            throw ValidationException::withMessages([
                'email' => 'No reset request found. Please request a new one.',
            ]);
        }

        try {
            $expiresAt = Carbon::parse($data['expires_at']);
        } catch (\Throwable) {
            cache()->forget('password_reset:v2:'.$email);

            throw ValidationException::withMessages([
                'otp' => 'The reset code has expired. Please request a new one.',
            ]);
        }

        if (now()->isAfter($expiresAt)) {
            cache()->forget('password_reset:v2:'.$email);

            throw ValidationException::withMessages([
                'otp' => 'The reset code has expired. Please request a new one.',
            ]);
        }

        if ((int) ($data['attempts'] ?? 0) >= 5) {
            cache()->forget('password_reset:v2:'.$email);

            throw ValidationException::withMessages([
                'otp' => 'Too many failed attempts. Please request a new code.',
            ]);
        }

        if (! Hash::check($otp, $data['otp_hash'])) {
            cache()->put(
                'password_reset:v2:'.$email,
                [
                    'otp_hash' => $data['otp_hash'],
                    'expires_at' => $data['expires_at'],
                    'attempts' => (int) ($data['attempts'] ?? 0) + 1,
                ],
                now()->addMinutes(10)
            );

            throw ValidationException::withMessages([
                'otp' => 'The provided code is incorrect.',
            ]);
        }

        $user = User::where('email', $email)->firstOrFail();

        DB::transaction(function () use ($user, $password) {
            $user->update([
                'password' => Hash::make($password),
            ]);
        });

        cache()->forget('password_reset:v2:'.$email);

        return [
            'message' => 'Password has been reset successfully.',
        ];
    }
}
