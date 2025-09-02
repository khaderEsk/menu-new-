<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Rate\AddRequest;
use App\Http\Requests\Rate\ShowRequest;
use App\Http\Requests\Rate\UpdateRequest;
use App\Http\Resources\RateResource;
use App\Models\Admin;
use App\Models\Notification;
use App\Models\Rate;
use App\Models\Restaurant;
use App\Notifications\RateNotification;
use App\Services\FirebaseService;
use App\Services\RateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class RateController extends Controller
{
    public function __construct(private RateService $rateService, private FirebaseService $firebaseService) {}

    // Show All rates For Admin
    public function showAll(ShowRequest $request)
    {
        try {
            $id = auth()->user()->id;
            $query = Rate::query();
            $query->where('customer_id', $id);

            if ($request->has('gender')) {
                $query->whereHas('customer', function ($q) use ($request) {
                    $q->where('gender', $request->gender);
                });
            }

            if ($request->has('type')) {
                if ($request->type === 'person') {
                    $query->whereHas('customer', function ($q) use ($request) {
                        $q->where('name', '!=', null);
                    });
                } else {
                    $query->whereHas('customer', function ($q) use ($request) {
                        $q->where('name', '=', null);
                    });
                }
            }

            if ($request->has('from_age')) {
                $query->whereHas('customer', function ($q) use ($request) {
                    $q->where('birthday', '>=', $request->from_age)->where('birthday', '<=', $request->to_age);
                });
            }

            if ($request->has('from_date') || $request->has('to_date')) {
                if ($request->has('from_date') && $request->has('to_date')) {
                    $query->whereHas('customer', function ($q) use ($request) {
                        $q->where('created_at', '>=', $request->from_date)->where('created_at', '<=', $request->to_date);
                    });
                } else if ($request->has('from_date')) {
                    $query->whereHas('customer', function ($q) use ($request) {
                        $q->where('created_at', '>=', $request->from_date);
                    });
                } else if ($request->has('to_date')) {
                    $query->whereHas('customer', function ($q) use ($request) {
                        $q->where('created_at', '<=', $request->to_date);
                    });
                }
            }

            if ($request->has('rate')) {
                $query->where('rate', $request->rate);
            }

            $rates = $query->latest()->paginate($request->input('per_page', 25));

            // return response()->json($reviews);
            $data = RateResource::collection($rates);
            return $this->paginateSuccessResponse($data, trans('locale.ratesFound'), 200);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Add rate
    public function create(AddRequest $request)
    {
        try {
            $id = auth()->user()->id;
            $customer = auth()->user();
            $data = $request->validated();
            $data['restaurant_id'] = $customer->restaurant_id;
            if (is_null($request->rate)) {
                $a = [];
                $sum = 0;
                if ($request->has('service') && $request->service != 0) {
                    $a = \array_merge($a, ['service' => $data['service']]);
                    $sum += $request->service;
                }
                if ($request->has('arakel') && $request->arakel != 0) {
                    $a = \array_merge($a, ['arakel' => $data['arakel']]);
                    $sum += $request->arakel;
                }
                if ($request->has('foods') && $request->foods != 0) {
                    $a = \array_merge($a, ['foods' => $data['foods']]);
                    $sum += $request->foods;
                }
                if ($request->has('drinks') && $request->drinks != 0) {
                    $a = \array_merge($a, ['drinks' => $data['drinks']]);
                    $sum += $request->drinks;
                }
                if ($request->has('sweets') && $request->sweets != 0) {
                    $a = \array_merge($a, ['sweets' => $data['sweets']]);
                    $sum += $request->sweets;
                }
                if ($request->has('games_room') && $request->games_room != 0) {
                    $a = \array_merge($a, ['games_room' => $data['games_room']]);
                    $sum += $request->games_room;
                }
                $num = count($a);
                $avg = $sum / $num;
                $data['rate'] = round($avg, 0);
            }
            // $admin = Admin::whereRestaurantId($data['restaurant_id'])->first();
            // Check Type Of Rate
            // -------------------------------------------------------------------------
            $restaurant = Restaurant::whereId($customer->restaurant_id)->first();
            $admin = Admin::whereRestaurantId($customer->restaurant_id)->get();
            if ($restaurant->rate_format->value == 1) {
                if ($data['service'] == 1 || $data['arakel'] == 1 || $data['foods'] == 1 || $data['drinks'] == 1 || $data['sweets'] == 1 || $data['games_room'] == 1) {
                    // If Rate Bad And Rate Owner known => Send Notification To Admin Application
                    $bodyFire = 'Bad Rate : name : ' . $data['name'] . ' , Phone : ' . $data['phone']
                        . ' , service : ' . $data['service'] . ' , arakel : ' . $data['arakel'] . ' , foods : ' . $data['foods'] . ' , drinks : ' . $data['drinks'] . ' , sweets : ' . $data['sweets'] . ' , games_room : ' . $data['games_room'] .
                        ' , Gender : ' . $data['gender'] . ' , Birthday : ' . $data['birthday'];
                    for ($i = 0; $i < count($admin); $i++) {
                        $firstElement = $admin->get($i);
                        if ($firstElement) {
                            if ($firstElement->fcm_token && $data['name'] != '') {
                                // $this->SendNotification($firstElement->fcm_token, $data);
                                $this->firebaseService->sendNotification($firstElement->fcm_token, 'Bad Rate', $bodyFire, []);
                            }
                        }
                    }
                    $customer->notify(new RateNotification(
                        title: 'Bad Rate',
                        body: 'now Rate',
                        restaurant_id: $data['restaurant_id'],
                        phone: $data['phone'],
                        rate: 'bad',
                        note: $data['note']
                    ));

                    // Notification::create([
                    //     'restaurant_id' => $data['restaurant_id'],
                    //     'title' => 'Bad Rate',
                    //     'body' => $body = 'Bad Rate : name : ' . $data['name'] . ' , Phone : ' . $data['phone']
                    //         . ' , service : ' . $data['service'] . ' , arakel : ' . $data['arakel'] . ' , foods : ' . $data['foods'] . ' , drinks : ' . $data['drinks'] . ' , sweets : ' . $data['sweets'] . ' , games_room : ' . $data['games_room'] .
                    //         ' , Gender : ' . $data['gender'] . ' , Birthday : ' . $data['birthday'],
                    //     'phone' => $data['phone'],
                    // ]);
                    $rate = 'bad';
                }
            }
            // Check Type Of Rate

            if ($restaurant->rate_format->value == 0) {

                if ($data['rate'] == 1) {
                    // If Rate Bad And Rate Owner known => Send Notification To Admin Application
                    $admin = Admin::whereRestaurantId($customer->restaurant_id)->get();
                    for ($i = 0; $i < count($admin); $i++) {
                        $firstElement = $admin->get($i);
                        if ($firstElement) {
                            if ($firstElement->fcm_token && $data['name'] != '') {
                                // $this->SendNotification($firstElement->fcm_token, $data);
                                $bodyFire = 'Bad Rate : name : ' . $data['name'] . ' , Phone : ' . $data['phone'] . ' , Gender : ' . $data['gender'] . ' , Birthday : ' . $data['birthday'];
                                $this->firebaseService->sendNotification($firstElement->fcm_token, 'Bad Rate', $bodyFire, []);

                                $data2 = [
                                    // ' Rate' => 'Bad',
                                    'Name' => $data['name'],
                                    // 'Phone' => $data['phone'],
                                    'Rate' => $data['rate'],
                                    'Gender' => $data['gender'],
                                    'Birthday' => $data['birthday'],
                                ];
                                $bodyJson = json_encode($data2);
                                Notification::create([
                                    'restaurant_id' => $firstElement->restaurant_id,
                                    'title' => 'Bad Rate',
                                    'body' => $bodyJson,
                                    //      $body = 'Bad Rate : name : ' . $data['name'] . ' , Phone : ' . $data['phone'] . ' , Rate : '
                                    // . $data['rate'] . ' , Gender : ' . $data['gender'] . ' , Birthday : ' . $data['birthday'],
                                    'phone' => $data['phone'],
                                ]);
                            }
                        }
                    }

                    $rate = 'bad';
                } elseif ($data['rate'] == 2) {
                    $rate = 'good';
                } else {
                    $rate = 'perfect';
                }
            }

            $rate = $this->rateService->create($id, $data);

            return $rate;
            // return $this->successResponse($data, trans('locale.created'), 200);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // private function SendNotification($fcm_token, $arr)
    // {
    //     // تحضير رمز FCM
    //     $firebaseToken = [];
    //     $firebaseToken[0] = $fcm_token;

    //     // تحضير نص الإشعار
    //     $body = 'Bad Rate : name : ' . $arr['name'] . ' , Phone : ' . $arr['phone'] . ' , Rate : '
    //         . $arr['rate'] . ' , Gender : ' . $arr['gender'] . ' , Birthday : ' . $arr['birthday'];

    //     // مفتاح API الخاص بخادم Firebase
    //     $SERVER_API_KEY = config('services.firebase.server_key');

    //     // إعداد بيانات الإشعار
    //     $data = [
    //         "registration_ids" => $firebaseToken,
    //         "notification" => [
    //             "title" => 'Bad Rate',
    //             "body" => $body,
    //             "content_available" => true,
    //             "priority" => "high",
    //         ]
    //     ];
    //     $dataString = json_encode($data);

    //     // إعداد رؤوس الطلب
    //     $headers = [
    //         'Authorization: key=' . $SERVER_API_KEY,
    //         'Content-Type: application/json',
    //     ];

    //     // تهيئة وتنفيذ طلب CURL
    //     $ch = curl_init();
    //     curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
    //     curl_setopt($ch, CURLOPT_POST, true);
    //     curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    //     curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    //     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    //     curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);

    //     $response = curl_exec($ch);
    //     $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    //     curl_close($ch);



    //     // التعامل مع الاستجابة
    //     if ($httpCode == 200) {
    //         $responseData = json_decode($response, true);
    //         if ($responseData['success'] > 0) {
    //             // تم إرسال الإشعار بنجاح
    //             return true;
    //         } else {
    //             // فشل في إرسال الإشعار
    //             Log::error('Failed to send notification', ['response' => $responseData]);
    //             return false;
    //         }
    //     } else {
    //         // فشل في الاتصال بخادم FCM
    //         Log::error('FCM request failed', ['http_code' => $httpCode, 'response' => $response]);
    //         return false;
    //     }
    // }

}
