<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Restaurant\IdRequest;
use App\Http\Requests\Restaurant\ShowRequest;
use App\Http\Resources\LoginRestaurantAdmin;
use App\Http\Resources\RestaurantResource;
use App\Models\Restaurant;
use App\Services\RestaurantService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Throwable;

class RestaurantController extends Controller
{
    public function __construct(private RestaurantService $restaurantService)
    {
    }

    // Show All Restaurant Pagination
    public function showMyRestaurants(ShowRequest $request)
    {
        try{
            $admin = auth()->user()->id;
            $data =  $this->restaurantService->allRestaurantManager($admin);
            if (\count($data) == 0)
                return $this->successResponse([],trans('locale.dontHaveRestaurants'),200);

            $data = $request->validated();

            // Filter By Search
            if(\array_key_exists('search',$data))
            {
                $data = $request->validated();
                $restaurant =  $this->restaurantService->searchRestaurantManager($admin,$data['search'],$request->input('per_page', 25));
                $restaurants = RestaurantResource::collection($restaurant);
                return $this->paginateSuccessResponse($restaurants,trans('locale.restaurantFound'),200);
            }

            $restaurant = $this->restaurantService->paginateRestaurantManager($admin,$request->input('per_page', 25));
            $data = RestaurantResource::collection($restaurant);
            return $this->paginateSuccessResponse($data,trans('locale.restaurantFound'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // update super admin restaurant id
    public function restaurantId(IdRequest $request)
    {
        try{
            $data = $request->validated();
            $admin = auth()->user();
            $restaurant = Restaurant::whereId($request->id)->first();
            if ($restaurant->end_date < Carbon::now()->toDateString()) {
                // return response()->json(['status' => false ,'message'=> trans('locale.')]);
                return $this->messageErrorResponse(trans('locale.restaurantHasExpired'),400);
            }
            if($restaurant->is_active == 0)
                return response()->json(['status' => false ,'message'=> trans('locale.blocked')]);
            $data = LoginRestaurantAdmin::make($restaurant);
            $super = $this->restaurantService->updateRestaurantIdAdmin($request->id,$admin->id);
            if($super == 0)
            {
                return $this->messageErrorResponse(trans('locale.invalidItem'),403);
            }
            return $this->successResponse($data,trans('locale.successfully'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }
}
