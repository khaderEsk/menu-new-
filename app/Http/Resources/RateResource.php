<?php

namespace App\Http\Resources;

use App\Models\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $restaurant = Restaurant::where('id', $this['restaurant_id'])->first();
        // dd($restaurant);


        if ($restaurant->rate_format->value == 1) {
            $data = [
                'id' => $this->id,
                // 'restaurant_id' => $this->restaurant_id,
                'name' => $this->name,
                'phone' => $this->phone,
                'birthday' => $this->customer->birthday,
                'gender' => $this->gender,
                // 'restaurant' => $this->restaurant->name,
                'rate' => $this->rate,
                'note' => $this->note,
                'service' => $this->service,
                'arakel' => $this->arakel,
                'foods' => $this->foods,
                'drinks' => $this->drinks,
                'sweets' => $this->sweets,
                'games_room' => $this->games_room,
                'type' => $this->type,
            ];
        } else {
            $data = [
                'id' => $this->id,
                // 'restaurant_id' => $this->restaurant_id,
                'name' => $this->name,
                'phone' => $this->phone,
                'birthday' => $this->birthday,
                'gender' => $this->gender,
                'restaurant' => $this->restaurant->name,
                'rate' => $this->rate,
                'note' => $this->note,
                'type' => $this->type
            ];
        }

        return $data;
    }
}
