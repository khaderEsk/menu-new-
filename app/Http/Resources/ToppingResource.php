<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ToppingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'price' => $this->price,
            'icon' => $this->getFirstMediaUrl('toppings') != "" ? $this->getFirstMediaUrl('toppings')
                                                                 : $this->item->restaurant->getFirstMediaUrl('logo'),
            'name' => $this->name,
            'name_en' => $this->translate('en')->name,
            'name_ar' => $this->translate('ar')->name,
        ];
    }
}
