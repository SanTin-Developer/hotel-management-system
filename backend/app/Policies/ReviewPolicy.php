<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'staff']);
    }

    public function view(User $user, Review $review): bool
    {
        if ($user->hasAnyRole(['admin', 'manager', 'staff'])) {
            return true;
        }

        return $review->guest?->email === $user->email;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Review $review): bool
    {
        if ($user->hasAnyRole(['admin', 'manager'])) {
            return true;
        }

        return $review->guest?->email === $user->email
            && $review->status === 'pending';
    }

    public function delete(User $user, Review $review): bool
    {
        if ($user->hasAnyRole(['admin', 'manager'])) {
            return true;
        }

        return $review->guest?->email === $user->email
            && $review->status === 'pending';
    }

    public function approve(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }

    public function reject(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }
}
