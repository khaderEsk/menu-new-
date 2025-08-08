<?php

namespace Database\Factories;

use App\Models\Restaurant;
use App\Models\Table;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_name' => fake()->unique()->userName(),
            'password' => fake()->password(),
            'name' => fake()->name(),
            'phone' => fake()->unique()->phoneNumber(),
            'birthday' => fake()->numberBetween(7,60),
            "gender" => fake()->randomElement(['male' , 'female']),
            'restaurant_id' => Restaurant::inRandomOrder()->first()?->id,
            'table_id' => Table::inRandomOrder()->first()?->id,
        ];
    }
}
