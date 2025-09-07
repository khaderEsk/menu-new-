<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Item>
 */
class ItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'price' => $this->faker->randomFloat(2, 1, 100),
            'index' => $this->faker->randomNumber(),
            'is_active' => 1,
            'category_id' => Category::factory(),
            'restaurant_id' => function (array $attributes) {
                return Category::find($attributes['category_id'])->restaurant_id;
            },
        ];
    }
}
