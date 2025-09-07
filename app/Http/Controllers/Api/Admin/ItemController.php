<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Item\IdRequest;
use App\Http\Requests\Item\ReOrderRequest;
use App\Http\Requests\Item\StoreRequest;
use App\Http\Requests\Item\ShowSimilarItemsRequest;
use App\Http\Requests\Item\ShowRequest;
use App\Http\Requests\Item\UpdateRequest;
use App\Http\Resources\ShowItemResource;
use App\Models\Item;
use App\Services\ComponentService;
use App\Services\ItemService;
use App\Services\NutritionFactService;
use App\Services\SizeService;
use App\Services\ToppingService;
use Throwable;

class ItemController extends Controller
{

    public function __construct(
        private ItemService $itemService,
        private ToppingService $toppingService,
        private SizeService $sizeService,
        private ComponentService $componentService,
        private NutritionFactService $nutritionFactService
    ) {}

    // Show All Items
    public function showAll(ShowRequest $request)
    {
        try {
            $data = $request->validated();
            if ($request->has('category_id'))
                $items = $this->itemService->all($data['category_id']);
            else
                $items = Item::whereRestaurantId($data['restaurant_id'])->whereNull('item_id')->orderBy('index')->get();
            if (\count($items) == 0) {
                return $this->successResponse([], trans('locale.dontHaveItems'), 200);
            }
            $query = Item::query();
            if ($request->has('search')) {
                $search = $request->search;
                $query->whereTranslationLike('name', "%$search%");
            }
            if ($request->has('category_id')) {
                $query->where('category_id', $request->category_id);
            }
            if ($request->has('restaurant_id')) {
                $query->where('restaurant_id', $request->restaurant_id);
            }
            $items = $query->with('items')->whereNull('item_id')->orderBy('index')->paginate($request->input('per_page', 25));
            $data = ShowItemResource::collection($items);
            return $this->paginateSuccessResponse($data, trans('locale.ItemsFound'), 200);

            // // Filter Name
            // if(\array_key_exists('search',$data))
            // {
            //     $items =  $this->itemService->search($data,$request->input('per_page', 25));
            //     $data = ShowItemResource::collection($items);
            //     return $this->paginateSuccessResponse($data,trans('locale.ItemsFound'),200);
            // }

            // $items = $this->itemService->paginate($data,$request->input('per_page', 25));
            // $data = ShowItemResource::collection($items);
            // return $this->paginateSuccessResponse($data,trans('locale.ItemsFound'),200);

        } catch (Throwable $th) {
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    public function showSimilarItems(ShowSimilarItemsRequest $request)
    {
        $admin = auth()->user();
        $type = $request->type;
        $item = Item::whereRestaurantId($admin->restaurant_id)->whereTranslationLike('name', "%$type%")->first();
        $items = Item::with('items')->whereCategoryId($item->category_id)->whereNull('item_id')->get();
        $data = ShowItemResource::collection($items);
        return $this->successResponse($data, trans('locale.ItemsFound'), 200);
    }


    // Add Item
    public function create(StoreRequest $request)
    {

        try {
            $sizeImages = $request->file('sizes');
            $item = $this->itemService->create($request->validated(), $sizeImages);
            $data = ShowItemResource::make($item);
            return $this->successResponse($data, trans('locale.created'), 200);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Update Item Data
    public function update(UpdateRequest $request)
    {

        try {
            $sizeImages = $request->file('sizes');
            $item =  $this->itemService->update($request->validated(), $sizeImages);
            $data = ShowItemResource::make($item);
            return $this->successResponse($data, trans('locale.updated'), 200);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Active Or DisActive Item
    public function deactivate(IdRequest $request)
    {
        try {
            $admin = auth()->user();
            $data = $this->itemService->show($request->id, $admin->restaurant_id);
            $item = $this->itemService->activeOrDesactive($data, $admin->restaurant_id);
            if ($item == 0) {
                return $this->messageErrorResponse(trans('locale.invalidItem'), 403);
            }
            return $this->messageSuccessResponse(trans('locale.successfully'), 200);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // ReOrder Items
    public function reOrder(ReOrderRequest $request)
    {
        try {
            $admin = auth()->user();
            $data = $request->validated();
            // $admin = $this->itemService->showAdmin($id);
            $item = $this->itemService->showItem($data, $admin->restaurant_id);

            // If New Index < Item Index => All Item Between Old Index And New Index make +1
            if ($data['index'] < $item->index)
                Item::where('category_id', $data['category_id'])->where('restaurant_id', $admin->restaurant_id)->whereBetween('index', [$data['index'], $item->index])->increment('index');
            // Else If New Index > Item Index => All Item Between Old Index And New Index make -1
            else
                Item::where('category_id', $data['category_id'])->where('restaurant_id', $admin->restaurant_id)->whereBetween('index', [$item->index, $data['index']])->decrement('index');

            $this->itemService->updateIndex($admin->restaurant_id, $request->validated());
            return $this->messageSuccessResponse(trans('locale.successfully'), 200);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Delete Item
    public function delete(IdRequest $request)
    {
        try {
            $admin = auth()->user();
            $done = Item::whereRestaurantId($admin->restaurant_id)->whereId($request->id)->first();
            $done->clearMediaCollection('item');
            $done->clearMediaCollection('item_icon');
            $restaurant = $this->itemService->destroy($request->id, $admin->restaurant_id);

            if ($restaurant == 0) {
                return $this->messageErrorResponse(trans('locale.invalidItem'), 403);
            }
            return $this->messageSuccessResponse(trans('locale.deleted'), 200);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }
}
