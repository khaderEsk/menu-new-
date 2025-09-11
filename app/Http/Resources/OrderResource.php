<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public static $userTypeId; // <-- ADD THIS LINE

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if ($this->status === null) {
            $this->status = 'waiting';
        }

        $data = [
            'id' => $this->id,
            'item_id' => $this->item_id,
            'name' => $this->name,
            'name_en' => $this->translate('en')->name,
            'name_ar' => $this->translate('ar')->name,
            'type_en' => $this->translate('en')->type,
            'type_ar' => $this->translate('ar')->type,
            'price' => $this->price,
            'size' => $this->size ?? null,
            'count' => $this->count,
            'total' => $this->price * $this->count,
            'table_id' => $this->table_id,
            'number_table' => $this->whenLoaded('table', fn() => $this->table->number_table),
            'status' => $this->status,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'invoice_id' => $this->invoice_id,
            'translations' => $this->getTranslationsArray(),
        ];

        // Make sure this uses self::
        if (in_array(self::$userTypeId, [1, 2, 3])) {
            $data['price'] = $this->price;
        }

        return $data;
    }
}
