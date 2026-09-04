<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'amount' => fake()->randomFloat(2, 10, 5000),
            'payment_method' => fake()->randomElement(['cash', 'card', 'bank_transfer', 'online']),
            'transaction_id' => fake()->optional()->bothify('TXN-####-????'),
            'status' => 'pending',
            'paid_at' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => 'pending']);
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'status' => 'paid',
            'paid_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn () => ['status' => 'failed']);
    }

    public function refunded(): static
    {
        return $this->state(fn () => [
            'status' => 'refunded',
            'paid_at' => now()->subDay(),
        ]);
    }
}
