<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\City\AddCityRequest;
use App\Http\Requests\City\CityIdRequest;
use App\Http\Requests\City\SearchRequest;
use App\Http\Requests\City\UpdateCityRequest;
use App\Http\Resources\CityResource;
use App\Models\City;
use App\Services\CityService;
use Throwable;

class CityController extends Controller
{
    public function __construct(private CityService $cityService)
    {
    }

    public function showAll(SearchRequest $request)
    {
        try{
            $admin = auth()->user();
            $data = $request->validated();
            if($admin->hasAnyRole(['citySuperAdmin', 'dataEntry']))
            {
                if($admin->city_id != null)
                {
                    $city=City::whereId($admin->city_id)->get();
                    $data = CityResource::collection($city);
                    return $this->SuccessResponse($data,trans('locale.cityFound'),200);
                }
            }
            if($request->search)
            {
                $data = $request->validated();
                $city =  $this->cityService->search($data['search'],$request->input('per_page', 25));
                $cities = CityResource::collection($city);
                return $this->paginateSuccessResponse($cities,trans('locale.citiesFound'),200);
            }
            else
            {
                $cities = $this->cityService->all();
                if (\count($cities) == 0) {
                    return $this->successResponse([],trans('locale.doNotHaveCities'),200);
                }
                $cities = $this->cityService->paginate($request->input('per_page', 25));
                $data = CityResource::collection($cities);
                return $this->paginateSuccessResponse($data,trans('locale.citiesFound'),200);
            }
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }

    }

    public function create(AddCityRequest $request)
    {
        try{
            $city = $this->cityService->create($request->validated());
            $data = CityResource::make($city);
            return $this->successResponse($data,trans('locale.created'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    public function update(UpdateCityRequest $request)
    {
        try{
            $admin = auth()->user();
            if($admin->hasAnyRole(['citySuperAdmin', 'dataEntry']))
            {
                if($admin->city_id != null)
                {
                    if($request->id != $admin->city_id)
                    return $this->messageSuccessResponse(trans('locale.youCantUpdateCity'),200);
                }
            }
            $id = auth()->user()->id;
            $city = $this->cityService->update($id,$request->validated());
            $city = $this->cityService->show($request->id);
            $data = CityResource::make($city);
            return $this->successResponse($data,trans('locale.updated'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    public function activeOrNot(CityIdRequest $request)
    {
        try{
            $admin = auth()->user();
            if($admin->hasAnyRole(['citySuperAdmin', 'dataEntry']))
            {
                if($admin->city_id != null)
                {
                    if($request->id != $admin->city_id)
                    return $this->messageSuccessResponse(trans('locale.youCantDeActiveThisCity'),200);
                }
            }
            $admin = auth()->user()->id;
            $city = $this->cityService->show($request->id);
            $item = $this->cityService->activeOrDesactive($city);
            if($item == 0)
            {
                return $this->messageErrorResponse(trans('locale.invalidItem'),403);
            }
            return $this->messageSuccessResponse(trans('locale.successfully'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    public function delete(CityIdRequest $request)
    {
        try{
            $admin = auth()->user();
            if($admin->hasAnyRole(['citySuperAdmin', 'dataEntry']))
            {
                if($admin->city_id != null)
                {
                    if($request->id != $admin->city_id)
                    return $this->messageSuccessResponse(trans('locale.youCantDeleteThisCity'),200);
                }
            }
            $admin = auth()->user()->id;
            $city = $this->cityService->destroy($request->id);
            if($city == -10)
            {
                return $this->messageErrorResponse(trans('locale.youCantDeleteThisCityBecauseItHasRestaurant') ,403);
            }
            if($city == -5)
                return $this->messageErrorResponse(trans('locale.youCantDeleteThisCityBecauseItHasSuperAdmin') ,403);
            if($city == 0)
            {
                return $this->messageErrorResponse(trans('locale.invalidItem'),403);
            }
            return $this->messageSuccessResponse(trans('locale.deleted'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Show City By Id
    public function showById(CityIdRequest $request)
    {
        try{
            $admin = auth()->user();
            if($admin->hasAnyRole(['citySuperAdmin', 'dataEntry']))
            {
                if($admin->city_id != null)
                {
                    if($request->id != $admin->city_id)
                    return $this->messageSuccessResponse(trans('locale.youCantShowThisCity'),200);
                }
            }
            $city = $this->cityService->show($request->id);
            $data = CityResource::make($city);
            return $this->successResponse($data,trans('locale.cityFound'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }
}
