<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CitySuperAdminResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if($this->is_active === null)
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

        $flattenedRole = call_user_func_array('array_merge', $roles->toArray());


        $rolesArray = $roles->toArray();
        $flattenedRolesArray = array_map(function($role) {
            return is_array($role) ? implode(', ', $role) : $role;
        }, $rolesArray);

        $rolesString = implode(', ', $flattenedRolesArray);

        // $flattenedPermissions = call_user_func_array('array_merge', $permissions->toArray());
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'user_name' => $this->user_name,
            'is_active' => $this->is_active,
            'city_id' =>  $this->city_id,
            'city' => CityResource::make($this->whenLoaded('city')),
            'restaurant_id' => $this->restaurant_id,
            'roles' => $rolesString,
            'permissions' => $permissions,
        ];
        return $data;
    }
}
