<?php

namespace App\Repositories;

use App\Models\Room;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

class RoomRepository
{
    public function query(): Builder
    {
        return Room::query();
    }

    public function getAll(array $filters = []): LengthAwarePaginator
    {
        return Room::query()
            ->with([
                'roomType',
                'amenities',
                'images',
            ])
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

    public function getById(int $id): Room
    {
        return Room::query()
            ->with([
                'roomType',
                'amenities',
                'images',
                'bookingItems',
            ])
            ->withCount('bookingItems')
            ->findOrFail($id);
    }

    public function findLockedByIds(array $ids): Collection
    {
        return Room::query()
            ->with('roomType')
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
    }

    public function findOrFail(int $id): Room
    {
        $room = Room::find($id);

        if (! $room) {
            throw new ModelNotFoundException('Room not found.');
        }

        return $room;
    }
}
