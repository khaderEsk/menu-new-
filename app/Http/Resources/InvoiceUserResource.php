<?php

namespace App\Http\Resources;

use App\Enum\InvoiceStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class InvoiceUserResource extends JsonResource
{
    /**
     * This static property will hold the user type ID.
     */
    public static $userTypeId;


    /**
     * NOTE: The __construct method has been DELETED from this file.
     * This is the fix that will solve the error.
     */

    public function toArray(Request $request): array
    {
        if ($this->status == 'Paid' || $this->status == 'Received' || $this->status == 'Under delivery')
            $status = $this->status;

        if ($this->price != null)
            $formattedPrice = number_format($this->price);
        if ($this->total != null)
            $formattedTotal = number_format($this->total);
        if ($this->discount != null)
            $formattedDiscount = number_format($this->discount);
        if ($this->consumer_spending != null)
            $formattedConsumerSpending = number_format($this->consumer_spending);
        if ($this->local_administration != null)
            $formattedLocalAdministration = number_format($this->local_administration);
        if ($this->reconstruction != null)
            $formattedReconstruction = number_format($this->reconstruction);
        if ($this->received_at != null)
            $receipt_at = $this->receipt_at->format('Y-m-d h:i:s');
        if ($this->received_at == null)
            $receipt_at = $this->receipt_at;
        if ($this->delivery_price != null)
            $formattedDeliveryPrice = round($this->delivery_price);
        if ($this->price != null && $this->delivery_price != null) {
            $total_with_delivery_price = $this->price + $formattedDeliveryPrice;
            if ($this->discount != null)
                $total_with_delivery_price = $total_with_delivery_price - $this->discount;
            $total_with_delivery_price  = number_format($total_with_delivery_price);
            $formattedTotal = $total_with_delivery_price;
        }

        $data = [
            'id' => $this->id,
            'num' => $this->num,
            'created_at' => $this->created_at->format('Y-m-d h:i:s'),
            'customer_received_at' => $this->customer_received_at,
            'received_at' => $receipt_at,
            'customer_id' => $this->customer_id,
            'user_id' => $this->user_id,
            'user' => $this->user->name ?? null,
            'username' => $this->user->username ?? null,
            'user_phone' => $this->user->phone ?? null,
            'delivery_name' => $this->delivery->name ?? null,
            'delivery_phone' => $this->delivery->phone ?? null,
            'delivery_address' => $this->delivery->address ?? null,
            'price' => $formattedPrice ?? null,
            'status' => ucfirst(str_replace('_', ' ', strtolower($this->status->name))),
            'total_estimated_duration' => $this->total_estimated_duration,
            'total' => $formattedTotal ?? null,
            'total_with_delivery_price' => $total_with_delivery_price ?? null,
            'discount' => $formattedDiscount ?? null,
            'table_id' =>  $this->table_id,
            'restaurant_id' =>  $this->restaurant_id,
            'url' => $this->address->url ?? null,
            'region' => $this->address->region ?? null,
            'longitude' => $this->address->longitude ?? null,
            'latitude' => $this->address->latitude ?? null,
            'delivery_price' => $formattedDeliveryPrice ?? null,
            'orders' => OrderResource::collection($this->orders),
            'distance_km' => $this->deliveryRoute->distance ?? null,
            $this->mergeWhen($this->status->value == InvoiceStatus::UNDER_DELIVERY->value, [
                'delivery_latitude' => $this->deliveryRoute?->start_lat ?? null,
                'delivery_longitude' => $this->deliveryRoute?->start_lon ?? null,
            ]),
        ];

        if ($this->address_id == null) {
            $data['consumer_spending'] = $formattedConsumerSpending ?? null;
            $data['local_administration'] = $formattedLocalAdministration ?? null;
            $data['reconstruction'] = $formattedReconstruction ?? null;
            $data['total'] = $formattedTotal ?? null;
        }
        return $data;
    }
}
