<?php

namespace Database\Factories;

use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StaffFactory extends Factory
{
    protected $model = Staff::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'employee_id' => 'EMP-'.strtoupper(fake()->bothify('#####')),
            'position' => fake()->randomElement(['Receptionist', 'Housekeeper', 'Manager', 'Chef', 'Concierge', 'Bellboy']),
            'hire_date' => fake()->dateTimeBetween('-5 years', 'now'),
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
