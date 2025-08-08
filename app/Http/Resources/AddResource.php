<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AddResource extends JsonResource
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
            'item_id' => $this->item_id,
            'name' => $this->name ?? null,
            'name_en' => $this->translate('en')->name ?? null,
            'name_ar' => $this->translate('ar')->name ?? null,
            'price' => $this->price,
            'image' => $this->getFirstMediaUrl('item') ?? null,
            'translations' => $this->getTranslationsArray(),
        ];

        return $data;
    }
}
