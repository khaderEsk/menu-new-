<?php

namespace Database\Factories;

use App\Models\Restaurant;
use App\Models\Table;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Table>
 */
class TableFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number_table' => fake()->numberBetween(1,100),
            'number_of_chairs' => fake()->numberBetween(2,10),
            'restaurant_id' => Restaurant::inRandomOrder()->first()?->id,
        ];
    }
}
