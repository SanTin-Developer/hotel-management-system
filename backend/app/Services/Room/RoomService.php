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
            $publicIds = $room->images()
                ->pluck('image_public_id')
                ->filter()
                ->values()
                ->all();

            $room->images()->delete();

            if ($room->image_public_id && ! in_array($room->image_public_id, $publicIds, true)) {
                $publicIds[] = $room->image_public_id;
            }

            foreach ($publicIds as $publicId) {
                $this->cloudinary->destroy($publicId);
            }

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
            $upload = $this->cloudinary->upload(
                $file,
                'hotel/rooms'
            );

            $sortOrder = $room->images()->max('sort_order') ?? -1;

            $room->images()->create([
                'image_url' => $upload['secure_url'],
                'image_public_id' => $upload['public_id'],
                'sort_order' => $sortOrder + 1,
            ]);

            if (! $room->image_url) {
                $room->update([
                    'image_url' => $upload['secure_url'],
                    'image_public_id' => $upload['public_id'],
                ]);
            }

            return $room->refresh()
                ->load(['roomType', 'images'])
                ->loadCount('bookingItems');
        });
    }

    public function removeImage(Room $room, int $imageId): Room
    {
        return DB::transaction(function () use ($room, $imageId) {
            $image = $room->images()->findOrFail($imageId);

            if ($image->image_public_id) {
                $this->cloudinary->destroy($image->image_public_id);
            }

            $wasCover = $room->image_url === $image->image_url;

            $image->delete();

            if ($wasCover) {
                $next = $room->images()
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->first();

                $room->update([
                    'image_url' => $next?->image_url,
                    'image_public_id' => $next?->image_public_id,
                ]);
            }

            return $room->refresh()
                ->load(['roomType', 'images'])
                ->loadCount('bookingItems');
        });
    }
}
