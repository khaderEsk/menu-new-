<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Admin;
use App\Models\Category;
use App\Models\Component;
use App\Models\Customer;
use App\Models\Item;
use App\Models\Restaurant;
use App\Models\Size;
use App\Models\SuperAdmin;
use App\Models\Table;
use App\Models\Topping;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
        // -- STEP 1: Create the SuperAdmin --
        $superAdmin = SuperAdmin::factory()->create(['user_name' => 'superadmin']);

        // -- STEP 2: Create the main Restaurant --
        $restaurant = Restaurant::factory()->create([
            'super_admin_id' => $superAdmin->id,
            'name_url' => 'my-test-restaurant'
        ]);

        // -- STEP 3: Create a main Admin for the Restaurant --
        $admin = Admin::factory()->create([
            'restaurant_id' => $restaurant->id,
            'user_name' => 'test_admin',
            'password' => bcrypt('password123'),
        ]);

        // -- STEP 4: Create Categories, and for each category, create Items with all their details --
        $this->command->info('Creating Categories and Items...');
        Category::factory(5)
            ->for($restaurant)
            ->has(
                Item::factory(3)
                    ->for($restaurant)
                    ->has(Size::factory(3), 'sizes') // Each item has 3 sizes
                    ->has(Topping::factory(4), 'toppings') // Each item has 4 toppings
                    ->has(Component::factory(5), 'components') // Each item has 5 components
            )
            ->create();

        // -- STEP 5: Create Tables and Customers for the Restaurant --
        $this->command->info('Creating Tables and Customers...');
        Table::factory(10)->create(['restaurant_id' => $restaurant->id]);
        $customers = Customer::factory(20)->create(['restaurant_id' => $restaurant->id]);


        $this->call([
            // 1. Seed tables with no dependencies first
            // RoleSeeder::class,
            // SuperAdminSeeder::class,
            // FontSeeder::class,       // Must run before RestaurantSeeder
            // CitySeeder::class,       // Must run before RestaurantSeeder
            // // EmojiSeeder::class,      // Must run before RestaurantSeeder
            // MenuFormSeeder::class,   // Must run before RestaurantSeeder
            // TypeSeeder::class,       // Must run before AdminSeeder
            // PackageSeeder::class,

            // 2. Now seed tables that depend on the ones above
            // RestaurantSeeder::class, // Depends on SuperAdmin, Font, City, etc.
            // AdminSeeder::class,      // Depends on Type and Restaurant

            // 3. Seed the rest
            //            AdvertisementSeeder::class,
            //            CouponSeeder::class,
            //            CustomerSeeder::class,
            //            DataEntrySeeder::class,
            //            NotificationSeeder::class,
            //            RateSeeder::class,
            //            TableSeeder::class,

            PaymentGetwaySeeder::class,
        ]);
    }
}
