<?php

namespace App\Services;

use App\Models\RoomType;
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
        return $roomType->loadCount('rooms');
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

            return $roomType->refresh()->loadCount('rooms');
        });
    }

    public function delete(RoomType $roomType): void
    {
        DB::transaction(function () use ($roomType) {
            $roomType->delete();
        });
    }

    public function uploadImage(RoomType $roomType, UploadedFile $file): RoomType
    {
        return DB::transaction(function () use ($roomType, $file) {
            $previous = $roomType->image_public_id;

            $upload = $this->cloudinary->upload(
                $file,
                'hotel/room-types'
            );

            $roomType->update([
                'image_url' => $upload['secure_url'],
                'image_public_id' => $upload['public_id'],
            ]);

            if ($previous) {
                $this->cloudinary->destroy($previous);
            }

            return $roomType->refresh()->loadCount('rooms');
        });
    }

    public function removeImage(RoomType $roomType): RoomType
    {
        return DB::transaction(function () use ($roomType) {
            $publicId = $roomType->image_public_id;

            $roomType->update([
                'image_url' => null,
                'image_public_id' => null,
            ]);

            if ($publicId) {
                $this->cloudinary->destroy($publicId);
            }

            return $roomType->refresh()->loadCount('rooms');
        });
    }
}
