<?php

namespace Database\Factories;

use App\Models\RoomType;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoomTypeFactory extends Factory
{
    protected $model = RoomType::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'description' => fake()->paragraph(),
            'capacity' => random_int(1, 6),
            'base_price' => fake()->randomFloat(2, 30, 500),
            'size' => fake()->randomFloat(2, 15, 100),
            'bed_type' => fake()->randomElement(['single', 'double', 'queen', 'king', 'twin']),
            'image_url' => fake()->imageUrl(),
            'status' => 'active',
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => ['status' => 'active']);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => 'inactive']);
    }
}
