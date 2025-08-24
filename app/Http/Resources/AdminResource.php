<?php

namespace App\Http\Resources;

use App\Models\EmployeeTable;
use App\Models\Type;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $totalSeconds = 0;
        if (request()->has('startDate') && request()->has('endDate')) {
            $num = EmployeeTable::whereRestaurantId($this->id)->where('created_at', '>=', request()->startDate)->where('created_at', '<=', request()->endDate)->get();
            for ($i = 0; $i < count($num); $i++) {
                $firstElement = $num->get($i);
                $time = $firstElement->order_time;
                list($hours, $minutes, $seconds) = explode(':', $time);
                $totalSeconds += ($hours * 3600) + ($minutes * 60) + $seconds;
            }
        } elseif (request()->has('endDate')) {
            $num = EmployeeTable::whereRestaurantId($this->id)->whereDate('created_at', '<=', request()->endDate)->get();
            for ($i = 0; $i < count($num); $i++) {
                $firstElement = $num->get($i);
                $time = $firstElement->order_time;
                list($hours, $minutes, $seconds) = explode(':', $time);
                $totalSeconds += ($hours * 3600) + ($minutes * 60) + $seconds;
            }
        } elseif (request()->has('startDate')) {
            $num = EmployeeTable::whereRestaurantId($this->id)->whereDate('created_at', request()->startDate)->get();
            for ($i = 0; $i < count($num); $i++) {
                $firstElement = $num->get($i);
                $time = $firstElement->order_time;
                list($hours, $minutes, $seconds) = explode(':', $time);
                $totalSeconds += ($hours * 3600) + ($minutes * 60) + $seconds;
            }
        } else {
            $num = EmployeeTable::whereAdminId($this->id)->get();
            for ($i = 0; $i < count($num); $i++) {
                $firstElement = $num->get($i);
                $time = $firstElement->order_time;
                list($hours, $minutes, $seconds) = explode(':', $time);
                $totalSeconds += ($hours * 3600) + ($minutes * 60) + $seconds;
            }
        }
        $n = count($num) == 0 ? 1 : count($num);
        $total = $totalSeconds / $n;
        $hours = floor($total / 3600);
        $minutes = floor(($total % 3600) / 60);
        $seconds = $total % 60;
        $avg = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);


        if ($this->is_active === null)
            $this->is_active = 1;

        $roles = $this->roles->map(function ($role) {
            return [
                trans('roles.' . $role->name),
            ];
        });

        $permissions = $this->permissions->map(function ($permission) {
            return [
                'name' => trans('permissions.' . $permission->name),
            ];
        });
        // $flattenedPermissions = call_user_func_array('array_merge', $permissions->toArray());
        // $flattenedRole = call_user_func_array('array_merge', $roles->toArray());

        $rolesArray = $roles->toArray();
        $flattenedRolesArray = array_map(function ($role) {
            return is_array($role) ? implode(', ', $role) : $role;
        }, $rolesArray);

        $rolesString = implode(', ', $flattenedRolesArray);
        $typeName = null;
        if ($this->type_id) {
            $typeModel = Type::find($this->type_id);
            $typeName = $typeModel?->name; // Use the null-safe operator just in case
        }
        if ($this->type_id > 2) {
            $n = count($num);
        } else {
            $n = 0;
            $avg = "ــ";
        }

        $data = [
            'id' => $this->id,
            'user_name' => $this->user_name,
            'name' => $this->name,
            'mobile' => $this->mobile,
            'number' => $n,
            'type_id' => $this->type_id,
            // 'type' => $this->type->name ?? null,
            'type' => $typeName, // <-- Use the new $typeName variable
            'email' => $this->email,
            'is_active' => $this->is_active,
            'message_bad' => $this->restaurant->message_bad ?? null,
            'message_good' => $this->restaurant->message_good ?? null,
            'message_perfect' => $this->restaurant->message_perfect ?? null,
            'avg' => $avg,
            'restaurant_id' => $this->restaurant_id,
            'fcm_token' => $this->fcm_token,
            'roles' => $rolesString,
            'permissions' => $permissions,
            // 'category' => $this->categories,
            // 'roles' => $this->roles->pluck('name'),
            // 'permissions' => $this->permissions->pluck('name'),
            // 'restaurant' => RestaurantResource::make($this->whenLoaded('restaurant')),
            'email' => $this->email
        ];
        return $data;
    }
}
