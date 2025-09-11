<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Rate>
 */
class RateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'rate' => fake()->numberBetween(1,3),
            'note' => fake()->word(),
            'customer_id' => Customer::inRandomOrder()->first()?->id,
            'restaurant_id' => Restaurant::inRandomOrder()->first()?->id,
        ];
    }
}
