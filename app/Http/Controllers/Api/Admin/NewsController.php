<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\News\AddRequest;
use App\Http\Requests\News\IdRequest;
use App\Http\Requests\News\ShowRequest;
use App\Http\Requests\News\UpdateRequest;
use App\Http\Resources\NewsResource;
use App\Models\News;
use App\Services\NewsService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Throwable;

class NewsController extends Controller
{
    public function __construct(private NewsService $NewsService)
    {
    }

    // Show All News For Admin
    public function showAll(ShowRequest $request)
    {
        try{
            $admin = auth()->user();
            $news = $this->NewsService->paginate($admin->restaurant_id,$request->input('per_page', 25));
            if (\count($news) == 0) {
                return $this->successResponse([],trans('locale.dontHaveNews'),200);
            }
            $data = NewsResource::collection($news);
            return $this->paginateSuccessResponse($data,trans('locale.newsFound'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Add News
    public function create(AddRequest $request)
    {
        try{
            $restaurant_id = auth()->user()->restaurant_id;
            $news = $this->NewsService->create($restaurant_id,$request->validated());
            if ($request->hasFile('image'))
            {
                $extension = $request->file('image')->getClientOriginalExtension();
                $randomFileName = str()->random(10) . '.' . $extension;
                $news->addMediaFromRequest('image')->usingFileName($randomFileName)->toMediaCollection('news');
            }

            $data = NewsResource::make($news);
            return $this->successResponse($data,trans('locale.created'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Update News
    public function update(UpdateRequest $request)
    {
        try{
            $admin = auth()->user();
            $data = $request->validated();

            $arrNewsTranslation = Arr::only($request->validated(),
            ['id','name_en','name_ar','description_en','description_ar']);

            $this->NewsService->update($admin->restaurant_id,$arrNewsTranslation);
            $item = News::whereId($data['id'])->first();

            if ($request->hasFile('image'))
            {
                $item->clearMediaCollection('news');
                $extension = $request->file('image')->getClientOriginalExtension();
                $randomFileName = str()->random(10) . '.' . $extension;
                $item->addMediaFromRequest('image')->usingFileName($randomFileName)->usingName($item->name)->toMediaCollection('news');
            }

            $data = NewsResource::make($item);
            return $this->successResponse($data,trans('locale.updated'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Show News By Id
    public function showById(IdRequest $request)
    {
        try{
            $admin = auth()->user();
            $news = $this->NewsService->show($admin->restaurant_id,$request->validated());
            $data = NewsResource::make($news);
            return $this->successResponse($data,trans('locale.newsFound'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Delete News
    public function delete(IdRequest $request)
    {
        try{
            $restaurant_id = auth()->user()->restaurant_id;
            $done = News::whereRestaurantId($restaurant_id)->whereId($request->id)->first();
            $done->clearMediaCollection('news');
            $news = $this->NewsService->destroy($request->id,$restaurant_id);
            if($news == 0)
            {
                return $this->messageErrorResponse(trans('locale.invalidItem'),403);
            }
            return $this->messageSuccessResponse(trans('locale.deleted'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }
}
