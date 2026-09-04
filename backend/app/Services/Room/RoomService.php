<?php

namespace App\Services\Room;

use App\Models\Room;
use Illuminate\Support\Facades\DB;

class RoomService
{
    public function getAll(array $filters = [])
    {
        return Room::query()
            ->with('roomType')
            ->withCount('bookingItems')
            ->when(
                ! empty($filters['search']),
                function ($query) use ($filters) {
                    $search = $filters['search'];

                    $query->where(function ($query) use ($search) {
                        $query
                            ->where('room_number', 'ILIKE', "%{$search}%")
                            ->orWhere('description', 'ILIKE', "%{$search}%")
                            ->orWhereHas('roomType', function ($query) use ($search) {
                                $query->where(
                                    'name',
                                    'ILIKE',
                                    "%{$search}%"
                                );
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
                ! empty($filters['room_type_id']),
                fn ($query) => $query->where(
                    'room_type_id',
                    $filters['room_type_id']
                )
            )
            ->orderBy('room_number')
            ->paginate(
                $filters['per_page'] ?? 15
            );
    }

    public function getById(Room $room): Room
    {
        return $room->newQuery()
            ->with([
                'roomType',
                'bookingItems',
            ])
            ->withCount('bookingItems')
            ->findOrFail($room->id);
    }

    public function create(array $data): Room
    {
        return DB::transaction(function () use ($data) {
            return Room::create($data)->load('roomType');
        });
    }

    public function update(Room $room, array $data): Room
    {
        return DB::transaction(function () use ($room, $data) {
            $room->update($data);

            return $room->refresh()
                ->load('roomType')
                ->loadCount('bookingItems');
        });
    }

    public function delete(Room $room): void
    {
        DB::transaction(function () use ($room) {
            $room->delete();
        });
    }

    public function syncAmenities(Room $room, array $amenityIds): Room
    {
        return DB::transaction(function () use ($room, $amenityIds) {
            $room->amenities()->sync($amenityIds);

            return $room->refresh()
                ->load([
                    'roomType',
                    'amenities',
                ])
                ->loadCount('bookingItems');
        });
    }

    public function removeAmenity(Room $room, int $amenityId): Room
    {
        return DB::transaction(function () use ($room, $amenityId) {
            $room->amenities()->detach($amenityId);

            return $room->refresh()
                ->load([
                    'roomType',
                    'amenities',
                ])
                ->loadCount('bookingItems');
        });
    }
}
