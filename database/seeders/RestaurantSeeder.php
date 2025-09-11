<?php

namespace Database\Seeders;

use App\Models\Restaurant;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RestaurantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Restaurant::factory()->count(2)->create(
            [
                'font_id_en' => 1,
                'font_id_ar' => 2,
            ]
        );
        foreach (Restaurant::all() as $restaurant) {
            $url = 'https://picsum.photos/200/300';
            $restaurant->addMediaFromUrl($url)->toMediaCollection("cover");
            $restaurant->addMediaFromUrl($url)->toMediaCollection("logo");
        }
    }
}
