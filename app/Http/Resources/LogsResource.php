<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LogsResource extends JsonResource
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
            'loggable_type' => $this->loggable_type,
            'loggable_id' => $this->loggable_id,
            'action' => $this->action,
            'entity_type' => $this->entity_type,
            'entity_id' => $this->entity_id,
            'description' => $this->description,
            'original_data' => $this->original_data,
            'new_data' => $this->new_data,
            'created_at' => $this->created_at->format('Y-m-d'),
        ];

        return $data;
    }
}
