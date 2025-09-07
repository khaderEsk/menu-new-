<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ComponentResource extends JsonResource
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
            'status'=>$this->status,
            'name'=>$this->name,
            'name_en' => $this->translate('en')->name ,
            'name_ar' => $this->translate('ar')->name ,
        ];
    }
}
