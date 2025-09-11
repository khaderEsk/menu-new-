<?php

namespace App\Services;

use App\Models\Service;
use App\Models\ServiceTranslation;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ServiceService
{
    // to show paginate service active
    public function paginate($id,$num)
    {
        $services = Service::whereRestaurantId($id)->latest()->paginate($num);
        return $services;
    }

    // to create service
    public function create($id,$data)
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
            'price' => $data['price'],
        ];
        $service = Service::create($arr);
        return $service;
    }

    // to update service
    public function update($id,$data)
    {


        $service = Service::whereId($data['id'])->update([
            'price' => $data['price'],
        ]);
        foreach (['en','ar'] as $lang)
        {
            $category = ServiceTranslation::where('locale',$lang)->whereServiceId($data['id'])->update([
                'name' => $data['name_'.$lang],
            ]);
        }
        return $service;
    }

    // to show a service
    public function show($id,$data)
    {
        $service = Service::whereRestaurantId($id)->findOrFail($data['id']);
        return $service;
    }

    // to delete a service
    public function destroy($id,$restaurant_id)
    {
        $service = Service::whereRestaurantId($restaurant_id)->whereId($id)->first();
        if ($service->qr_code) {
            Storage::delete($service->qr_code);
        }
        return $service->forceDelete();
    }

    public function search($data,$num)
    {
        $service=Service::whereTranslationLike('name',"%$data%")->latest()->paginate($num);
        return $service;
    }
}
