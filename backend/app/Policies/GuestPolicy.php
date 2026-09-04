<?php

namespace App\Policies;

use App\Models\Guest;
use App\Models\User;

class GuestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'staff']);
    }

    public function view(User $user, Guest $guest): bool
    {
        if ($user->hasAnyRole(['admin', 'manager', 'staff'])) {
            return true;
        }

        return $guest->email === $user->email;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'staff']);
    }

    public function update(User $user, Guest $guest): bool
    {
        if ($user->hasAnyRole(['admin', 'manager'])) {
            return true;
        }

        return $guest->email === $user->email;
    }

    public function delete(User $user, Guest $guest): bool
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }
}
