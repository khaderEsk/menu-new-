<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PackageResource extends JsonResource
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

        $data = [
            'id' => $this->id,
            'title' => $this->title,
            'title_en' => $this->translate('en')->title,
            'title_ar' => $this->translate('ar')->title,
            'price' => $this->price,
            'value' => $this->value,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at->format('Y-m-d'),
            'translations' => $this->getTranslationsArray(),
        ];
        return $data;
    }
}
