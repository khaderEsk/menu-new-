<?php

namespace App\Http\Controllers\Api\Customer;

use App\Events\NewOrder;
use App\Events\TableUpdatedEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Order\CustomerAdd2Request;
use App\Http\Requests\Order\CustomerAddRequest;
use App\Http\Requests\Order\CustomershowRequest;
use App\Http\Requests\Order\CustomerUpdateRequest;
use App\Http\Requests\Order\IdRequest;
use App\Http\Resources\InvoiceResources;
use App\Http\Resources\InvoiceUserResource;
use App\Http\Resources\OrderResource;
use App\Http\Resources\TableResource;
use App\Models\Address;
use App\Models\Admin;
use App\Models\CategoryTranslation;
use App\Models\Coupon;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\ItemTranslation;
use App\Models\Order;
use App\Models\Table;
use App\Models\Size;
use App\Models\Topping;
use App\Models\Component;
use App\Models\Restaurant;
use App\Services\FirebaseService;
use GuzzleHttp\Client;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use App\Enum\InvoiceStatus;
use Throwable;

class OrderController extends Controller
{
    public function __construct(private OrderService $orderService, private FirebaseService $firebaseService) {}

    // private function calculateInvoice($orders, $restaurant)
    // {
    //     $sum = $orders->sum(fn($order) => $order->price * $order->count);
    //     $consumer_spending = $sum * $restaurant->consumer_spending / 100;
    //     $local_administration = $sum * $restaurant->local_administration / 100;
    //     $reconstruction = $sum * $restaurant->reconstruction / 100;

    //     return [
    //         'price' => round($sum, 0),
    //         'consumer_spending' => round($consumer_spending, 0),
    //         'local_administration' => round($local_administration, 0),
    //         'reconstruction' => round($reconstruction, 0),
    //         'total' => round($sum + $consumer_spending + $local_administration + $reconstruction, 0),
    //     ];
    // }

    // Show All orders For Admin
    public function showAll(CustomershowRequest $request)
    {
        try {
            $data = $request->validated();
            $customer = auth()->user();
            $orders = Order::whereRestaurantId($customer->restaurant_id)
                ->whereNull('invoice_id')->whereTableId($customer->table_id)
                ->latest()->whereDate('created_at', now())
                ->orWhereDate('created_at', Carbon::tomorrow())->get();

            // $orders = Order::whereRestaurantId($customer->restaurant_id)->whereCustomerId($customer->id)->latest()->paginate($request->input('per_page', 25));
            // $orders = Order::whereRestaurantId($customer->restaurant_id)->whereTableId($data['table_id'])->whereCustomerId($customer->id)->latest()->paginate($request->input('per_page', 25));
            if (\count($orders) == 0) {
                $inv = Invoice::whereTableId($customer->table_id)->whereStatus(2)->whereDate('created_at', now())->latest()->first();
                if ($inv) {
                    $orders = Order::where('invoice_id', $inv->id)->latest()->get();

                    $sum = 0;
                    foreach ($orders as $order) {
                        $sum += $order['price'] * $order['count'];
                    }
                    $restaurant = Restaurant::whereId($customer->restaurant_id)->first();
                    $consumer_spending = $sum * $restaurant->consumer_spending / 100;
                    $local_administration = $sum * $restaurant->local_administration / 100;
                    $reconstruction = $sum * $restaurant->reconstruction / 100;
                    $total = $sum + $consumer_spending + $local_administration + $reconstruction;
                    if ($restaurant->is_taxes == 0) {
                        $invoice_res = [
                            'price' => round($sum, 0),
                            'consumer_spending' => 1,
                            'local_administration' => 1,
                            'reconstruction' => 1,
                            'total' => round($sum, 0),
                        ];

                        $inv->update([
                            'price' => round($sum, 0),
                            'consumer_spending' => 1,
                            'local_administration' => 1,
                            'reconstruction' => 1,
                            'total' => round($sum, 0)
                        ]);
                    } elseif ($restaurant->is_taxes == 1) {
                        $invoice_res = [
                            'price' => round($sum, 0),
                            'consumer_spending' => round($consumer_spending, 0),
                            'local_administration' => round($local_administration, 0),
                            'reconstruction' => round($reconstruction, 0),
                            'total' => round($total, 0),
                        ];

                        $inv->update([
                            'price' => round($sum, 0),
                            'consumer_spending' => round($consumer_spending, 0),
                            'local_administration' => round($local_administration, 0),
                            'reconstruction' => round($reconstruction, 0),
                            'total' => round($total, 0)
                        ]);
                    }
                    $order_res = OrderResource::collection($orders);

                    $invoice_res = InvoiceResources::make($inv);
                    $data = [
                        'orders' => $order_res,
                        'invoice' => $invoice_res,
                    ];
                    return response()->json(['status' => true, 'data' => $data, 'message' => trans('locale.foundSuccessfully')], 200);
                }
                return $this->successResponse([], trans('locale.dontHaveOrder'), 200);
            }
            $sum = 0;
            foreach ($orders as $order) {
                $sum += $order['price'] * $order['count'];
            }
            $restaurant = Restaurant::whereId($customer->restaurant_id)->first();
            $consumer_spending = $sum * $restaurant->consumer_spending / 100;
            $local_administration = $sum * $restaurant->local_administration / 100;
            $reconstruction = $sum * $restaurant->reconstruction / 100;
            $total = $sum + $consumer_spending + $local_administration + $reconstruction;
            if ($restaurant->is_taxes == 0) {
                $invoice_res = [
                    'price' => round($sum, 0),
                    'consumer_spending' => 1,
                    'local_administration' => 1,
                    'reconstruction' => 1,
                    'total' => round($sum, 0),
                ];
            } elseif ($restaurant->is_taxes == 1) {
                $invoice_res = [
                    'price' => round($sum, 0),
                    'consumer_spending' => round($consumer_spending, 0),
                    'local_administration' => round($local_administration, 0),
                    'reconstruction' => round($reconstruction, 0),
                    'total' => round($total, 0),
                ];
            }
            $order_res = OrderResource::collection($orders);
            $data = [
                'orders' => $order_res,
                'invoice' => $invoice_res,
            ];
            return response()->json(['status' => true, 'data' => $data, 'message' => trans('locale.ordersFound')], 200);
            // return response()->json(['status' => true,'data' => $data,'total' => $sum,'message' => trans('locale.ordersFound')],200);
            // return $this->successResponse($data,trans('locale.ordersFound'),200);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Add order
    public function create(CustomerAddRequest $request)
    {
        try {
            $customer = auth()->user();
            if ($customer->hasRole('customer')) {
                $customer_id = $customer->id;
                $data = $request->validated();
                $restaurant = Restaurant::whereId($customer->restaurant_id)->first();
                if ($restaurant->accepted_by_waiter == 1)
                    $employee = Admin::role('employee')->whereRestaurantId($customer->restaurant_id)->whereTypeId(5)->get();

                else
                    $employee = Admin::role('employee')->whereRestaurantId($customer->restaurant_id)->whereIn('type_id', [3, 4, 5, 8])->get();

                $inv = Invoice::whereTableId($customer->table_id)->whereIn('status', [1, 2])->whereDate('created_at', now())->orWhereDate('created_at', Carbon::tomorrow())->latest()->first();
                if ($inv) {
                    $waiter = Admin::role('employee')->whereRestaurantId($customer->restaurant_id)->whereTypeId(5)->get();
                    $userTable = Table::whereId($customer->table_id)->first();
                    for ($i = 0; $i < count($waiter); $i++) {
                        $firstElement = $waiter->get($i);
                        if ($firstElement->fcm_token) {
                            try {
                                $title = "waiter";
                                $body = "table number: {$userTable->number_table}";
                                // محاولة إرسال الإشعار
                                $this->firebaseService->sendNotification($firstElement->fcm_token, $title, $body, []);
                            } catch (\Kreait\Firebase\Exception\Messaging\InvalidMessage $e) {
                                // تسجيل الخطأ إذا كان token غير صالح
                                Log::warning("FCM token غير صالح للموظف رقم " . ($i + 1) . ": {$firstElement->fcm_token} - الخطأ: " . $e->getMessage());
                            } catch (\Exception $e) {
                                // تسجيل أي خطأ آخر
                                Log::error("خطأ في إرسال الإشعار للموظف رقم " . ($i + 1) . ": {$firstElement->fcm_token} - الخطأ: " . $e->getMessage());
                            }
                        }
                    }
                    return $this->messageErrorResponse(trans('locale.youCannotAddNewOrder'), 400);
                } else {
                    //$ord = Order::whereTableId($customer->table_id)->where('status', '!=', "done")->whereDate('created_at',now())->latest()->get();
                    $ord = Order::whereTableId($customer->table_id)->whereNull('invoice_id')->whereDate('created_at', now())->latest()->get();
                    if ($ord->isNotEmpty()) {
                        $waiter = Admin::role('employee')->whereRestaurantId($customer->restaurant_id)->whereTypeId(5)->get();
                        $userTable = Table::whereId($customer->table_id)->first();
                        for ($i = 0; $i < count($waiter); $i++) {
                            $firstElement = $waiter->get($i);
                            if ($firstElement->fcm_token) {
                                try {
                                    $title = "waiter";
                                    $body = "table number: {$userTable->number_table}";
                                    $this->firebaseService->sendNotification($firstElement->fcm_token, $title, $body, []);
                                } catch (\Kreait\Firebase\Exception\Messaging\InvalidMessage $e) {
                                    Log::warning("FCM token غير صالح للموظف رقم " . ($i + 1) . ": {$firstElement->fcm_token} - الخطأ: " . $e->getMessage());
                                } catch (\Exception $e) {
                                    Log::error("خطأ في إرسال الإشعار للموظف رقم " . ($i + 1) . ": {$firstElement->fcm_token} - الخطأ: " . $e->getMessage());
                                }
                            }
                        }
                        return $this->messageErrorResponse(trans('locale.youCannotAddNewOrder'), 400);
                    } else {
                        foreach ($data['data'] as $d) {
                            $item = Item::whereId($d['item_id'])->first();
                            $price = $this->calculatePriceProduct($d['item_id'], $d['toppings'], $d['size_id']);
                            $en = ItemTranslation::where('locale', 'en')->whereItemId($d['item_id'])->first();
                            $ar = ItemTranslation::where('locale', 'ar')->whereItemId($d['item_id'])->first();
                            $name_en = $en->name;
                            $name_ar = $ar->name;
                            $en_category = CategoryTranslation::where('locale', 'en')->whereCategoryId($item->category_id)->first();
                            $ar_category = CategoryTranslation::where('locale', 'ar')->whereCategoryId($item->category_id)->first();
                            $type_en = $en_category->name;
                            $type_ar = $ar_category->name;
                            $count = $d['count'];

                            $order2 = Order::create([
                                'item_id' => $item->id,
                                'price' => $price,
                                'count' => $count,
                                'table_id' => $customer->table_id,
                                'restaurant_id' => $customer->restaurant_id,
                                'customer_id' => $customer_id,

                                'en' => [
                                    'name' => $name_en,
                                    'type' => $type_en,
                                ],
                                'ar' => [
                                    'name' => $name_ar,
                                    'type' => $type_ar,
                                ],
                            ]);
                            $this->addDetailsItem($order2, $d['size_id'], $d['components'], $d['toppings']);
                            $title = "new order";
                            $body = "order number: {$order2->id}, table number: {$order2->table->number_table}, name: {$name_en} - {$name_ar}";
                            $data2 = [
                                'order_id' => $order2->id,
                                'restaurant_id' => $order2->restaurant_id,
                                'table_id' => $order2->table_id,
                                'customer_id' => $order2->customer_id,
                                'price' => $order2->price,
                                'count' => $order2->count,
                                'name_en' => $name_en,
                                'name_ar' => $name_ar,
                            ];

                            for ($i = 0; $i < count($employee); $i++) {
                                $firstElement = $employee->get($i);
                                if ($firstElement->fcm_token) {
                                    try {
                                        // محاولة إرسال الإشعار
                                        $this->firebaseService->sendNotification($firstElement->fcm_token, $title, $body, $data2);
                                    } catch (\Kreait\Firebase\Exception\Messaging\InvalidMessage $e) {
                                        // تسجيل الخطأ إذا كان token غير صالح
                                        Log::warning("FCM token غير صالح للموظف رقم " . ($i + 1) . ": {$firstElement->fcm_token} - الخطأ: " . $e->getMessage());
                                    } catch (\Exception $e) {
                                        // تسجيل أي خطأ آخر
                                        Log::error("خطأ في إرسال الإشعار للموظف رقم " . ($i + 1) . ": {$firstElement->fcm_token} - الخطأ: " . $e->getMessage());
                                    }
                                }
                            }
                        }
                        $table = Table::whereId($customer->table_id)->first();
                        $table->new_order = 1;
                        $table->save();
                        // $tables = Table::whereRestaurantId($customer->restaurant_id)->get();
                        // $allTable = TableResource::collection($tables);
                        // event(new TableUpdatedEvent($allTable));
                        $tables = Table::whereRestaurantId($customer->restaurant_id)->paginate($request->input('per_page', 50));
                        $allTable = TableResource::collection($tables);
                        $t = $this->paginateSuccessResponse($allTable, trans('locale.created'), 200);
                        event(new TableUpdatedEvent($t->getData(true)));
                        return $this->messageSuccessResponse(trans('locale.created'), 200);
                    }
                }
            } elseif ($customer->hasRole('takeout')) {
                $customer_id = $customer->id;
                $data = $request->validated();
                if ((!$request->has('longitude') && !$request->has('latitude')) && !$request->has('address') && !$request->has('friend_address'))
                    return $this->messageErrorResponse(trans('locale.pleaseEnterAnAddress'), 400);

                $restaurant = Restaurant::whereId($customer->restaurant_id)->first();
                if ($restaurant) {
                    if ($restaurant->accepted_by_waiter == 1)
                        $employee = Admin::role('employee')->whereRestaurantId($customer->restaurant_id)->whereTypeId(5)->get();

                    else
                        $employee = Admin::role('employee')->whereRestaurantId($customer->restaurant_id)->whereIn('type_id', [3, 4, 5, 8])->get();
                }

                $inv = Invoice::whereUserId($customer->id)->whereStatus(1)->whereDate('created_at', now())->orWhereDate('created_at', Carbon::tomorrow())->latest()->first();
                if ($inv) {
                    foreach ($data['data'] as $d) {
                        $item = Item::whereId($d['item_id'])->first();
                        $price = $this->calculatePriceProduct($d['item_id'], $d['toppings'], $d['size_id']);
                        $en = ItemTranslation::where('locale', 'en')->whereItemId($d['item_id'])->first();
                        $ar = ItemTranslation::where('locale', 'ar')->whereItemId($d['item_id'])->first();
                        $name_en = $en->name;
                        $name_ar = $ar->name;
                        $en_category = CategoryTranslation::where('locale', 'en')->whereCategoryId($item->category_id)->first();
                        $ar_category = CategoryTranslation::where('locale', 'ar')->whereCategoryId($item->category_id)->first();
                        $type_en = $en_category->name;
                        $type_ar = $ar_category->name;
                        $count = $d['count'];

                        $order = Order::create([
                            'item_id' => $item->id,
                            'price' => $price,
                            'count' => $count,
                            'user_id' => $customer->id,
                            'restaurant_id' => $customer->restaurant_id,
                            'invoice_id' => $inv->id,

                            'en' => [
                                'name' => $name_en,
                                'type' => $type_en,
                            ],
                            'ar' => [
                                'name' => $name_ar,
                                'type' => $type_ar,
                            ],
                        ]);
                        $this->addDetailsItem($order, $d['size_id'], $d['components'], $d['toppings']);
                        $title = "new order";
                        $body = "order number: {$order->id}, user id: {$customer->id}, user name: {$customer->name}, name: {$name_en} - {$name_ar}";
                        $data2 = [
                            'order_id' => $order->id,
                            'restaurant_id' => $order->restaurant_id,
                            'user_id' => $customer->id,
                            'price' => $order->price,
                            'count' => $order->count,
                            'name_en' => $name_en,
                            'name_ar' => $name_ar,
                        ];

                        for ($i = 0; $i < count($employee); $i++) {
                            $firstElement = $employee->get($i);
                            if ($firstElement->fcm_token) {
                                try {
                                    // محاولة إرسال الإشعار
                                    $this->firebaseService->sendNotification($firstElement->fcm_token, $title, $body, $data2);
                                } catch (\Kreait\Firebase\Exception\Messaging\InvalidMessage $e) {
                                    // تسجيل الخطأ إذا كان token غير صالح
                                    Log::warning("FCM token غير صالح للموظف رقم " . ($i + 1) . ": {$firstElement->fcm_token} - الخطأ: " . $e->getMessage());
                                } catch (\Exception $e) {
                                    // تسجيل أي خطأ آخر
                                    Log::error("خطأ في إرسال الإشعار للموظف رقم " . ($i + 1) . ": {$firstElement->fcm_token} - الخطأ: " . $e->getMessage());
                                }
                            }
                        }
                    }
                    $orders = Invoice::with('orders')->with('address')->whereUserId($customer->id)->where(function ($query4) {
                        $query4->whereDate('created_at', now()->toDateString())->orWhereDate('created_at', Carbon::yesterday()->toDateString());
                    })->whereIn('status', [0, 1, 2, 5, 6])->latest()->get();
                    $data = InvoiceUserResource::collection($orders);
                    $userTypeId = auth()->user()->type_id;
                    event(new NewOrder($data, $userTypeId));
                    return $this->messageSuccessResponse(trans('locale.created'), 200);
                }
                // $token = PersonalAccessToken::findToken($request->bearerToken());
                // $aa = $data['data'][0];
                else {
                    $sum = 0;
                    foreach ($data['data'] as $d) {
                        $item = Item::whereId($d['item_id'])->first();
                        $price = $this->calculatePriceProduct($d['item_id'], $d['toppings'] ?? [], $d['size_id'] ?? null);
                        $en = ItemTranslation::where('locale', 'en')->whereItemId($d['item_id'])->first();
                        $ar = ItemTranslation::where('locale', 'ar')->whereItemId($d['item_id'])->first();
                        $name_en = $en->name;
                        $name_ar = $ar->name;
                        $en_category = CategoryTranslation::where('locale', 'en')->whereCategoryId($item->category_id)->first();
                        $ar_category = CategoryTranslation::where('locale', 'ar')->whereCategoryId($item->category_id)->first();
                        $type_en = $en_category->name;
                        $type_ar = $ar_category->name;
                        $count = $d['count'];

                        $order2 = Order::create([
                            'item_id' => $item->id,
                            'price' => $price,
                            'count' => $count,
                            'user_id' => $customer->id,
                            'restaurant_id' => $customer->restaurant_id,

                            'en' => [
                                'name' => $name_en,
                                'type' => $type_en,
                            ],
                            'ar' => [
                                'name' => $name_ar,
                                'type' => $type_ar,
                            ],
                        ]);
                        $this->addDetailsItem($order2, $d['size_id'], $d['components'], $d['toppings']);
                        $orders[] = $order2;
                        $sum += $price * $count;

                        $title = "new order";
                        $body = "order number: {$order2->id}, user id: {$customer->id}, user name: {$customer->name}, name: {$name_en} - {$name_ar}";
                        $data2 = [
                            'order_id' => $order2->id,
                            'restaurant_id' => $order2->restaurant_id,
                            'user_id' => $customer->id,
                            'price' => $order2->price,
                            'count' => $order2->count,
                            'name_en' => $name_en,
                            'name_ar' => $name_ar,
                        ];

                        for ($i = 0; $i < count($employee); $i++) {
                            $firstElement = $employee->get($i);
                            if ($firstElement->fcm_token) {
                                try {
                                    // محاولة إرسال الإشعار
                                    $this->firebaseService->sendNotification($firstElement->fcm_token, $title, $body, $data2);
                                } catch (\Kreait\Firebase\Exception\Messaging\InvalidMessage $e) {
                                    // تسجيل الخطأ إذا كان token غير صالح
                                    Log::warning("FCM token غير صالح للموظف رقم " . ($i + 1) . ": {$firstElement->fcm_token} - الخطأ: " . $e->getMessage());
                                } catch (\Exception $e) {
                                    // تسجيل أي خطأ آخر
                                    Log::error("خطأ في إرسال الإشعار للموظف رقم " . ($i + 1) . ": {$firstElement->fcm_token} - الخطأ: " . $e->getMessage());
                                }
                            }
                        }
                    }
                    $n = Invoice::where('restaurant_id', $customer->restaurant_id)->max('num') ?? 0;
                    $num = $n + 1;

                    // dd($request->delivery_price);
                    $invoice = Invoice::create([
                        'status' => InvoiceStatus::WAITING->value,
                        'restaurant_id' => $customer->restaurant_id,
                        'is_delivery' => $request->is_delivery ?? 0,
                        'user_id' => $customer->id,
                        'num' => $num,
                        'delivery_price' => $request->delivery_price,
                    ]);
                    $restaurant = Restaurant::whereId($customer->restaurant_id)->first();
                    $consumer_spending = $sum * $restaurant->consumer_spending / 100;
                    $local_administration = $sum * $restaurant->local_administration / 100;
                    $reconstruction = $sum * $restaurant->reconstruction / 100;
                    $total = $sum + $consumer_spending + $local_administration + $reconstruction;

                    if ($request->has('latitude') && $request->has('longitude') && $request->latitude !== null && $request->longitude !== null) {
                        // إنشاء عميل Guzzle
                        $client = new Client();

                        // إعداد رأس User-Agent مع معلومات التطبيق
                        $headers = [
                            'User-Agent' => 'Menu/1.0 (your.email@example.com)'  // قم بتغيير هذه القيمة
                        ];

                        // إرسال طلب إلى API Nominatim
                        $response = $client->get('https://nominatim.openstreetmap.org/reverse', [
                            'headers' => $headers,
                            'query' => [
                                'lat' => $request->latitude,
                                'lon' => $request->longitude,
                                'format' => 'json',
                                'addressdetails' => 1,
                            ]
                        ]);

                        // تحويل الاستجابة إلى مصفوفة
                        $data = json_decode($response->getBody(), true);

                        // التحقق من وجود العنوان في الاستجابة
                        if (isset($data['address'])) {
                            $city = $data['address']['city'] ?? null;
                            $region = $data['address']['state'] ?? null;
                            $street = $data['address']['road'] ?? null;
                            $neighborhood = $data['address']['suburb'] ?? null;

                            // وضع القيم في مصفوفة
                            $addressParts = [$region, $city, $street, $neighborhood];

                            // إزالة القيم الفارغة (null)
                            $addressParts = array_filter($addressParts, fn($value) => !is_null($value));

                            // دمج الأجزاء المتاحة في السلسلة
                            $r = implode(' - ', $addressParts);

                            // $r = $city .' - '. $street .' - '. $neighborhood;
                            // $address = Address::create([
                            //     'city' => $region ?? null,
                            //     'region' => $r ?? null,
                            //     'url' => $data['url'] ?? null,
                            //     'user_id' => $customer->id,
                            //     'latitude' => $request->latitude,
                            //     'longitude' => $request->longitude,
                            // ]);
                        } else
                            return response()->json(['error' => 'لا يمكن العثور على الموقع'], 404);

                        // $address = Address::create([
                        //     'city' => $data['city'] ?? null,
                        //     'region' => isset($data['region']) ? $data['region'] : (isset($data['address']) ? $data['address'] : null),
                        //     'url' => $data['url'] ?? null,
                        //     'user_id' => $customer->id,
                        // ]);
                    } else {
                        if ($request->has('friend_address') && $request->friend_address !== null)
                            $address = Address::whereRegion($data['friend_address'])->first();

                        elseif ($request->has('address') && $request->address !== null)
                            $address = Address::whereId($data['address'])->first();

                        elseif ($request->has('isDelivery') && $request->isDelivery == false)
                            $address = Address::create();
                    }

                    $p = round($sum, 0);
                    $t = round($sum, 0);
                    if ($request->has('code') && $request->code != null) {
                        $coupon = Coupon::whereCode($request->code)->first();
                        if ($coupon) {
                            // if($coupon->type == "فاتورة")
                            // {
                            //     // $p = $p + $request->delivery_price;
                            //     $t = $t + $request->delivery_price;
                            //     // $p = $p - ($p * $coupon->percent/100);
                            //     $t = $t - ($t * $coupon->percent/100);
                            // }
                            // elseif($coupon->type == "منتجات")
                            // {
                            //     $discountP = $p * $coupon->percent/100;
                            //     // $discountT = $t * $coupon->percent/100;
                            //     $p = $p - $discountP;
                            //     // $t = $t + $request->delivery_price - $discountT;
                            // }
                            // elseif($coupon->type == "توصيل")
                            // {
                            //     // $p = $p + ($request->delivery_price - ($request->delivery_price * $coupon->percent/100));
                            //     $t = $t + ($request->delivery_price - ($request->delivery_price * $coupon->percent/100));
                            // }
                            $td = $t + $request->delivery_price;
                            $invoice->update([
                                'status' => InvoiceStatus::WAITING->value,
                                'price' => round($p, 0),
                                'delivery_price' => $request->delivery_price,
                                'consumer_spending' => 0,
                                'local_administration' => 0,
                                'reconstruction' => 0,
                                'total' => round($td, 0),
                                'address_id' => $address->id,
                                'discount' => $t * $coupon->percent / 100,
                            ]);
                        }
                    } else {
                        //$p = $p + $request->delivery_price;
                        $t = $t + $request->delivery_price;

                        $invoice->update([
                            'status' => InvoiceStatus::WAITING->value,
                            'price' => round($p, 0),
                            'delivery_price' => $request->delivery_price,
                            'consumer_spending' => 0,
                            'local_administration' => 0,
                            'reconstruction' => 0,
                            'total' => round($t, 0),
                            'address_id' => $address->id,
                        ]);
                    }

                    foreach ($orders as $order) {
                        $order->update([
                            'invoice_id' => $invoice->id,
                        ]);
                    }
                    $orders = Invoice::with('orders')->with('address')->whereUserId($customer->id)->where(function ($query4) {
                        $query4->whereDate('created_at', now()->toDateString())->orWhereDate('created_at', Carbon::yesterday()->toDateString());
                    })->whereIn('status', [0, 1, 2, 5, 6, 7])->latest()->get();
                    $data = InvoiceUserResource::collection($orders);
                    $userTypeId = auth()->user()->type_id;
                    event(new NewOrder($data, $userTypeId));
                    return $this->messageSuccessResponse(trans('locale.created'), 200);
                }
            }
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Add order
    public function create2(CustomerAdd2Request $request)
    {
        try {
            $customer = auth()->user();
            $customer_id = $customer->id;
            $data = $request->validated();
            $data['item_id'];
            $inv = Invoice::whereTableId($customer->table_id)->whereStatus(2)->whereDate('created_at', now())->orWhereDate('created_at', Carbon::tomorrow())->latest()->first();
            if ($inv) {
                $item = Item::whereId($data['item_id'])->first();
                $price = $item->price;
                $en = ItemTranslation::where('locale', 'en')->whereItemId($data['item_id'])->first();
                $ar = ItemTranslation::where('locale', 'ar')->whereItemId($data['item_id'])->first();
                $name_en = $en->name;
                $name_ar = $ar->name;
                $en_category = CategoryTranslation::where('locale', 'en')->whereCategoryId($item->category_id)->first();
                $ar_category = CategoryTranslation::where('locale', 'ar')->whereCategoryId($item->category_id)->first();
                $type_en = $en_category->name;
                $type_ar = $ar_category->name;
                $count = $data['count'];

                $order = Order::create([
                    'item_id' => $item->id,
                    'price' => $price,
                    'count' => $count,
                    'table_id' => $customer->table_id,
                    'restaurant_id' => $customer->restaurant_id,
                    'customer_id' => $customer_id,
                    'invoice_id' => $inv->id,

                    'en' => [
                        'name' => $name_en,
                        'type' => $type_en,
                    ],
                    'ar' => [
                        'name' => $name_ar,
                        'type' => $type_ar,
                    ],
                ]);
                $restaurant = Restaurant::whereId($customer->restaurant_id)->first();
                $title = "new order";
                $body = "order number: {$order->table_id}, table number: {$order->table->number_table}, name: {$name_en} - {$name_ar}";
                $data2 = [
                    'order_id' => $order->id,
                    'restaurant_id' => $order->restaurant_id,
                    'table_id' => $order->table_id,
                    'customer_id' => $order->customer_id,
                    'price' => $order->price,
                    'count' => $order->count,
                    'name_en' => $name_en,
                    'name_ar' => $name_ar,
                ];

                if ($restaurant->accepted_by_waiter == 1)
                    $employee = Admin::role('employee')->whereRestaurantId($customer->restaurant_id)->whereTypeId(5)->get();

                else
                    $employee = Admin::role('employee')->whereRestaurantId($customer->restaurant_id)->whereIn('type_id', [3, 4, 5, 8])->get();

                for ($i = 0; $i < count($employee); $i++) {
                    $firstElement = $employee->get($i);
                    if ($firstElement->fcm_token)
                        $this->firebaseService->sendNotification($firstElement->fcm_token, $title, $body, $data2);
                }
                $table = Table::whereId($customer->table_id)->first();
                $table->new_order = 1;
                $table->save();
                return $this->messageSuccessResponse(trans('locale.created'), 200);
            }

            $item = Item::whereId($data['item_id'])->first();
            $price = $item->price;
            $en = ItemTranslation::where('locale', 'en')->whereItemId($data['item_id'])->first();
            $ar = ItemTranslation::where('locale', 'ar')->whereItemId($data['item_id'])->first();
            $name_en = $en->name;
            $name_ar = $ar->name;
            $en_category = CategoryTranslation::where('locale', 'en')->whereCategoryId($item->category_id)->first();
            $ar_category = CategoryTranslation::where('locale', 'ar')->whereCategoryId($item->category_id)->first();
            $type_en = $en_category->name;
            $type_ar = $ar_category->name;
            $count = $data['count'];

            $order2 = Order::create([
                'item_id' => $item->id,
                'price' => $price,
                'count' => $count,
                'table_id' => $customer->table_id,
                'restaurant_id' => $customer->restaurant_id,
                'customer_id' => $customer_id,

                'en' => [
                    'name' => $name_en,
                    'type' => $type_en,
                ],
                'ar' => [
                    'name' => $name_ar,
                    'type' => $type_ar,
                ],
            ]);
            $restaurant = Restaurant::whereId($customer->restaurant_id)->first();
            $title = "new order";
            $body = "order number: {$order2->table_id}, table number: {$order2->table->number_table}, name: {$name_en} - {$name_ar}";
            $data2 = [
                'order_id' => $order2->id,
                'restaurant_id' => $order2->restaurant_id,
                'table_id' => $order2->table_id,
                'customer_id' => $order2->customer_id,
                'price' => $order2->price,
                'count' => $order2->count,
                'name_en' => $name_en,
                'name_ar' => $name_ar,
            ];

            if ($restaurant->accepted_by_waiter == 1)
                $employee = Admin::role('employee')->whereRestaurantId($customer->restaurant_id)->whereTypeId(5)->get();

            else
                $employee = Admin::role('employee')->whereRestaurantId($customer->restaurant_id)->whereIn('type_id', [3, 4, 5, 8])->get();

            for ($i = 0; $i < count($employee); $i++) {
                $firstElement = $employee->get($i);
                if ($firstElement->fcm_token)
                    $this->firebaseService->sendNotification($firstElement->fcm_token, $title, $body, $data2);
            }
            $table = Table::whereId($customer->table_id)->first();
            $table->new_order = 1;
            $table->save();
            return $this->messageSuccessResponse(trans('locale.created'), 200);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Update order
    public function update(CustomerUpdateRequest $request)
    {
        try {
            $data = $request->validated();
            $restaurant_id = auth()->user()->restaurant_id;
            $status = Order::whereId($data['id'])->first('status');
            if ($status->status != 'done')
                return $this->messageErrorResponse(trans('locale.youCantUpdate'), 403);

            $order = $this->orderService->update($restaurant_id, $data);
            if ($order == 0) {
                return $this->messageErrorResponse(trans('locale.invalidItem'), 403);
            }
            $orderShow = $this->orderService->show($restaurant_id, $data);
            $data = OrderResource::make($orderShow);
            return $this->successResponse($data, trans('locale.updated'), 200);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Show order By Id
    public function showById(IdRequest $request)
    {
        try {
            $data = $request->validated();
            $order = $this->orderService->show($data['restaurant_id'], $request->validated());
            $data = OrderResource::make($order);
            return $this->successResponse($data, trans('locale.ordersFound'), 200);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Delete order
    public function delete(IdRequest $request)
    {
        try {
            $data = $request->validated();
            $status = Order::whereId($data['id'])->first('status');
            // dd($status->status);
            if ($status->status == 'done' || $status->status == 'preparation') {
                return $this->messageErrorResponse(trans('locale.youCantDeleted'), 403);
            }
            $invoice_id = Order::whereId($data['id'])->first('invoice_id');
            $invoice = Invoice::whereId($invoice_id->invoice_id)->first()->delete();
            return $this->messageSuccessResponse(trans('locale.deleted'), 200);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    public function calculatePriceProduct($item_id, array $toppings, $size_id)
    {
        if (empty($size_id) && empty($toppings[0]['topping_id'])) {
            // dd($item_id);
            return  Item::where('id', $item_id)->first()->price;
        }
        $price = 0;
        if (!empty($size_id)) {
            $price = Size::findOrFail($size_id)->price;
        }
        if (!empty($toppings)) {
            $toppings = Topping::query()->whereIn('id', $toppings)->get();
            foreach ($toppings as $topping) {
                $price += $topping->price;
            }
        }
        return $price;
    }

    public function addDetailsItem(Order $order, $size_id, $component_ids, $topping_ids)
    {
        $toppings = Topping::whereIn('id', $topping_ids)
            ->get()
            ->map(function ($topping) {
                return [
                    'name' => $topping->name,
                    'price' => $topping->price,
                ];
            })
            ->toArray();

        $components = Component::whereIn('id', $component_ids)
            ->get()
            ->map->only(['name'])
            ->toArray();
        $size = Size::where('id', $size_id)
            ->get()
            ->map->only(['name', 'price'])
            ->first();
        if (isset($toppings))
            $order->toppings = json_encode($toppings);
        if (isset($components))
            $order->components = json_encode($components);
        if (isset($size))
            $order->size = json_encode($size);
        $order->save();
    }
}
