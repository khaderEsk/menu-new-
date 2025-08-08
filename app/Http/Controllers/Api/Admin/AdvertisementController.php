<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Advertisement\AddRequest;
use App\Http\Requests\Advertisement\IdRequest;
use App\Http\Requests\Advertisement\ShowRequest;
use App\Http\Requests\Advertisement\UpdateRequest;
use App\Http\Resources\AdvertisementResources;
use App\Models\Advertisement;
use App\Services\AdvertisementService;
use Illuminate\Support\Arr;
use Throwable;

class AdvertisementController extends Controller
{
    public function __construct(private AdvertisementService $advertisementService)
    {
    }

    // Show All Advertisements For Admin
    public function showAll(ShowRequest $request)
    {
        try{
            $admin = auth()->user();
            $advertisements= $this->advertisementService->paginate($admin->restaurant_id,$request->input('per_page', 25));
            if (\count($advertisements) == 0) {
                return $this->successResponse([],trans('locale.dontHaveAdvertisements'),200);
            }
            $data = AdvertisementResources::collection($advertisements);
            return $this->paginateSuccessResponse($data,trans('locale.advertisementsFound'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Add Advertisement
    public function create(AddRequest $request)
    {
        try{
            $restaurant_id = auth()->user()->restaurant_id;
            $advertisement = $this->advertisementService->create($restaurant_id,$request->validated());
            if ($request->hasFile('image'))
            {
                $extension = $request->file('image')->getClientOriginalExtension();
                $randomFileName = str()->random(10) . '.' . $extension;
                $advertisement->addMediaFromRequest('image')->usingFileName($randomFileName)->toMediaCollection('advertisement');
            }
            $data = AdvertisementResources::make($advertisement);
            return $this->successResponse($data,trans('locale.created'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Update Advertisement => Title And To Date
    public function update(UpdateRequest $request)
    {
        try{
            $admin = auth()->user();
            $arrData = Arr::only($request->validated(),['id','title','from_date','to_date','is_panorama','hide_date']);
            $item = $this->advertisementService->update($admin->restaurant_id,$arrData);
            if($item == 0)
            {
                return $this->messageErrorResponse(trans('locale.invalidItem'),403);
            }
            $advertisement = $this->advertisementService->show($admin->restaurant_id,$request->validated());
            if ($request->hasFile('image'))
            {
                $advertisement->clearMediaCollection('advertisement');
                $extension = $request->file('image')->getClientOriginalExtension();
                $randomFileName = str()->random(10) . '.' . $extension;
                $advertisement->addMediaFromRequest('image')->usingFileName($randomFileName)->toMediaCollection('advertisement');
            }
            $data = AdvertisementResources::make($advertisement);
            return $this->successResponse($data,trans('locale.updated'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Show Advertisement By Id
    public function showById(IdRequest $request)
    {
        try{
            $admin = auth()->user();
            $advertisement = $this->advertisementService->show($admin->restaurant_id,$request->validated());
            $data = AdvertisementResources::make($advertisement);
            return $this->SuccessResponse($data,trans('locale.advertisementFound'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Delete Advertisement
    public function delete(IdRequest $request)
    {
        try{
            $restaurant_id = auth()->user()->restaurant_id;
            $done = Advertisement::whereRestaurantId($restaurant_id)->whereId($request->id)->first();
            $done->clearMediaCollection('advertisement');
            $advertisement = $this->advertisementService->destroy($request->id,$restaurant_id);
            if($advertisement == 0)
            {
                return $this->messageErrorResponse(trans('locale.invalidItem'),403);
            }
            return $this->messageSuccessResponse(trans('locale.deleted'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }
}
