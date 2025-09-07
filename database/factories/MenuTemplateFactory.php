<?php

namespace Database\Factories;

use App\Models\SuperAdmin;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MenuTemplate>
 */
class MenuTemplateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
            'is_active' => 1,
            'super_admin_id' => SuperAdmin::factory(),
        ];
    }
}
