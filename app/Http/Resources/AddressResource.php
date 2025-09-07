<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AddressResource extends JsonResource
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
            'url' => $this->url,
            'city' => $this->city,
            'region' => $this->region,
            'user_id' => $this->user_id,
            'restaurant_id' => $this->restaurant_id,
        ];

        return $data;
    }
}
