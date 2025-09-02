<?php

namespace App\Http\Resources;

use App\Enum\InvoiceStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceUserMobileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
            if ($this->status ==  InvoiceStatus::PROCESSING)
                $status = "processing";
            elseif($this->status == InvoiceStatus::APPROVED)
                $status = "Approved";
            elseif($this->status == InvoiceStatus::UNDER_DELIVERY)
                $status = "under delivery";
            elseif($this->status == InvoiceStatus::COMPLETED)
                $status = "delivered";


        if ($this->total != null)
            $formattedTotal = number_format($this->total);

        $data = [
            'id' => $this->id,
            'num' => $this->num,
            'created_at' => $this->created_at->format('Y-m-d h:i:s'),
            'user' => $this->user->name ?? null,
            'user_phone' => $this->user->phone ?? null,
            'status' => $status ?? null,
            'total' => $formattedTotal ?? null,
            'restaurant_id' =>  $this->restaurant_id,
            'address' => $this->address->region ?? null,
            'url' => $this->address->url ?? null,
            'longitude' => $this->address->longitude ?? null,
            'latitude' => $this->address->latitude ?? null,
            'delivery_price' => (string)$this->delivery_price,
            'orders' => OrderMobileResource::collection($this->orders),
        ];
        return $data;
    }
}
