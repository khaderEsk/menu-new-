<?php

namespace App\Services;

use App\Models\User;
use App\Models\Order;
use App\Models\Coupon;
use App\Models\Invoice;
use App\Models\UserCoupon;
use App\Traits\ResponseTrait;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\UnauthorizedException;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class CouponService
{
    use ResponseTrait;

    public function paginate($id, $num)
    {
        $coupons = Coupon::whereRestaurantId($id)->latest()->paginate($num);
        return $coupons;
    }

    public function create($id, $data)
    {
        $data['code'] = mt_rand(100000, 999999);
        $data['restaurant_id'] = $id;
        $data['type'] = "منتجات";

        $coupon = Coupon::create($data);

        $redeemUrl = env('APP_URL') . "/redeem-coupon/{$coupon->code}";

        $qrPath = 'public/qrcodes/' . $coupon->code . '.png';
        $qrCode = Builder::create()
            ->writer(new PngWriter())
            ->data($redeemUrl)
            ->size(200)
            ->margin(10)
            ->build();

        Storage::put($qrPath, $qrCode->getString());

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


    public function grantCouponToUser($couponId, $userId)
    {
        $coupon = Coupon::findOrFail($couponId);
        $user = User::findOrFail($userId);

        // التحقق من صلاحية الكوبون
        if (!$coupon->is_active || now()->lt($coupon->from_date) || now()->gt($coupon->to_date)) {
            throw new \Exception("الكوبون غير صالح للاستخدام.");
        }
        // ربط الكوبون بالمستخدم (جدول many-to-many)
        $coupon->users()->syncWithoutDetaching([
            $user->id => [
                'used' => false,
                'granted_at' => now()
            ]
        ]);
        return true;
    }

    public function redeemCouponOnInvoice($invoiceId, $code)
    {
        $coupon = Coupon::where('code', $code)->firstOrFail();


        $invoice = Invoice::findOrFail($invoiceId);

        $userCopon = UserCoupon::query()
            ->where('user_id', $invoice->user_id)
            ->where('coupon_id', $coupon->id)
            ->first();

        if ($userCopon == null) {
            return $this->messageErrorResponse('user dont hase coupon', 200);
        }


        if (!$coupon->is_active || now()->lt($coupon->from_date) || now()->gt($coupon->to_date) || $userCopon->used == true) {
            return response()->json(['message' => 'coupon is invaled'], 400);
        }

        $discountAmount = ($coupon->percent / 100) * $invoice->total;
        $invoice->discount = $discountAmount;
        $invoice->total = $invoice->total - $discountAmount;
        $invoice->coupon_id = $coupon->id;
        $invoice->save();


        $coupon->users()->syncWithoutDetaching([
            $invoice->user_id => [
                'used' => true,
                'used_at' => now()
            ]
        ]);

        return $this->messageSuccessResponse('coupon applyed asuccessfully', 200);
    }
}
