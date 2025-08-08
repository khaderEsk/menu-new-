<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\Category;
use App\Models\CategoryTranslation;

class CategoryService
{
    // to show all Category active
    public function all($restaurant_id)
    {
        $categories = Category::whereRestaurantId($restaurant_id)->whereNull('category_id')->orderBy('index')->get();
        return $categories;
    }

    // to show paginate City active
    public function paginate($restaurant_id,$num)
    {
        $categories = Category::whereRestaurantId($restaurant_id)->whereNull('category_id')->orderBy('index')->paginate($num);
        return $categories;
    }

    public function subCategory($restaurant_id,$id,$num)
    {
        $categories = Category::whereRestaurantId($restaurant_id)->whereCategoryId($id)->orderBy('index')->paginate($num);
        return $categories;
    }

    public function searchsubCategory($restaurant_id,$id,$data,$num)
    {
        $category=Category::whereRestaurantId($restaurant_id)->whereCategoryId($id)->whereTranslationLike('name',"%$data%")->orderBy('index')->paginate($num);
        return $category;
    }

    // to create Category
    public function create($id,$data,$maxIndex,$category_id)
    {

        $arr = [
            'en' => [
                'name' => $data['name_en'],
                'restaurant_id' => $id,
            ],
            'ar' => [
            'name' => $data['name_ar'],
            'restaurant_id' => $id,
            ],
            'restaurant_id' => $id,
            'index' => $maxIndex + 1,
            'category_id' => $category_id
        ];

        $category = Category::create($arr);
        return $category;
    }

    // to update Category
    public function update($restaurant_id,$data)
    {
        $category_id = Category::whereId($data['id'])->whereRestaurantId($restaurant_id->restaurant_id)->first();
        if($category_id->id !=  $data['id'])
            return response()->json(['status' => false,'message' => "you can't update"],200);

        foreach (['en','ar'] as $lang)
        {
            $category = CategoryTranslation::where('locale',$lang)->whereCategoryId($data['id'])->update([
                'name' => $data['name_'.$lang],
            ]);
        }
        return $category_id;
    }

    public function updateSub($restaurant_id,$data,$maxIndex)
    {
        $category_id = Category::whereId($data['id'])->whereRestaurantId($restaurant_id->restaurant_id)->first();
        if($category_id->id !=  $data['id'])
            return response()->json(['status' => false,'message' => "you can't update"],200);

        foreach (['en','ar'] as $lang)
        {
            $category = CategoryTranslation::where('locale',$lang)->whereCategoryId($data['id'])->update([
                'name' => $data['name_'.$lang],
            ]);
        }

        Category::whereId($data['id'])->whereRestaurantId($restaurant_id->restaurant_id)->update([
            'index' => $maxIndex + 1,
            'category_id' => $data['category_id'],
        ]);
        return $category_id;
    }

    // to update index Category
    public function updateIndex($restaurant_id,$data)
    {
        $index = Category::whereId($data['id'])->whereRestaurantId($restaurant_id)->update([
            'index' => $data['index'],
        ]);
        return $index;
    }

    // to show a Category
    public function show($id,$restaurant_id)
    {
        $category = Category::whereRestaurantId($restaurant_id)->findOrFail($id);
        return $category;
    }

    // to show a sub Category
    public function showSubCategory($data,$restaurant_id)
    {
        $category = Category::whereCategoryId($data['category_id'])->whereRestaurantId($restaurant_id)->findOrFail($data['id']);
        return $category;
    }

    public function showAdmin(string $id)
    {
        $category = Admin::with('restaurant')->whereId($id)->get();
        return $category;
    }


    // to delete a Category
    public function destroy($id,$restaurant_id)
    {
        $show = Category::whereId($id)->whereRestaurantId($restaurant_id)->first();

        $category = count(Category::whereCategoryId($id)->whereRestaurantId($restaurant_id)->get());
        if($show->is_active == 1 && $category)
        return -10;

        Category::whereRestaurantId($restaurant_id)->orderBy('index')->where('index', '>', $show->index)->decrement('index');
        $done = Category::whereRestaurantId($restaurant_id)->whereId($id)->forceDelete();
        Category::whereCategoryId($id)->whereRestaurantId($restaurant_id)->forceDelete();
        return $done;
    }

    public function activeOrDesactive($data)
    {
        if($data['is_active'] == 1)
        {
            // Category::whereRestaurantId($data['restaurant_id'])->whereNull('deleted_at')->orderBy('index')->where('index', '>', $data['index'])->decrement('index');
            $category = Category::whereRestaurantId($data['restaurant_id'])->whereId($data['id'])->update([
                'is_active' => 0,
            ]);
        }
        else
        {
            // if($data['category_id'] != null)
            //     $maxIndex = Category::where('category_id',$data['category_id'])->whereNull('deleted_at')->where('restaurant_id', $data['restaurant_id'])->max('index');
            // else
            //     $maxIndex = Category::where('restaurant_id', $data['restaurant_id'])->whereNull('deleted_at')->max('index');

             $category = Category::whereRestaurantId($data['restaurant_id'])->whereId($data['id'])->update([
                'is_active' => 1,
                // 'index' => $maxIndex + 1,
            ]);
        }
        return $category;
    }

    public function search($restaurant_id,$data,$num)
    {
        $category=Category::whereRestaurantId($restaurant_id)->whereNull('category_id')->whereTranslationLike('name',"%$data%")->orderBy('index')->paginate($num);
        return $category;
    }

}
