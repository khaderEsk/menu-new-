<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderMobileResource extends JsonResource
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
            'name' => $this->translate('ar')->name,
            'type' => $this->translate('ar')->type,
            'price' => $this->price,
            'count' => $this->count,
            'total' => $this->price * $this->count?? null,
        ];
        return $data;
    }
}
