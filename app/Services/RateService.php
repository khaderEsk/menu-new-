<?php

namespace App\Services;

use App\Models\CitySuperAdmin;
use App\Models\Customer;
use App\Models\Rate;
use App\Models\SuperAdmin;
use Illuminate\Support\Arr;

class RateService
{
    // to show all rate active
    public function all()
    {
        $rates = Rate::latest()->get();
        return $rates;
    }


    // to show all rate of restaurant active
    public function rateRestaurant($restaurant_id)
    {
        $rates = Rate::where(['restaurant_id' => $restaurant_id])->latest()->get();
        return $rates;
    }

    // to show paginate rate active
    public function paginate($num)
    {
        $rates = Rate::paginate($num);
        return $rates;
    }

    // For Loop On 1 Year And get Average For Each Month In This Year
    public function getChart($id)
    {
        $monthes = [];
        for ($i = 1; $i <= 12; $i++) {
            $num = Rate::where(['restaurant_id' => $id])->whereMonth('created_at', '=', $i)->average('rate');
            $monthes[] = (int)($num * 100) / 100;
        }
        return $monthes;
    }

    // to create rate
    public function create($id,$data)
    {
        $data['customer_id'] = $id;
        $arrRate = Arr::only($data, ['rate', 'note','restaurant_id','customer_id','service','arakel','foods','drinks','sweets','games_room']);
        $arrCustomer = Arr::only($data, ['name', 'gender', 'phone', 'birthday']);

        $rate = Rate::create($arrRate);
        $customer = Customer::whereId($id)->update($arrCustomer);

        return $rate;
    }

    // to update  rate
    public function update($id,$data)
    {
        $rate = Rate::whereSuperAdminId($id)->whereId($data['id'])->update($data);
        return $rate;
    }

    // to show a rate
    public function show(string $id)
    {
        $rate = Rate::findOrFail($id);
        return $rate;
    }

    // // to find user
    // public function findAdmin(string $id)
    // {
    //     $superAdmin = SuperAdmin::whereId($id)->get();
    //     $citySuperAdmin = CitySuperAdmin::whereId($id)->get();
    //     if($superAdmin)
    //         return 'superAdmin';
    //     if($citySuperAdmin)
    //         return 'citySuperAdmin';
    // }

    // to delete a rate
    public function destroy(string $id,$admin)
    {
        return Rate::whereSuperAdminId($admin)->whereId($id)->forceDelete();
    }

    public function activeOrDesactive($data,$admin)
    {
        if($data['is_active'] == 1)
        {
            $rate = Rate::whereSuperAdminId($admin)->whereId($data['id'])->update([
                'is_active' => 0,
            ]);
        }
        else
        {
             $rate = Rate::whereSuperAdminId($admin)->whereId($data['id'])->update([
                'is_active' => 1,
            ]);
        }
        return $rate;
    }

    public function search($where,$num)
    {
        $rates = Rate::where($where)->latest()->paginate($num);
        return $rates;
    }
}
