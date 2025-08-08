<?php

namespace Database\Seeders;

use App\Models\MenuTemplate;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MenuFormSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        MenuTemplate::factory()->count(5)->create();

    }
}
