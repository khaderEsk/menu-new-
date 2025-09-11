<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Category\ShowCategoryRestaurantRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\Request;
use Throwable;

class CategoryController extends Controller
{

    public function __construct(private CategoryService $categoryService)
    {
    }

    // Show All Master Category or Sub Category For Restaurant
    public function showAll(ShowCategoryRestaurantRequest $request)
    {
        try{
            $data = $request->validated();
            $categories = Category::whereRestaurantId($data['restaurant_id'])->whereNull('category_id')->where('is_active',1)->orderBy('index')->get();
            if (\count($categories) == 0) {
                $data = CategoryResource::collection($categories);
                return $this->paginateSuccessResponse($data,trans('locale.dontHaveCategories'),200);
            }

            $query = Category::query();
            $query->where('restaurant_id', $data['restaurant_id']);

            if ($request->has('category_id')) {
                $query->where('category_id', $request->category_id);
            }
            if ($request->has('search')) {
                $search = $request->search;
                if($request->has('category_id'))
                    $query->whereTranslationLike('name', "%$search%");
                else
                    $query->whereNull('category_id')->whereTranslationLike('name', "%$search%");
            }

            if(!$request->has('category_id'))
                $categories = $query->whereNull('category_id')->Active()->orderBy('index')->paginate($request->input('per_page', 25));

            else
                $categories = $query->Active()->orderBy('index')->paginate($request->input('per_page', 25));

            // $categories = $query->Active()->orderBy('index')->paginate($request->input('per_page', 25));
            $data = CategoryResource::collection($categories);
            return $this->paginateSuccessResponse($data,trans('locale.restaurantFound'),200);


            // $data = $request->validated();
            // $Categories = $this->categoryService->paginate($data['restaurant_id'],$request->input('per_page', 25));
            // return $Categories;
            // if (\count($Categories) == 0) {
            //     $data = CategoryResource::collection($Categories);
            //     return $this->paginateSuccessResponse($data,trans('locale.dontHaveCategories'),200);
            // }

            // if(\array_key_exists('category_id',$data))
            // {
            //     // Filter Name
            //     if(\array_key_exists('search',$data))
            //     {
            //         $data = $request->validated();
            //         $data =  $this->categoryService->searchsubCategory($data['restaurant_id'],$data['category_id'],$data['search'],$request->input('per_page', 25));
            //         $categories = CategoryResource::collection($data);
            //         return $this->paginateSuccessResponse($categories,trans('locale.categoriesFound'),200);
            //     }

            //     $data = $request->validated();
            //     $data =  $this->categoryService->subCategory($data['restaurant_id'],$data['category_id'],$request->input('per_page', 25));
            //     $categories = CategoryResource::collection($data);
            //     return $this->paginateSuccessResponse($categories,trans('locale.categoriesFound'),200);
            // }

            // Filter Name
            // if(\array_key_exists('search',$data))
            // {
            //     $data = $request->validated();
            //     $data =  $this->categoryService->search($data['restaurant_id'],$data['search'],$request->input('per_page', 25));
            //     $categories = CategoryResource::collection($data);
            //     return $this->paginateSuccessResponse($categories,trans('locale.categoriesFound'),200);
            // }

            // $data = $this->categoryService->paginate($data['restaurant_id'],$request->input('per_page', 25));
            // $categories = CategoryResource::collection($data);
            // return $this->paginateSuccessResponse($categories,trans('locale.categoriesFound'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

}
