<?php

namespace App\Services;

use App\Models\Coupon;
use SimpleSoftwareIO\QrCode\Facades\QrCode;


class CouponService
{
    public function paginate($id, $num)
    {
        $coupons = Coupon::whereRestaurantId($id)->latest()->paginate($num);
        return $coupons;
    }

    public function create($id, $data)
    {
        $data['restaurant_id'] = $id;
        $data['type'] = "منتجات";
        $coupon = Coupon::create($data);

        $qrPath = 'qrcodes/' . $coupon->code . '.png';
        QrCode::format('png')->size(200)->generate($coupon->code, storage_path('app/public/' . $qrPath));

        return $coupon;
    }

    public function update($id, $data)
    {
        $coupon = Coupon::whereRestaurantId($id)->whereId($data['id'])->update($data);
        return $coupon;
    }

    public function show($id, $data)
    {
        $coupon = Coupon::whereRestaurantId($id)->findOrFail($data['id']);
        return $coupon;
    }

    public function destroy($id, $restaurant_id)
    {
        return Coupon::whereRestaurantId($restaurant_id)->whereId($id)->delete();
    }

    public function activeOrDesactive($data)
    {
        $coupon = Coupon::whereId($data['id'])->update([
            'is_active' => $data['is_active'] == 1 ? 0 : 1,
        ]);
        return $coupon;
    }
}
