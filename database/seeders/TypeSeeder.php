<?php

namespace Database\Seeders;

use App\Models\Type;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Type::create(['name' => 'admin']);
        Type::create(['name' => 'restaurant manager']);
        Type::create(['name' => 'accountant']);
        Type::create(['name' => 'chef']);
        Type::create(['name' => 'waiter']);
        Type::create(['name' => 'shisha']);
        Type::create(['name' => 'data entry']);
        Type::create(['name' => 'bar']);
    }
}
