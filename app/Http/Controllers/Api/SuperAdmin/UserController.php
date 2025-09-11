<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AddRequest as AdminAddRequest;
use App\Http\Requests\AdminRestaurant\AddRequest;
use App\Http\Requests\AdminRestaurant\IdRequest;
use App\Http\Requests\AdminRestaurant\ShowAllRequest;
use App\Http\Requests\AdminRestaurant\UpdateRequest;
use App\Http\Resources\AdminResource;
use App\Models\Admin;
use App\Services\AdminRestaurantService;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Throwable;

class UserController extends Controller
{
    public function __construct(private AdminRestaurantService $userService)
    {
    }

    // Show All Admins
    public function showAll(ShowAllRequest $request)
    {
        try{
            $data = $request->validated();
            $dataAdmin =  $this->userService->all($data['restaurant_id']);
            if (\count($dataAdmin) == 0) {
                return $this->successResponse([],trans('locale.dontHaveAdminOrEmployee'),200);
            }


            $query = Admin::query();
            // Filter by role
            if ($request->has('role')) {
                $role = Role::where('name', $request->role)->first();
                if ($role) {
                    $query->role($request->role);
                }
                else {
                    $rolesTranslations = trans('roles');

                    $roleKey = array_search($data['role'], $rolesTranslations);
                    if (!$roleKey) {
                        return "the role is incorrect";
                    }
                    $role = Role::where('name', $roleKey)->first();
                    $query->role($role);
                }
            }
            // Filter search
            if ($request->has('search')) {
                $search = $request->search;
                $query->where('name', 'like', '%' . $search . '%');
            }
            // Filter Active
            if ($request->has('active')) {
                $query->where('is_active', $request->active);
            }
            // Filter By City
            if ($request->has('city_id')) {
                $query->where('city_id', $request->city_id);
            }

            $admin = $query->whereRestaurantId($data['restaurant_id'])->paginate($request->input('per_page', 25));
            $data = AdminResource::collection($admin);
            return $this->paginateSuccessResponse($data,trans('locale.foundSuccessfully'),200);
            // ---------------------------------------------------------------------


            // $data = $request->validated();
            // $dataAdmin =  $this->userService->all($data['restaurant_id']);
            // if (\count($dataAdmin) == 0) {
            //     return $this->successResponse([],trans('locale.dontHaveAdminOrEmployee'),200);
            // }

            // $where = [];

            // if(\array_key_exists('role',$data))
            // {
            //     // Filter search
            //     if(\array_key_exists('search',$data))
            //         $where = \array_merge($where,[['name','like','%'.$data['search'].'%']]);
            //     // Filter Active
            //     if(\array_key_exists('active',$data))
            //         $where = \array_merge($where,['is_active'=> $data['active']]);
            //     // Filter By City
            //     if(\array_key_exists('city_id',$data))
            //         $where = \array_merge($where,['city_id'=> $data['city_id']]);
            //     if(\array_key_exists('search',$data) || \array_key_exists('active',$data) || \array_key_exists('city_id',$data))
            //     {
            //         $superAdmin =  $this->userService->searchRole($data['role'],$where,$request->input('per_page', 25));
            //         $data = AdminResource::collection($superAdmin);
            //         return $this->paginateSuccessResponse($data,trans('locale.foundSuccessfully'),200);
            //     }
            //     $superAdmin = $this->userService->paginateRole($request->input('per_page', 25),$data['role'],$data['restaurant_id']);
            //     $data = AdminResource::collection($superAdmin);
            //     return $this->paginateSuccessResponse($data,trans('locale.successfully'),200);

            // }
            // // Filter search
            // if(\array_key_exists('search',$data))
            //     $where = \array_merge($where,[['name','like','%'.$data['search'].'%']]);
            // // Filter Active
            // if(\array_key_exists('active',$data))
            //     $where = \array_merge($where,['is_active'=> $data['active']]);
            // // Filter By City
            // if(\array_key_exists('city_id',$data))
            //     $where = \array_merge($where,['city_id'=> $data['city_id']]);

            // if(\array_key_exists('search',$data) || \array_key_exists('active',$data) || \array_key_exists('city_id',$data))
            // {
            //     $superAdmin =  $this->userService->search($where,$request->input('per_page', 25));
            //     $data = AdminResource::collection($superAdmin);

            //     return $this->paginateSuccessResponse($data,trans('locale.foundSuccessfully'),200);
            // }

            // $superAdmin = $this->userService->paginate($request->input('per_page', 25),$data['restaurant_id']);
            // $data = AdminResource::collection($superAdmin);
            // return $this->paginateSuccessResponse($data,trans('locale.citySuperAdminFoundSuccessfully'),200);

        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }

    }

    // // Add User
    // public function create(AddRequest $request)
    // {
    //     try{
    //         $restaurant_id = auth()->user()->restaurant_id;
    //         $data = $this->userService->create($request->validated(),$restaurant_id);
    //         return $this->successResponse($data,trans('locale.created'),200);
    //     } catch(Throwable $th){
    //         $message = $th->getMessage();
    //         return $this->messageErrorResponse($message);
    //     }
    // }

    public function create(AdminAddRequest $request)
    {
        try{
            if($request->has('restaurant_id'))
                $restaurant_id = $request->restaurant_id;
            else
                $restaurant_id = auth()->user()->restaurant_id;
            $user = $this->userService->create1($request->validated(),$restaurant_id);
            if($user === "the role is incorrect")
                return $this->messageErrorResponse(trans('locale.theRoleIsIncorrect'),400);
            $data = AdminResource::make($user);
            return $this->successResponse($data,trans('locale.created'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Update User
    public function update(UpdateRequest $request)
    {
        try{
            $arrAdmin = Arr::only($request->validated(),
            ['id','name','password','user_name','mobile','type_id','category','restaurant_id']);

            $user = $this->userService->update($arrAdmin);
            if($user == 0)
            {
                return $this->messageErrorResponse(trans('locale.invalidItem'),403);
            }
            $arrAdmin['admin_id'] = $arrAdmin['id'];
            $user = $this->userService->show($arrAdmin);
            $data = AdminResource::make($user);
            return $this->successResponse($data,trans('locale.updated'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Show User By Id
    public function showById(IdRequest $request)
    {
        try{
            $admin = auth()->user();
            $user = $this->userService->show($request->validated());
            $data = AdminResource::make($user);
            return $this->successResponse($data,trans('locale.foundSuccessfully'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Delete User
    public function delete(IdRequest $request)
    {
        try{
            $user = $this->userService->destroy($request->validated());
            if($user == 0)
            {
                return $this->messageErrorResponse(trans('locale.invalidItem'),403);
            }
            return $this->messageSuccessResponse(trans('locale.deleted'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Active Or DisActive User
    public function deactivate(IdRequest $request)
    {
        try{
            $admin = auth()->user();
            $user = $this->userService->show($request->validated());
            $item = $this->userService->activeOrDesactive($user);
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
