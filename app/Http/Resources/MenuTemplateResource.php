<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MenuTemplateResource extends JsonResource
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
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'is_active' => $this->is_active,
        ];

        return $data;
    }
}
