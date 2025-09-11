<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
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
            'body' => $this->body,
            'phone' => $this->phone,
            'restaurant_id' => $this->restaurant_id,
            'read_at' => $this->read_at ? 1 : 0,

            // 'admin' => $this->admin->name,
        ];
        return $data;
    }
}
