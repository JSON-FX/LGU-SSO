<?php

namespace Database\Factories;

use App\Models\Application;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Application>
 */
class ApplicationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company().' System',
            'description' => fake()->sentence(),
            'redirect_uris' => ['http://'.fake()->domainName().'/callback'],
            'rate_limit_per_minute' => fake()->numberBetween(30, 120),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function withCredentials(string $clientId, string $clientSecret): static
    {
        return $this->state(fn (array $attributes) => [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
        ]);
    }
}
