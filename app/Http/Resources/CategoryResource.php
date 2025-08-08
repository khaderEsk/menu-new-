<?php

namespace App\Http\Resources;

use App\Models\Category;
use App\Models\Item;
use App\Models\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
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
        $restaurant= Restaurant::whereId($this->restaurant_id)->first();
        if(count($item) > 0)
            $content = 2;

        elseif(count($category) > 0)
            $content = 1;

        if($this->is_active === null)
            $this->is_active = 1;

        if($restaurant->is_sub_move == 1 &&  $this->category_id != null)
            $img = env('APP_URL')."/storage/sub_gif/IMG_20241026_133729_995.gif";
        else
            $img = $this->getFirstMediaUrl('category') ?: $this->restaurant->getFirstMediaUrl('logo');

        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'name_en' => $this->translate('en')->name,
            'name_ar' => $this->translate('ar')->name,
            'image' => $img,
            'is_active' => $this->is_active,
            'index' => $this->index,
            'category_id' => $this->category_id,
            // 'parent_name' => $this->children,
            'content' => $content,
            'translations' => $this->getTranslationsArray(),
        ];
        return $data;
    }
}
