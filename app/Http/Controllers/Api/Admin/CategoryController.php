<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Category\AddRequest;
use App\Http\Requests\Category\IdRequest;
use App\Http\Requests\Category\ReOrderRequest;
use App\Http\Requests\Category\ShowRequest;
use App\Http\Requests\Category\UpdateRequest;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\AdminCategoryResource;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\Request;
use App\Http\Resources\CategoriesItemsResource;
use Throwable;

class CategoryController extends Controller
{
    public function __construct(private CategoryService $categoryService) {}

    // Show All Master Categories For Admin

    public function showAllCategoriesAndSub(ShowRequest $request)
    {
        try {
            $restaurant_id = auth()->user()->restaurant_id;
            $query = Category::query();
            if ($request->has('search')) {
                $search = $request->search;
                $query->whereTranslationLike('name', "%$search%");
            }
            $categories = $query->whereRestaurantId($restaurant_id)->orderBy('index')->get();
            $data = CategoryResource::collection($categories);
            return $this->successResponse($data, trans('locale.categoriesFound'), 200);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    public function showAllCategoriesAndSubInItem(ShowRequest $request)
    {
        try {
            $restaurant_id = auth()->user()->restaurant_id;
            $matchingCategories = [];
            $categories = Category::whereRestaurantId($restaurant_id)->get();
            foreach ($categories as $c) {
                $category = Category::whereCategoryId($c->id)->whereRestaurantId($restaurant_id)->get();

                if (count($category) == 0) {
                    $matchingCategories[] = $c;
                }
            }
            $data = CategoriesItemsResource::collection($matchingCategories);
            return $this->successResponse($data, trans('locale.categoriesFound'), 200);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    public function showAll(ShowRequest $request)
    {
        try {
            $data = $request->validated();
            $restaurant_id = auth()->user()->restaurant_id;
            if ($request->has('category_id'))
                $Categories = Category::whereCategoryId($data['category_id'])->get();

            else
                $Categories = $this->categoryService->paginate($restaurant_id, $request->input('per_page', 25));
            if (\count($Categories) == 0) {
                $data = CategoryResource::collection($Categories);
                return $this->successResponse($data, trans('locale.dontHaveCategories'), 200);
            }

            $query = Category::query();

            if ($request->has('category_id')) {
                $query->where('category_id', $request->category_id);
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->whereTranslationLike('name', "%$search%");
            }

            if (!$request->has('category_id'))
                $categories = $query->whereNull('category_id')->whereRestaurantId($restaurant_id)->orderBy('index')->paginate($request->input('per_page', 25));

            else
                $categories = $query->whereRestaurantId($restaurant_id)->orderBy('index')->paginate($request->input('per_page', 25));

            $data = CategoryResource::collection($categories);
            return $this->paginateSuccessResponse($data, trans('locale.categoriesFound'), 200);

            // $restaurant_id = auth()->user()->restaurant_id;
            // $Categories = $this->categoryService->paginate($restaurant_id,$request->input('per_page', 2));
            // if (\count($Categories) == 0) {
            //     $data = CategoryResource::collection($Categories);
            //     return $this->paginateSuccessResponse($data,trans('locale.dontHaveCategories'),200);
            // }

            // $data = $request->validated();
            // if(\array_key_exists('category_id',$data))
            // {
            //     // Filter Name
            //     if(\array_key_exists('search',$data))
            //     {
            //         $data = $request->validated();
            //         $data =  $this->categoryService->searchsubCategory($restaurant_id,$data['category_id'],$data['search'],$request->input('per_page', 2));
            //         $categories = CategoryResource::collection($data);
            //         return $this->paginateSuccessResponse($categories,trans('locale.categoriesFound'),200);
            //     }

            //     $data = $request->validated();
            //     $data =  $this->categoryService->subCategory($restaurant_id,$data['category_id'],$request->input('per_page', 2));
            //     $categories = CategoryResource::collection($data);
            //     return $this->paginateSuccessResponse($categories,trans('locale.categoriesFound'),200);
            // }

            // // Filter Name
            // if(\array_key_exists('search',$data))
            // {
            //     $data = $request->validated();
            //     $data =  $this->categoryService->search($restaurant_id,$data['search'],$request->input('per_page', 2));
            //     $categories = CategoryResource::collection($data);
            //     return $this->paginateSuccessResponse($categories,trans('locale.categoriesFound'),200);
            // }

            // $data = $this->categoryService->paginate($restaurant_id,$request->input('per_page', 2));
            // $categories = CategoryResource::collection($data);
            // return $this->paginateSuccessResponse($categories,trans('locale.categoriesFound'),200);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Add Category
    public function create(AddRequest $request)
    {
        try {
            $restaurant_id = auth()->user()->restaurant_id;
            $data = $request->validated();
            // return Max Index In Master Category For Admin
            $maxIndex = Category::where('restaurant_id', $restaurant_id)->max('index');
            // $category_id = null;
            if (\array_key_exists('category_id', $data)) {
                $maxIndex = Category::where('category_id', $data['category_id'])->where('restaurant_id', $restaurant_id)->max('index');
                // $category_id = $data['category_id'];
            }
            $category = $this->categoryService->create($restaurant_id, $request->validated(), $maxIndex, $request->category_id);
            if ($request->hasFile('image')) {
                $extension = $request->file('image')->getClientOriginalExtension();
                $randomFileName = str()->random(10) . '.' . $extension;
                $category->addMediaFromRequest('image')->usingFileName($randomFileName)->toMediaCollection('category');
            }

            $data = CategoryResource::make($category);
            return $this->successResponse($data, trans('locale.created'), 200);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Update Category
    public function update(UpdateRequest $request)
    {
        try {
            $admin = auth()->user();
            $data = $request->validated();
            // $restaurant_id = $this->categoryService->showAdmin($id);
            if (\array_key_exists('category_id', $data)) {
                $category = Category::whereId($data['id'])->whereRestaurantId($admin->restaurant_id)->first();
                if ($category->category_id != $data['category_id']) {
                    Category::where('category_id', $category->category_id)->whereRestaurantId($admin->restaurant_id)->orderBy('index')->where('index', '>', $category->index)->decrement('index');
                    $maxIndex = Category::where('category_id', $data['category_id'])->where('restaurant_id', $admin->restaurant_id)->max('index');
                    $category = $this->categoryService->updateSub($admin, $data, $maxIndex);
                }
                $category = $this->categoryService->update($admin, $data);
            } else
                $category = $this->categoryService->update($admin, $data);
            $categ = Category::whereId($data['id'])->first();

            if ($request->hasFile('image')) {
                $categ->clearMediaCollection('category');
                $extension = $request->file('image')->getClientOriginalExtension();
                $randomFileName = str()->random(10) . '.' . $extension;
                $categ->addMediaFromRequest('image')->usingFileName($randomFileName)->toMediaCollection('category');
            }
            $data = CategoryResource::make($categ);
            return $this->successResponse($data, trans('locale.updated'), 200);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Active Or DisActive Master Category
    public function deactivate(IdRequest $request)
    {
        try {
            $admin = auth()->user();
            $menu = $this->categoryService->show($request->id, $admin->restaurant_id);
            $item = $this->categoryService->activeOrDesactive($menu);
            if ($item == 0) {
                return $this->messageErrorResponse(trans('locale.invalidItem'), 403);
            }
            return $this->messageSuccessResponse(trans('locale.successfully'), 200);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // ReOrder Master Categories For Admin
    public function reOrder(ReOrderRequest $request)
    {
        try {
            $admin = auth()->user();
            $data = $request->validated();

            if (\array_key_exists('category_id', $data)) {
                $category = $this->categoryService->showSubCategory($data, $admin->restaurant_id);
                // If New Index < sub Category Index => All sub Category Between Old Index And New Index make +1
                if ($data['index'] < $category->index)
                    Category::where('restaurant_id', $admin->restaurant_id)->whereCategoryId($data['category_id'])->whereBetween('index', [$data['index'], $category->index])->increment('index');
                // Else If New Index > sub Category Index => All sub Category Between Old Index And New Index make -1
                else
                    Category::where('restaurant_id', $admin->restaurant_id)->whereCategoryId($data['category_id'])->whereBetween('index', [$category->index, $data['index']])->decrement('index');

                $this->categoryService->updateIndex($admin->restaurant_id, $data);
                return $this->messageSuccessResponse(trans('locale.successfully'), 200);
            } else {
                $category = $this->categoryService->show($data['id'], $admin->restaurant_id);
                // If New Index < Master Category Index => All Master Category Between Old Index And New Index make +1
                if ($data['index'] < $category->index)
                    Category::where('restaurant_id', $admin->restaurant_id)->whereNull('Category_id')->whereBetween('index', [$data['index'], $category->index])->increment('index');
                // Else If New Index > Master Category Index => All Master Category Between Old Index And New Index make -1
                else
                    Category::where('restaurant_id', $admin->restaurant_id)->whereNull('Category_id')->whereBetween('index', [$category->index, $data['index']])->decrement('index');

                $this->categoryService->updateIndex($admin->restaurant_id, $data);
                return $this->messageSuccessResponse(trans('locale.successfully'), 200);
            }
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Delete Category
    public function delete(IdRequest $request)
    {
        try {
            $restaurant_id = auth()->user()->restaurant_id;
            $done = Category::whereRestaurantId($restaurant_id)->whereId($request->id)->first();
            if ($done->is_active == 1) {
                return $this->messageErrorResponse(trans('locale.youCantDeleteCategory'));
            }
            $done->clearMediaCollection('category');
            $category = $this->categoryService->destroy($request->id, $restaurant_id);
            if ($category == -10) {
                return $this->messageErrorResponse(trans('locale.youCantDeleteThisCategory'), 403);
            }

            if ($category == 0) {
                return $this->messageErrorResponse(trans('locale.invalidItem'), 403);
            }
            return $this->messageSuccessResponse(trans('locale.deleted'), 200);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }

        // // Edit Indexes After Master Category Index And Make It -1
        // MasterCategory::where('index', '>', $masterCategory->index)->decrement('index');
        // $this->repository->DeleteById(MasterCategory::class, $arr['masterId']);
        // return \returnSuccess(null, 'Master Category Deleted Successfully', 200);
    }

    public function showCategoryAndSubsAndItems(Request $request)
    {
        try {
            $restaurant_id = $request->restaurant_id;
            $categories = Category::whereNull('category_id')->where('is_active', 1)->where('restaurant_id', $restaurant_id)->with(['categories', 'items'])->get();
            $data = AdminCategoryResource::collection($categories);
            return $this->successResponse($data, trans('locale.categoriesFound'), 200);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }
}
