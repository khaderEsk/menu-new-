<?php

namespace App\Http\Resources;

use App\Models\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResources extends JsonResource
{
    /**
     * دالة مساعدة لتنسيق القيم فقط إذا كانت غير null أو 0 أو 1.
     *
     * @param mixed $value
     * @return string|null
     */
    private function formatValue($value): ?string
    {
        return ($value !== null && $value !== 0 && $value !== 1) ? number_format($value) : null;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // تنسيق القيم المطلوبة باستخدام الدالة formatValue
        $formattedDeliveryPrice = $this->formatValue($this->delivery_price);
        $formattedPrice = $this->formatValue($this->price);
        $formattedDiscount = $this->formatValue($this->discount);
        $formattedTotal = $this->formatValue($this->total);
        $formattedConsumerSpending = $this->formatValue($this->consumer_spending);
        $formattedLocalAdministration = $this->formatValue($this->local_administration);
        $formattedReconstruction = $this->formatValue($this->reconstruction);




        // بناء المصفوفة النهائية
        $data = [
            'id' => $this->id,
            'num' => $this->num,
            'created_at' => $this->created_at->format('Y-m-d h:i:s'),
            'customer_id' => $this->customer_id,
            'price' => $this->price,
            'total' => $this->total,
            'status' => ucfirst(str_replace('_', ' ', strtolower($this->status->name))),
            'discount' => $formattedDiscount,
            'table_id' => $this->table_id,
            'admin_id' => $this->admin_id ?? null,
            'admin_name' => $this->admin->name ?? null,
            'number_table' => $this->table->number_table ?? null,
            'restaurant_id' => $this->restaurant_id,
            'restaurant_name' => $this->restaurant->name ?? null, // التأكد من أن المطعم موجود
            'logo' => $this->restaurant->getFirstMediaUrl('logo') ?? null, // التأكد من وجود المطعم
            'waiter' => $this->admin->name ?? null,
            'delivery_price' => $formattedDeliveryPrice,
            'total_with_delivery_price' => $formattedTotal,
            // 'orders' => $this->orders,
            'orders' => OrderResource::collection($this->orders),
        ];

        // إضافة القيم فقط إذا كانت غير null
        if ($formattedConsumerSpending !== null) {
            $data['consumer_spending'] = $formattedConsumerSpending;
        }
        if ($formattedLocalAdministration !== null) {
            $data['local_administration'] = $formattedLocalAdministration;
        }
        if ($formattedReconstruction !== null) {
            $data['reconstruction'] = $formattedReconstruction;
        }

        return $data;
    }
}
