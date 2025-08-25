<?php

namespace App\Http\Controllers\Api\Admin;

use Throwable;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Admin;
use App\Models\Order;
use App\Models\Address;
use App\Models\Invoice;
use App\Events\NewOrder;
use App\Enum\InvoiceStatus;
use App\Events\OrderUpdated;
use Illuminate\Http\Request;
use App\Services\InvoiceService;
use App\Services\FirebaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Events\InvoiceStatusUpdated;
use App\Http\Controllers\Controller;
use App\Services\UserTakeoutService;
use App\Http\Requests\IdInvoiceRequest;
use App\Http\Resources\AddressResource;
use App\Http\Requests\Delivey\IdRequest;
use App\Http\Resources\DeliveryResource;
use App\Http\Requests\Delivey\AddRequest;
use App\Http\Requests\RegisterUserRequest;
use App\Http\Resources\InvoiceUserResource;
use App\Http\Requests\Delivey\UpdateRequest;
use App\Http\Requests\Delivey\ShowAllRequest;
use App\Http\Requests\User\UpdateInfoRequest;
use App\Http\Resources\InvoiceUserMobileResource;
use App\Http\Requests\Invoice\StatusInvoiceRequest;
use App\Http\Requests\Invoice\IdRequest as InvoiceIdRequest;
use CuyZ\Valinor\Mapper\Tree\Message\Message;

class UserTakeoutController extends Controller
{
    public function __construct(
        private UserTakeoutService $userService,
        private InvoiceService $invoiceService,
        private FirebaseService $firebaseService
    ) {}

    // Show All deliveries For Admin
    public function showAll(ShowAllRequest $request)
    {
        try {
            $admin = auth()->user();
            $user = $this->userService->paginate($admin->restaurant_id, $request->input('per_page', 10));
            if (\count($user) == 0) {
                return $this->successResponse([], trans('locale.dontHaveUser'), 200);
            }
            $data = DeliveryResource::collection($user);
            return $this->paginateSuccessResponse($data, trans('locale.foundSuccessfully'), 200);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Add user
    public function create(RegisterUserRequest $request)
    {
        try {
            $user = $this->userService->create($request->validated());
            $data = DeliveryResource::make($user);
            $tokenResult = $user->createToken('auth_token');
            $token = $tokenResult->plainTextToken;
            $tokenResult->accessToken->forceFill(['platform' => request()->header('User-Agent')])->save();

            if (request()->fcm_token != null) {
                $user->update([
                    'fcm_token' => $request->fcm_token,
                ]);
            }

            $data['token'] = $token;
            $data['address'] = $request->address;
            return $this->successResponse($data, trans('locale.created'), 200);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Update user
    public function update(UpdateRequest $request)
    {
        try {
            $admin = auth()->user();
            $data_val = $request->validated();
            $user = $this->userService->update($admin->restaurant_id, $data_val);
            if ($user == 0) {
                return $this->messageErrorResponse(trans('locale.invalidItem'), 403);
            }
            $showuser = $this->userService->show($data_val['id']);
            $data = DeliveryResource::make($showuser);
            return $this->successResponse($data, trans('locale.updated'), 200);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Show user By Id
    public function showById(IdRequest $request)
    {
        try {
            $admin = auth()->user();
            $data_val = $request->validated();
            $user = $this->userService->show($data_val['id']);
            $data = DeliveryResource::make($user);
            return $this->successResponse($data, trans('locale.foundSuccessfully'), 200);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Delete user
    public function delete(IdRequest $request)
    {
        try {
            $restaurant_id = auth()->user()->restaurant_id;
            $data_val = $request->validated();
            $user = $this->userService->destroy($data_val['id'], $restaurant_id);
            if ($user == 0) {
                return $this->messageErrorResponse(trans('locale.invalidItem'), 403);
            }
            return $this->messageSuccessResponse(trans('locale.deleted'), 200);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Active Or DisActive user
    public function deactivate(IdRequest $request)
    {
        try {
            $data_val = $request->validated();
            $user = $this->userService->show($data_val['id']);
            $data = $this->userService->activeOrDesactive($user);
            if ($data == 0) {
                return $this->messageErrorResponse(trans('locale.invalidItem'), 403);
            }
            return $this->messageSuccessResponse(trans('locale.successfully'), 200);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // show address user
    public function showAddress()
    {
        try {
            $restaurant_id = auth()->user()->restaurant_id;
            $addresses = Address::whereRestaurantId($restaurant_id)->get();
            if (\count($addresses) == 0) {
                return $this->successResponse([], trans('locale.dontHaveUser'), 200);
            }
            $data = AddressResource::make($addresses);
            return $this->successResponse($data, trans('locale.foundSuccessfully'), 200);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    public function showOrderUser(IdRequest $request)
    {
        try {
            $user = auth()->user();
            $invoices = Invoice::with('orders')->whereUserId($request->id)->paginate($request->input('per_page', 10));
            if (\count($invoices) == 0) {
                return $this->successResponse([], trans('locale.dontHaveOrder'), 200);
            }
            $data = InvoiceUserResource::collection($invoices);
            return $this->paginateSuccessResponse($data, trans('locale.foundSuccessfully'), 200);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    public function showOrdersTakeout(Request $request)
    {
        try {
            $user = auth()->user();
            $invoices = Invoice::with('orders')->whereNotNull('user_id')->whereRestaurantId($user->restaurant_id)->latest()->paginate($request->input('per_page', 10));
            if (\count($invoices) == 0) {
                return $this->successResponse([], trans('locale.dontHaveOrder'), 200);
            }
            // dd($invoices->first()->address->region);
            $data = InvoiceUserResource::collection($invoices);
            return $this->paginateSuccessResponse($data, trans('locale.foundSuccessfully'), 200);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    public function giveOrderToDelivery(IdInvoiceRequest $request)
    {
        try {
            $orders = Order::whereInvoiceId($request->invoice_id)->whereIn('status', ['accepted', 'preparation', 'done'])->get();
            if ($orders->isEmpty())
                return $this->messageErrorResponse(trans('locale.youCannotSendTheRequestBecause'), 400);
            $invoice = Invoice::whereId($request->invoice_id)->update([
                'delivery_id' => $request->delivery_id,
                'receipt_at' => now(),

            ]);

            try {
                $delivery = User::find($request->delivery_id);
                $fcmToken = $delivery?->fcm_token;

                if ($delivery && $fcmToken) {
                    Log::info('delivery has FCM token. Preparing Firebase notification.');
                    $title = "لديك طلب جديد";
                    $body = "لديك طلب جديد";
                    $this->firebaseService->sendNotification($fcmToken, $title, $body, []);
                    Log::info('Firebase notification sent to user_id');
                }
            } catch (\Exception $e) {
                Log::error('Firebase Notification Failed: ' . $e->getMessage());
            }

            return $this->messageSuccessResponse(trans('locale.successfully'), 200);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    public function deleteOrderTakeout(InvoiceIdRequest $request)
    {
        try {
            $restaurant_id = auth()->user()->restaurant_id;
            $data_val = $request->validated();
            Invoice::whereRestaurantId($restaurant_id)->whereId($data_val['id'])->delete();
            return $this->messageSuccessResponse(trans('locale.deleted'), 200);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    public function rejectedOrder(InvoiceIdRequest $request)
    {
        try {
            $restaurant_id = auth()->user()->restaurant_id;
            $data_val = $request->validated();
            Invoice::whereRestaurantId($restaurant_id)->whereId($data_val['id'])->update([
                'status' => 0,
            ]);
            return $this->messageSuccessResponse(trans('locale.successfully'), 200);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // public function updateStatusOrder(StatusInvoiceRequest $request)
    // {
    //     try{
    //         $admin = Admin::with('type')->find(auth()->id());
    //         dd($admin);
    //         $data_val = $request->validated();
    //         Order::whereInvoiceId($data_val['id'])->update([
    //             'status' => 'done',
    //         ]);
    //         if($data_val['status'] == 'ready' || $data_val['status'] == 'processing')
    //             $data_val['status'] = 2;

    //         elseif($data_val['status'] == 'Paid')
    //             $data_val['status'] = 3;

    //         elseif($data_val['status'] == 'Received')
    //             $data_val['status'] = 4;

    //         elseif($data_val['status'] == 'Under delivery')
    //             $data_val['status'] = 5;

    //         elseif($data_val['status'] == 'rejected')
    //             $data_val['status'] = 0;

    //         elseif($data_val['status'] == 'approved')
    //             $data_val['status'] = 6;

    //         $type = $admin->type->name;
    //         if($data_val['status'] == 3 ||$data_val['status'] == 5 )
    //         {
    //             return $this->messageErrorResponse("يمكن القيام بهذة العملية من قبل رجل التوصيل",400);
    //         }
    //         if($data_val['status'] == 2 && ($type == "chef" || $type == "bar"))
    //         {
    //             Invoice::whereRestaurantId($admin->restaurant_id)->whereId($data_val['id'])->update([
    //                 'status' => $data_val['status'],
    //             ]);
    //             $invoice = Invoice::with('orders')->whereId($data_val['id'])->first();
    //             if($invoice->delivery_id != null)
    //             {
    //                 $invoices = Invoice::with('orders')->whereIn('status', [1, 2])->whereRestaurantId($invoice->restaurant_id)->whereDeliveryId($invoice->delivery_id)->get();
    //                 $data = InvoiceUserMobileResource::collection($invoices);
    //                 event(new OrderUpdated($data));
    //                 $invoices = Invoice::with('orders')->where('status', 5)->whereRestaurantId($invoice->restaurant_id)->whereDeliveryId($invoice->delivery_id)->get();
    //                 $data = InvoiceUserMobileResource::collection($invoices);
    //                 event(new OrderUpdated($data));
    //                 $invoices = Invoice::with('orders')->whereIn('status', [3, 4])->whereRestaurantId($invoice->restaurant_id)->whereDeliveryId($invoice->delivery_id)->get();
    //                 $data = InvoiceUserMobileResource::collection($invoices);
    //                 event(new OrderUpdated($data));
    //             }
    //             if($invoice->user_id != null)
    //             {
    //                 $orders = Invoice::with('orders')->with('address')->whereUserId($invoice->user_id)->where(function($query4){
    //                     $query4->whereDate('created_at', now()->toDateString())->orWhereDate('created_at', Carbon::yesterday()->toDateString());
    //                 })->whereIn('status', [0, 1, 2, 5, 6, 7])->latest()->get();
    //                 $data = InvoiceUserResource::collection($orders);
    //                 event(new NewOrder($data));
    //             }
    //             return $this->messageSuccessResponse(trans('locale.successfully'),200);
    //         }

    //         elseif(($data_val['status'] == 2 || $data_val['status'] == 6) && ($type == "waiter" || $type == "shisha"))
    //         {
    //             Invoice::whereRestaurantId($admin->restaurant_id)->whereId($data_val['id'])->update([
    //                 'status' => $data_val['status'],
    //             ]);
    //             $invoice = Invoice::with('orders')->whereId($data_val['id'])->first();
    //             if($invoice->delivery_id != null)
    //             {
    //                 $invoices = Invoice::with('orders')->whereIn('status', [1, 2])->whereRestaurantId($invoice->restaurant_id)->whereDeliveryId($invoice->delivery_id)->get();
    //                 $data = InvoiceUserMobileResource::collection($invoices);
    //                 event(new OrderUpdated($data));
    //                 $invoices = Invoice::with('orders')->where('status', 5)->whereRestaurantId($invoice->restaurant_id)->whereDeliveryId($invoice->delivery_id)->get();
    //                 $data = InvoiceUserMobileResource::collection($invoices);
    //                 event(new OrderUpdated($data));
    //                 $invoices = Invoice::with('orders')->whereIn('status', [3, 4])->whereRestaurantId($invoice->restaurant_id)->whereDeliveryId($invoice->delivery_id)->get();
    //                 $data = InvoiceUserMobileResource::collection($invoices);
    //                 event(new OrderUpdated($data));
    //             }
    //             if($invoice->user_id != null)
    //             {
    //                 $orders = Invoice::with('orders')->with('address')->whereUserId($invoice->user_id)->where(function($query4){
    //                     $query4->whereDate('created_at', now()->toDateString())->orWhereDate('created_at', Carbon::yesterday()->toDateString());
    //                 })->whereIn('status', [0, 1, 2, 5, 6, 7])->latest()->get();
    //                 $data = InvoiceUserResource::collection($orders);
    //                 event(new NewOrder($data));
    //             }
    //             return $this->messageSuccessResponse(trans('locale.successfully'),200);
    //         }
    //         elseif(($data_val['status'] == 0 ||$data_val['status'] == 1 ||$data_val['status'] == 2 || $data_val['status'] == 7 || $data_val['status'] == 6)
    //         && ($type == "admin" || $type == "restaurant manager" || $type == "data entry" || $type == "accountant"))
    //         {
    //             Invoice::whereRestaurantId($admin->restaurant_id)->whereId($data_val['id'])->update([
    //                 'status' => $data_val['status'],
    //             ]);
    //             $invoice = Invoice::with('orders')->whereId($data_val['id'])->first();
    //             if($invoice->delivery_id != null)
    //             {
    //                 $invoices = Invoice::with('orders')->whereIn('status', [1, 2])->whereRestaurantId($invoice->restaurant_id)->whereDeliveryId($invoice->delivery_id)->get();
    //                 $data = InvoiceUserMobileResource::collection($invoices);
    //                 event(new OrderUpdated($data));
    //                 $invoices = Invoice::with('orders')->where('status', 5)->whereRestaurantId($invoice->restaurant_id)->whereDeliveryId($invoice->delivery_id)->get();
    //                 $data = InvoiceUserMobileResource::collection($invoices);
    //                 event(new OrderUpdated($data));
    //                 $invoices = Invoice::with('orders')->whereIn('status', [3, 4])->whereRestaurantId($invoice->restaurant_id)->whereDeliveryId($invoice->delivery_id)->get();
    //                 $data = InvoiceUserMobileResource::collection($invoices);
    //                 event(new OrderUpdated($data));
    //             }
    //             if($invoice->user_id != null)
    //             {
    //                 $orders = Invoice::with('orders')->with('address')->whereUserId($invoice->user_id)->where(function($query4){
    //                     $query4->whereDate('created_at', now()->toDateString())->orWhereDate('created_at', Carbon::yesterday()->toDateString());
    //                 })->whereIn('status', [0, 1, 2, 5, 6, 7])->latest()->get();
    //                 $data = InvoiceUserResource::collection($orders);
    //                 event(new NewOrder($data));
    //             }
    //             return $this->messageSuccessResponse(trans('locale.successfully'),200);
    //         }
    //         elseif(($data_val['status'] == 0 ||$data_val['status'] == 1 ||$data_val['status'] == 2 || $data_val['status'] == 7 || $data_val['status'] == 6 || $data_val['status'] == 4) && $type == "accountant")
    //         {
    //             Invoice::whereRestaurantId($admin->restaurant_id)->whereId($data_val['id'])->update([
    //                 'status' => $data_val['status'],
    //             ]);
    //             $invoice = Invoice::with('orders')->whereId($data_val['id'])->first();
    //             if($invoice->delivery_id != null)
    //             {
    //                 $invoices = Invoice::with('orders')->whereIn('status', [1, 2])->whereRestaurantId($invoice->restaurant_id)->whereDeliveryId($invoice->delivery_id)->get();
    //                 $data = InvoiceUserMobileResource::collection($invoices);
    //                 event(new OrderUpdated($data));
    //                 $invoices = Invoice::with('orders')->where('status', 5)->whereRestaurantId($invoice->restaurant_id)->whereDeliveryId($invoice->delivery_id)->get();
    //                 $data = InvoiceUserMobileResource::collection($invoices);
    //                 event(new OrderUpdated($data));
    //                 $invoices = Invoice::with('orders')->whereIn('status', [3, 4])->whereRestaurantId($invoice->restaurant_id)->whereDeliveryId($invoice->delivery_id)->get();
    //                 $data = InvoiceUserMobileResource::collection($invoices);
    //                 event(new OrderUpdated($data));
    //             }
    //             if($invoice->user_id != null)
    //             {
    //                 $orders = Invoice::with('orders')->with('address')->whereUserId($invoice->user_id)->where(function($query4){
    //                     $query4->whereDate('created_at', now()->toDateString())->orWhereDate('created_at', Carbon::yesterday()->toDateString());
    //                 })->whereIn('status', [0, 1, 2, 5, 6, 7])->latest()->get();
    //                 $data = InvoiceUserResource::collection($orders);
    //                 event(new NewOrder($data));
    //             }
    //             return $this->messageSuccessResponse(trans('locale.successfully'),200);
    //         }
    //         $invoice = Invoice::with('orders')->whereId($data_val['id'])->first();
    //         if($invoice->delivery_id != null)
    //         {
    //             $invoices = Invoice::with('orders')->whereIn('status', [1, 2])->whereRestaurantId($invoice->restaurant_id)->whereDeliveryId($invoice->delivery_id)->get();
    //             $data = InvoiceUserMobileResource::collection($invoices);
    //             event(new OrderUpdated($data));
    //             $invoices = Invoice::with('orders')->where('status', 5)->whereRestaurantId($invoice->restaurant_id)->whereDeliveryId($invoice->delivery_id)->get();
    //             $data = InvoiceUserMobileResource::collection($invoices);
    //             event(new OrderUpdated($data));
    //             $invoices = Invoice::with('orders')->whereIn('status', [3, 4])->whereRestaurantId($invoice->restaurant_id)->whereDeliveryId($invoice->delivery_id)->get();
    //             $data = InvoiceUserMobileResource::collection($invoices);
    //             event(new OrderUpdated($data));
    //         }
    //         if($invoice->user_id != null)
    //         {
    //             $orders = Invoice::with('orders')->with('address')->whereUserId($invoice->user_id)->where(function($query4){
    //                 $query4->whereDate('created_at', now()->toDateString())->orWhereDate('created_at', Carbon::yesterday()->toDateString());
    //             })->whereIn('status', [0, 1, 2, 5, 6, 7])->latest()->get();
    //             $data = InvoiceUserResource::collection($orders);
    //             event(new NewOrder($data));
    //         }
    //         return $this->messageErrorResponse(trans('locale.youCantDoThisOperation'),400);
    //     } catch(Throwable $th){
    //         $message = $th->getMessage();
    //         return $this->messageErrorResponse($message);
    //     }
    // }
    public function updateStatusOrder(StatusInvoiceRequest $request): JsonResponse
    {
        try {
            $validatedData = $request->validated();
            $status = InvoiceStatus::fromString($validatedData['status']);

            // 1. Find the invoice securely, ensuring it belongs to the admin's restaurant.
            $invoice = Invoice::where('id', $validatedData['id'])
                ->where('restaurant_id', auth()->user()->restaurant_id)
                ->firstOrFail();

            // 
            // 2. Update the invoice status. This action will automatically trigger the queued observer.
            $invoice->status = $status->value;
            if ($request->has('delivery_id')) {
                $invoiceWithDelivery = Invoice::where('restaurant_id', auth()->user()->restaurant_id)
                    ->where('delivery_id', $request->delivery_id)
                    ->where('status', InvoiceStatus::COMPLETED->value)
                    ->first();
                dd($invoiceWithDelivery);
                if ($invoiceWithDelivery->status != 6) {
                    return response()->json([
                        'status' => false,
                        'message' => trans('locale.delivery')
                    ], 500);
                }
                dd("Dsdsd");

                // if ($invoices) {
                //     return response()->json([
                //         'status' => false,
                //         'message' => trans('locale.delivery')
                //     ], 500);
                // }
                $invoice->delivery_id = $request->delivery_id;
            }
            $invoice->save();
            // event(new InvoiceStatusUpdated($invoice));
            Log::info($invoice);

            // 3. Update the status of related orders. This is a fast operation.
            $orderStatus = $this->getOrderStatusFromInvoiceStatus($status);
            if ($orderStatus !== null) {
                Order::where('invoice_id', $validatedData['id'])->update(['status' => $orderStatus]);
            }

            // The controller's job is done. It returns an immediate success response.
            return $this->messageSuccessResponse(trans('locale.successfully'), 200);
        } catch (\Throwable $th) {
            Log::error('--- updateStatusOrder Controller Error ---', ['message' => $th->getMessage()]);
            return $this->messageErrorResponse('An error occurred: ' . $th->getMessage());
        }
    }

    /**
     * A simple helper to map InvoiceStatus to Order status.
     */
    private function getOrderStatusFromInvoiceStatus(InvoiceStatus $status): ?string
    {
        return match ($status) {
            InvoiceStatus::APPROVED => 'accepted',
            InvoiceStatus::PROCESSING => 'preparation',
            InvoiceStatus::UNDER_DELIVERY => 'done',
            default => null,
        };
    }
    /**
     * Handles sending WebSocket updates to the delivery driver.
     */
    private function sendDeliveryDriverUpdates($invoice, $adminUser)
    {
        Log::info('--- sendDeliveryDriverUpdates: Checking for delivery driver ---');
        if ($invoice->delivery_id != null) {
            Log::info('Delivery driver found. Preparing to send OrderUpdated event.');
            $deliveryInvoices = Invoice::with('orders')
                ->whereIn('status', [
                    InvoiceStatus::WAITING->value,
                    InvoiceStatus::APPROVED->value,
                    InvoiceStatus::PROCESSING->value,
                    InvoiceStatus::UNDER_DELIVERY->value,
                ])
                ->whereRestaurantId($adminUser->restaurant_id)
                ->whereDeliveryId($invoice->delivery_id)
                ->get();

            event(new OrderUpdated(InvoiceUserMobileResource::collection($deliveryInvoices)));
            Log::info('OrderUpdated event dispatched for delivery_id: ' . $invoice->delivery_id);
        }
    }

    /**
     * Handles sending notifications (Firebase) and real-time events (WebSocket) to the customer.
     */
    private function sendUserNotifications($invoice, $status, $adminUser)
    {
        Log::info('--- sendUserNotifications: Checking for customer ---');
        if ($invoice->user_id != null) {
            Log::info('Customer found for user_id: ' . $invoice->user_id);

            // --- Part 1: Send Firebase Push Notification ---
            try {
                $customer = $invoice->user;
                $fcmToken = $customer?->fcm_token;

                if ($customer && $fcmToken) {
                    Log::info('Customer has FCM token. Preparing Firebase notification.');
                    $title = "Your Order Status Updated";
                    $formattedStatus = ucfirst(str_replace('_', ' ', strtolower($status->name)));
                    $body = "Your order #{$invoice->num} is now {$formattedStatus}.";
                    $this->firebaseService->sendNotification($fcmToken, $title, $body, []);
                    Log::info('Firebase notification sent to user_id: ' . $invoice->user_id);
                }
            } catch (\Exception $e) {
                Log::error('Firebase Notification Failed: ' . $e->getMessage());
            }

            // --- Part 2: Send Real-Time WebSocket Event ---
            Log::info('Preparing to send NewOrder event.');
            $userOrders = Invoice::with(['orders.restaurant', 'address', 'user', 'delivery', 'deliveryRoute'])
                ->whereUserId($invoice->user_id)
                ->whereDate('created_at', '>=', Carbon::yesterday())
                ->whereIn('status', [
                    InvoiceStatus::REJECTED->value,
                    InvoiceStatus::WAITING->value,
                    InvoiceStatus::APPROVED->value,
                    InvoiceStatus::PROCESSING->value,
                    InvoiceStatus::UNDER_DELIVERY->value,
                    InvoiceStatus::COMPLETED->value,
                ])
                ->latest()
                ->get();

            $userTypeId = $adminUser->type_id;
            event(new NewOrder($userOrders, $userTypeId));
            Log::info('NewOrder event dispatched for user_id: ' . $invoice->user_id);
        }
    }

    public function showProfile()
    {
        $user = auth()->user();
        $data = DeliveryResource::make($user);
        return $this->successResponse($data, trans('locale.foundSuccessfully'), 200);
    }
}
