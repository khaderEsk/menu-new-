<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CitySuperAdmin\CreateRequest;
use App\Http\Requests\CitySuperAdmin\IdRequest;
use App\Http\Requests\CitySuperAdmin\ShowAllRequest;
use App\Http\Requests\CitySuperAdmin\UpdateRequest;
use App\Http\Resources\CitySuperAdminResource;
use App\Services\CitySuperAdminService;
use Illuminate\Http\Request;
use Throwable;

class CitySuperController extends Controller
{
    public function __construct(private CitySuperAdminService $citySuperAdminService)
    {
    }

    // Show All City Super Admins
    public function showAll(ShowAllRequest $request)
    {
        try{
            $data =  $this->citySuperAdminService->all();
            if (\count($data) == 0) {
                return $this->successResponse([],"Dont Have City Super Admin",200);
            }

            $data = $request->validated();
            $where = [];

            // Filter search
            if(\array_key_exists('search',$data))
                $where = \array_merge($where,[['name','like','%'.$data['search'].'%']]);
            // Filter Active
            if(\array_key_exists('active',$data))
                $where = \array_merge($where,['is_active'=> $data['active']]);
            // Filter By City
            if(\array_key_exists('city_id',$data))
                $where = \array_merge($where,['city_id'=> $data['city_id']]);

            if(\array_key_exists('search',$data) || \array_key_exists('active',$data) || \array_key_exists('city_id',$data))
            {
                $citySuperAdmin =  $this->citySuperAdminService->search($where,$request->input('per_page', 25));
                $data = CitySuperAdminResource::collection($citySuperAdmin);
                return $this->paginateSuccessResponse($data,"City Super Admin Found Successfully",200);
            }

            $citySuperAdmin = $this->citySuperAdminService->paginate($request->input('per_page', 25));
            $data = CitySuperAdminResource::collection($citySuperAdmin);
            return $this->paginateSuccessResponse($data,"City Super Admin Found Successfully",200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }

    }

    // Add City Super Admin
    public function create(CreateRequest $request)
    {
        try{
            $this->citySuperAdminService->create($request->validated());
            return $this->messageSuccessResponse("City Super Admin Created Successfully",200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Active Or DisActive City Super Admin
    public function deactivate(IdRequest $request)
    {
        try{
            $admin = auth()->user()->id;
            $citySuperAdmin = $this->citySuperAdminService->show($request->id);
            $item = $this->citySuperAdminService->activeOrDesactive($citySuperAdmin,$admin);
            if($item == 0)
            {
                return $this->messageErrorResponse("Invalid Item",403);
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
            $citySuper = $this->citySuperAdminService->update($request->validated());
            if($citySuper == 0)
            {
                return $this->messageErrorResponse("Invalid Item",403);
            }
            return $this->messageSuccessResponse("City Super Admin Updated Successfully",200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Show City Super Admin By Id
    public function showById(IdRequest $request)
    {
        try{
            $citySuperAdmin = $this->citySuperAdminService->show($request->id);
            $data = CitySuperAdminResource::make($citySuperAdmin);
            return $this->successResponse($data,"city Super Admin Found Successfully",200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }
}
