<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin App\Models\Review
 */
class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'booking_id' => $this->booking_id,
            'guest_id' => $this->guest_id,
            'rating' => $this->rating,
            'comment' => $this->comment,
            'status' => $this->status,

            'guest' => $this->whenLoaded(
                'guest',
                fn () => [
                    'id' => $this->guest->id,
                    'full_name' => $this->guest->full_name,
                    'email' => $this->guest->email,
                ]
            ),

            'booking' => $this->whenLoaded(
                'booking',
                fn () => [
                    'id' => $this->booking->id,
                    'booking_code' => $this->booking->booking_code,
                ]
            ),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
