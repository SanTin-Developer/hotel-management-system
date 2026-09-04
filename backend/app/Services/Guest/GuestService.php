<?php

namespace App\Services\Guest;

use App\Models\Guest;
use Illuminate\Support\Facades\DB;

class GuestService
{
    public function getAll(array $filters = [])
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
                    $filters['country']
                )
            )
            ->orderBy('full_name')
            ->paginate($filters['per_page'] ?? 15);
    }

    public function getById(Guest $guest): Guest
    {
        return $guest->loadCount('bookings', 'reviews');
    }

    public function create(array $data): Guest
    {
        return DB::transaction(function () use ($data) {
            return Guest::create($data);
        });
    }

    public function update(Guest $guest, array $data): Guest
    {
        return DB::transaction(function () use ($guest, $data) {
            $guest->update($data);

            return $guest->refresh()->loadCount('bookings', 'reviews');
        });
    }

    public function delete(Guest $guest): void
    {
        DB::transaction(function () use ($guest) {
            $guest->delete();
        });
    }
}
