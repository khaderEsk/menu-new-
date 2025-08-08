<?php

namespace App\Services;

use App\Models\Admin;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserService
{
    // to show all user active
    public function all($id,$admin_id)
    {
        $users = Admin::where('id','!=',$admin_id)->whereRestaurantId($id)->with('permissions')->with('roles')->latest()->get();
        return $users;

    }

    // to show paginate  active
    public function paginate($num,$id,$admin_id)
    {
        $admins = Admin::where('id','!=',$admin_id)->whereRestaurantId($id)->latest()->paginate($num);
        return $admins;
    }

    public function searchRole($id,$role,$where,$num,$admin_id)
    {
        $admin=Admin::where('id','!=',$admin_id)->whereRestaurantId($id)->role($role)->where($where)->latest()->paginate($num);
        return $admin;
    }

      // to show paginate  admin
      public function paginateRole($id,$num,$role,$admin_id)
      {
          $admin = Admin::where('id','!=',$admin_id)->whereRestaurantId($id)->role($role)->latest()->paginate($num);
          return $admin;
      }

    // to create
    public function create($data,$id)
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
                $admin->givePermissionTo('order.index','order.add','order.update','order.delete','table.index','table.add','table.update','table.delete','service.index','service.add','service.update','service.delete','delivery.index','delivery.add','delivery.update','delivery.active','delivery.delete','coupon.index','coupon.add','coupon.update','coupon.delete','coupon.active');
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


        // $data['restaurant_id'] = $id;
        // $admin = Admin::create($data);
        // $admin->assignRole($data['role']);
        // $admin->givePermissionTo($data['permission']);
        return $admin;
    }

    // to update user
    public function update($admin,$data,$arrRole,$admin_id)
    {
        if(\array_key_exists('password',$data))
        {
            if(is_null($data['password']))
                $data = Arr::only($data, ['id','name','user_name','mobile','type']);
            else
                $data['password'] = Hash::make($data['password']);
        }
        $rolesTranslations = trans('roles');

        $roleKey = array_search($arrRole['role'], $rolesTranslations);
        if (!$roleKey) {
            return "the role is incorrect";
        }
        $role = Role::where('name', $roleKey)->first();
        $data['restaurant_id'] = $admin->restaurant_id;
        $user = Admin::where('id','!=',$admin_id)->whereRestaurantId($admin->restaurant_id)->whereId($data['id'])->update($data);
        $admin =  Admin::findOrFail($data['id']);
        // $admin->removeRole($role);
        if(\array_key_exists('permission',$arrRole) && \array_key_exists('role',$arrRole))
        {
            $permissions = $admin->permissions;
            $admin->revokePermissionTo($permissions);
            $admin->syncRoles($role->name);
        }


        // $admin->assignRole($role);
    // if ($role) {
    // }

        if ($role->name === "admin")
        {
            $admin->assignRole($role);
            $admin->givePermissionTo('category.index','category.add','category.update','category.active','category.delete','reorder','item.index','item.add','item.update','item.active','item.delete','update_restaurant_admin','order.index','order.add','order.update','order.delete','user.index','user.add','user.update','user.delete','user.active','advertisement.index','advertisement.add','advertisement.update','advertisement.delete','news.index','news.add','news.update','news.delete','rate.index','excel','notifications.index','table.index','table.add','table.update','table.delete','service.index','service.add','service.update','service.delete','delivery.index','delivery.add','delivery.update','delivery.active','delivery.delete','coupon.index','coupon.add','coupon.update','coupon.delete','coupon.active');
            return 1;
        }

        // if ($role) {
        //     $admin->assignRole($role);
        // }
// // **********************************************************
//             $data['restaurant_id'] = $admin->restaurant_id;
//             $user = Admin::where('id','!=',$admin_id)->whereRestaurantId($admin->restaurant_id)->whereId($data['id'])->update($data);
//             $admin =  Admin::findOrFail($data['id']);

//             $permissions = $admin->permissions;
//             $admin->revokePermissionTo($permissions);

//             $rolesTranslations = trans('roles');

//             $roleKey = array_search($arrRole['role'], $rolesTranslations);

//             $role = Role::where('name', $roleKey)->first();

//         if ($role) {
//             $admin->removeRole($role);
//             $admin->assignRole($role);
//         }

        if(\array_key_exists('permission',$arrRole) && \array_key_exists('role',$arrRole))
        {
            if (!empty($arrRole['permission'])) {
                foreach ($arrRole['permission'] as $permission) {
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
        }

        // $data['restaurant_id'] = $admin->restaurant_id;
        // $user = Admin::whereRestaurantId($admin->restaurant_id)->whereId($data['id'])->update($data);
        // $Admin =  Admin::findOrFail($data['id']);
        // $Admin->assignRole($arrRole['role']);
        // $Admin->givePermissionTo($arrRole['permission']);
        return $user;
    }


    // to show a user
    public function show($id,$data,$admin_id)
    {
        $user = Admin::where('id','!=',$admin_id)->whereRestaurantId($id)->findOrFail($data['id']);
        return $user;
    }

    // to delete a user
    public function destroy($data,$restaurant_id)
    {
        return Admin::whereRestaurantId($restaurant_id)->whereId($data['id'])->forceDelete();
    }

    public function search($id,$num,$where,$admin_id)
    {
        $user=Admin::where('id','!=',$admin_id)->whereRestaurantId($id)->where($where)->latest()->paginate($num);
        return $user;

    }

    public function activeOrDesactive($data)
    {
        if($data['is_active'] == 1)
        {
            $admin = Admin::whereId($data['id'])->update([
                'is_active' => 0,
            ]);
        }
        else
        {
             $admin = Admin::whereId($data['id'])->update([
                'is_active' => 1,
            ]);
        }
        return $admin;
    }

}
