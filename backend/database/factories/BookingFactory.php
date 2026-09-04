<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Guest;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        $checkIn = fake()->dateTimeBetween('+1 week', '+2 months');
        $checkOut = (clone $checkIn)->modify('+'.random_int(1, 7).' days');

        return [
            'booking_code' => 'BK-'.now()->format('Ymd').'-'.strtoupper(fake()->bothify('??????')),
            'guest_id' => Guest::factory(),
            'check_in' => $checkIn->format('Y-m-d'),
            'check_out' => $checkOut->format('Y-m-d'),
            'adults' => random_int(1, 4),
            'children' => random_int(0, 3),
            'total_amount' => fake()->randomFloat(2, 50, 5000),
            'booking_source' => fake()->randomElement(['website', 'phone', 'walk_in', 'third_party']),
            'status' => 'pending',
            'special_request' => fake()->optional()->sentence(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => 'pending']);
    }

    public function confirmed(): static
    {
        return $this->state(fn () => ['status' => 'confirmed']);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => ['status' => 'cancelled']);
    }

    public function completed(): static
    {
        return $this->state(fn () => ['status' => 'completed']);
    }
}
