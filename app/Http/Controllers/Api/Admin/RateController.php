<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exports\RateExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Rate\ShowRequest;
use App\Http\Resources\RateResource;
use App\Models\Rate;
use App\Services\RateService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class RateController extends Controller
{
    public function __construct(private RateService $rateService) {}
    // Show All Rates For Admin
    public function showAll(ShowRequest $request)
    {
        try {
            $restaurant_id = auth()->user()->restaurant_id;
            $query = Rate::query();
            $query->where('restaurant_id', $restaurant_id);

            if ($request->has('gender')) {
                $query->whereHas('customer', function ($q) use ($request) {
                    $q->where('gender', $request->gender);
                });
            }

            if ($request->has('type')) {
                if ($request->type === 'person') {
                    $query->whereHas('customer', function ($q) use ($request) {
                        $q->where('name', '!=', null);
                    });
                } else {
                    $query->whereHas('customer', function ($q) use ($request) {
                        $q->where('name', '=', null);
                    });
                }
            }

            // if ($request->has('from_age')) {
            //     $query->whereHas('customer', function ($q) use ($request) {
            //         $q->where('birthday', '>=', $request->from_age)->where('birthday', '<=', $request->to_age);
            //     });
            // }

            if ($request->has('from_age') || $request->has('to_age')) {
                if ($request->has('from_age') && $request->has('to_age')) {
                    $query->whereHas('customer', function ($q) use ($request) {
                        $q->where('birthday', '>=', $request->from_age)->where('birthday', '<=', $request->to_age);
                    });
                } else if ($request->has('from_age')) {
                    $query->whereHas('customer', function ($q) use ($request) {
                        $q->where('birthday', '>=', $request->from_age);
                    });
                } else if ($request->has('to_age')) {
                    $query->whereHas('customer', function ($q) use ($request) {
                        $q->where('birthday', '<=', $request->to_age);
                    });
                }
            }

            if ($request->has('from_date') || $request->has('to_date')) {
                if ($request->has('from_date') && $request->has('to_date')) {
                    $query->whereHas('customer', function ($q) use ($request) {
                        $q->whereDate('created_at', '>=', $request->from_date)->whereDate('created_at', '<=', $request->to_date);
                    });
                } else if ($request->has('from_date')) {
                    $query->whereHas('customer', function ($q) use ($request) {
                        $q->whereDate('created_at', '>=', $request->from_date);
                    });
                } else if ($request->has('to_date')) {
                    $query->whereHas('customer', function ($q) use ($request) {
                        $q->whereDate('created_at', '<=', $request->to_date);
                    });
                }
            }

            if ($request->has('rate')) {
                $query->where('rate', $request->rate);
            }

            $rates = $query->latest()->paginate($request->input('per_page', 25));

            // return response()->json($reviews);
            $data = RateResource::collection($rates);
            return $this->paginateSuccessResponse($data, trans('locale.rateFound'), 200);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Export Rates To Excel
    public function export(ShowRequest $request)
    {
        try {
            return Excel::download(new RateExport($request), 'rate.xlsx');
            //     return Excel::download(new RateExport($request),'rate.xlsx',null, [
            //         'Content-Type' => 'application/xlsx',
            //         'Cache-Control' => 'max-age=0',
            //         'Access-Control-Allow-Origin' => '*',
            //         'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS',
            //         'Access-Control-Allow-Headers' => 'Content-Type, Authorization',
            //     ]
            // );
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }
}
