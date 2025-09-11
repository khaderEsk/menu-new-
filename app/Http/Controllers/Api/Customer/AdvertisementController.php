<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Advertisement\IdRestaurantRequest;
use App\Http\Requests\Advertisement\ShowAdvertisementRequest;
use App\Http\Requests\Item\IdRequest;
use App\Http\Resources\AdvertisementResources;
use App\Services\AdvertisementService;
use Illuminate\Http\Request;
use Throwable;

class AdvertisementController extends Controller
{
    public function __construct(private AdvertisementService $advertisementService)
    {
    }

    // Show All Advertisements For Restaurant
    public function showAll(ShowAdvertisementRequest $request)
    {
        try{
            $data = $request->validated();
            $advertisements= $this->advertisementService->paginate($data['restaurant_id'],$request->input('per_page', 25));
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

    // Show Advertisement By Id
    public function showById(IdRestaurantRequest $request)
    {
        try{
            $data = $request->validated();
            $advertisement = $this->advertisementService->show($data['restaurant_id'],$request->validated());
            $data = AdvertisementResources::make($advertisement);
            return $this->SuccessResponse($data,trans('locale.advertisementFound'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }
}
