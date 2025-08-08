<?php

namespace Database\Seeders;

use App\Models\Font;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FontSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $fonts = [
            ['id' => 1, 'name' => 'Cairo', 'locale' => 'ar'],
            ['id' => 2, 'name' => 'Almarai', 'locale' => 'ar'],
            ['id' => 3, 'name' => 'Lalezar', 'locale' => 'ar'],
            ['id' => 4, 'name' => 'Roboto', 'locale' => 'en'],
            ['id' => 5, 'name' => 'Open Sans', 'locale' => 'en'],
        ];

        foreach ($fonts as $font) {
            Font::updateOrCreate(['id' => $font['id']], $font);
        }
    }
}
