<?php

namespace App\Services;

use App\Models\Package;
use App\Models\PackageRestaurant;
use App\Models\PackageTranslation;
use App\Models\Restaurant;
use Illuminate\Support\Carbon;

class PackageService
{
    // to show paginate package active
    public function paginate($num)
    {
        $packages = Package::latest()->paginate($num);
        return $packages;
    }

    // to create package
    public function create($data)
    {
        $lan = [
            'en' => [
                'title' => $data['title_en'],
            ],
            'ar' => [
                'title' => $data['title_ar'],
            ],
        ];
        $arr = array_merge($data,$lan);
        $package = Package::create($arr);
        return $package;
    }

    // to update package
    public function update($data)
    {

        foreach (['en','ar'] as $lang)
        {
            PackageTranslation::where('locale',$lang)->wherePackageId($data['id'])->update([
                'title' => $data['title_'.$lang],
            ]);
        }
        $package = Package::whereId($data['id'])->update([
            'price' => $data['price'],
            'value' => $data['value'],
        ]);
        return $package;
    }

    // to show a package
    public function show($data)
    {
        $package = Package::whereId($data['id'])->first();
        return $package;
    }

    // to delete a package
    public function destroy($id)
    {
        return Package::whereId($id)->delete();
    }

    public function activeOrDesactive($data)
    {
        if($data['is_active'] == 1)
        {
            $package = Package::whereId($data['id'])->update([
                'is_active' => 0,
            ]);
        }
        else
        {
             $package = Package::whereId($data['id'])->update([
                'is_active' => 1,
            ]);
        }
        return $package;
    }

    public function search($where,$num)
    {
        $package = Package::where($where)->latest()->paginate($num);
        return $package;
    }

    // create Subscription
    public function subscription($data)
    {
        $subscription = PackageRestaurant::create($data);
        $package = Package::whereId($data['package_id'])->first();
        $restaurant = Restaurant::whereId($data['restaurant_id'])->first();
        if($restaurant->end_date == null || $restaurant->end_date < Carbon::now()->toDateString())
        {
            $endDate = Carbon::now()->toDateString();
            $newDate = Carbon::parse($endDate)->addDays($package->value);
            $newDate->format('Y-m-d');
            $restaurant = Restaurant::whereId($data['restaurant_id'])->update([
                'end_date' => $newDate,
            ]);
        }
        else
        {
            $newDate = Carbon::parse($restaurant->end_date)->addDays($package->value);
            $newDate->format('Y-m-d');
            $restaurant = Restaurant::whereId($data['restaurant_id'])->update([
                'end_date' => $newDate,
            ]);
        }
        return $subscription;
    }

    // to show a restaurant subscription
    public function showRestaurantSubscription($data)
    {
        $package = Restaurant::with('package')->findOrFail($data['id']);
        return $package;
    }

}
