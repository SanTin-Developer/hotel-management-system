<?php

namespace App\Repositories;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Builder;

class PaymentRepository
{
    public function query(): Builder
    {
        return Payment::query();
    }

    public function findLocked(int $id): Payment
    {
        return Payment::query()
            ->whereKey($id)
            ->lockForUpdate()
            ->firstOrFail();
    }

    public function getBookingWithPayments(int $bookingId): Booking
    {
        return Booking::query()
            ->with('payments')
            ->whereKey($bookingId)
            ->lockForUpdate()
            ->firstOrFail();
    }

    public function isTransactionUsed(string $transactionId): bool
    {
        return Payment::query()
            ->where('transaction_id', $transactionId)
            ->exists();
    }

    public function getBookingPaidAmount(int $bookingId): float
    {
        $booking = Booking::query()
            ->with('payments')
            ->whereKey($bookingId)
            ->firstOrFail();

        return (float) $booking->payments
            ->whereIn('status', ['pending', 'paid'])
            ->sum('amount');
    }
}
