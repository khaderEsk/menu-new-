<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdvertisementResources extends JsonResource
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
            'title' => $this->title,
            'from_date' => $this->from_date,
            'to_date' => $this->to_date,
            'image' => $this->getFirstMediaUrl('advertisement'),
            'restaurant' => $this->restaurant->name,
            'is_panorama' => $this->is_panorama,
            'hide_date' => $this->hide_date,
        ];
        return $data;
    }
}
