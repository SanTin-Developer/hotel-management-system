<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin App\Models\Room
 */
class RoomResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'room_type_id' => $this->room_type_id,
            'room_number' => $this->room_number,
            'floor' => $this->floor,
            'status' => $this->status,
            'description' => $this->description,
            'description_kh' => $this->description_kh,
            'image_url' => $this->image_url,

            'images' => $this->whenLoaded(
                'images',
                fn () => $this->images->map(fn ($image) => [
                    'id' => $image->id,
                    'image_url' => $image->image_url,
                    'sort_order' => $image->sort_order,
                ])
            ),

            'room_type' => $this->whenLoaded(
                'roomType',
                fn () => new RoomTypeResource($this->roomType)
            ),

            'amenities' => AmenityResource::collection(
                $this->whenLoaded('amenities')
            ),

            'booking_items_count' => $this->whenCounted('bookingItems'),

            'status_histories' => $this->whenLoaded(
                'statusHistories',
                fn () => $this->statusHistories->map(fn ($history) => [
                    'id' => $history->id,
                    'status' => $history->status,
                    'note' => $history->note,
                    'changed_by' => $history->changedBy?->name,
                    'created_at' => $history->created_at?->toISOString(),
                ])
            ),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
