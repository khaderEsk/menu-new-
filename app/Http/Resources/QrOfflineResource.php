<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QrOfflineResource extends JsonResource
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
            'name' => $this->name,
            'email' => $this->email,
            'restaurant_url' => $this->restaurant_url,
            'website' => $this->website,
            'phone' => $this->phone,
            'facebook_url' => $this->facebook_url,
            'instagram_url' => $this->instagram_url,
            "whatsapp_phone" => $this->whatsapp_phone,
            "address" => $this->address,
            "qr_code" => env('APP_URL').'/'.str_replace('public', 'storage', $this->qr_code),
            "admin_id" => $this->admin_id,
        ];
        return $data;
    }
}
