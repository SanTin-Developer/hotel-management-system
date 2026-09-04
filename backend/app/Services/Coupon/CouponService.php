<?php

namespace App\Services\Coupon;

use App\Models\Coupon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CouponService
{
    public function getAll(array $filters = [])
    {
        return Coupon::query()
            ->withCount('bookings')
            ->when(
                ! empty($filters['search']),
                function ($query) use ($filters) {
                    $search = $filters['search'];

                    $query->where(function ($query) use ($search) {
                        $query
                            ->where('code', 'ILIKE', "%{$search}%")
                            ->orWhere('discount_type', 'ILIKE', "%{$search}%");
                    });
                }
            )
            ->when(
                ! empty($filters['status']),
                fn ($query) => $query->where(
                    'status',
                    $filters['status']
                )
            )
            ->when(
                ! empty($filters['discount_type']),
                fn ($query) => $query->where(
                    'discount_type',
                    $filters['discount_type']
                )
            )
            ->orderByDesc('created_at')
            ->paginate($filters['per_page'] ?? 15);
    }

    public function getById(Coupon $coupon): Coupon
    {
        return $coupon->loadCount('bookings');
    }

    public function create(array $data): Coupon
    {
        return DB::transaction(function () use ($data) {
            return Coupon::create($data)->loadCount('bookings');
        });
    }

    public function update(Coupon $coupon, array $data): Coupon
    {
        return DB::transaction(function () use ($coupon, $data) {
            $coupon->update($data);

            return $coupon->refresh()->loadCount('bookings');
        });
    }

    public function delete(Coupon $coupon): void
    {
        DB::transaction(function () use ($coupon) {
            $coupon->delete();
        });
    }

    public function apply(int $couponId, float $totalAmount): array
    {
        $coupon = Coupon::findOrFail($couponId);

        if ($coupon->status !== 'active') {
            throw ValidationException::withMessages([
                'coupon_id' => 'This coupon is no longer active.',
            ]);
        }

        if ($coupon->start_date->isFuture()) {
            throw ValidationException::withMessages([
                'coupon_id' => 'This coupon is not yet valid.',
            ]);
        }

        if ($coupon->end_date->isPast()) {
            throw ValidationException::withMessages([
                'coupon_id' => 'This coupon has expired.',
            ]);
        }

        if ($totalAmount < $coupon->min_amount) {
            throw ValidationException::withMessages([
                'coupon_id' => "Minimum order amount of {$coupon->min_amount} required for this coupon.",
            ]);
        }

        if ($coupon->usage_limit !== null) {
            $usedCount = $coupon->bookings()->count();

            if ($usedCount >= $coupon->usage_limit) {
                throw ValidationException::withMessages([
                    'coupon_id' => 'This coupon has reached its usage limit.',
                ]);
            }
        }

        $discount = match ($coupon->discount_type) {
            'percentage' => round($totalAmount * ($coupon->discount_value / 100), 2),
            'fixed' => min($coupon->discount_value, $totalAmount),
        };

        return [
            'coupon_id' => $coupon->id,
            'code' => $coupon->code,
            'discount_type' => $coupon->discount_type,
            'discount_value' => $coupon->discount_value,
            'discount_amount' => $discount,
            'final_amount' => round($totalAmount - $discount, 2),
        ];
    }
}
