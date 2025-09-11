<?php

namespace App\Http\Resources;

use App\Models\Category;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoriesItemsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $content = 0;
        $category = Category::whereCategoryId($this->id)->whereRestaurantId($this->restaurant_id)->get();
        $item = Item::whereCategoryId($this->id)->whereRestaurantId($this->restaurant_id)->get();
        if($this->is_active === null)
            $this->is_active = 1;

        $img = $this->getFirstMediaUrl('category') ?: $this->restaurant->getFirstMediaUrl('logo');

  if(count($item) > 0)
            $content = 2;

        elseif(count($category) > 0)
            $content = 1;


        if($content == 2 || $content == 0)
        {
            $data = [
                'id' => $this->id,
                'name' => $this->name,
                'name_en' => $this->translate('en')->name,
                'name_ar' => $this->translate('ar')->name,
                'image' => $img,
                'is_active' => $this->is_active,
                'index' => $this->index,
                'category_id' => $this->category_id,
                'content' => $content,
                'translations' => $this->getTranslationsArray(),
            ];
            return $data;

        }
  return [];


    }
}
