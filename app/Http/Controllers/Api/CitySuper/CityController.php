<?php

namespace App\Http\Controllers\Api\CitySuper;

use App\Http\Controllers\Controller;
use App\Http\Requests\City\SearchRequest;
use App\Services\CityService;
use Illuminate\Http\Request;

class CityController extends Controller
{
    public function __construct(private CityService $cityService)
    {
    }

    public function showAll(SearchRequest $request)
    {
        // $id = auth()->user()->id;
        if($request->search)
        {
            $data = $request->validated();
            $data =  $this->cityService->search($data['search'],$request->input('per_page', 25));
            return response()->json(['status' => true,'cities' => $data,'message' => "Cities Found Successfully"],200);
        }

        else
        {
            $cities = $this->cityService->all();
            if (\count($cities) == 0) {
                return response()->json(['status' => false,'data' => [],'message' =>'Dont Have Cities'], 402);
            }
            $cities = $this->cityService->paginate($request->input('per_page', 25));
            // $data = CityResource::collection($cities);
            return response()->json(['status' => true,'cities' => $cities,'message' => "Cities Found Successfully"],200);
        }
    }
}
