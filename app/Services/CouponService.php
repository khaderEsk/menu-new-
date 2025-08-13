<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Coupon;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Storage;
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
        // إنشاء كود عشوائي للكوبون
        $data['code'] = mt_rand(100000, 999999);
        $data['restaurant_id'] = $id;
        $data['type'] = "منتجات";

        // حفظ الكوبون في قاعدة البيانات
        $coupon = Coupon::create($data);

        // توليد توكن عشوائي لعامل التوصيل لضمان الأمان
        $driverToken = bin2hex(random_bytes(16));

        // تخزين التوكن في الكوبون (يجب إضافة عمود driver_token في الجدول)
        $coupon->driver_token = $driverToken;
        $coupon->save();

        // تحديد رابط Redeem الذي سيتم تضمينه في QR Code
        $redeemUrl = env('APP_URL') . "/redeem-coupon/{$coupon->code}?token={$driverToken}";

        // توليد QR Code وحفظه في التخزين
        $qrPath = 'public/qrcodes/' . $coupon->code . '.png';
        $qrCode = Builder::create()
            ->writer(new PngWriter())
            ->data($redeemUrl)
            ->size(200)
            ->margin(10)
            ->build();

        Storage::put($qrPath, $qrCode->getString());

        // تحديث مسار الصورة في قاعدة البيانات
        $coupon->qr_path = $qrPath;
        $coupon->save();

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


    public function redeemCoupon($data)
    {
        $couponCode = array_key_exists('code', $data);
        $orderId = array_key_exists('order_id', $data); // الفاتورة التي سيتم تطبيق الحسم عليها

        // البحث عن الكوبون
        $coupon = Coupon::where('code', $couponCode)->first();

        if (!$coupon) {
            return response()->json(['message' => 'الكوبون غير صالح'], 404);
        }

        if ($coupon->used) {
            return response()->json(['message' => 'تم استخدام الكوبون مسبقًا'], 400);
        }

        // البحث عن الطلب
        $order = Order::find($orderId);
        if (!$order) {
            return response()->json(['message' => 'الفاتورة غير موجودة'], 404);
        }

        $order->total_price -= ($order->total_price * $coupon->discount / 100);
        $order->save();

        // تحديث حالة الكوبون ليصبح مستخدم
        $coupon->used = true;
        $coupon->used_at = now();
        $coupon->save();

        return response()->json([
            'message' => 'تم تطبيق الكوبون بنجاح',
            'new_total' => $order->total_price
        ]);
    }
}
