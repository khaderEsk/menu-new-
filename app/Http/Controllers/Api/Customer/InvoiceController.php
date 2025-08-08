<?php

namespace App\Http\Controllers\Api\Customer;

use App\Enum\InvoiceStatus;
use App\Events\NewOrder;
use App\Http\Controllers\Controller;
use App\Http\Requests\Address\AddRequest as AddressAddRequest;
use App\Http\Requests\Invoice\AddAddressToInvoiceRequest;
use App\Http\Requests\Invoice\AddRequest;
use App\Http\Resources\InvoiceResources;
use App\Http\Resources\OrderResource;
use App\Models\Address;
use App\Models\Admin;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\Table;
use App\Services\FirebaseService;
use App\Services\InvoiceService;
use App\Services\OsrmService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class InvoiceController extends Controller
{
    public function __construct(private InvoiceService $invoiceService, private FirebaseService $firebaseService) {}
    public function invoices()
    {
        try {
            $user = auth()->user();
            // $user->id;
            if ($user->hasRole('customer'))
                $inv = Invoice::whereTableId($user->table_id)->whereStatus(2)->whereDate('created_at', now())->latest()->first();
            elseif ($user->hasRole('takeout'))
                $inv = Invoice::whereDate('created_at', now())->latest()->first();
            if ($inv) {
                $orders = Order::where('invoice_id', $inv->id)->latest()->get();
                if ($user->hasRole('customer')) {
                    if ($orders) {
                        foreach ($orders as $order) {
                            if ($order->status != 'done')
                                return response()->json(['status' => false, 'message' => trans('locale.youCantRequestInvoice')], 400);
                        }
                    }
                }
                $sum = 0;
                foreach ($orders as $order) {
                    $sum += $order['price'] * $order['count'];
                }
                $restaurant = Restaurant::whereId($user->restaurant_id)->first();
                $consumer_spending = $sum * $restaurant->consumer_spending / 100;
                $local_administration = $sum * $restaurant->local_administration / 100;
                $reconstruction = $sum * $restaurant->reconstruction / 100;
                $total = $sum + $consumer_spending + $local_administration + $reconstruction;
                if ($restaurant->is_taxes == 0) {
                    $total_with_delivery = $sum + $inv->delivery_price;
                    $invoice_res = [
                        'price' => round($sum, 0),
                        'consumer_spending' => 1,
                        'local_administration' => 1,
                        'reconstruction' => 1,
                        'total' => round($total_with_delivery, 0),
                    ];
                    $inv->update([
                        'price' => round($sum, 0),
                        'consumer_spending' => 1,
                        'local_administration' => 1,
                        'reconstruction' => 1,
                        'total' => round($total_with_delivery, 0)
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
                //----------------------------------------------
                $groupedItems = $orders->groupBy(function ($item) {
                    // استخدام الاسم مع التاريخ فقط (دون الوقت) كمفتاح
                    return $item->name . '-' . $item->created_at->format('Y-m-d');
                })->map(function ($items) {
                    $t = $items->first()->price;
                    $c = $items->sum('count');
                    $formattedTotal = number_format($t * $c);
                    $formattedPrice = number_format($items->first()->price);
                    // تجميع المعلومات المطلوبة لكل مجموعة
                    return [
                        'total' => $formattedTotal,
                        'count' => $c,
                        'created_at' => $items->first()->created_at->format('Y-m-d'),
                        'name' => $items->first()->name,
                        'status' => $items->first()->status,
                        'price' => $formattedPrice,
                        "name_en" => $items->first()->translate('en')->name,
                        "name_ar" => $items->first()->translate('ar')->name,
                        "type_en" => $items->first()->translate('en')->type,
                        "type_ar" => $items->first()->translate('ar')->type,
                        "translations" => $items->first()->translations,
                    ];
                });

                $groupedItemsArray = $groupedItems->toArray();

                $order_res = array_values($groupedItemsArray);
                //----------------------------------------------

                //$order_res = OrderResource::collection($orders);
                $invoice_res = InvoiceResources::make($inv);
                $invoice_res['delivery_price'] = $inv->delivery_price;
                $data = [
                    'orders' => $order_res,
                    'invoice' => $invoice_res,
                ];
                return response()->json(['status' => true, 'data' => $data, 'message' => trans('locale.foundSuccessfully')], 200);
            }
            $orders = Order::whereRestaurantId($user->restaurant_id)->whereNull('invoice_id')->whereDate('created_at', now()->format('Y-m-d'))->whereTableId($user->table_id)->get();
            if (count($orders) == 0) {
                $data = [
                    'orders' => [],
                    'invoice' => [],
                ];
                // return $this->messageSuccessResponse(trans('locale.dontHaveOrders'),200);
                return response()->json(['status' => true, 'data' => $data, 'message' => trans('locale.dontHaveOrders')], 200);
            }
            if ($orders) {
                foreach ($orders as $order) {
                    if ($order->status != 'done')
                        return response()->json(['status' => false, 'message' => trans('locale.youCantRequestInvoice')], 400);
                }
            }
            $data = ['table_id' => $user->table_id, 'status' => 2];
            $data['customer_id'] = $user->id;
            $invoice = $this->invoiceService->create($user->restaurant_id, $data);
            $data = InvoiceResources::make($invoice);
            foreach ($orders as $order) {
                $order->update([
                    'invoice_id' => $invoice->id,
                ]);
            }


            $sum = 0;
            $orders = Order::where('invoice_id', $invoice->id)->latest()->get();
            $groupedItems = $orders->groupBy(function ($item) {
                // استخدام الاسم مع التاريخ فقط (دون الوقت) كمفتاح
                return $item->name . '-' . $item->created_at->format('Y-m-d');
            })->map(function ($items) {
                $t = $items->first()->price;
                $c = $items->sum('count');
                // تجميع المعلومات المطلوبة لكل مجموعة
                return [
                    'total' => $t * $c,
                    'count' => $c,
                    'created_at' => $items->first()->created_at->format('Y-m-d'),
                    'name' => $items->first()->name,
                    'status' => $items->first()->status,
                    'price' => $items->first()->price,
                    "name_en" => $items->first()->translate('en')->name,
                    "name_ar" => $items->first()->translate('ar')->name,
                    "type_en" => $items->first()->translate('en')->type,
                    "type_ar" => $items->first()->translate('ar')->type,
                    "translations" => $items->first()->translations,
                ];
            });

            $groupedItemsArray = $groupedItems->toArray();

            $order_res = array_values($groupedItemsArray);
            //$order_res = OrderResource::collection($orders);
            $restaurant = Restaurant::where('id', $user->restaurant_id)->first();
            $consumer_spending = $restaurant['consumer_spending'];
            $local_administration = $restaurant['local_administration'];
            $reconstruction = $restaurant['reconstruction'];
            $invoice = Invoice::where('id', $invoice->id)->first();
            $invoice_res = InvoiceResources::make($invoice);
            foreach ($orders as $order) {
                $sum += $order['price'] * $order['count'];
            }

            $consumer_spending = $sum * $consumer_spending / 100;
            $local_administration = $sum * $local_administration / 100;
            $reconstruction = $sum * $reconstruction / 100;
            $total = $sum + $consumer_spending + $local_administration + $reconstruction;
            $rest = Restaurant::whereId($invoice->restaurant_id)->first();
            if ($rest->is_taxes == 0) {
                $invoice->update([
                    'price' => round($sum, 0),
                    'consumer_spending' => 1,
                    'local_administration' => 1,
                    'reconstruction' => 1,
                    'total' => round($sum, 0)
                ]);
            } elseif ($rest->is_taxes == 1) {
                $invoice->update([
                    'price' => round($sum, 0),
                    'consumer_spending' => round($consumer_spending, 0),
                    'local_administration' => round($local_administration, 0),
                    'reconstruction' => round($reconstruction, 0),
                    'total' => round($total, 0)
                ]);
            }
            $table = Table::where('id', $user->table_id)->first();
            $arr = ['number_table' => $table->number_table, 'number_invoice' => $invoice->num, 'message' => "the invoice is Ordered"];
            $admins = Admin::whereRestaurantId($user->restaurant_id)->get();
            $adminsWithPermission = $admins->filter(function ($admin) {
                return $admin->hasPermissionTo('order.index');
            });
            $title = "Invoice";
            $body = "Invoice: {$invoice->num}, table number: {$invoice->table_id}, price: {$invoice->price}, total: {$invoice->total}";
            for ($i = 0; $i < count($adminsWithPermission); $i++) {
                $firstElement = $adminsWithPermission->get($i);
                if ($firstElement && $firstElement->fcm_token)
                    $this->firebaseService->sendNotification($firstElement->fcm_token, $title, $body, []);
                // $this->SendNotification($firstElement->fcm_token, $arr);
            }

            $data = [
                'orders' => $order_res,
                'invoice' => $invoice_res,
            ];
            return response()->json(['status' => true, 'data' => $data, 'message' => trans('locale.foundSuccessfully')], 200);
            // return response()->json(['status' => true, 'orders' => $order_res, 'invoice' => $invoice_res, 'message' => trans('locale.foundSuccessfully')],200);

        } catch (Throwable $th) {
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    public function invoiceAddress(AddAddressToInvoiceRequest $request)
    {
        $user = auth()->user();
        $address = Address::create([
            'city' => $request->city ?? null,
            'url' => $request->address ?? null,
            'region' => $request->region ?? null,
            'user_id' => $user->id,
            'restaurant_id' => $user->restaurant_id
        ]);
        $invoices = Invoice::whereUserId($user->id)->whereIsDelivery(1)->whereNull('address_id')->where(function ($query) {
            $query->whereDate('created_at', now()->toDateString())
                ->orWhereDate('created_at', Carbon::yesterday()->toDateString());
        })->get();
        foreach ($invoices as $invoice) {
            $invoice->address_id = $address->id;
            $invoice->save();
        }
        return $this->messageSuccessResponse(trans('locale.successfully'), 200);
    }
    // private function SendNotification($fcm_token, $arr)
    // {
    //     // تحضير رمز FCM
    //     $firebaseToken = [];
    //     $firebaseToken[0] = $fcm_token;

    //     // تحضير نص الإشعار
    //     $body = 'number_table : ' . $arr['number_table'] . ' , number_invoice : ' . $arr['number_invoice'] . ' , message : '
    //         . $arr['message'];

    //     // مفتاح API الخاص بخادم Firebase
    //     $SERVER_API_KEY = config('services.firebase.server_key');

    //     // إعداد بيانات الإشعار
    //     $data = [
    //         "registration_ids" => $firebaseToken,
    //         "notification" => [
    //             "title" => 'invoice',
    //             "body" => $body,
    //             "content_available" => true,
    //             "priority" => "high",
    //         ]
    //     ];


    //     $response = Http::withHeaders([
    //         'Authorization' => 'key=' . $SERVER_API_KEY,
    //         'Content-Type' => 'application/json',
    //     ])->post('https://fcm.googleapis.com/fcm/send', $data);

    //     if ($response->successful()) {
    //         $responseData = $response->json();
    //         if ($responseData['success'] > 0) {
    //             return true;
    //         } else {
    //             Log::error('Failed to send notification', ['response' => $responseData]);
    //             return false;
    //         }
    //     } else {
    //         Log::error('FCM request failed', ['http_code' => $response->status(), 'response' => $response->body()]);
    //         return false;
    //     }
    // }
    public function updateLocation(AddressAddRequest $request, Invoice $invoice, OsrmService $osrmService)
    {
        try {
            // 1. Validate the incoming data from the driver's app
            $validated = $request->validated();

            // 2. Get customer's destination address
            $address = $invoice->address;
            if (!$address) {
                return response()->json(['message' => 'Destination address not found.'], 404);
            }

            // 3. Call the OSRM service to get the new route
            $routeData = $osrmService->getRoute(
                $validated['latitude'],   // Driver's current latitude
                $validated['longitude'],  // Driver's current longitude
                $address->latitude,       // Customer's latitude
                $address->longitude       // Customer's longitude
            );
            // dd($routeData['duration']);

            if (!$routeData) {
                Log::error("Failed to get route from OSRM for invoice #{$invoice->id} during tracking.");
                return response()->json(['message' => 'Could not calculate route.'], 500);
            }

            // 4. Update the delivery_routes and invoices tables
            $newDuration = round($routeData['duration'] / 60, 2); // In minutes

            $invoice->deliveryRoute()->update([
                'start_lat' => $validated['latitude'],
                'start_lon' => $validated['longitude'],
                'distance'  => round($routeData['distance'] / 1000, 2), // In KM
                'duration'  => $newDuration,
            ]);

            $invoice->total_estimated_duration = $newDuration;
            $invoice->saveQuietly();
            $customer = $invoice->user;
            if ($customer) {
                // Get all active invoices for this customer to broadcast
                // dd(Invoice::where('user_id', $customer->id)->where('status', 5)->get());
                $userOrders = Invoice::where('user_id', $customer->id)
                    ->whereIn(
                        'status',
                        [InvoiceStatus::UNDER_DELIVERY->value]
                    )
                    ->latest()
                    ->with('deliveryRoute') // <-- Please make sure this line is here
                    ->get();
                // Assuming the customer model has a 'user_type_id'
                $userTypeId = 0;
                // dd();
                // dd($userOrders->first()->deliveryRoute->distance);

                // Dispatch the event with the required data
                event ( new NewOrder($userOrders, $userTypeId));
                // dd($not);
            }


            // 5. Return a success response
            return $this->successResponse(['estimated_duration' => $newDuration], trans('locale.locationUpdated'), 200);
        } catch (Throwable $e) {
            return $this->messageErrorResponse($e->getMessage());
        }
    }
}
