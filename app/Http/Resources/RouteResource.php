<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RouteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'distance' => round(($this['distance'] ?? 0) / 1000, 2), // km
            'duration' => round(($this['time'] ?? 0) / 1000 / 60, 2), // min
        ];
    }
}
