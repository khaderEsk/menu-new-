<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliverySitesResource extends JsonResource
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
            'username' => $this->username,
            'is_active' => $this->is_active ?? 1,
            'restaurant_longitude' => $restaurant->longitude ?? null,
            'restaurant_latitude' => $restaurant->latitude ?? null,
            'distance' => $this->when(isset($this->distance), $this->distance),
            'invoice' => InvoiceUserResource::collection($this->whenLoaded('invoices')),
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
