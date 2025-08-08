<?php

namespace Database\Seeders;

use App\Models\SuperAdmin;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = SuperAdmin::create([
            'name' => 'levant',
            'user_name' => 'levant',
            'password' => '12345678', // password
        ]);

        $admin->assignRole(['superAdmin']);

        $admin->givePermissionTo('city.index','city.add','city.update','city.active','city.delete','super_admin.index','super_admin.add','super_admin.update','super_admin.active','super_admin.delete','menu.index','menu.add','menu.active','menu.delete','emoji.index','emoji.add','emoji.update','emoji.active','emoji.delete','restaurant.index','restaurant.add','restaurant.update','restaurant.active','restaurant.delete','restaurant.update_super_admin_restaurant_id','package.index','package.add','package.update','package.active','package.delete','package.add_subscription','package.show_restaurant_subscription','rate.index','excel','admin_restaurant.index','admin_restaurant.add','admin_restaurant.update','admin_restaurant.active','admin_restaurant.delete','restaurant_manager.index','restaurant_manager.add','restaurant_manager.update','restaurant_manager.deactivat','restaurant_manager.delete','logs','category.index','category.add','category.update','category.active','category.delete','reorder','item.index','item.add','item.update','item.active','item.delete','user.index','user.add','user.update','user.delete','user.active','advertisement.index','advertisement.add','advertisement.update','advertisement.delete','news.index','news.add','news.update','news.delete','table.index','table.add','table.update','table.delete','restaurantId','my_restaurants','service.index','service.add','service.update','service.delete','update_restaurant_admin','delivery.index','delivery.add','delivery.update','delivery.active','delivery.delete','coupon.index','coupon.add','coupon.update','coupon.delete','coupon.active');
    }
}
