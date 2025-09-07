<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'response_time' => $this->order_time,
            'table_id' => $this->table_id,
            'number_table' => $this->Table->number_table ?? null,
            'employee_id' => $this->admin_id,
            'name' => $this->admin->name,
            'type' => $this->admin->type->name ?? null,
            'admin_id' => $this->admin_id,
        ];
        return $data;
    }
}
