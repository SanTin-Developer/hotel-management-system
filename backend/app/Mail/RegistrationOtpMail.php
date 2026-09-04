<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RegistrationOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $fullName,
        public string $otp
    ) {}

    public function build()
    {
        return $this
            ->subject('Your Registration OTP')
            ->view('email.registration_otp');
    }
}
