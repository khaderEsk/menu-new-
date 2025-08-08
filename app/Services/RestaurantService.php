<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\RestaurantTranslation;
use App\Models\Restaurant;
use App\Models\SuperAdmin;

class RestaurantService
{
    // to show all Restaurant active
    public function all()
    {
        $restaurants = Restaurant::latest()->get();
        return $restaurants;
    }

    // to show paginate Restaurant active
    public function paginate($num)
    {
        $restaurants = Restaurant::with('admins')->latest()->paginate($num);
        return $restaurants;
    }

    public function allRestaurantManager($admin)
    {
        $restaurants = Restaurant::whereAdminId($admin)->latest()->get();
        return $restaurants;
    }
    // to show paginate Restaurant active
    public function paginateRestaurantManager($admin, $num)
    {
        $restaurants = Restaurant::whereAdminId($admin)->latest()->paginate($num);
        return $restaurants;
    }
    public function searchRestaurantManager($admin, $data, $num)
    {
        $restaurant = Restaurant::whereAdminId($admin)->whereTranslationLike('name', "%$data%")->latest()->paginate($num);
        return $restaurant;
    }
    // to create Restaurant
    public function create($id, $data)
    {
        $data['super_admin_id'] = $id;
        $lan = [
            'en' => [
                'name' => $data['name_en'],
                'note' => $data['note_en'],
            ],
            'ar' => [
                'name' => $data['name_ar'],
                'note' => $data['note_ar'],
            ],
        ];
        $arr = array_merge($data, $lan);
        $restaurant = Restaurant::create($arr);
        return $restaurant;
    }

    // to create Restaurant
    public function createAdmin($data)
    {
        $data['type_id'] = 1;
        $admin = Admin::create($data);
        $admin->assignRole(['admin']);
        $admin->givePermissionTo('category.index', 'category.add', 'category.update', 'category.active', 'category.delete', 'reorder', 'item.index', 'item.add', 'item.update', 'item.active', 'item.delete', 'update_restaurant_admin', 'order.index', 'order.add', 'order.update', 'order.delete', 'user.index', 'user.add', 'user.update', 'user.delete', 'user.active', 'advertisement.index', 'advertisement.add', 'advertisement.update', 'advertisement.delete', 'news.index', 'news.add', 'news.update', 'news.delete', 'rate.index', 'excel', 'notifications.index', 'table.index', 'table.add', 'table.update', 'table.delete', 'service.index', 'service.add', 'service.update', 'service.delete', 'delivery.index', 'delivery.add', 'delivery.update', 'delivery.active', 'delivery.delete', 'coupon.index', 'coupon.add', 'coupon.update', 'coupon.delete', 'coupon.active');

        return $admin;
    }

    // to update  Restaurant
    public function update($id, $arrRestaurant, $arrRestaurantTranslation)
    {
        foreach (['en', 'ar'] as $lang) {
            RestaurantTranslation::where('locale', $lang)->whereRestaurantId($arrRestaurantTranslation['id'])->update([
                'name' => $arrRestaurantTranslation['name_' . $lang],
                'note' => $arrRestaurantTranslation['note_' . $lang],
            ]);
        }

        $restaurant = Restaurant::whereId($arrRestaurant['id'])->update($arrRestaurant);
        // $restaurant = Restaurant::whereId($arrRestaurant['id'])->get();
        return $restaurant;
    }

    // to show a Restaurant
    public function show(string $id)
    {
        $Restaurant = Restaurant::with('admins')->findOrFail($id);
        return $Restaurant;
    }

    // to show a Restaurant
    public function showByName($data)
    {
        if (\array_key_exists('id', $data))
            $Restaurant = Restaurant::whereId($data['id'])->first();

        if (\array_key_exists('restaurant_name', $data))
        $Restaurant = Restaurant::whereNameUrl($data['restaurant_name'])->first();
    
        return $Restaurant;
    }

    // // to find user
    // public function findAdmin(string $id)
    // {
    //     $superAdmin = SuperAdmin::whereId($id)->get();
    //     $citySuperAdmin = CitySuperAdmin::whereId($id)->get();
    //     if($superAdmin)
    //         return 'superAdmin';
    //     if($citySuperAdmin)
    //         return 'citySuperAdmin';
    // }

    // to delete a Restaurant
    public function destroy(string $id, $admin)
    {
        RestaurantTranslation::whereRestaurantId($id)->forceDelete();
        Admin::whereRestaurantId($id)->forceDelete();
        $restaurant =  Restaurant::whereId($id)->forceDelete();
        return $restaurant;
    }

    public function activeOrDesactive($data, $admin)
    {
        if ($data['is_active'] == 1) {
            $Restaurant = Restaurant::whereId($data['id'])->update([
                'is_active' => 0,
            ]);
        } else {
            $Restaurant = Restaurant::whereId($data['id'])->update([
                'is_active' => 1,
            ]);
        }
        return $Restaurant;
    }

    public function filter($where, $num)
    {
        $restaurants = Restaurant::with('admins')->where($where)->latest()->paginate($num);
        return $restaurants;
    }

    public function search($data, $num)
    {
        $restaurant = Restaurant::with('admins')->whereTranslationLike('name', "%$data%")->latest()->paginate($num);
        return $restaurant;
    }

    // to update super admin restaurant id
    public function updateRestaurantId($id, $admin_id)
    {
        $super = SuperAdmin::whereId($admin_id)->update([
            'restaurant_id' => $id,
        ]);
        return $super;
    }

    // to update super admin restaurant id
    public function updateRestaurantIdAdmin($id, $admin_id)
    {
        $super = Admin::whereId($admin_id)->update([
            'restaurant_id' => $id,
        ]);
        return $super;
    }
}
