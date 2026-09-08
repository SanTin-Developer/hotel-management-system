<?php

namespace App\Jobs;

use App\Mail\BookingConfirmationMail;
use App\Models\Booking;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendBookingConfirmationEmail implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public array $backoff = [60, 300, 900];

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
            ->find($this->bookingId);

        if (! $booking?->guest?->email) {
            Log::warning('Booking confirmation email job skipped because the booking has no guest email.', [
                'booking_id' => $this->bookingId,
            ]);

            return;
        }

        Mail::to($booking->guest->email)
            ->send(
                new BookingConfirmationMail($booking)
            );
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Booking confirmation email job failed.', [
            'booking_id' => $this->bookingId,
            'exception' => $exception::class,
        ]);
    }
}
