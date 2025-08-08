<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use PhpParser\Node\Expr\Cast\Double;

class SizeResource extends JsonResource
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
            'name' => $this->name,
            'name_en' => $this->translate('en')->name,
            'name_ar' => $this->translate('ar')->name,
            'description_en' => $this->translate('en')->description,
            'description_ar' => $this->translate('ar')->description,
            'image' => $this->getFirstMediaUrl('size_images'),
        ];
    }
}
