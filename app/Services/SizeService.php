<?php

namespace App\Services;

use App\Models\Item;
use App\Models\Size;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class SizeService
{
    public function create(Item $item, array $data)
    {
        $size = new Size();
        $size->price = $data['price'];
        foreach (config('translatable.locales') as $locale) {
            $size->translateOrNew($locale)->name = $data['name_' . $locale];
        }
        $size->item()->associate($item);
        $size->save();
    }
    public function createMany(Item $item, ?array $sizesData)
    {
        if (empty($sizesData)) {
            return;
        }

        foreach ($sizesData as $sizeData) {
            $size = new Size();
            $size->price = $sizeData['price'];

            foreach (config('translatable.locales') as $locale) {
                $translation = $size->translateOrNew($locale);
                $translation->name = $sizeData['name_' . $locale];
                $translation->description = $sizeData['description_' . $locale] ?? null;
            }

            $size->item()->associate($item);
            $size->save();

            if (isset($sizeData['image']) && $sizeData['image'] instanceof UploadedFile) {
                $size->addMedia($sizeData['image'])->toMediaCollection('size_images');
            }
        }
    }
    public function updateMany(Item $item, array|null $data)
    {
        $item->sizes()->forceDelete();
        if (!empty($data)) {
            $this->createMany($item, $data);
        }
    }
}
