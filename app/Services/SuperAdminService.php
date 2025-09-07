<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\SuperAdmin;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SuperAdminService
{
    // to show all
    public function all()
    {
        $SuperAdmins = SuperAdmin::where('id', '!=', 1)->with('city')->with('permissions')->latest()->get();
        return $SuperAdmins;
    }

    // to show paginate  admin
    public function paginateRole($num, $role)
    {
        $SuperAdmins = SuperAdmin::where('id', '!=', 1)->with('city')->role($role)->latest()->paginate($num);
        return $SuperAdmins;
    }

    // to show paginate  active
    public function paginate($num)
    {
        $SuperAdmins = SuperAdmin::where('id', '!=', 1)->with('city')->latest()->paginate($num);
        return $SuperAdmins;
    }

    // to create
    public function create($data)
    {
        $superAdmin = SuperAdmin::create($data);

        $rolesTranslations = trans('roles');

        $roleKey = array_search($data['role'], $rolesTranslations);

        $role = Role::where('name', $roleKey)->first();

        if ($role) {
            $superAdmin->assignRole($role);
        }

        if (!empty($data['permission'])) {
            foreach ($data['permission'] as $permission) {
                $permissionsTranslations = trans('permissions');
                $permissionKey = array_search($permission, $permissionsTranslations);

                if ($permissionKey) {
                    $perm = Permission::where('name', $permissionKey)->first();
                    if ($perm) {
                        $superAdmin->givePermissionTo($perm);
                    }
                }
            }
        }
        return $superAdmin;
    }

    // to show all restaurant Manager
    public function allBoss($num)
    {
        $SuperAdmins = Admin::role("restaurantManager")->with('permissions')->latest()->paginate($num);
        return $SuperAdmins;
    }

    // to create restaurantManager
    public function createBoss($data)
    {
        $data['type_id'] = 2;
        $superAdmin = Admin::create($data);
        $superAdmin->assignRole(['restaurantManager']);
        $superAdmin->givePermissionTo('category.index', 'category.add', 'category.update', 'category.active', 'category.delete', 'reorder', 'item.index', 'item.add', 'item.update', 'item.active', 'item.delete', 'update_restaurant_admin', 'order.index', 'order.add', 'order.update', 'order.delete', 'user.index', 'user.add', 'user.update', 'user.delete', 'user.active', 'advertisement.index', 'advertisement.add', 'advertisement.update', 'advertisement.delete', 'news.index', 'news.add', 'news.update', 'news.delete', 'rate.index', 'excel', 'notifications.index', 'table.index', 'table.add', 'table.update', 'table.delete', 'restaurantId', 'my_restaurants', 'service.index', 'service.add', 'service.update', 'service.delete', 'delivery.index', 'delivery.add', 'delivery.update', 'delivery.active', 'delivery.delete', 'coupon.index', 'coupon.add', 'coupon.update', 'coupon.delete', 'coupon.active');
        return $superAdmin;
    }

    // to update
    public function update($arrRole, $arrAdmin)
    {
        if (\array_key_exists('password', $arrAdmin))
            $arrAdmin['password'] = Hash::make($arrAdmin['password']);

        $superAdmin = SuperAdmin::whereId($arrAdmin['id'])->update($arrAdmin);
        $admin =  SuperAdmin::findOrFail($arrAdmin['id']);

        $permissions = $admin->permissions;
        $admin->revokePermissionTo($permissions);

        $rolesTranslations = trans('roles');

        $roleKey = array_search($arrRole['role'], $rolesTranslations);

        $role = Role::where('name', $roleKey)->first();

        if ($role) {
            $admin->removeRole($role);
            $admin->assignRole($role);
        }

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



        // $role = $admin->roles->pluck('name')->first();
        // if($admin->hasAnyRole(['citySuperAdmin', 'dataEntry']))
        // $admin->removeRole($role);
        // $admin->assignRole($arrRole['role']);
        // $permissions = $admin->permissions;
        // $admin->revokePermissionTo($permissions);
        // $admin->givePermissionTo($arrRole['permission']);
        return $superAdmin;
    }

    // to show
    public function show(string $id)
    {
        $SuperAdmin = SuperAdmin::with('city')->findOrFail($id);
        return $SuperAdmin;
    }

    // to delete
    public function destroy(string $id, $admin)
    {
        return SuperAdmin::whereId($id)->forceDelete();
    }

    public function activeOrDesactive($data, $admin)
    {
        if ($data['is_active'] == 1) {
            $SuperAdmin = SuperAdmin::whereId($data['id'])->update([
                'is_active' => 0,
            ]);
        } else {
            $SuperAdmin = SuperAdmin::whereId($data['id'])->update([
                'is_active' => 1,
            ]);
        }
        return $SuperAdmin;
    }

    public function searchRole($role, $where, $num)
    {
        $SuperAdmin = SuperAdmin::where('id', '!=', 1)->role($role)->where($where)->latest()->paginate($num);
        return $SuperAdmin;
    }
    public function search($where, $num)
    {
        $SuperAdmin = SuperAdmin::where('id', '!=', 1)->where($where)->latest()->paginate($num);
        return $SuperAdmin;
    }

    // ------------------------------------

    // to update Boss
    public function updateBoss($arrAdmin)
    {
        if (\array_key_exists('password', $arrAdmin))
            $arrAdmin['password'] = Hash::make($arrAdmin['password']);

        $admin = Admin::whereId($arrAdmin['id'])->update($arrAdmin);
        return $admin;
    }

    // to show Boss
    public function showBoss(string $id)
    {
        $admin = Admin::findOrFail($id);
        return $admin;
    }

    // to delete Boss
    public function destroyBoss(string $id)
    {
        return Admin::whereId($id)->forceDelete();
    }

    // to active Or desactive Boss
    public function activeOrDesactiveBoss($data)
    {
        if ($data['is_active'] == 1) {
            $admin = Admin::whereId($data['id'])->update([
                'is_active' => 0,
            ]);
        } else {
            $admin = Admin::whereId($data['id'])->update([
                'is_active' => 1,
            ]);
        }
        return $admin;
    }
}
