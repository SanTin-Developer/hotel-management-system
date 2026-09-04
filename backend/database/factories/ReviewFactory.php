<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Guest;
use App\Models\Review;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReviewFactory extends Factory
{
    protected $model = Review::class;

    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'guest_id' => Guest::factory(),
            'rating' => random_int(1, 5),
            'comment' => fake()->optional()->paragraph(),
            'status' => 'pending',
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => ['status' => 'approved']);
    }

    public function rejected(): static
    {
        return $this->state(fn () => ['status' => 'rejected']);
    }
}
