<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\BookingStatusHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookingStatusHistoryFactory extends Factory
{
    protected $model = BookingStatusHistory::class;

    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'status' => fake()->randomElement(['pending', 'confirmed', 'cancelled', 'completed']),
            'changed_by' => User::factory(),
            'note' => fake()->sentence(),
            'created_at' => now(),
        ];
    }
}
