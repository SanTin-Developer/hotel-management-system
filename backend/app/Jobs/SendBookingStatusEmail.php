<?php

namespace App\Jobs;

use App\Mail\BookingStatusMail;
use App\Models\Booking;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendBookingStatusEmail implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        public int $bookingId,
        public string $newStatus,
        public ?string $note = null
    ) {}

    public function handle(): void
    {
        $booking = Booking::with('guest')->find($this->bookingId);

        if (! $booking?->guest?->email) {
            return;
        }

        Mail::to($booking->guest->email)
            ->send(new BookingStatusMail(
                $booking,
                $this->newStatus,
                $this->note
            ));
    }
}
