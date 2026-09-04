<?php

namespace Database\Factories;

use App\Models\Guest;
use Illuminate\Database\Eloquent\Factories\Factory;

class GuestFactory extends Factory
{
    protected $model = Guest::class;

    public function definition(): array
    {
        return [
            'full_name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'nationality' => fake()->country(),
            'id_type' => fake()->randomElement(['national_id', 'passport']),
            'id_number' => strtoupper(fake()->bothify('??-########')),
            'gender' => fake()->randomElement(['male', 'female', 'other']),
            'date_of_birth' => fake()->dateTimeBetween('-60 years', '-18 years'),
            'country' => fake()->country(),
        ];
    }
}
