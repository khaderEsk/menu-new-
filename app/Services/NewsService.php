<?php

namespace App\Services;

use App\Models\News;
use App\Models\NewsTranslation;

class NewsService
{
    // to show all News active
    public function all($id)
    {
        $news = News::whereRestaurantId($id)->latest()->get();
        return $news;
    }

    // to show paginate News active
    public function paginate($id,$num)
    {
        $news = News::whereRestaurantId($id)->latest()->paginate($num);
        return $news;
    }

    // to create News
    public function create($id,$data)
    {
        $data['restaurant_id'] = $id;
        $lan = [
            'en' => [
                'name' => $data['name_en'],
                'description' => $data['description_en'],
            ],
            'ar' => [
                'name' => $data['name_ar'],
                'description' => $data['description_ar'],
            ],
        ];
        $arr = array_merge($data,$lan);
        $news = News::create($arr);
        return $news;
    }

    // to update News
    public function update($id,$data)
    {

        foreach (['en','ar'] as $lang)
        {
            NewsTranslation::where('locale',$lang)->whereNewsId($data['id'])->update([
                'name' => $data['name_'.$lang],
                'description' => $data['description_'.$lang],
            ]);
        }

        $news = News::whereRestaurantId($id)->whereId($data['id'])->get();
        return $news;
    }

    // to show a News
    public function show($id,$data)
    {
        $news = News::whereRestaurantId($id)->findOrFail($data['id']);
        return $news;
    }

    // to delete a News
    public function destroy($id,$restaurant_id)
    {
        return News::whereRestaurantId($restaurant_id)->whereId($id)->forceDelete();
    }

    public function activeOrDesactive($data,$admin)
    {
        if($data['is_active'] == 1)
        {
            $news = News::whereSuperAdminId($admin)->whereId($data['id'])->update([
                'is_active' => 0,
            ]);
        }
        else
        {
             $news = News::whereSuperAdminId($admin)->whereId($data['id'])->update([
                'is_active' => 1,
            ]);
        }
        return $news;
    }

    public function search($data,$num)
    {
        $news=News::whereTranslationLike('name',"%$data%")->latest()->paginate($num);
        return $news;
    }
}
