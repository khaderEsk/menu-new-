<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\AddServiceRequest;
use App\Http\Requests\Service\AddRequest;
use App\Http\Requests\Service\IdRequest;
use App\Http\Requests\Service\UpdateRequest;
use App\Http\Resources\ServiceResource;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\Service;
use App\Models\ServiceTranslation;
use App\Models\Table;
use App\Services\ServiceService;
use Illuminate\Http\Request;
use Throwable;

class ServiceController extends Controller
{
    public function __construct(private ServiceService $serviceService)
    {
    }

    // Show All Tables For Admin
    public function showAll(Request $request)
    {
        try{
            $admin = auth()->user();
            $services= $this->serviceService->paginate($admin->restaurant_id,$request->input('per_page', 10));
            if (\count($services) == 0) {
                return $this->successResponse([],trans('locale.dontHaveService'),200);
            }
            $data = ServiceResource::collection($services);
            return $this->paginateSuccessResponse($data,trans('locale.foundSuccessfully'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Add Table
    public function create(AddRequest $request)
    {
        try{
            $restaurant_id = auth()->user()->restaurant_id;
            $table = $this->serviceService->create($restaurant_id,$request->validated());
            $data = ServiceResource::make($table);
            return $this->successResponse($data,trans('locale.created'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Update Table
    public function update(UpdateRequest $request)
    {
        try{
            $admin = auth()->user();
            $item = $this->serviceService->update($admin->restaurant_id,$request->validated());
            if($item == 0)
            {
                return $this->messageErrorResponse(trans('locale.invalidItem'),403);
            }
            $showItem = $this->serviceService->show($admin->restaurant_id,$request->validated());
            $data = ServiceResource::make($showItem);
            return $this->successResponse($data,trans('locale.updated'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Show Table By Id
    public function showById(IdRequest $request)
    {
        try{
            $admin = auth()->user();
            $table = $this->serviceService->show($admin->restaurant_id,$request->validated());
            $data = ServiceResource::make($table);
            return $this->successResponse($data,trans('locale.foundSuccessfully'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Delete Table
    public function delete(IdRequest $request)
    {
        try{
            $restaurant_id = auth()->user()->restaurant_id;
            $table = $this->serviceService->destroy($request->validated(),$restaurant_id);
            if($table == 0)
            {
                return $this->messageErrorResponse(trans('locale.invalidItem'),403);
            }
            return $this->messageSuccessResponse(trans('locale.deleted'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }
    public function serviceToOrder(AddServiceRequest $request)
    {
        try{
            $restaurant_id = auth()->user()->restaurant_id;
            $data_val = $request->validated();
            $service = Service::whereId($data_val['service_id'])->first();
            $restaurant = Restaurant::whereId($restaurant_id)->first();
            $en = ServiceTranslation::where('locale','en')->whereServiceId($service->id)->first();
            $ar = ServiceTranslation::where('locale','ar')->whereServiceId($service->id)->first();
            $name_en = $en->name;
            $name_ar = $ar->name;
            if($request->has('invoice_id'))
            {
                if(!is_null($request->invoice_id))
                {
                    $invoice = Invoice::whereId($data_val['invoice_id'])->first();
                    $order = Order::create([
                        'price' => $service->price,
                        'count' => $data_val['count'],
                        'table_id' => $invoice->table_id,
                        'invoice_id' => $data_val['invoice_id'],
                        'restaurant_id' => $restaurant_id,
                        'en' => [
                            'name' => $name_en,
                            'type' => 'service',
                        ],
                        'ar' => [
                            'name' => $name_ar,
                            'type' => 'خدمة',
                        ],
                    ]);
                    $order->status = 'done';
                    $order->save();
                    $sum = $service->price * $data_val['count'];
                    $consumer_spending = $sum * $restaurant->consumer_spending/100;
                    $local_administration = $sum * $restaurant->local_administration/100;
                    $reconstruction = $sum * $restaurant->reconstruction/100;
                    $total = $sum + $consumer_spending + $local_administration + $reconstruction;

                    $invoice_consumer_spending = $invoice->consumer_spending + $consumer_spending;
                    $invoice_local_administration = $invoice->local_administration + $local_administration;
                    $invoice_reconstruction = $invoice->reconstruction + $reconstruction;
                    $invoice_price = $invoice->price + $sum;
                    $invoice_total = $invoice->total + $total;

                    $invoice->update([
                        'price' => round($invoice_price, 0),
                        'consumer_spending' => round($invoice_consumer_spending, 0),
                        'local_administration' => round($invoice_local_administration, 0),
                        'reconstruction' => round($invoice_reconstruction, 0),
                        'total' => round($invoice_total, 0)
                    ]);
                }
            }
            if($request->has('table_id'))
            {
                if(!is_null($request->table_id))
                {

                    $table = Table::whereId($data_val['table_id'])->first();
                    $order = Order::create([
                        'price' => $service->price,
                        'count' => $data_val['count'],
                        'table_id' => $table->id,
                        'invoice_id' => $data_val['invoice_id'] ?? null,
                        'restaurant_id' => $restaurant_id,
                        'en' => [
                            'name' => $name_en,
                            'type' => 'service',
                        ],
                        'ar' => [
                            'name' => $name_ar,
                            'type' => 'خدمة',
                        ],
                    ]);
                    $order->status = 'done';
                    $order->save();
                    $sum = $service->price * $data_val['count'];
                }
            }
            return $this->messageSuccessResponse(trans('locale.created'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }
}
