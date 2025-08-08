<?php

namespace Database\Factories;

use App\Models\Restaurant;
use App\Models\Type;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Admin>
 */
class AdminFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'user_name' => $this->faker->unique()->userName(),
            'password' => Hash::make('12345678'),
            'mobile' => $this->faker->phoneNumber(),
            'is_active' => 1,
            'restaurant_id' => Restaurant::factory(),
            'type_id' => Type::factory(),
        ];
    }
}
