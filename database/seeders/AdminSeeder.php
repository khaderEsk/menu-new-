<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = Admin::create([
            'name' => 'admin1',
            'user_name' => 'levantAdmin',
            'password' => '12345678', // password
            'mobile' => '9932517478',
            'email' => 'levant@gmail.com'

        ]);

        $admin->syncRoles(['admin']);
    }
}
