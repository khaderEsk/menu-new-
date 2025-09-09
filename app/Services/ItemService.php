<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\Item;
use App\Models\ItemTranslation;
use App\Traits\ImageTrait;
use Illuminate\Support\Facades\Log;

class ItemService
{
    use ImageTrait;
    public function __construct(
        private SizeService $sizeService,
        private ComponentService $componentService,
        private NutritionFactService $nutritionFactService,
        private ToppingService $toppingService
    ) {}
    // to show all item active
    public function all($data)
    {
        $items = Item::whereCategoryId($data)->orderBy('index')->get();
        return $items;
    }

    // to show paginate City active
    public function paginate($data, $num)
    {
        $items = Item::whereCategoryId($data['category_id'])->orderBy('index')->paginate($num);
        return $items;
    }

    // to create item
    public function create($data, $sizeImages)
    {
        $admin = auth()->user();
        // return Max Index In Master Category For Admin
        $maxIndex = Item::where('restaurant_id', $admin->restaurant_id)->where('category_id', $data['category_id'])->max('index');

        $arr = [
            'en' => [
                'name' => $data['name_en'],
                'description' => $data['description_en'] ?? null,
                'restaurant_id' => $admin->restaurant_id,
                'category_id' => $data['category_id'],

            ],
            'ar' => [
                'name' => $data['name_ar'],
                'description' => $data['description_ar'] ?? null,
                'restaurant_id' => $admin->restaurant_id,
                'category_id' => $data['category_id'],
            ],
            'price' => $data['price'] ?? null,
            'index' => $maxIndex + 1,
            'category_id' => $data['category_id'],
            'restaurant_id' => $admin->restaurant_id,
            'is_panorama' => $data['is_panorama'],
            'currency' => $data['currency'],
        ];
        $item = Item::create($arr);
        $this->uploadSingleImage($item, 'image', 'item');
        $this->uploadSingleImage($item, 'icon', 'item_icon');
        $this->sizeService->createMany($item, $data['sizes'] ?? []);
        $this->toppingService->createMany($item, $data['toppings'] ?? []);
        $this->componentService->createMany($item, $data['components'] ?? []);
        $this->nutritionFactService->create($item, $data['nutrition'] ?? []);
        // $item->save();
        return $item;
    }

    // to update item
    public function update($data, $sizeImages)
    {
        Log::info($data);
        $admin = auth()->user();
        $item = Item::whereId($data['id'])->first();
        // return Max Index In Master Category For Admin
        if (\array_key_exists('category_id', $data)) {
            if ($data['category_id'] != $item->category_id) {
                $item = Item::whereId($data['id'])->whereRestaurantId($admin->restaurant_id)->first();
                Item::where('category_id', $item->category_id)->whereRestaurantId($admin->restaurant_id)->orderBy('index')->where('index', '>', $item->index)->decrement('index');
                $maxIndex = Item::where('restaurant_id', $admin->restaurant_id)->whereCategoryId($data['category_id'])->max('index');
                $data['maxIndex'] = $maxIndex + 1;
            } else {
                $maxIndex = $item->index;
                $data['maxIndex'] = $maxIndex;
            }
        }
        if ($item->id !=  $data['id'])
            return response()->json(['status' => false, 'message' => "you can't update"], 200);


        $item->update([
            'price' => $data['price'] ?? null,
            'index' => $data['maxIndex'],
            'category_id' => $data['category_id'],
            'is_panorama' => $data['is_panorama'],
            'currency' => array_key_exists('currency', $data) ? $data['currency'] : $item->currency,
        ]);
        foreach (['en', 'ar'] as $lang) {
            $category = ItemTranslation::where('locale', $lang)->whereItemId($data['id'])->update([
                'name' => $data['name_' . $lang],
                'description' => $data['description_' . $lang] ?? null,
            ]);
        }

        $this->uploadSingleImage($item, 'image', 'item');
        $this->uploadSingleImage($item, 'icon', 'item_icon');
        $sizesData = $data['sizes'] ?? [];
        foreach ($sizesData as $index => &$size) { // Note the '&' which modifies the array directly
            if (isset($sizeImages[$index]['image'])) {
                $size['image'] = $sizeImages[$index]['image'];
            }
        }
        $this->sizeService->updateMany($item, $sizesData);
        // $this->sizeService->updateMany($item, $data['sizes'] ?? []);
        $this->toppingService->updateMany($item, $data['toppings'] ?? []);
        $this->componentService->updateMany($item, $data['components'] ?? []);
        $this->nutritionFactService->update($item, $data['nutrition'] ?? []);

        return $item;
    }
    // to update index item
    public function updateIndex($restaurant_id, $data)
    {
        $index = Item::whereId($data['id'])->whereRestaurantId($restaurant_id)->whereCategoryId($data['category_id'])->update([
            'index' => $data['index'],
        ]);
        return $index;
    }

    // to show a item
    public function show($id, $restaurant_id)
    {
        $item = Item::with('restaurant')->whereRestaurantId($restaurant_id)->findOrFail($id);
        return $item;
    }

    // to show a item
    public function showItem($data, $restaurant_id)
    {
        $item = Item::with('restaurant')->whereRestaurantId($restaurant_id)->whereCategoryId($data['category_id'])->findOrFail($data['id']);
        return $item;
    }

    public function showAdmin(string $id)
    {
        $category = Admin::with('restaurant')->whereId($id)->get();
        return $category;
    }

    // to delete a item
    public function destroy($id, $restaurant)
    {
        $item = Item::findOrFail($id);
        Item::whereCategoryId($item->category_id)->orderBy('index')->where('index', '>', $item->index)->decrement('index');

        return Item::whereRestaurantId($restaurant)->whereId($id)->forceDelete();
    }

    public function activeOrDesactive($data, $restaurant)
    {
        if ($data['is_active'] == 1) {
            $item = Item::whereRestaurantId($restaurant)->whereId($data['id'])->update([
                'is_active' => 0,
            ]);
        } else {
            $item = Item::whereRestaurantId($restaurant)->whereId($data['id'])->update([
                'is_active' => 1,
            ]);
        }
        return $item;
    }

    public function search($data, $num)
    {
        $search = $data['search'];
        $item = Item::whereCategoryId($data['category_id'])->whereTranslationLike('name', "%$search%")->orderBy('index')->paginate($num);
        return $item;
    }
}
