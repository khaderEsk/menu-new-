<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PermissionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $translatedRoles = $this->roles->map(function ($role) {
            return [
                'name' => $role->name,
                'translated' => trans('permissions.' . $role->name),
            ];
        });

        $data = [
            $translatedRoles
        ];

        return $data;
    }
}
