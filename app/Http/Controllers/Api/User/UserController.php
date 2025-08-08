<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Address\AddRequest;
use App\Http\Requests\Address\IdRequest;
use App\Http\Requests\Address\VerifyAddressOrCreateOneRequest;
use App\Http\Requests\Coupon\CheckCouponRequest;
use App\Http\Requests\User\ChangePasswordRequest;
use App\Http\Requests\User\UpdateInfoRequest;
use App\Http\Resources\DeliveryResource;
use App\Models\Address;
use App\Models\Restaurant;
use App\Models\Coupon;
use App\Models\User;
use App\Services\UserTakeoutService;
use GuzzleHttp\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Throwable;

class UserController extends Controller
{
    public function __construct(private UserTakeoutService $userService) {}

    public function getAddress(): JsonResponse
    {
        try {
            $user = auth()->user();

            // 1. Call the single, clean service method to perform the entire process.
            $remainingAddresses = $this->userService->getAndPruneAddresses($user);

            // 2. Return the result in the success response.
            return $this->successResponse(
                $remainingAddresses,
                trans('locale.successfully'),
                200
            );
        } catch (\Throwable $th) {
            report($th);
            return $this->messageErrorResponse('An error occurred while fetching addresses.');
        }
    }
    public function notAvailable()
    {
        $user = auth()->user();
        $user->is_active = $user->is_active == 1 ? 0 : 1;
        $user->save();
        return $this->messageSuccessResponse(trans('locale.successfully'), 200);
    }

    public function deleteAddress(IdRequest $request)
    {
        try {
            $data_val = $request->validated();
            $address = Address::where('id', $data_val['id'])->delete();
            return $this->messageSuccessResponse(trans('locale.deleted'), 200);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    public function locationTracking(AddRequest $request)
    {
        try {
            $data_val = $request->validated();
            $user = auth()->user();
            $data_val['user_id'] = $user->id;
            if ($user->role == 1 || $user->role == 0) {
                $user->longitude = $data_val['longitude'];
                $user->latitude = $data_val['latitude'];
                $user->save();
                return $this->messageSuccessResponse(trans('locale.updated'), 200);
            } else
                return $this->messageErrorResponse(trans('locale.youCantDoThisOperation'), 400);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        $user = auth()->user();
        $new_password = Hash::make($request->new_password);
        $user->update([
            'password' => $new_password,
        ]);
        return $this->messageSuccessResponse(trans('locale.updated'), 200);
    }

    public function update(UpdateInfoRequest $request)
    {
        try {
            $admin = auth()->user();
            $data_val = $request->validated();
            $user = User::where('restaurant_id', $admin->restaurant_id)->whereId($admin->id)->update($data_val);
            if ($user == 0) {
                return $this->messageErrorResponse(trans('locale.invalidItem'), 403);
            }
            $showuser = $this->userService->show($admin->id);
            $data = DeliveryResource::make($showuser);
            return $this->successResponse($data, trans('locale.updated'), 200);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    public function addAddress(VerifyAddressOrCreateOneRequest $request)
    {
        try {
            $data_val = $request->validated();
            $user = auth()->user();
            $data_val['user_id'] = $user->id;
            $deliveryPrice = 0;
            if ($user->role == 0) {
                if ($request->has('isDelivery') && $data_val['isDelivery'] == false) {
                    $deliveryPrice = 0;
                } else {
                    $restaurant = Restaurant::whereId($user->restaurant_id)->first();

                    if ($request->has('friend_address') && $request->friend_address != null) {
                        // $address = Address::whereId($request->address)->first();
                        $coordinates = $this->getCoordinates($request->friend_address);
                        if ($coordinates) {
                            $latitude = $coordinates['latitude'];
                            $longitude = $coordinates['longitude'];
                        }

                        $address = Address::create([
                            'city' =>  $request->city ?? null,
                            'region' => $request->region ?? null,
                            'user_id' => $user->id,
                            'latitude' => $latitude,
                            'longitude' => $longitude,
                            'description' => $request->description
                        ]);
                        if ($coordinates) {
                            $distance = $this->calculateDistance((float)$restaurant->latitude, (float)$restaurant->longitude, $address->latitude, $address->longitude);
                            $deliveryPrice = $distance * $restaurant->price_km;
                        }
                    } elseif ($request->has('address') && $request->address != null) {
                        $address = Address::whereId($request->address)->first();
                        // $address = Address::all();
                        // return $address;
                        $coordinates = $this->getCoordinates($address->region);
                        if ($coordinates) {
                            $latitude = $coordinates['latitude'];
                            $longitude = $coordinates['longitude'];
                        }

                        $address->update([
                            'region' => $address->region ?? null,
                            'latitude' => $latitude ?? null,
                            'longitude' => $longitude ?? null,
                        ]);

                        if ($coordinates) {
                            $distance = $this->calculateDistance((float)$restaurant->latitude, (float)$restaurant->longitude, $address->latitude, $address->longitude);
                            $deliveryPrice = $distance * $restaurant->price_km;
                        }
                    } else {
                        $client = new Client();

                        $headers = [
                            'User-Agent' => 'Menu/1.0 (your.email@example.com)'
                        ];
                        $response = $client->get('https://nominatim.openstreetmap.org/reverse', [
                            'headers' => $headers,
                            'query' => [
                                'lat' => $request->latitude,
                                'lon' => $request->longitude,
                                'format' => 'json',
                                'addressdetails' => 1,
                            ]
                        ]);

                        $data = json_decode($response->getBody(), true);
                        if (isset($data['address'])) {
                            $city = $data['address']['city'] ?? null;
                            $region = $data['address']['state'] ?? null;
                            $street = $data['address']['road'] ?? null;
                            $neighborhood = $data['address']['suburb'] ?? null;

                            $addressParts = [$region, $city, $street, $neighborhood];

                            $addressParts = array_filter($addressParts, fn($value) => !is_null($value));

                            $r = implode(' - ', $addressParts);
                            $address = Address::create([
                                // 'city' => $region ?? null,
                                // 'region,' => $r ?? null,
                                'city' => $request->city,
                                'region' => $request->region,
                                'url' => $data['url'] ?? null,
                                'user_id' => $user->id,
                                'latitude' => $request->latitude ?? null,
                                'longitude' => $request->longitude ?? null,
                                'description' => $request->description
                            ]);
                        } else
                            return response()->json(['error' => 'لا يمكن العثور على الموقع'], 404);

                        $distance = $this->calculateDistance((float)$restaurant->latitude, (float)$restaurant->longitude, $request->latitude, $request->longitude);
                        $deliveryPrice = $distance * $restaurant->price_km;
                    }
                }

                $data = [
                    'delivery_price' => round($deliveryPrice),
                ];
                return $this->successResponse($data, trans('locale.created'), 200);
            } else
                return $this->messageErrorResponse(trans('locale.youCantDoThisOperation'), 400);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    public function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        // تحويل الدرجات إلى راديان
        $lat1 = deg2rad($lat1);
        $lon1 = deg2rad($lon1);
        $lat2 = deg2rad($lat2);
        $lon2 = deg2rad($lon2);

        // الفرق بين خطي العرض والطول
        $deltaLat = $lat2 - $lat1;
        $deltaLon = $lon2 - $lon1;

        // معادلة Haversine
        $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
            cos($lat1) * cos($lat2) * sin($deltaLon / 2) * sin($deltaLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        // نصف قطر الأرض (تقريبًا 6371 كم)
        $radius = 6371;

        // المسافة بالكم
        $distance = $radius * $c;

        return $distance;
    }

    public function checkCoupon(CheckCouponRequest $request)
    {
        $user = auth()->user();
        $coupon = Coupon::whereCode($request->code)->first();
        if ($coupon) {
            $percent = $request->total * $coupon->percent / 100;
        }
        $data = [
            'percent' => $coupon->percent,
            'total' => $request->total - $percent,
        ];
        return $this->successResponse($data, trans('locale.successfully'), 200);
    }

    public function getCoordinates($address)
    {
        $address = urlencode($address);
        $url = "https://nominatim.openstreetmap.org/search?format=json&q={$address}&countrycodes=SY";
        $response = Http::withHeaders([
            'User-Agent' => 'Menu/1.0 (your-email@example.com)'
        ])->get($url);

        if ($response->successful()) {
            $data = $response->json();

            if (isset($data[0])) {
                $latitude = $data[0]['lat'];
                $longitude = $data[0]['lon'];

                return [
                    'latitude' => $latitude,
                    'longitude' => $longitude
                ];
            }
        }
        return null;
    }
}
