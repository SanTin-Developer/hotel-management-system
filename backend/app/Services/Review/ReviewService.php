<?php

namespace App\Services\Review;

use App\Models\Review;
use Illuminate\Support\Facades\DB;

class ReviewService
{
    public function getAll(array $filters = [])
    {
        return Review::query()
            ->with([
                'guest:id,full_name,email',
                'booking:id,booking_code',
            ])
            ->when(
                ! empty($filters['search']),
                function ($query) use ($filters) {
                    $search = $filters['search'];

                    $query->whereHas('guest', function ($query) use ($search) {
                        $query
                            ->where('full_name', 'ILIKE', "%{$search}%")
                            ->orWhere('email', 'ILIKE', "%{$search}%");
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
                ! empty($filters['rating']),
                fn ($query) => $query->where(
                    'rating',
                    $filters['rating']
                )
            )
            ->when(
                ! empty($filters['guest_id']),
                fn ($query) => $query->where(
                    'guest_id',
                    $filters['guest_id']
                )
            )
            ->when(
                ! empty($filters['booking_id']),
                fn ($query) => $query->where(
                    'booking_id',
                    $filters['booking_id']
                )
            )
            ->orderByDesc('created_at')
            ->paginate($filters['per_page'] ?? 15);
    }

    public function getById(Review $review): Review
    {
        return $review->load([
            'guest:id,full_name,email,phone',
            'booking:id,booking_code,check_in,check_out',
        ]);
    }

    public function create(array $data): Review
    {
        return DB::transaction(function () use ($data) {
            return Review::create($data)->load([
                'guest:id,full_name,email',
                'booking:id,booking_code',
            ]);
        });
    }

    public function update(Review $review, array $data): Review
    {
        return DB::transaction(function () use ($review, $data) {
            $review->update($data);

            return $review->refresh()->load([
                'guest:id,full_name,email',
                'booking:id,booking_code',
            ]);
        });
    }

    public function delete(Review $review): void
    {
        DB::transaction(function () use ($review) {
            $review->delete();
        });
    }

    public function approve(Review $review): Review
    {
        return DB::transaction(function () use ($review) {
            $review->update(['status' => 'approved']);

            return $review->refresh()->load([
                'guest:id,full_name,email',
                'booking:id,booking_code',
            ]);
        });
    }

    public function reject(Review $review): Review
    {
        return DB::transaction(function () use ($review) {
            $review->update(['status' => 'rejected']);

            return $review->refresh()->load([
                'guest:id,full_name,email',
                'booking:id,booking_code',
            ]);
        });
    }
}
