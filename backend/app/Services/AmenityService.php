<?php

namespace App\Services;

use App\Models\Amenity;
use Illuminate\Support\Facades\DB;

class AmenityService
{
    public function getAll(array $filters = [])
    {
        return Amenity::query()
            ->withCount('rooms')
            ->when(
                ! empty($filters['search']),
                function ($query) use ($filters) {
                    $search = $filters['search'];

                    $query->where(function ($query) use ($search) {
                        $query
                            ->where('name', 'ILIKE', "%{$search}%")
                            ->orWhere('name_kh', 'ILIKE', "%{$search}%")
                            ->orWhere('description', 'ILIKE', "%{$search}%")
                            ->orWhere('description_kh', 'ILIKE', "%{$search}%");
                    });
                }
            )
            ->orderBy('name')
            ->paginate($filters['per_page'] ?? 15);
    }

    public function getById(Amenity $amenity): Amenity
    {
        return Amenity::query()
            ->withCount('rooms')
            ->findOrFail($amenity->id);
    }

    public function create(array $data): Amenity
    {
        return DB::transaction(function () use ($data) {
            return Amenity::create($data);
        });
    }

    public function update(Amenity $amenity, array $data): Amenity
    {
        return DB::transaction(function () use ($amenity, $data) {
            $amenity->update($data);

            return $amenity->refresh()->loadCount('rooms');
        });
    }

    public function delete(Amenity $amenity): void
    {
        DB::transaction(function () use ($amenity) {
            $amenity->delete();
        });
    }
}
