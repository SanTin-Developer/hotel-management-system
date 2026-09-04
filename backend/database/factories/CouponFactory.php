<?php

namespace Database\Factories;

use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;

class CouponFactory extends Factory
{
    protected $model = Coupon::class;

    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('now', '+1 month');
        $endDate = (clone $startDate)->modify('+'.random_int(30, 90).' days');

        return [
            'code' => strtoupper(fake()->bothify('????-####')),
            'discount_type' => fake()->randomElement(['percentage', 'fixed']),
            'discount_value' => fake()->randomFloat(2, 5, 50),
            'min_amount' => fake()->randomFloat(2, 0, 200),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'usage_limit' => random_int(10, 100),
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

    public function percentage(): static
    {
        return $this->state(fn () => [
            'discount_type' => 'percentage',
            'discount_value' => fake()->randomFloat(2, 5, 50),
        ]);
    }

    public function fixed(): static
    {
        return $this->state(fn () => [
            'discount_type' => 'fixed',
            'discount_value' => fake()->randomFloat(2, 5, 200),
        ]);
    }
}
