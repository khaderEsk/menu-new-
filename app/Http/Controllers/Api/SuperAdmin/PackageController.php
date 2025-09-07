<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Package\AddRequest;
use App\Http\Requests\Package\CreateSubscriptionRequest;
use App\Http\Requests\Package\IdRequest;
use App\Http\Requests\Package\ShowRequest;
use App\Http\Requests\Package\UpdateRequest;
use App\Http\Requests\Restaurant\IdRequest as RestaurantIdRequest;
use App\Http\Resources\PackageResource;
use App\Http\Resources\SubscriptionResource;
use App\Models\Package;
use App\Models\Restaurant;
use App\Services\PackageService;
use Illuminate\Http\Request;
use Throwable;

class PackageController extends Controller
{
    public function __construct(private PackageService $packageService)
    {
    }

    // Show All package For Admin
    public function showAll(ShowRequest $request)
    {
        try{
            $admin = auth()->user();
            $data = $request->validated();
            $where = [];
            $packages= $this->packageService->paginate($request->input('per_page', 25));
            if (\count($packages) == 0) {
                return $this->successResponse([],trans('locale.dontHavePackages'),200);
            }
            // Filter Active
            if(\array_key_exists('is_active',$data))
            {
                $where = \array_merge($where,['is_active'=> $data['active']]);
                $package =  $this->packageService->search($where,$request->input('per_page', 25));
                $data = PackageResource::collection($package);
                return $this->paginateSuccessResponse($data,trans('locale.packagesFound'),200);
            }
            $package = PackageResource::collection($packages);
            return $this->paginateSuccessResponse($package,trans('locale.packagesFound'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Add package
    public function create(AddRequest $request)
    {
        try{
            $package = $this->packageService->create($request->validated());
            $data = PackageResource::make($package);
            return $this->successResponse($data,trans('locale.created'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Update packages
    public function update(UpdateRequest $request)
    {
        try{
            $package = $this->packageService->update($request->validated());
            if($package == 0)
            {
                return $this->messageErrorResponse(trans('locale.invalidItem'),403);
            }
            $package = Package::whereId($request->id)->first();
            $data = PackageResource::make($package);
            return $this->successResponse($data,trans('locale.updated'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Show package By Id
    public function showById(IdRequest $request)
    {
        try{
            $package = $this->packageService->show($request->validated());
            $data = PackageResource::make($package);
            return $this->successResponse($data,trans('locale.packagesFound'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Delete package
    public function delete(IdRequest $request)
    {
        try{
            $package = $this->packageService->destroy($request->id);
            if($package == 0)
            {
                return $this->messageErrorResponse(trans('locale.invalidItem'),403);
            }
            return $this->messageSuccessResponse(trans('locale.deleted'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Active Or DisActive Emoji
    public function deactivate(IdRequest $request)
    {
        try{
            $package = $this->packageService->show($request->validated());
            $active = $this->packageService->activeOrDesactive($package);
            if($active == 0)
            {
                return $this->messageErrorResponse(trans('locale.invalidItem'),403);
            }
            return $this->messageSuccessResponse(trans('locale.successfully'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // create Subscription
    public function createSubscription(CreateSubscriptionRequest $request)
    {
        try{
            $data = $request->validated();
            $admin = auth()->user();
            if($admin->hasAnyRole(['citySuperAdmin', 'dataEntry']))
            {
                if($admin->city_id != null)
                {
                    $restaurant = Restaurant::findOrFail($data['restaurant_id']);
                    if($restaurant->city_id != $admin->city_id)
                        return $this->messageErrorResponse(trans('locale.youCantAddSubscriptionOtherCity'));
                }
            }

            $subscription = $this->packageService->subscription($request->validated());
            return $this->successResponse($subscription,trans('locale.created'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // show By Id Subscription
    public function showByIdSubscription(RestaurantIdRequest $request)
    {
        try{
            $subscription = $this->packageService->showRestaurantSubscription($request->validated());
            $data = SubscriptionResource::make($subscription);

            return $this->successResponse($data,trans('locale.successfully'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }
}
