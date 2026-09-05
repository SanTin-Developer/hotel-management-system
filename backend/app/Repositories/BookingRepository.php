<?php

namespace App\Repositories;

use App\Models\Booking;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class BookingRepository
{
    public function query(): Builder
    {
        return Booking::query();
    }

    public function getAll(array $filters = []): LengthAwarePaginator
    {
        return Booking::query()
            ->with([
                'guest:id,full_name,email,phone',
                'rooms:id,room_number',
                'bookingItems.room:id,room_number',
            ])
            ->when(
                ! empty($filters['search']),
                function ($query) use ($filters) {
                    $search = $filters['search'];

                    $query->where(function ($query) use ($search) {
                        $query
                            ->where('booking_code', 'ILIKE', "%{$search}%")
                            ->orWhereHas('guest', function ($query) use ($search) {
                                $query
                                    ->where('full_name', 'ILIKE', "%{$search}%")
                                    ->orWhere('email', 'ILIKE', "%{$search}%")
                                    ->orWhere('phone', 'ILIKE', "%{$search}%");
                            });
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
                ! empty($filters['guest_id']),
                fn ($query) => $query->where(
                    'guest_id',
                    $filters['guest_id']
                )
            )
            ->when(
                ! empty($filters['check_in']),
                fn ($query) => $query->whereDate(
                    'check_in',
                    '>=',
                    $filters['check_in']
                )
            )
            ->when(
                ! empty($filters['check_out']),
                fn ($query) => $query->whereDate(
                    'check_out',
                    '<=',
                    $filters['check_out']
                )
            )
            ->latest('created_at')
            ->paginate($filters['per_page'] ?? 20);
    }

    public function findLocked(int $id): Booking
    {
        return Booking::query()
            ->whereKey($id)
            ->lockForUpdate()
            ->firstOrFail();
    }
}
