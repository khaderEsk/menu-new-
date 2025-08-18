<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CitySuperAdmin\CreateBossRequest;
use App\Http\Requests\CitySuperAdmin\CreateRequest;
use App\Http\Requests\CitySuperAdmin\IdRequest;
use App\Http\Requests\CitySuperAdmin\ShowAllRequest;
use App\Http\Requests\CitySuperAdmin\UpdateRequest;
use App\Http\Requests\RestaurantManager\IdRequest as RestaurantManagerIdRequest;
use App\Http\Requests\RestaurantManager\UpdateRequest as RestaurantManagerUpdateRequest;
use App\Http\Resources\AdminResource;
use App\Http\Resources\CitySuperAdminResource;
use App\Models\Admin;
use App\Models\Restaurant;
use App\Models\SuperAdmin;
use App\Services\SuperAdminService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Throwable;

class AdminController extends Controller
{
    public function __construct(private SuperAdminService $superAdminService)
    {
    }

    // Show All City Super Admins
    public function showAll(ShowAllRequest $request)
    {
        try{
            $admin = auth()->user();
            $data = $request->validated();
            if($admin->hasAnyRole(['citySuperAdmin', 'dataEntry']))
            {
                $SuperAdmins = SuperAdmin::where('id','!=',1)->where('id','!=',$admin->id)->whereCityId($admin->city_id)->with('city')->with('permissions')->latest()->get();
                if (\count($SuperAdmins) == 0) {
                    return $this->successResponse([],trans('locale.doNotHaveCitySuperAdminDataEntry'),200);
                }

                $query = SuperAdmin::query();

                if ($request->has('role')) {
                    $query->role($request->role);
                }

                if ($request->has('search')) {
                    $search = $request->search;
                    $query->where('name','like', "%$search%");
                }

                if ($request->has('active')) {
                    $query->where('is_active', $request->active);
                }
                if($admin->city_id != null)
                {
                    $restaurant = $query->with('city')->where('id','!=',1)->where('id','!=',$admin->id)->whereCityId($admin->city_id)->paginate($request->input('per_page', 25));
                    $data = CitySuperAdminResource::collection($restaurant);
                    return $this->paginateSuccessResponse($data,trans('locale.restaurantFound'),200);
                }
                else
                {
                    if ($request->has('city_id')) {
                        $query->where('city_id', $request->city_id);
                    }
                    $restaurant = $query->where('id','!=',1)->where('id','!=',$admin->id)->paginate($request->input('per_page', 25));
                    $data = CitySuperAdminResource::collection($restaurant);
                    return $this->paginateSuccessResponse($data,trans('locale.restaurantFound'),200);
                }
            }

            $dataAdmin =  $this->superAdminService->all();
            if (\count($dataAdmin) == 0) {
                return $this->successResponse([],trans('locale.doNotHaveCitySuperAdminDataEntry'),200);
            }

            $query = SuperAdmin::query();

            if ($request->has('role')) {
                $query->role($request->role);
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where('name','like', "%$search%");
            }

            if ($request->has('active')) {
                $query->where('is_active', $request->active);
            }

            if ($request->has('city_id')) {
                $query->where('city_id', $request->city_id);
            }

            $restaurant = $query->where('id','!=',1)->with('city')->paginate($request->input('per_page', 25));
            $data = CitySuperAdminResource::collection($restaurant);
            return $this->paginateSuccessResponse($data,trans('locale.restaurantFound'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }

    }

    // Add City Super Admin
    public function create(CreateRequest $request)
    {
        try{
            $data = $request->validated();
            $admin = auth()->user();
            if($admin->hasAnyRole(['citySuperAdmin', 'dataEntry']))
            {
                if($admin->city_id != null)
                {
                    if($data['city_id'] != $admin->city_id)
                        return $this->messageErrorResponse(trans('locale.youCantAddOtherCity'));
                }
            }
            $superAdmin = $this->superAdminService->create($request->validated());
            $data = CitySuperAdminResource::make($superAdmin);
            return $this->SuccessResponse($data,trans('locale.created'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Active Or DisActive City Super Admin
    public function deactivate(IdRequest $request)
    {
        try{
            if($request->id == 1)
            {
                return $this->messageErrorResponse(trans('locale.invalidItem'),403);
            }
            $admin = auth()->user();

            $superAdmin = $this->superAdminService->show($request->id);
            if($admin->hasAnyRole(['citySuperAdmin', 'dataEntry']))
            {
                if($admin->city_id != null)
                {
                    if($superAdmin->city_id != $admin->city_id)
                        return $this->messageErrorResponse(trans('locale.youCantDeactivateOtherCity'));
                }
            }

            $item = $this->superAdminService->activeOrDesactive($superAdmin,$admin->id);
            if($item == 0)
            {
                return $this->messageErrorResponse(trans('locale.invalidItem'),403);
            }
            return $this->messageSuccessResponse("Successfully",200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Update City Super Admin Data
    public function update(UpdateRequest $request)
    {
        try{
            if($request->id == 1)
            {
                return $this->messageErrorResponse(trans('locale.invalidItem'),403);
            }
            $super = $this->superAdminService->show($request->id);
            $admin = auth()->user();
            if($admin->hasAnyRole(['citySuperAdmin', 'dataEntry']))
            {
                if($admin->city_id != null)
                {
                    if($super->city_id != $admin->city_id)
                        return $this->messageErrorResponse(trans('locale.youCantUpdateOtherCity'));

                }
            }
            $arrAdmin = Arr::only($request->validated(),
            ['id','name','password','user_name','city_id','restaurant_id']);

            $arrRole = Arr::only($request->validated(),
            ['role','permission']);

            $citySuper = $this->superAdminService->update($arrRole,$arrAdmin);
            if($citySuper == 0)
            {
                return $this->messageErrorResponse(trans('locale.invalidItem'),403);
            }

            if ($super) {
                $super->tokens()->delete();
            }
            $super = $this->superAdminService->show($request->id);
            $data = CitySuperAdminResource::make($super);
            return $this->successResponse($data,trans('locale.updated'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Show City Super Admin By Id
    public function showById(IdRequest $request)
    {
        try{
            $admin = auth()->user();
            $superAdmin = $this->superAdminService->show($request->id);
            if($admin->hasAnyRole(['citySuperAdmin', 'dataEntry']))
            {
                if($admin->city_id != null)
                {
                    if($superAdmin->city_id != $admin->city_id)
                        return $this->messageErrorResponse(trans('locale.youCantShowOtherCity'));
                }
            }
            $data = CitySuperAdminResource::make($superAdmin);
            return $this->successResponse($data,trans('locale.superAdminFoundSuccessfully'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Delete
    public function delete(IdRequest $request)
    {
        try{
            $admin = auth()->user();

            if($admin->hasAnyRole(['citySuperAdmin', 'dataEntry']))
            {
                if($admin->city_id != null)
                {
                    $super = $this->superAdminService->show($request->id);
                    if($super->city_id != $admin->city_id)
                        return $this->messageErrorResponse(trans('locale.youCantDeletedRestaurantOtherCity'));
                }
            }
            $admin = auth()->user()->id;
            if($request->id == 1)
            {
                return $this->messageErrorResponse(trans('locale.invalidItem'),403);
            }
            if($admin == $request->id)
            {
                return $this->messageErrorResponse(trans('locale.youCantDeleted'),403);
            }
            $data = $this->superAdminService->destroy($request->id,$admin);
            if($data == 0)
            {
                return $this->messageErrorResponse(trans('locale.invalidItem'),403);
            }
            return $this->messageSuccessResponse(trans('locale.deleted'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // --------------------------------------------

    // Show restaurant Manager
    public function showAllBoss(Request $request)
    {
        try{
            $admin = auth()->user();
            if($admin->hasAnyRole(['citySuperAdmin', 'dataEntry']))
            {
                if($admin->city_id != null)
                {
                    $paginatedRestaurants = Restaurant::whereCityId($admin->city_id)->with('admin')->paginate($request->input('per_page', 25));
                    // $superAdmin = $paginatedRestaurants->map(function ($restaurant) {
                    //     return $restaurant->admin;
                    // })->filter();
                    //

                    // ---------------------
                    $superAdmin = $paginatedRestaurants->map(function ($restaurant) {
                        return $restaurant->admin;
                    })->filter(function ($admin) {
                        return !is_null($admin);
                    })->unique('id')->values();
                    // ------------------------
                    $unlinkedAdmins = Admin::role('restaurantManager')->whereDoesntHave('restaurants')->get();

                    $allAdmins = $superAdmin->concat($unlinkedAdmins)->unique('id')->values();
                    // -------------------------
                    $data = AdminResource::collection($allAdmins);
                    $meta = [
                        'current_page' => $paginatedRestaurants->currentPage(),
                        'last_page' => $paginatedRestaurants->lastPage(),
                        'per_page' => $paginatedRestaurants->perPage(),
                        'total' => $paginatedRestaurants->total(),
                        'count' => $paginatedRestaurants->count(),
                        'total_pages' => $paginatedRestaurants->lastPage(),
                    ];
                    return response()->json(['status' => true,'data' => $data,'meta' => $meta,'message' => trans('locale.restaurantManagerFoundSuccessfully')],200);

                }
            }
            else
                $superAdmin = $this->superAdminService->allBoss($request->input('per_page', 25));
                $data = AdminResource::collection($superAdmin);
                return $this->paginateSuccessResponse($data,trans('locale.restaurantManagerFoundSuccessfully'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Add restaurant Manager
    public function createBoss(CreateBossRequest $request)
    {
        try{
            $superAdmin = $this->superAdminService->createBoss($request->validated());
            $data = AdminResource::make($superAdmin);
            return $this->SuccessResponse($data,trans('locale.created'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Update Restaurant Manager
    public function updateBoss(RestaurantManagerUpdateRequest $request)
    {
        try{
            $arrAdmin = $request->validated();
            $citySuper = $this->superAdminService->updateBoss($arrAdmin);
            if($citySuper == 0)
            {
                return $this->messageErrorResponse(trans('locale.invalidItem'),403);
            }
            $admin = $this->superAdminService->showBoss($request->id);

            if ($admin) {
                $admin->tokens()->delete();
            }

            $data = AdminResource::make($admin);
            return $this->successResponse($data,trans('locale.updated'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Show Restaurant Manager By id
    public function showByIdBoss(RestaurantManagerIdRequest $request)
    {
        try{
            $admin = $this->superAdminService->showBoss($request->id);
            $data = AdminResource::make($admin);
            return $this->successResponse($data,trans('locale.superAdminFoundSuccessfully'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Delete Restaurant Manager
    public function deleteBoss(RestaurantManagerIdRequest $request)
    {
        try{
            if($request->id == 1)
            {
                return $this->messageErrorResponse(trans('locale.invalidItem'),403);
            }
            $data = $this->superAdminService->destroyBoss($request->id);
            if($data == 0)
            {
                return $this->messageErrorResponse(trans('locale.invalidItem'),403);
            }
            return $this->messageSuccessResponse(trans('locale.deleted'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Active Or DisActive  Restaurant Manager
    public function deactivateBoss(RestaurantManagerIdRequest $request)
    {
        try{
            $admin = $this->superAdminService->showBoss($request->id);
            if($request->id == 1)
            {
                return $this->messageErrorResponse(trans('locale.invalidItem'),403);
            }
            $item = $this->superAdminService->activeOrDesactiveBoss($admin);
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
}
