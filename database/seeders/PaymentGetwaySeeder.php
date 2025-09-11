<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PaymentGetwaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $payments = [
            'PayPal',
            'sham-cach',
            'stripe'
        ];

        foreach ($payments as $payment) {
            PaymentMethod::query()
                ->create([
                    'name' => $payment,
                    'isActive' => true,
                ]);
        }
    }
}
