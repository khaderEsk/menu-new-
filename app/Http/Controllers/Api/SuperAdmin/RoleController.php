<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Role\ShowRequest;
use App\Http\Resources\CitySuperAdminResource;
use App\Http\Resources\PermissionResource;
use App\Http\Resources\PermResource;
use App\Models\Admin;
use App\Models\Restaurant;
use App\Models\SuperAdmin;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Throwable;

class RoleController extends Controller
{
    public function getPermissions(ShowRequest $request)
    {
        try{
            $role = $request->validated();
            $role = Role::findByName($role['role'], 'web');

            if (!$role) {
                return $this->messageErrorResponse(trans('locale.roleNotFound'),404);
            }

            if($role->name == 'admin')
            {
                $query = $role->permissions();
                $admin = auth()->user();
                $restaurant = Restaurant::whereId($admin->restaurant_id)->first(['is_advertisement','is_rate','rate_format','is_table','is_order','is_news']);
                if ($restaurant->is_advertisement == 0) {
                    $search = 'advertisement';
                    $query->where('name','not like', "%$search%");
                }

                if ($restaurant->is_rate == 0) {
                    $search = 'rate';
                    $query->where('name','not like', "%$search%");
                }
                if ($restaurant->is_table == 0) {
                    $search = 'table';
                    $query->where('name','not like', "%$search%");
                }
                if ($restaurant->is_order == 0) {
                    $search = 'order';
                    $query->where('name','not like', "%$search%");
                }
                if ($restaurant->is_news == 0) {
                    $search = 'news';
                    $query->where('name','not like', "%$search%");
                }
                $permissions = $query->get();
            }
            else
                $permissions = $role->permissions;

            $translatedRoles = $permissions->map(function ($role) {
                return [
                    'name' => trans('permissions.' . $role->name),
                ];
            });

            return response()->json($translatedRoles);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    public function getRolesAdmin()
    {
        try{
            $admin = auth()->user();
            if($admin->hasRole(['admin']))
                $role = Role::where('name','employee')->get();

            elseif($admin->hasRole(['restaurantManager']))
            {
                $rest = Admin::role('admin')->whereRestaurantId($admin->restaurant_id)->count();
                if($rest == 0)
                    $role = Role::whereName('admin')->orwhere('name','employee')->get();
                else
                    $role = Role::where('name','employee')->get();


            }

            elseif($admin->hasAnyRole(['superAdmin','citySuperAdmin','dataEntry']))
                $role = Role::whereName('admin')->orwhere('name','employee')->get();

            $translatedRoles = $role->map(function ($r) {
                return [
                    'id' => $r->id,
                    'name' => trans('roles.' . $r->name),
                ];
            });


            return response()->json($translatedRoles);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }


    public function getRolesSuperAdmin(Request $request)
    {
        try{
            if($request->has('type'))
            {
                $role = Role::whereName('admin')->orwhere('name','employee')->get();
                $translatedRoles = $role->map(function ($r) {
                    return [
                        'id' => $r->id,
                        'name' => trans('roles.' . $r->name),
                    ];
                });

                return response()->json($translatedRoles);
            }
            else
            {
                $role = Role::whereName('dataEntry')->orWhere('name','citySuperAdmin')->get();
                $translatedRoles = $role->map(function ($r) {
                    return [
                        'id' => $r->id,
                        'name' => trans('roles.' . $r->name),
                    ];
                });

                return response()->json($translatedRoles);
            }
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    public function dataEntry(Request $request)
    {
        try{
            $superAdmin = SuperAdmin::whereId($request->id)->first();
            $superAdmin->givePermissionTo('city.index','city.add','city.update','city.active','city.delete','super_admin.index','super_admin.add','super_admin.update','super_admin.active','super_admin.delete','menu.index','menu.add','menu.active','menu.delete','emoji.index','emoji.add','emoji.update','emoji.active','emoji.delete','restaurant.index','restaurant.add','restaurant.update','restaurant.active','restaurant.delete','restaurant.update_super_admin_restaurant_id','package.index','package.add','package.update','package.active','package.delete','package.add_subscription','package.show_restaurant_subscription','rate.index','excel','admin_restaurant.index','admin_restaurant.add','admin_restaurant.update','admin_restaurant.active','admin_restaurant.delete','category.index','category.add','category.update','category.active','category.delete','reorder','item.index','item.add','item.update','item.active','item.delete','user.index','user.add','user.update','user.delete','user.active','advertisement.index','advertisement.add','advertisement.update','advertisement.delete','news.index','news.add','news.update','news.delete','table.index','table.add','table.update','table.delete','delivery.index','delivery.add','delivery.update','delivery.active','delivery.delete','coupon.index','coupon.add','coupon.update','coupon.delete','coupon.active');
            return $this->successResponse($superAdmin,trans('locale.successfully'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }


    public function permissions(ShowRequest $request)
    {
        try{
            $role = $request->validated();
            $role = Role::findByName($role['role'], 'web');

            if (!$role) {
                return $this->messageErrorResponse(trans('locale.roleNotFound'),404);
            }

             $permissions = $role->permissions;

             $permission = PermResource::collection($permissions);

            return response()->json($permission);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

}
