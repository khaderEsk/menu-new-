<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PermissionsRoleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

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
        $flattenedRole = call_user_func_array('array_merge', $roles->toArray());

        $data = [
            'id' => $this->id,
            'user_name' => $this->user_name,
            'name' => $this->name,
            'mobile' => $this->mobile,
            'is_active' => $this->is_active,
            'fcm_token' => $this->fcm_token,
            'roles' => $flattenedRole,
            'permissions' => $permissions,
            // 'roles' => $this->roles->pluck('name'),
            // 'permissions' => $this->permissions->pluck('name'),
            'restaurant' => RestaurantResource::make($this->whenLoaded('restaurant')),
        ];
        return $data;
    }
}
