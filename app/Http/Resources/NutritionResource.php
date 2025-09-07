<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NutritionResource extends JsonResource
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
            'amount' => $this->amount,
            'unit' => $this->unit,
            'kcal' => $this->kcal,
            'protein' => $this->protein,
            'fat' => $this->fat,
            'carbs' => $this->carbs
        ];
    }
}
