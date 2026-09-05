<?php

namespace App\Services\Guest;

use App\Models\Guest;
use App\Repositories\GuestRepository;
use Illuminate\Support\Facades\DB;

class GuestService
{
    public function __construct(
        private readonly GuestRepository $guestRepository
    ) {}

    public function getAll(array $filters = [])
    {
        return $this->guestRepository->getAll($filters);
    }

    public function getById(Guest $guest): Guest
    {
        return $this->guestRepository->getById($guest->id);
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
