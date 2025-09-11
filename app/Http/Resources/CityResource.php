<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CityResource extends JsonResource
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
            'name' => $this->name,
            'name_em' => $this->translate('en')->name ?? null,
            'name_ar' => $this->translate('ar')->name ?? null,
            'is_active' => $this->is_active,
            'translations' => $this->getTranslationsArray(),
        ];
        return $data;

        // return parent::toArray($request);
    }
}
