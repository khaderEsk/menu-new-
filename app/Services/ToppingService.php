<?php

namespace App\Services;

use App\Models\Item;
use App\Models\Topping;

class ToppingService
{
    public function create(Item $item, array $data)
    {
        $topping = new Topping();
        $topping->price = $data['price'];
        foreach (config('translatable.locales') as $locale) {
            $topping->translateOrNew($locale)->name = $data['name_' . $locale];
        }
        $topping->save();
    }
    public function createMany(Item $item, array|null $data)
    {

        if (!empty($data))
            foreach ($data as $index => $toppingData) {


                $topping = new Topping();
                $topping->price = $toppingData['price'];
                foreach (config('translatable.locales') as $locale) {
                    $topping->translateOrNew($locale)->name = $toppingData['name_' . $locale];
                }
                $topping->item()->associate($item);
                $topping->save();
                if (Request()->hasFile("toppings.$index.icon")) {
                    $extension = Request()->file("toppings.$index.icon")->getClientOriginalExtension();
                    $randomFileName = str()->random(10) . "-" . Date('Y-d-m')  . '.' . $extension;
                    $topping->addMediaFromRequest("toppings.$index.icon")
                        ->usingFileName($randomFileName)
                        ->usingName($topping->name)
                        ->toMediaCollection('toppings');
                }
            }
    }

    public function updateMany(Item $item, array|null $data)
    {
        $item->toppings()->delete();
        if (!empty($data)) {
            $this->createMany($item, $data);
        }
    }
}
