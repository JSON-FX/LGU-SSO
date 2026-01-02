<?php

namespace Database\Factories;

use App\Enums\CivilStatus;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'middle_name' => fake()->optional()->lastName(),
            'last_name' => fake()->lastName(),
            'suffix' => fake()->optional(0.1)->suffix(),
            'birthday' => fake()->date('Y-m-d', '-20 years'),
            'civil_status' => fake()->randomElement(CivilStatus::cases()),
            'residence' => fake()->streetAddress(),
            'nationality' => 'Filipino',
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password',
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
