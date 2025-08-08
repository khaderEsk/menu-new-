<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Qr\AddQrRequest;
use App\Http\Requests\Qr\IdQrRequest;
use App\Http\Resources\QrOfflineResource;
use App\Models\IpQr;
use App\Models\Restaurant;
use App\Services\QrService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Throwable;

class IpQrController extends Controller
{
    public function __construct(private QrService $qrService)
    {
    }
    public function showAll(Request $request)
    {
        try{
            $admin = auth()->user();
            $qr = $this->qrService->paginate($request->per_page);
            $data = QrOfflineResource::collection($qr);
            return $this->paginateSuccessResponse($data,trans('locale.foundSuccessfully'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Show qr By Id
    public function showById(IdQrRequest $request)
    {
        try{
            $data = $request->validated();
            $qr = $this->qrService->show($data['restaurant_id']);
            if(is_null($qr))
                return $this->messageErrorResponse(trans('locale.youDontHaveQR'), 400);
            $data = QrOfflineResource::make($qr);
            return $this->successResponse($data,trans('locale.foundSuccessfully'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    public function create(AddQrRequest $request)
    {
        try{
            $data = $request->validated();
            $ip_qr = IpQr::whereRestaurantId($data['restaurant_id'])->first();
            $admin = Restaurant::whereId($data['restaurant_id'])->first();
            if(!is_null($ip_qr))
            {
                $qr = $this->qrService->update($ip_qr->id, $ip_qr->qr_code, $data);
                return $this->messageSuccessResponse(trans('locale.updated'),200);
            }
            $data['restaurant_url'] = "https://menu.le.sy/".$admin->name;
            $qr = $this->qrService->create($data);
            return $this->messageSuccessResponse(trans('locale.created'),200);

        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }
}
