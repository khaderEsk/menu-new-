<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\News\CustomerIdNewsRequest;
use App\Http\Requests\News\CustomerShowNewsRequest;
use App\Http\Requests\News\IdRequest;
use App\Http\Requests\News\ShowRequest;
use App\Http\Resources\NewsResource;
use App\Services\NewsService;
use Illuminate\Http\Request;
use Throwable;

class NewsController extends Controller
{
    public function __construct(private NewsService $newsService)
    {
    }

    // Show All news For Restaurant
    public function showAll(CustomerShowNewsRequest $request)
    {
        try{
            $data = $request->validated();
            $news= $this->newsService->paginate($data['restaurant_id'],$request->input('per_page', 25));
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

    // Show news By Id
    public function showById(CustomerIdNewsRequest $request)
    {
        try{
            $data = $request->validated();
            $news = $this->newsService->show($data['restaurant_id'],$request->validated());
            $data = NewsResource::make($news);
            return $this->SuccessResponse($data,trans('locale.newsFound'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }
}
