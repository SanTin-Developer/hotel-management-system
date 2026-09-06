<?php

namespace App\Repositories;

use App\Models\Guest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GuestRepository
{
    public function getAll(array $filters = []): LengthAwarePaginator
    {
        return Guest::query()
            ->withCount('bookings')
            ->when(
                ! empty($filters['search']),
                function ($query) use ($filters) {
                    $search = $filters['search'];

                    $query->where(function ($query) use ($search) {
                        $query
                            ->where('full_name', 'ILIKE', "%{$search}%")
                            ->orWhere('email', 'ILIKE', "%{$search}%")
                            ->orWhere('phone', 'ILIKE', "%{$search}%")
                            ->orWhere('id_number', 'ILIKE', "%{$search}%");
                    });
                }
            )
            ->when(
                ! empty($filters['country']),
                fn ($query) => $query->where(
                    'country',
                    'ILIKE',
                    "%{$filters['country']}%"
                )
            )
            ->orderBy('full_name')
            ->paginate($filters['per_page'] ?? 15);
    }

    public function getById(int $id): Guest
    {
        return Guest::query()
            ->withCount('bookings', 'reviews')
            ->findOrFail($id);
    }

    public function findById(int $id): ?Guest
    {
        return Guest::find($id);
    }
}
