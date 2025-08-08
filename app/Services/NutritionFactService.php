<?php

namespace App\Services;

use App\Models\Item;
use App\Models\NutritionFact;

class NutritionFactService
{

    public function create(Item $item, array|null $data): NutritionFact|null
    {
        $nutritionFact = null;
        if (!empty($data)) {
            $nutritionFact = new NutritionFact($data);
            $nutritionFact->item()->associate($item);
            $nutritionFact->save();
        }
        return $nutritionFact;
    }

    public function update(Item $item, array $data): NutritionFact|null
    {
        $item->nutrition()->delete();
        $nutritionFact = null;
        if (!empty($data)) {
            $nutritionFact = new NutritionFact($data);
            $nutritionFact->item()->associate($item);
            $nutritionFact->save();
        }
        return $nutritionFact;
    }
}
