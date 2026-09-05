<?php

namespace App\Services\Booking;

use App\Models\Room;
use Illuminate\Support\Collection;

class BookingPriceService
{
    public function calculateBaseTotal(
        Collection $rooms,
        int $nights
    ): float {
        return (float) $rooms->sum(
            fn (Room $room) => (float) $room->roomType->base_price * $nights
        );
    }

    public function calculateRoomSubtotal(
        float $pricePerNight,
        int $nights
    ): float {
        return round($pricePerNight * $nights, 2);
    }

    public function calculateFinalTotal(
        float $baseTotal,
        ?array $couponResult
    ): float {
        if (! $couponResult) {
            return round($baseTotal, 2);
        }

        return round($couponResult['final_amount'], 2);
    }
}
