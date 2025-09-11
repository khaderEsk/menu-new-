<?php

namespace App\Services;

use App\Models\Component;
use App\Models\Item;

class ComponentService
{
    public function create(Item $item, array $data)
    {
        $component = new Component();
        $component->status = $data['status'];
        foreach (config('translatable.locales') as $locale) {
            $component->translateOrNew($locale)->name = $data['name_' . $locale];
        }
        $component->item()->associate($item);
        $component->save();
    }
    public function createMany(Item $item, array|null $data)
    {
        if (!empty($data))
            foreach ($data as $componentData) {
                $component = new Component();
                $component->status = $componentData['status'];
                foreach (config('translatable.locales') as $locale) {
                    $component->translateOrNew($locale)->name = $componentData['name_' . $locale];
                }
                $component->item()->associate($item);
                $component->save();
            }
    }

    public function updateMany(Item $item, array|null $data)
    {
        $item->components()->delete();
        if (!empty($data)) {
            $this->createMany($item, $data);
        }
    }
}
