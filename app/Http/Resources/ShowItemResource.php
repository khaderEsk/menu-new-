<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShowItemResource extends JsonResource
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
            'name' => $this->name ?? null,
            'name_en' => $this->translate('en')->name ?? null,
            'name_ar' => $this->translate('ar')->name ?? null,
            'description' => $this->description,
            'description_en' => $this->translate('en')->description ?? null,
            'description_ar' => $this->translate('ar')->description ?? null,
            'price' => $this->price,
            'index' => $this->index,
            'is_active' => $this->is_active,
            'image' => $this->getFirstMediaUrl('item') ?: $this->restaurant->getFirstMediaUrl('logo'),
            'icon' => $this->getFirstMediaUrl('item_icon') ?: $this->restaurant->getFirstMediaUrl('logo'),
            'restaurant_id' => $this->restaurant_id,
            'restaurant' => $this->restaurant->name,
            'category' => $this->category->name,
            'category_id' => $this->category_id,
            'is_panorama' => $this->is_panorama,
            // 'adds' => AddResource::collection($this->items),
            'sizes' => SizeResource::collection($this->sizes),
            'components' => ComponentResource::collection($this->components),
            'toppings' => ToppingResource::collection($this->toppings),
            'nutrition' => NutritionResource::make($this->nutrition),
            // 'translations' => $this->getTranslationsArray(),
            'currency' => $this->currency,
        ];

        return $data;
    }
}
