<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\Emoji;
use App\Models\Font;
use App\Models\MenuTemplate;
use App\Models\SuperAdmin;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Restaurant>
 */
class RestaurantFactory extends Factory
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
                'name'=>$this->faker->word(3,true),
                'note' => fake()->sentence(),
            ],
            'ar'=>[
                'name'=>$this->faker->word(3,true),
                'note' => fake()->sentence(),
            ],
            'name_url' => fake()->unique()->word(),
            'facebook_url' => fake()->unique()->url(),
            'instagram_url' => fake()->unique()->url(),
            'whatsapp_phone' => fake()->unique()->phoneNumber(),
            'end_date' => fake()->unique()->date(),
            'color' => "Color(0xffff0000)",
            'font_id_en' => Font::inRandomOrder()->first()?->id,
            'font_id_ar' => Font::inRandomOrder()->first()?->id,
            'city_id' => City::inRandomOrder()->first()?->id,
            'emoji_id' => Emoji::inRandomOrder()->first()?->id,
            'menu_template_id' => MenuTemplate::inRandomOrder()->first()?->id,
            'super_admin_id' => SuperAdmin::inRandomOrder()->first()?->id,

        ];
    }
}
