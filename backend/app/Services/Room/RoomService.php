<?php

namespace App\Services\Room;

use App\Models\Room;
use App\Models\RoomStatusHistory;
use App\Repositories\RoomRepository;
use App\Services\Media\CloudinaryService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class RoomService
{
    public function __construct(
        private readonly RoomRepository $roomRepository,
        private readonly CloudinaryService $cloudinary
    ) {}

    public function getAll(array $filters = [])
    {
        return $this->roomRepository->getAll($filters);
    }

    public function getById(Room $room): Room
    {
        return $this->roomRepository->getById($room->id);
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

    public function changeStatus(Room $room, string $status, ?int $changedBy = null, ?string $note = null): Room
    {
        return DB::transaction(function () use ($room, $status, $changedBy, $note) {
            $room->update(['status' => $status]);

            RoomStatusHistory::create([
                'room_id' => $room->id,
                'status' => $status,
                'changed_by' => $changedBy,
                'note' => $note,
            ]);

            return $room->refresh()
                ->load('roomType')
                ->loadCount('bookingItems')
                ->load('statusHistories.changedBy');
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

    public function uploadImage(Room $room, UploadedFile $file): Room
    {
        return DB::transaction(function () use ($room, $file) {
            $previous = $room->image_public_id;

            $upload = $this->cloudinary->upload(
                $file,
                'hotel/rooms'
            );

            $room->update([
                'image_url' => $upload['secure_url'],
                'image_public_id' => $upload['public_id'],
            ]);

            if ($previous) {
                $this->cloudinary->destroy($previous);
            }

            return $room->refresh()
                ->load('roomType')
                ->loadCount('bookingItems');
        });
    }

    public function removeImage(Room $room): Room
    {
        return DB::transaction(function () use ($room) {
            $publicId = $room->image_public_id;

            $room->update([
                'image_url' => null,
                'image_public_id' => null,
            ]);

            if ($publicId) {
                $this->cloudinary->destroy($publicId);
            }

            return $room->refresh()
                ->load('roomType')
                ->loadCount('bookingItems');
        });
    }
}
