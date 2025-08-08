<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Coupon\AddCouponRequest;
use App\Http\Requests\Coupon\IdCouponRequest;
use App\Http\Requests\Coupon\UpdateCouponRequest;
use App\Http\Resources\CouponResource;
use App\Services\CouponService;
use Illuminate\Http\Request;
use Throwable;

class CouponController extends Controller
{
    public function __construct(private CouponService $couponService)
    {
    }

    public function showAll(Request $request)
    {
        try{
            $admin = auth()->user();
            $coupons= $this->couponService->paginate($admin->restaurant_id,$request->input('per_page', 10));
            if (\count($coupons) == 0) {
                return $this->successResponse([],trans('locale.dontHaveCoupons'),200);
            }
            $data = CouponResource::collection($coupons);
            return $this->paginateSuccessResponse($data,trans('locale.foundSuccessfully'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    public function create(AddCouponRequest $request)
    {
        try{
            $restaurant_id = auth()->user()->restaurant_id;
            $table = $this->couponService->create($restaurant_id,$request->validated());
            $data = CouponResource::make($table);
            return $this->successResponse($data,trans('locale.created'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    public function update(UpdateCouponRequest $request)
    {
        try{
            $admin = auth()->user();
            $item = $this->couponService->update($admin->restaurant_id,$request->validated());
            if($item == 0)
            {
                return $this->messageErrorResponse(trans('locale.invalidItem'),403);
            }
            $coupon = $this->couponService->show($admin->restaurant_id,$request->validated());
            $data = CouponResource::make($coupon);
            return $this->successResponse($data,trans('locale.updated'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    public function showById(IdCouponRequest $request)
    {
        try{
            $admin = auth()->user();
            $coupon = $this->couponService->show($admin->restaurant_id,$request->validated());
            $data = CouponResource::make($coupon);
            return $this->successResponse($data,trans('locale.foundSuccessfully'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    public function delete(IdCouponRequest $request)
    {
        try{
            $restaurant_id = auth()->user()->restaurant_id;
            $coupon = $this->couponService->destroy($request->validated(),$restaurant_id);
            if($coupon == 0)
            {
                return $this->messageErrorResponse(trans('locale.invalidItem'),403);
            }
            return $this->messageSuccessResponse(trans('locale.deleted'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    public function deactivate(IdCouponRequest $request)
    {
        try{
            $data_val = $request->validated();
            $admin = auth()->user();
            $coupon = $this->couponService->show($admin->restaurant_id, $data_val);
            $data = $this->couponService->activeOrDesactive($coupon);
            if($data == 0)
            {
                return $this->messageErrorResponse(trans('locale.invalidItem'),403);
            }
            return $this->messageSuccessResponse(trans('locale.successfully'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }
}
