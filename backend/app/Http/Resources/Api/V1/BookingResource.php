<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin App\Models\Booking
 */
class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'booking_code' => $this->booking_code,

            'guest_id' => $this->guest_id,

            'check_in' => $this->check_in?->toDateString(),
            'check_out' => $this->check_out?->toDateString(),

            'adults' => $this->adults,
            'children' => $this->children,

            'total_amount' => $this->total_amount,
            'deposit_rate' => $this->deposit_rate,
            'deposit_amount' => $this->deposit_amount,

            'booking_source' => $this->booking_source,
            'status' => $this->status,
            'special_request' => $this->special_request,

            'guest' => $this->whenLoaded(
                'guest',
                fn () => [
                    'id' => $this->guest->id,
                    'full_name' => $this->guest->full_name,
                    'email' => $this->guest->email,
                    'phone' => $this->guest->phone,
                ]
            ),

            'rooms' => RoomResource::collection(
                $this->whenLoaded('rooms')
            ),

            'booking_items' => $this->whenLoaded(
                'bookingItems',
                fn () => $this->bookingItems->map(fn ($item) => [
                    'id' => $item->id,
                    'room_id' => $item->room_id,
                    'price_per_night' => $item->price_per_night,
                    'nights' => $item->nights,
                    'subtotal' => $item->subtotal,
                    'status' => $item->status,
                ])
            ),

            'coupon' => $this->whenLoaded(
                'coupon',
                fn () => [
                    'id' => $this->coupon->id,
                    'code' => $this->coupon->code,
                    'discount_type' => $this->coupon->discount_type,
                    'discount_value' => $this->coupon->discount_value,
                ]
            ),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
