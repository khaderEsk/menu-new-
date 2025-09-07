<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Exports\RateExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Rate\ShowChartRequest;
use App\Http\Requests\Rate\ShowRequest;
use App\Http\Resources\RateResource;
use App\Models\Customer;
use App\Models\Rate;
use App\Services\RateService;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class RateController extends Controller
{
    public function __construct(private RateService $rateService)
    {
    }

    // Show All Rates For Admin
    public function showAll(ShowRequest $request)
    {
        try{
            $query = Rate::query();

            if ($request->has('gender')) {
                $query->whereHas('customer', function ($q) use ($request) {
                    $q->where('gender', $request->gender);
                });
            }

            if ($request->has('type') )
            {
                if ($request->type === 'person') {
                    $query->whereHas('customer', function ($q) use ($request) {
                        $q->where('name','!=', null);
                    });
                }
                else{
                    $query->whereHas('customer', function ($q) use ($request) {
                        $q->where('name','=', null);
                    });
                }
            }

            if ($request->has('from_age') || $request->has('to_age')) {
                if($request->has('from_age') && $request->has('to_age'))
                {
                    $query->whereHas('customer', function ($q) use ($request) {
                        $q->where('birthday', '>=', $request->from_age)->where('birthday', '<=', $request->to_age);
                    });
                }
                else if ($request->has('from_age'))
                {
                $query->whereHas('customer', function ($q) use ($request) {
                        $q->where('birthday', '>=', $request->from_age);
                    });
                }
                else if ($request->has('to_age'))
                {
                $query->whereHas('customer', function ($q) use ($request) {
                        $q->where('birthday', '<=', $request->to_age);
                    });
                }
            }

            if ($request->has('from_date') || $request->has('to_date')) {
                if($request->has('from_date') && $request->has('to_date'))
                {
                    $query->whereHas('customer', function ($q) use ($request) {
                        $q->where('created_at', '>=', $request->from_date)->where('created_at', '<=', $request->to_date);
                    });
                }
                else if ($request->has('from_date'))
                {
                $query->whereHas('customer', function ($q) use ($request) {
                        $q->where('created_at', '>=', $request->from_date);
                    });
                }
                else if ($request->has('to_date'))
                {
                $query->whereHas('customer', function ($q) use ($request) {
                        $q->where('created_at', '<=', $request->to_date);
                    });
                }
            }

            if ($request->has('rate')) {
                $query->where('rate', $request->rate);
            }

            if ($request->has('restaurant_id')) {
                $query->where('restaurant_id', $request->restaurant_id);
            }

            $rates = $query->latest()->paginate($request->input('per_page', 25));
            $data = RateResource::collection($rates);
            return $this->paginateSuccessResponse($data,trans('locale.rateFound'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Get Chart For restaurant
    // public function getChart(ShowChartRequest $request)
    // {
    //     $data = $request->validated();
    //     $monthes = $this->rateService->getChart($data['restaurant_id']);
    //     return $monthes;
    // }

    // Export Rates To Excel
    public function export(ShowRequest $request)
    {
        try{
            return Excel::download(new RateExport($request),'rate.xlsx');
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }

    }
}
