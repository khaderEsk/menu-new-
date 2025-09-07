<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Package>
 */
class PackageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'en'=>[
                'title'=>$this->faker->word(3,true),
            ],
            'ar'=>[
                'title'=>$this->faker->word(3,true),
            ],
            'price'=> fake()->numberBetween(1000,9000),
            "value"=> fake()->numberBetween(30,120),
        ];
    }
}
