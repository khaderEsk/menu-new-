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
        $restaurant = Restaurant::where('id',$this->restaurant_id)->first();
        if($restaurant->rate_format->value == 1)
        {
            $data = [
                'id' => $this->id,
                'restaurant_id' => $this->restaurant_id,
                'name' => $this->customer->name,
                'phone' => $this->customer->phone,
                'birthday' => $this->customer->birthday,
                'gender' => $this->customer->gender,
                'restaurant' => $this->restaurant->name,
                'rate' => $this->rate,
                'note' => $this->note,
                'service' => $this->service,
                'arakel' => $this->arakel,
                'foods' => $this->foods,
                'drinks' => $this->drinks,
                'sweets' => $this->sweets,
                'games_room' => $this->games_room,
            ];
        }
        else
        {
            $data = [
                'id' => $this->id,
                'restaurant_id' => $this->restaurant_id,
                'name' => $this->customer->name,
                'phone' => $this->customer->phone,
                'birthday' => $this->customer->birthday,
                'gender' => $this->customer->gender,
                'restaurant' => $this->restaurant->name,
                'rate' => $this->rate,
                'note' => $this->note,
            ];
        }

        return $data;
    }
}
