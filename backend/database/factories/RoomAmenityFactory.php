<?php

namespace Database\Factories;

use App\Models\Amenity;
use App\Models\Room;
use App\Models\RoomAmenity;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoomAmenityFactory extends Factory
{
    protected $model = RoomAmenity::class;

    public function definition(): array
    {
        return [
            'room_id' => Room::factory(),
            'amenity_id' => Amenity::factory(),
        ];
    }
}
