<?php

namespace Database\Factories;

use App\Models\Room;
use App\Models\RoomStatusHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoomStatusHistoryFactory extends Factory
{
    protected $model = RoomStatusHistory::class;

    public function definition(): array
    {
        return [
            'room_id' => Room::factory(),
            'status' => fake()->randomElement(['available', 'occupied', 'maintenance', 'cleaning', 'out_of_service']),
            'changed_by' => User::factory(),
            'note' => fake()->sentence(),
            'created_at' => now(),
        ];
    }
}
