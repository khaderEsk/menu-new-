<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Menu\AddRequest;
use App\Http\Requests\Menu\DeactivateRequest;
use App\Http\Requests\Menu\SearchRequest;
use App\Http\Resources\MenuTemplateResource;
use App\Services\MenuTemplateService;
use Throwable;

class MenuTemplateController extends Controller
{
    public function __construct(private MenuTemplateService $menuTemplateService)
    {
    }

    // Show All Menu From
    public function showAll(SearchRequest $request)
    {
        try{
            $menuTemplates = $this->menuTemplateService->all();
            if (\count($menuTemplates) == 0) {
                return $this->successResponse([],trans('locale.dontHaveMenuTemplates'),200);
            }

            $where = [];
            $data = $request->validated();
            // Filter Name
            if(\array_key_exists('search',$data))
                $where = \array_merge($where,[['name','like','%'.$data['search'].'%']]);

            // Filter Active
            if(\array_key_exists('active',$data))
                $where = \array_merge($where,['is_active'=> $data['active']]);

            if(\array_key_exists('search',$data) || \array_key_exists('active',$data))
            {
                $menu =  $this->menuTemplateService->search($where,$request->input('per_page', 25));
                $data = MenuTemplateResource::collection($menu);
                return $this->paginateSuccessResponse($data,trans('locale.menuTemplateFound'),200);
            }
            $menuTemplates = $this->menuTemplateService->paginate($request->input('per_page', 25));
            $data = MenuTemplateResource::collection($menuTemplates);
            return $this->paginateSuccessResponse($data,trans('locale.menuTemplateFound'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Add Menu Form
    public function create(AddRequest $request)
    {
        try{
            $id = auth()->user()->id;
            $menu = $this->menuTemplateService->create($id,$request->validated());
            $data = MenuTemplateResource::make($menu);
            return $this->successResponse($data,trans('locale.created'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

   // Active Or DisActive Menu Form
    public function deactivate(DeactivateRequest $request)
    {
        try{
            $admin = auth()->user()->id;
            $menu = $this->menuTemplateService->show($request->id,$admin);
            $item = $this->menuTemplateService->activeOrDesactive($menu,$admin);
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

    // Delete Menu Form
    public function delete(DeactivateRequest $request)
    {
        try{
            $admin = auth()->user()->id;
            $menu = $this->menuTemplateService->destroy($request->id);
            if($menu == -10)
            {
                return $this->messageErrorResponse(trans('locale.youCantDeleteMenuTemplateBecauseItHasRestaurant'),403);
            }

            if($menu == 0)
            {
                return $this->messageErrorResponse(trans('locale.invalidItem'),403);
            }
            return $this->messageSuccessResponse(trans('locale.deleted'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

     // Show Menu By Id
     public function showById(DeactivateRequest $request)
     {
         try{
            $menu = $this->menuTemplateService->show($request->id);
            $data = MenuTemplateResource::make($menu);
            return $this->successResponse($data,trans('locale.menuTemplateFound'),200);
         } catch(Throwable $th){
             $message = $th->getMessage();
             return $this->messageErrorResponse($message);
         }
     }
}
