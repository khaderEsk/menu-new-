<?php

namespace App\Services;

use App\Models\Admin;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Arr;

class AdminRestaurantService
{
    // to show all
    public function all($restaurant_id)
    {
        $SuperAdmins = Admin::with('roles')->with('permissions')->whereRestaurantId($restaurant_id)->with('permissions')->latest()->get();
        return $SuperAdmins;
    }

    // to show paginate  admin
    public function paginateRole($num,$role,$restaurant_id)
    {
        $SuperAdmins = Admin::with('roles')->with('permissions')->whereRestaurantId($restaurant_id)->role($role)->latest()->paginate($num);
        return $SuperAdmins;
    }

    // to show paginate  active
    public function paginate($num,$restaurant_id)
    {
        $SuperAdmins = Admin::with('roles')->with('permissions')->whereRestaurantId($restaurant_id)->latest()->paginate($num);
        return $SuperAdmins;
    }

      // to create
    public function create($data)
    {
        $superAdmin = Admin::create($data);
        $superAdmin->assignRole($data['role']);
        $superAdmin->givePermissionTo($data['permission']);
        return $superAdmin;
    }

      // to create
    public function create1($data,$id)
    {
        $rolesTranslations = trans('roles');
        $roleKey = array_search($data['role'], $rolesTranslations);
        if (!$roleKey) {
            return "the role is incorrect";
        }
        $role = Role::where('name', $roleKey)->first();

        $data['restaurant_id'] = $id;
        if(!request()->has('type_id'))
            $data['type_id'] = 1;
        $admin = Admin::create($data);
        if ($role->name === "admin")
        {
            $admin->assignRole($role);
            $admin->givePermissionTo('category.index','category.add','category.update','category.active','category.delete','reorder','item.index','item.add','item.update','item.active','item.delete','update_restaurant_admin','order.index','order.add','order.update','order.delete','user.index','user.add','user.update','user.delete','user.active','advertisement.index','advertisement.add','advertisement.update','advertisement.delete','news.index','news.add','news.update','news.delete','rate.index','excel','notifications.index','table.index','table.add','table.update','table.delete','service.index','service.add','service.update','service.delete','delivery.index','delivery.add','delivery.update','delivery.active','delivery.delete','coupon.index','coupon.add','coupon.update','coupon.delete','coupon.active');
        }

        if ($role->name === "employee")
        {
            if($data['type_id'] == 3)
            {
                $admin->assignRole($role);
                $admin->givePermissionTo('category.index','item.index','order.index','order.add','order.update','order.delete','table.index','table.add','table.update','table.delete','service.index','service.add','service.update','service.delete','delivery.index','delivery.add','delivery.update','delivery.active','delivery.delete','coupon.index','coupon.add','coupon.update','coupon.delete','coupon.active');
            }
            elseif($data['type_id'] == 4)
            {
                $admin->assignRole($role);
                $admin->givePermissionTo('category.index','item.index','order.index','order.add','order.update','order.delete','notifications.index','table.index','service.index','service.add','service.update','service.delete');
                $data['category'] = array_map('intval', $data['category']);
                $admin->categories()->sync($data['category']);
            }
            elseif($data['type_id'] == 5)
            {
                $admin->assignRole($role);
                $admin->givePermissionTo('category.index','item.index','order.index','order.add','order.update','order.delete','notifications.index','table.index','service.index','service.add','service.update','service.delete');

            }
            elseif($data['type_id'] == 6)
            {
                $admin->assignRole($role);
                $admin->givePermissionTo('category.index','item.index','order.index','notifications.index','table.index','service.index','service.add','service.update','service.delete');

            }
            elseif($data['type_id'] == 8)
            {
                $admin->assignRole($role);
                $admin->givePermissionTo('category.index','item.index','order.index','order.add','order.update','order.delete','notifications.index','table.index','service.index','service.add','service.update','service.delete');
                $data['category'] = array_map('intval', $data['category']);
                $admin->categories()->sync($data['category']);
            }
        }
        if ($role) {
            $admin->assignRole($role);
        }

        if (!empty($data['permission'])) {
            foreach ($data['permission'] as $permission) {
                $permissionsTranslations = trans('permissions');
                $permissionKey = array_search($permission, $permissionsTranslations);

                if ($permissionKey) {
                    $perm = Permission::where('name', $permissionKey)->first();
                    if ($perm) {
                        $admin->givePermissionTo($perm);
                    }
                }
            }
        }


        return $admin;
    }

    // to update
    public function update($arrAdmin)
    {
        if(\array_key_exists('password',$arrAdmin))
            $arrAdmin['password'] = Hash::make($arrAdmin['password']);

        $admin = Admin::whereId($arrAdmin['id'])->first();
        $permissions = $admin->permissions;
        $admin->revokePermissionTo($permissions);
        $admin->categories()->sync($arrAdmin['category']);
        if($arrAdmin['type_id'] == 3)
        {
            $admin->givePermissionTo('order.index','order.add','order.update','order.delete','table.index','table.add','table.update','table.delete','service.index','service.add','service.update','service.delete','delivery.index','delivery.add','delivery.update','delivery.active','delivery.delete','coupon.index','coupon.add','coupon.update','coupon.delete','coupon.active');
        }
        elseif($arrAdmin['type_id'] == 4)
        {
            $admin->givePermissionTo('category.index','item.index','order.index','order.add','order.update','order.delete','notifications.index','table.index','service.index','service.add','service.update','service.delete');
        }
        elseif($arrAdmin['type_id'] == 5)
        {
            $admin->givePermissionTo('category.index','item.index','order.index','order.add','order.update','order.delete','notifications.index','table.index','service.index','service.add','service.update','service.delete');
        }
        elseif($arrAdmin['type_id'] == 6)
        {
            $admin->givePermissionTo('category.index','item.index','order.index','notifications.index','table.index','service.index','service.add','service.update','service.delete');
        }
        elseif($arrAdmin['type_id'] == 8)
        {
            $admin->givePermissionTo('category.index','item.index','order.index','order.add','order.update','order.delete','notifications.index','table.index','service.index','service.add','service.update','service.delete');
        }

        $arrAdmin = Arr::only($arrAdmin,['id','name','password','user_name','mobile','type_id','restaurant_id']);
        $superAdmin = Admin::whereId($arrAdmin['id'])->update($arrAdmin);
        return $superAdmin;
    }

    // to show
    public function show($data)
    {
        $SuperAdmin = Admin::with('roles')->with('permissions')->findOrFail($data['admin_id']);
        return $SuperAdmin;
    }

    // to delete
    public function destroy($data)
    {
        return Admin::whereId($data['admin_id'])->forceDelete();
    }

    public function activeOrDesactive($data)
    {
        if($data['is_active'] == 1)
        {
            $SuperAdmin = Admin::whereId($data['id'])->update([
                'is_active' => 0,
            ]);
        }
        else
        {
             $SuperAdmin = Admin::whereId($data['id'])->update([
                'is_active' => 1,
            ]);
        }
        return $SuperAdmin;
    }

    public function searchRole($role,$where,$num)
    {
        $SuperAdmin=Admin::role($role)->where($where)->latest()->paginate($num);
        return $SuperAdmin;
    }
    public function search($where,$num)
    {
        $SuperAdmin=Admin::with('roles')->with('permissions')->where($where)->latest()->paginate($num);
        return $SuperAdmin;
    }
}
