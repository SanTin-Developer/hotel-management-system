<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    public function view(User $user, Booking $booking): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'staff'])
            || $booking->guest?->email === $user->email;
    }

    public function cancel(User $user, Booking $booking): bool
    {
        if ($user->hasAnyRole(['admin', 'manager'])) {
            return true;
        }

        return $user->hasRole('customer')
            && $booking->guest?->email === $user->email
            && in_array($booking->status, [
                'pending',
                'confirmed',
            ], true);
    }

    public function confirm(User $user, Booking $booking): bool
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }

    public function complete(User $user, Booking $booking): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'staff']);
    }
}
