<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookingItemFactory extends Factory
{
    protected $model = BookingItem::class;

    public function definition(): array
    {
        $nights = random_int(1, 7);
        $pricePerNight = fake()->randomFloat(2, 50, 500);

        return [
            'booking_id' => Booking::factory(),
            'room_id' => Room::factory(),
            'price_per_night' => $pricePerNight,
            'nights' => $nights,
            'subtotal' => round($pricePerNight * $nights, 2),
            'status' => 'reserved',
        ];
    }
}
