<?php

namespace App\Services;

use App\Models\RoomType;
use App\Models\RoomTypeImage;
use App\Services\Media\CloudinaryService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class RoomTypeService
{
    public function __construct(
        private readonly CloudinaryService $cloudinary
    ) {}

    public function getAll(array $filters = [])
    {
        return RoomType::query()
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
                            ->orWhere('bed_type', 'ILIKE', "%{$search}%");
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
            ->orderBy('name')
            ->paginate($filters['per_page'] ?? 15);
    }

    public function getById(RoomType $roomType): RoomType
    {
        return $roomType->loadCount('rooms')->load('images');
    }

    public function create(array $data): RoomType
    {
        return DB::transaction(function () use ($data) {
            return RoomType::create($data);
        });
    }

    public function update(RoomType $roomType, array $data): RoomType
    {
        return DB::transaction(function () use ($roomType, $data) {
            $roomType->update($data);

            return $roomType->refresh()->loadCount('rooms')->load('images');
        });
    }

    public function delete(RoomType $roomType): void
    {
        DB::transaction(function () use ($roomType) {
            $roomType->delete();
        });
    }

    public function uploadImages(RoomType $roomType, array $files): RoomType
    {
        return DB::transaction(function () use ($roomType, $files) {
            $maxSort = $roomType->images()->max('sort_order') ?? -1;

            foreach ($files as $index => $file) {
                $upload = $this->cloudinary->upload(
                    $file,
                    'hotel/room-types'
                );

                RoomTypeImage::create([
                    'room_type_id' => $roomType->id,
                    'image_url' => $upload['secure_url'],
                    'image_public_id' => $upload['public_id'],
                    'sort_order' => $maxSort + 1 + $index,
                ]);
            }

            return $roomType->refresh()->load('images')->loadCount('rooms');
        });
    }

    public function removeImage(RoomType $roomType, RoomTypeImage $image): RoomType
    {
        return DB::transaction(function () use ($roomType, $image) {
            $publicId = $image->image_public_id;

            $image->delete();

            if ($publicId) {
                $this->cloudinary->destroy($publicId);
            }

            return $roomType->refresh()->load('images')->loadCount('rooms');
        });
    }

    public function reorderImages(RoomType $roomType, array $imageIds): RoomType
    {
        return DB::transaction(function () use ($roomType, $imageIds) {
            foreach ($imageIds as $index => $imageId) {
                RoomTypeImage::where('id', $imageId)
                    ->where('room_type_id', $roomType->id)
                    ->update(['sort_order' => $index]);
            }

            return $roomType->refresh()->load('images')->loadCount('rooms');
        });
    }
}
