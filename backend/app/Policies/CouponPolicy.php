<?php

namespace App\Policies;

use App\Models\Coupon;
use App\Models\User;

class CouponPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'staff']);
    }

    public function view(User $user, Coupon $coupon): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'staff']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }

    public function update(User $user, Coupon $coupon): bool
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }

    public function delete(User $user, Coupon $coupon): bool
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }
}
