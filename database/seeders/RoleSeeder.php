<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superAdmin = Role::create(['name' => 'superAdmin']);
        $citySuperAdmin = Role::create(['name' => 'citySuperAdmin']);
        $admin = Role::create(['name' => 'admin']);
        $dataEntry = Role::create(['name' => 'dataEntry']);
        // $dataEntry = Role::create(['name' => 'adminDataEntry']);
        $employee = Role::create(['name' => 'employee']);
        $customer = Role::create(['name' => 'customer']);
        $restaurantManager = Role::create(['name' => 'restaurantManager']);
        $takeout = Role::create(['name' => 'takeout']);
        // -----------Permission-----------------
        Permission::create(['name' => 'update_super_admin.update']);

        Permission::create(['name' => 'excel']);
        Permission::create(['name' => 'reorder']);

        Permission::create(['name' => 'city.index']);
        Permission::create(['name' => 'city.add']);
        Permission::create(['name' => 'city.update']);
        Permission::create(['name' => 'city.active']);
        Permission::create(['name' => 'city.delete']);
        // Permission::create(['name' => 'city.show_by_id']);

        Permission::create(['name' => 'super_admin.index']);
        Permission::create(['name' => 'super_admin.add']);
        Permission::create(['name' => 'super_admin.update']);
        // Permission::create(['name' => 'super_admin.show_by_id']);
        Permission::create(['name' => 'super_admin.active']);
        Permission::create(['name' => 'super_admin.delete']);

        Permission::create(['name' => 'menu.index']);
        Permission::create(['name' => 'menu.add']);
        // Permission::create(['name' => 'menu.update']);
        Permission::create(['name' => 'menu.active']);
        Permission::create(['name' => 'menu.delete']);
        // Permission::create(['name' => 'menu.show_by_id']);

        Permission::create(['name' => 'emoji.index']);
        Permission::create(['name' => 'emoji.add']);
        Permission::create(['name' => 'emoji.update']);
        Permission::create(['name' => 'emoji.active']);
        Permission::create(['name' => 'emoji.delete']);
        // Permission::create(['name' => 'emoji.show_by_id']);

        Permission::create(['name' => 'restaurant.index']);
        Permission::create(['name' => 'restaurant.add']);
        Permission::create(['name' => 'restaurant.update']);
        // Permission::create(['name' => 'restaurant.show_by_id']);
        Permission::create(['name' => 'restaurant.active']);
        Permission::create(['name' => 'restaurant.delete']);
        Permission::create(['name' => 'restaurant.update_super_admin_restaurant_id']);

        Permission::create(['name' => 'package.index']);
        Permission::create(['name' => 'package.add']);
        Permission::create(['name' => 'package.update']);
        Permission::create(['name' => 'package.active']);
        Permission::create(['name' => 'package.delete']);
        // Permission::create(['name' => 'package.show_by_id']);
        Permission::create(['name' => 'package.add_subscription']);
        Permission::create(['name' => 'package.show_restaurant_subscription']);

        Permission::create(['name' => 'admin_restaurant.index']);
        Permission::create(['name' => 'admin_restaurant.add']);
        // Permission::create(['name' => 'admin_restaurant.show_by_id']);
        Permission::create(['name' => 'admin_restaurant.update']);
        Permission::create(['name' => 'admin_restaurant.active']);
        Permission::create(['name' => 'admin_restaurant.delete']);

        Permission::create(['name' => 'rate.index']);
        Permission::create(['name' => 'rate.add']);
        Permission::create(['name' => 'rate.update']);
        Permission::create(['name' => 'rate.active']);
        Permission::create(['name' => 'rate.delete']);

        Permission::create(['name' => 'notifications.index']);

        Permission::create(['name' => 'advertisement.index']);
        Permission::create(['name' => 'advertisement.add']);
        Permission::create(['name' => 'advertisement.update']);
        Permission::create(['name' => 'advertisement.delete']);
        // Permission::create(['name' => 'advertisement.show_by_id']);

        Permission::create(['name' => 'category.index']);
        Permission::create(['name' => 'category.add']);
        Permission::create(['name' => 'category.update']);
        Permission::create(['name' => 'category.active']);
        Permission::create(['name' => 'category.delete']);

        Permission::create(['name' => 'item.index']);
        Permission::create(['name' => 'item.add']);
        Permission::create(['name' => 'item.update']);
        Permission::create(['name' => 'item.active']);
        Permission::create(['name' => 'item.delete']);

        // Permission::create(['name' => 'info_admin.update']);
        // Permission::create(['name' => 'info_admin.show_by_id']);
        Permission::create(['name' => 'update_restaurant_admin']);

        Permission::create(['name' => 'order.index']);
        Permission::create(['name' => 'order.add']);
        Permission::create(['name' => 'order.update']);
        // Permission::create(['name' => 'order.show_by_id']);
        Permission::create(['name' => 'order.delete']);

        Permission::create(['name' => 'user.index']);
        Permission::create(['name' => 'user.add']);
        Permission::create(['name' => 'user.update']);
        // Permission::create(['name' => 'user.show_by_id']);
        Permission::create(['name' => 'user.delete']);
        Permission::create(['name' => 'user.active']);

        Permission::create(['name' => 'news.index']);
        Permission::create(['name' => 'news.add']);
        Permission::create(['name' => 'news.update']);
        // Permission::create(['name' => 'news.show_by_id']);
        Permission::create(['name' => 'news.delete']);

        Permission::create(['name' => 'table.index']);
        Permission::create(['name' => 'table.add']);
        Permission::create(['name' => 'table.update']);
        // Permission::create(['name' => 'table.show_by_id']);
        Permission::create(['name' => 'table.delete']);

        Permission::create(['name' => 'restaurantId']);
        Permission::create(['name' => 'my_restaurants']);

        Permission::create(['name' => 'restaurant_manager.index']);
        Permission::create(['name' => 'restaurant_manager.add']);
        Permission::create(['name' => 'restaurant_manager.update']);
        Permission::create(['name' => 'restaurant_manager.deactivat']);
        // Permission::create(['name' => 'restaurant_manager.show_by_id']);
        Permission::create(['name' => 'restaurant_manager.delete']);

        Permission::create(['name' => 'logs']);

        Permission::create(['name' => 'service.index']);
        Permission::create(['name' => 'service.add']);
        Permission::create(['name' => 'service.update']);
        Permission::create(['name' => 'service.delete']);

        Permission::create(['name' => 'delivery.index']);
        Permission::create(['name' => 'delivery.add']);
        Permission::create(['name' => 'delivery.update']);
        // Permission::create(['name' => 'delivery.show_by_id']);
        Permission::create(['name' => 'delivery.delete']);
        Permission::create(['name' => 'delivery.active']);

        Permission::create(['name' => 'coupon.index']);
        Permission::create(['name' => 'coupon.add']);
        Permission::create(['name' => 'coupon.update']);
        // Permission::create(['name' => 'coupon.show_by_id']);
        Permission::create(['name' => 'coupon.delete']);
        Permission::create(['name' => 'coupon.active']);

        $superAdmin->givePermissionTo('city.index','super_admin.index','super_admin.add','super_admin.update','super_admin.active','super_admin.delete','menu.index','menu.add','menu.active','menu.delete','emoji.index','emoji.add','emoji.update','emoji.active','emoji.delete','restaurant.index','restaurant.add','restaurant.update','restaurant.active','restaurant.delete','restaurant.update_super_admin_restaurant_id','package.index','package.add','package.update','package.active','package.delete','package.add_subscription','package.show_restaurant_subscription','rate.index','excel','admin_restaurant.index','admin_restaurant.add','admin_restaurant.update','admin_restaurant.active','admin_restaurant.delete','restaurant_manager.index','restaurant_manager.add','restaurant_manager.update','restaurant_manager.deactivat','restaurant_manager.delete',

        'category.index','category.add','category.update','category.active','category.delete','reorder','item.index','item.add','item.update','item.active','item.delete','user.index','user.add','user.update','user.delete','user.active','advertisement.index','advertisement.add','advertisement.update','advertisement.delete','news.index','news.add','news.update','news.delete','table.index','table.add','table.update','table.delete','restaurantId','my_restaurants','service.index','service.add','service.update','service.delete','update_restaurant_admin','delivery.index','delivery.add','delivery.update','delivery.active','delivery.delete','coupon.index','coupon.add','coupon.update','coupon.delete','coupon.active');

        $admin->givePermissionTo('category.index','category.add','category.update','category.active','category.delete','reorder','item.index','item.add','item.update','item.active','item.delete','order.index','order.add','order.update','order.delete','advertisement.index','advertisement.add','advertisement.update','advertisement.delete','news.index','news.add','news.update','news.delete','rate.index','excel','notifications.index','table.index','table.add','table.update','table.delete','service.index','service.add','service.update','service.delete','update_restaurant_admin','delivery.index','delivery.add','delivery.update','delivery.active','delivery.delete','coupon.index','coupon.add','coupon.update','coupon.delete','coupon.active');
    }
}

