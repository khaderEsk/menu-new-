<?php

namespace App\Http\Controllers\Api\User;

use App\Enum\InvoiceStatus;
use App\Events\NewOrder;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Requests\Invoice\acceptRequest;
use App\Http\Requests\Invoice\DeliveryUpdateStatusRequest;
use App\Http\Requests\Invoice\IdRequest;
use App\Http\Resources\InvoiceUserResource;
use App\Http\Resources\InvoiceUserMobileResource;
use App\Http\Resources\OrderResource;
use App\Http\Resources\RouteResource;
use Carbon\Carbon;
use App\Services\InvoiceService;
use App\Models\Invoice;
use App\Models\User;
use App\Services\FirebaseService;
use App\Services\GraphHopperService;
use App\Services\OsrmService;
use Throwable;

class OrderController extends Controller
{
    public function __construct(private InvoiceService $invoiceService, private FirebaseService $firebaseService) {}

    public function acceptOrder(AcceptRequest $request)
    {
        $user = auth()->user();
        $data_val = $request->validated();
        $order = $this->invoiceService->acceptOrder($user->id, $data_val);
    }

    public function showOrder()
    {
        // $user = auth()->user();
        // $orders= $this->invoiceService->showOrder($user->restaurant_id,$user->id,request()->input('per_page', 10));
        // if (\count($orders) == 0) {
        //     return $this->successResponse([],trans('locale.dontHaveOrder'),200);
        // }
        // $data = OrderResource::collection($orders);
        // return $this->paginateSuccessResponse($data,trans('locale.ordersFound'),200);
        try {
            $user = auth()->user();

            $query = Invoice::with('orders')->whereRestaurantId($user->restaurant_id)->whereDeliveryId($user->id);
            // dd(request()->status);
            if (request()->has('status')) {
                if (request()->status == "processing")
                    $query->whereIn('status', [1, 2]);

                elseif (request()->status == "under_delivery")
                    $query->where('status', 5);

                elseif (request()->status == "delivered")
                    $query->whereIn('status', 6);
            }

            if (request()->has('search'))
                $query->where('num', request()->search);

            if (request()->has('created_at'))
                $query->whereDate('created_at', request()->created_at);

            // if (request()->has('received')) {
            //     $query->whereIn('status', [3, 4]);
            // }
            // if (request()->has('not_received')) {
            //     $query->whereIn('status', [1, 2]);
            // }

            $invoices = $query->latest()->paginate(request()->input('per_page', 10));
            if (\count($invoices) == 0) {
                return $this->successResponse([], trans('locale.dontHaveOrder'), 200);
            }
            $data = InvoiceUserMobileResource::collection($invoices);

            // $user = auth()->user();
            // $invoices = $this->invoiceService->showOrder($user->restaurant_id,$user->id,request()->input('per_page', 10));
            // if (\count($invoices) == 0) {
            //     return $this->successResponse([],trans('locale.dontHaveOrder'),200);
            // }
            // $data = InvoiceUserResource::collection($invoices);
            return $this->paginateSuccessResponse($data, trans('locale.foundSuccessfully'), 200);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    public function updateOrder(DeliveryUpdateStatusRequest $request)
    {
        try {
            $admin = auth()->user();
            $userTypeId = $admin->type_id;
            $data = $request->validated();
            if (Invoice::find($request->id)->status == InvoiceStatus::COMPLETED) {
                return $this->messageErrorResponse(trans('locale.orderDelivered'), 403);
            }
            if ($request->status == 'under_delivery') {
                $data['status'] = 5;
                $admin->status = "busy";
                $admin->save();
            } elseif ($request->status == 'delivered') {
                $data['status'] = InvoiceStatus::COMPLETED;
                $data['customer_received_at'] = now();
                $admin->status = "available";
                $admin->save();
            }
            $invoice = $this->invoiceService->updateStatus($data);
            if ($invoice == 0) {
                return $this->messageErrorResponse(trans('locale.invalidItem'), 403);
            }
            $title = "تفاصيل الطلب";
            if ($request->status == 'under_delivery')
                $body = "طلبك في طريقه اليك";
            elseif ($request->status == 'delivered')
                $body = "تم استلام الطلب";
            $users = User::whereRestaurantId($admin->restaurant_id)->where('role', 0)->get();
            for ($i = 0; $i < count($users); $i++) {
                $firstElement = $users->get($i);
                if ($firstElement->fcm_token)
                    // dd($firstElement->fcm_token);
                    $this->firebaseService->sendNotification($firstElement->fcm_token, $title, $body, []);
            }

            $i = Invoice::with('orders')->whereId($data['id'])->first();
            if ($i->user_id != null) {
                $orders = Invoice::with('orders')->with('address')->whereUserId($i->user_id)->where(function ($query4) {
                    $query4->whereDate('created_at', now()->toDateString())->orWhereDate('created_at', Carbon::yesterday()->toDateString());
                })->whereIn('status', [0, 1, 2, 5, 6, 7])->latest()->get();
                $data = InvoiceUserResource::collection($orders);

                event(new NewOrder($data, $userTypeId));
            }
            return $this->messageSuccessResponse(trans('locale.updated'), 200);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    public function showOrderById(IdRequest $request)
    {
        try {
            $user = auth()->user();
            $invoice = Invoice::with('orders')->whereRestaurantId($user->restaurant_id)->findOrFail($request->id);
            $data = InvoiceUserMobileResource::make($invoice);
            return $this->successResponse($data, trans('locale.foundSuccessfully'), 200);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    public function rejectedOrder(IdRequest $request)
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

    public function showAll(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();

            // 1. Call the single, clean service method to get the paginated and processed invoices.
            $invoices = $this->invoiceService->getInvoicesForUser($user, $request->all());

            // 2. The service returns a complete paginator, ready for the resource.
            $data = InvoiceUserResource::collection($invoices);
            return $this->paginateSuccessResponse($data, trans('locale.foundSuccessfully'), 200);
        } catch (\Throwable $th) {
            report($th);
            return $this->messageErrorResponse('An error occurred while fetching your orders.');
        }
    }

    public function delete(IdRequest $request)
    {
        try {
            $data = $request->validated();
            $invoice = Invoice::whereId($data['id'])->first();
            if ($invoice->status == InvoiceStatus::WAITING) {
                $invoice->delete();
                return $this->messageSuccessResponse(trans('locale.deleted'), 200);
            }
            return $this->messageErrorResponse(trans('locale.youCantDeleted'), 403);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }
}
