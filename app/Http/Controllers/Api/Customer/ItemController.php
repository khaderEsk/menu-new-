<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Item\ShowItemRestaurantRequest;
use App\Http\Resources\ShowItemResource;
use App\Http\Resources\UserShowItemsResource;
use App\Models\Item;
use App\Services\ItemService;
use Illuminate\Http\Request;
use Throwable;

class ItemController extends Controller
{
    public function __construct(private ItemService $itemService)
    {
    }

    // Show All Items
    public function showAll(ShowItemRestaurantRequest $request)
    {
        try{
            $data = $request->validated();
            $query = Item::with('items');
            $query->whereNull('item_id')->where('category_id', $data['category_id']);

            $item = $query->count();
            if ($item == 0) {
                return $this->successResponse([],trans('locale.dontHaveItems'),200);
            }
            if ($request->has('search')) {
                $search = $request->search;
                $query->whereTranslationLike('name', "%$search%");
            }

            $items = $query->Active()->orderBy('index')->paginate($request->input('per_page', 25));
            $data = ShowItemResource::collection($items);
            return $this->paginateSuccessResponse($data,trans('locale.restaurantFound'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }
}
