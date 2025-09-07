<?php

namespace App\Services;

use App\Models\Advertisement;
use App\Models\Restaurant;

class AdvertisementService
{
    // to show all Advertisement active
    public function all($id)
    {
        $advertisements = Advertisement::whereRestaurantId($id)->latest()->get();
        return $advertisements;
    }

    // to show paginate Advertisement active
    public function paginate($id,$num)
    {
        $advertisements = Advertisement::whereRestaurantId($id)->latest()->paginate($num);
        foreach($advertisements as $advertisement)
        {
            if($advertisement->to_date < now())
                $advertisement->forceDelete();

        }
        $advertisements = Advertisement::whereRestaurantId($id)->latest()->paginate($num);

        return $advertisements;
    }

    // to create Advertisement
    public function create($id,$data)
    {
        $data['restaurant_id'] = $id;
        $advertisement = Advertisement::create($data);
        return $advertisement;
    }

    // to update Advertisement
    public function update($id,$data)
    {
        $data['restaurant_id'] = $id;
        $advertisement = Advertisement::whereRestaurantId($id)->whereId($data['id'])->update($data);
        return $advertisement;
    }

    // to show a Advertisement
    public function show($id,$data)
    {
        $advertisement = Advertisement::whereRestaurantId($id)->findOrFail($data['id']);
        return $advertisement;
    }

    // to delete a Advertisement
    public function destroy($id,$restaurant_id)
    {
        return Advertisement::whereRestaurantId($restaurant_id)->whereId($id)->forceDelete();
    }

    public function activeOrDesactive($data,$admin)
    {
        if($data['is_active'] == 1)
        {
            $Advertisement = Advertisement::whereSuperAdminId($admin)->whereId($data['id'])->update([
                'is_active' => 0,
            ]);
        }
        else
        {
             $Advertisement = Advertisement::whereSuperAdminId($admin)->whereId($data['id'])->update([
                'is_active' => 1,
            ]);
        }
        return $Advertisement;
    }

    public function search($data,$num)
    {
        $Advertisement=Advertisement::whereTranslationLike('name',"%$data%")->latest()->paginate($num);
        return $Advertisement;
    }
}
