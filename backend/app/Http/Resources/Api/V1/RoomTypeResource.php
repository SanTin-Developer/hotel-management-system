<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin App\Models\RoomType
 */
class RoomTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'name_kh' => $this->name_kh,
            'description' => $this->description,
            'description_kh' => $this->description_kh,
            'capacity' => $this->capacity,
            'base_price' => $this->base_price,
            'size' => $this->size,
            'bed_type' => $this->bed_type,
            'image_url' => $this->image_url,
            'status' => $this->status,

            'images' => $this->whenLoaded(
                'images',
                fn () => $this->images->map(fn ($image) => [
                    'id' => $image->id,
                    'image_url' => $image->image_url,
                    'sort_order' => $image->sort_order,
                ])
            ),

            'rooms_count' => $this->whenCounted('rooms'),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
