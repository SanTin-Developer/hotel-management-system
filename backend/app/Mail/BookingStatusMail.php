<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Booking $booking,
        public string $newStatus,
        public ?string $note
    ) {}

    public function envelope(): Envelope
    {
        $label = ucwords(str_replace('_', ' ', $this->newStatus));

        return new Envelope(
            subject: "Booking {$label} - {$this->booking->booking_code}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'email.booking_status',
        );
    }
}
