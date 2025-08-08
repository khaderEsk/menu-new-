<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Notification\ShowRequest;
use App\Http\Resources\DeliveryResource;
use App\Http\Resources\NotificationResource;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use LDAP\Result;
use Throwable;

class NotificationController extends Controller
{
    public function __construct(private NotificationService $notificationService)
    {
    }
    public function showAll(ShowRequest $request)
    {
        try{
            $restaurant_id = auth()->user()->restaurant_id;
            $notifications = $this->notificationService->paginate($restaurant_id,$request->input('per_page', 25));
            $data = NotificationResource::collection($notifications);
            if (\count($notifications) == 0)
                return $this->paginateSuccessResponse($data,trans('locale.dontHaveNotifications'),200);

            return $this->paginateSuccessResponse($data,trans('locale.notificationsFound'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    public function sendNotification(ShowRequest $request)
    {
        try{

            $restaurant_id = auth()->user()->restaurant_id;
            $query = User::query();

            $query->where('restaurant_id', $restaurant_id);

            if ($request->has('gender')) {
                $query->where('gender', $request->gender);
            }

            if ($request->has('address')) {
                $address = $request->input('address');
                $query->whereHas('addresses', function ($q) use ($address) {
                    $q->where('region', 'LIKE', "%$address%")
                     ->orWhere('url', 'LIKE', "%$address%")
                     ->orWhere('city', 'LIKE', "%$address%");
                })->get();
            }

            if ($request->has('from_age')) {
                $query->whereHas('customer', function ($q) use ($request) {
                    $q->where('birthday', '>=', $request->from_age)->where('birthday', '<=', $request->to_age);
                });
            }

            if ($request->has('from_date') || $request->has('to_date')) {
                if($request->has('from_date') && $request->has('to_date'))
                {
                    $query->whereDate('birthday', '>=', $request->from_date)->whereDate('created_at', '<=', $request->to_date);
                }
                else if ($request->has('from_date'))
                {
                    $query->whereDate('birthday', '>=', $request->from_date);
                }
                else if ($request->has('to_date'))
                {
                    $query->whereDate('birthday', '<=', $request->to_date);
                }
            }

            $users = $query->latest()->paginate($request->input('per_page', 25));
            $data = DeliveryResource::collection($users);
            return $this->paginateSuccessResponse($data,trans('locale.foundSuccessfully'),200);

        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }
}
