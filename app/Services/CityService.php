<?php

namespace App\Services;

use App\Models\City;
use App\Models\CitySuperAdmin;
use App\Models\Restaurant;
use App\Models\CityTranslation;
use App\Models\SuperAdmin;

class CityService
{
    // to show all City active
    public function all()
    {
        $cities = City::latest()->get();
        return $cities;
    }

    // to show paginate City active
    public function paginate($num)
    {
        $cities = City::latest()->paginate($num);
        return $cities;
    }

    // to create city
    public function create($data)
    {
        $arr = [
            'en' => [
                'name' => $data['name_en'],
            ],
            'ar' => [
            'name' => $data['name_ar'],
            ],
        ];
        $city = City::create($arr);
        return $city;
    }

    // to update  city
    public function update($id,$data)
    {
        foreach (['en','ar'] as $lang)
        {
            $city = CityTranslation::where('locale',$lang)->whereCityId($data['id'])->update([
                'name' => $data['name_'.$lang],
            ]);
        }
    }

    // to show a city
    public function show(string $id)
    {
        $city = City::findOrFail($id);
        return $city;
    }

    // to delete a city
    public function destroy(string $id)
    {
        $cityRestaurant = count(Restaurant::whereCityId($id)->get());
        $citySuperAdmin = count(SuperAdmin::whereCityId($id)->get());
        if($cityRestaurant != 0)
        {
            return -10;
        }
        if($citySuperAdmin != 0)
        {
            return -5;
        }
        return City::whereId($id)->delete();
    }

    public function activeOrDesactive($data)
    {
        if($data['is_active'] == 1)
        {
            $city = City::whereId($data['id'])->update([
                'is_active' => 0,
            ]);
        }
        else
        {
             $city = City::whereId($data['id'])->update([
                'is_active' => 1,
            ]);
        }
        return $city;
    }

    public function search($data,$num)
    {
        $city=City::whereTranslationLike('name',"%$data%")->latest()->paginate($num);
        return $city;
    }
}
