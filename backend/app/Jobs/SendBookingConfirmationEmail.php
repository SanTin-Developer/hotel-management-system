<?php

namespace App\Jobs;

use App\Mail\BookingConfirmationMail;
use App\Models\Booking;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendBookingConfirmationEmail implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        public int $bookingId
    ) {}

    public function handle(): void
    {
        $booking = Booking::query()
            ->with([
                'guest',
                'bookingItems.room.roomType',
            ])
            ->findOrFail($this->bookingId);

        Mail::to($booking->guest->email)
            ->send(
                new BookingConfirmationMail($booking)
            );
    }
}
