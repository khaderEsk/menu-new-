<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NewsResource extends JsonResource
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
            'name' => $this->name,
            'description' => $this->description,
            'name_en' => $this->translate('en')->name,
            'name_ar' => $this->translate('ar')->name,
            'description_en' => $this->translate('en')->description,
            'description_ar' => $this->translate('ar')->description,
            'image' => $this->getFirstMediaUrl('news'),
            'created_at' => $this->created_at->format('Y-m-d'),
            'translations' => $this->getTranslationsArray(),
        ];
        return $data;
    }
}
