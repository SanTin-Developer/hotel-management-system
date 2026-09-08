<?php

namespace App\Jobs;

use App\Mail\RegistrationOtpMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendPasswordResetOtpEmail implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public array $backoff = [60, 300, 900];

    public function __construct(
        public string $email,
        public string $fullName,
        public string $otp
    ) {}

    public function handle(): void
    {
        Mail::to($this->email)->send(
            new RegistrationOtpMail(
                fullName: $this->fullName,
                otp: $this->otp
            )
        );
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Password reset OTP email job failed.', [
            'exception' => $exception::class,
        ]);
    }
}
