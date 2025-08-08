<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminCategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // --- Logic Implementation ---

        // 1. ✅ FIX: Ensure the necessary relationships are loaded.
        // This makes the resource self-sufficient. If the controller didn't load
        // 'categories' or 'items', this line will load them for the current category.
        // This is crucial for the recursive calls to work correctly.
        $this->resource->loadMissing(['categories', 'items']);

        // 2. Get the filtered collections of active children.
        $activeSubCategories = $this->categories->where('is_active', true);
        $activeItems = $this->items->where('is_active', true);

        // 3. Determine the content type based on the filtered collections.
        $content = 0;
        if ($activeSubCategories->isNotEmpty()) {
            // If there are active sub-categories, they take priority.
            $content = 1;
        } elseif ($activeItems->isNotEmpty()) {
            // Otherwise, if there are active items, show them.
            $content = 2;
        }

        // --- Data Structuring ---

        return [
            'id' => $this->id,
            'name' => $this->name,
            'name_en' => $this->translate('en')->name,
            'name_ar' => $this->translate('ar')->name,
            'image' => $this->getFirstMediaUrl('category') ?: ($this->restaurant?->getFirstMediaUrl('logo')),
            'is_active' => $this->is_active,
            'index' => $this->index,
            'category_id' => $this->category_id,
            'content' => $content, // The correctly determined content flag
            'translations' => $this->getTranslationsArray(),

            // Conditionally include sub-categories ONLY if content is 1.
            // The recursive call will also trigger the loadMissing logic, ensuring
            // the whole tree is processed correctly.
            'sub_category' => $this->when($content === 1, function () use ($activeSubCategories) {
                return self::collection($activeSubCategories);
            }, []),

            // Conditionally include items ONLY if content is 2.
            'items' => $this->when($content === 2, function () use ($activeItems) {
                return ShowItemResource::collection($activeItems);
            }, []),
        ];
    }
}
