<?php

namespace App\Http\Resources;

use App\Models\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */

    public function toArray(Request $request): array
    {
        // ✅ CLEAN: Access the restaurant via the eager-loaded relationship. No more queries!
        $restaurant = $this->whenLoaded('restaurant');

        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->email,
            'role' => $this->role,
            'phone' => $this->phone,
            'gender' => $this->gender,
            'birthday' => $this->birthday,
            'address' => $this->whenLoaded('latestAddress'),
            'restaurant_id' => $this->restaurant_id,
            'status' => $this->status,
            'is_active' => $this->is_active ?? 1,
            'token' => $this->when(isset($this->token), $this->token),
            'image' => $this->getFirstMediaUrl('delivery') ?? null,
            'restaurant_longitude' => $restaurant->longitude ?? null,
            'restaurant_latitude' => $restaurant->latitude ?? null,
            'distance' => $this->when(isset($this->distance), $this->distance),
            'invoice' => InvoiceUserResource::collection($this->whenLoaded('invoices')),
            'address' => new AddressResource($this->whenLoaded('latestAddress')),
        ];

        // This logic remains the same.
        if ($this->role == 0) {
            $latestAddress = $this->whenLoaded('latestAddress');
            $data['longitude'] = $latestAddress->longitude ?? null;
            $data['latitude'] = $latestAddress->latitude ?? null;
        } elseif ($this->role == 1) {
            $data['longitude'] = $this->longitude ?? null;
            $data['latitude'] = $this->latitude ?? null;
        }

        return $data;
    }
}
