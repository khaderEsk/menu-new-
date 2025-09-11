<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmployeeTable\ShowRequest;
use App\Http\Resources\Admin\OrderRequestResource;
use App\Models\EmployeeTable;
use Throwable;

class OrderRequestController extends Controller
{
    public function showAll(ShowRequest $request)
    {
        try{
            $admin = auth()->user();
            $orders = EmployeeTable::whereRestaurantId($admin->restaurant_id)->paginate($request->input('per_page', 10));
            if (\count($orders) == 0) {
                return $this->successResponse([],trans('locale.dontHaveData'),200);
            }
            
            $query = EmployeeTable::query();
            
            if ($request->has('search')) {
                $query->whereHas('admin', function ($q) use ($request) {
                    $q->where('name','like', "%$request->search%");
                });
            }
            // if ($request->has('type')) {
                //     $query->whereHas('admin', function ($q) use ($request) {
                    //         $q->whereType($request->type);
            //     });
            // }
            if ($request->has('type')) {
                if($request->type == 'waiter')
                    $type = 5;
                elseif($request->type == 'shisha')
                    $type = 6;
                $query->whereHas('admin', function ($q) use ($type) {
                    $q->whereTypeId($type);
                });
            }
            if ($request->has('table_id')) {
                $query->where('table_id', $request->table_id);
            }
            if ($request->has('emp_id')) {
                $query->where('admin_id', $request->emp_id);
            }
            
            if ($request->has('date')) {
                $query->whereDate('created_at', $request->date);
            }
            $order = $query->whereRestaurantId($admin->restaurant_id)->paginate($request->input('per_page', 10));
            $data = OrderRequestResource::collection($order);
            return $this->paginateSuccessResponse($data,trans('locale.successfully'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }
}
