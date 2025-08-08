<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserShowItemsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if($this->is_active === null)
            $this->is_active = 1;
        if($this->price != null)
            $formattedPrice = number_format($this->price);

        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'name_en' => $this->translate('en')->name,
            'name_ar' => $this->translate('ar')->name,
            'description' => $this->description,
            'description_en' => $this->translate('en')->description,
            'description_ar' => $this->translate('ar')->description,
            'price' => $formattedPrice ?? null,
            'index' => $this->index,
            'is_active' => $this->is_active,
            'image' => $this->getFirstMediaUrl('item') ?: $this->restaurant->getFirstMediaUrl('logo'),
            'restaurant_id' => $this->restaurant_id,
            'restaurant' => $this->restaurant->name,
            'category' => $this->category->name,
            'category_id' => $this->category_id,
            'is_panorama' => $this->is_panorama,
            'translations' => $this->getTranslationsArray(),
        ];

        $firstAdd = [
            'id' => $this->id,
            'item_id' => $this->item_id,
            'name' => $this->name ?? null,
            'name_en' => $this->translate('en')->name ?? null,
            'name_ar' => $this->translate('ar')->name ?? null,
            'price' => $formattedPrice ?? null,
            'image' => $this->getFirstMediaUrl('item') ?: $this->restaurant->getFirstMediaUrl('logo'),
            'translations' => $this->getTranslationsArray(),
        ];

        if ($this->items->isNotEmpty()) {
            $additionalAdds = $this->items->map(function ($item) {
              if($item->price != null)
                $formattedPrice2 = number_format($item->price);
                return [
                    'id' => $item->id,
                    'item_id' => $item->item_id,
                    'name' => $item->name ?? null,
                    'name_en' => $item->translate('en')->name ?? null,
                    'name_ar' => $item->translate('ar')->name ?? null,
                    'price' => $formattedPrice2 ?? null,
                    'image' => $item->getFirstMediaUrl('item') ?? ($this->getFirstMediaUrl('item') ?: $this->restaurant->getFirstMediaUrl('logo')),
                    'translations' => $item->getTranslationsArray(),
                ];
            })->toArray();
        } else {
            $additionalAdds = [];
        }

        $data['adds'] = array_merge([$firstAdd], $additionalAdds);

        return $data;
    }
}
