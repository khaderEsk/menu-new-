<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CouponResource extends JsonResource
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
            'code' => $this->code,
            'from_date' => $this->from_date,
            'to_date' =>  $this->to_date,
            'type' => $this->type,
            'percent' => $this->percent,
            'is_active' => $this->is_active,
            'restaurant_id' => $this->restaurant_id,
        ];
        return $data;
    }
}
