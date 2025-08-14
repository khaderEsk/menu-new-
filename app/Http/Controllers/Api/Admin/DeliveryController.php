<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Delivery\DeliveryPriceRequest;
use App\Http\Requests\Delivey\AddRequest;
use App\Http\Requests\Delivey\IdRequest;
use App\Http\Requests\Delivey\ShowAllRequest as DeliveyShowAllRequest;
use App\Http\Requests\Delivey\UpdateRequest;
use App\Http\Resources\DeliveryResource;
use App\Http\Resources\DeliverySitesResource;
use App\Http\Resources\InvoiceUserResource;
use App\Models\DeliveryRoute;
use App\Models\Invoice;
use App\Models\Restaurant;
use App\Models\User;
use App\Services\DeliveryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Throwable;


use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class DeliveryController extends Controller
{
    public function __construct(private DeliveryService $deliveryService) {}

    // Show All deliveries For Admin
    public function showAll(DeliveyShowAllRequest $request): JsonResponse
    {
        try {
            $admin = auth()->user();
            // Pass all necessary parameters to the service.
            $deliveries = $this->deliveryService->paginate(
                $admin->restaurant_id,
                $request->input('per_page', 10),
                $request->input('search') // Pass the search term
            );
            // This part of your original code was already good.
            if ($deliveries->isEmpty()) {
                return $this->successResponse([], trans('locale.dontHaveDelivery'), 200);
            }
            $data = DeliveryResource::collection($deliveries);
            return $this->paginateSuccessResponse($data, trans('locale.foundSuccessfully'), 200);
        } catch (\Throwable $th) {
            report($th); // It's better to log the error.
            return $this->messageErrorResponse('An error occurred while fetching delivery staff.');
        }
    }

    public function showAllSites(DeliveyShowAllRequest $request): JsonResponse
    {
        try {
            $admin = auth()->user();
            // Pass all necessary parameters to the service.
            $deliveries = $this->deliveryService->paginate(
                $admin->restaurant_id,
                $request->input('per_page', 10),
                $request->input('search') // Pass the search term
            );
            // This part of your original code was already good.
            if ($deliveries->isEmpty()) {
                return $this->successResponse([], trans('locale.dontHaveDelivery'), 200);
            }
            $data = DeliverySitesResource::collection($deliveries);
            return $this->paginateSuccessResponse($data, trans('locale.foundSuccessfully'), 200);
        } catch (\Throwable $th) {
            report($th); // It's better to log the error.
            return $this->messageErrorResponse('An error occurred while fetching delivery staff.');
        }
    }

    public function route($id)
    {
        $admin = auth()->user();
        $deliveries = $this->deliveryService->findDelivery(
            $admin->restaurant_id,
            $id,
        );
        return $deliveries;

        $fromLat = $delivery->start_lat;
        $fromLng = $delivery->start_lon;
        $toLat   = $delivery->end_lat;
        $toLng   = $delivery->end_lon;
        $profile = 'driving';

        if (abs($fromLat - $toLat) < 1e-8 && abs($fromLng - $toLng) < 1e-8) {
            throw ValidationException::withMessages([
                'to' => ['نقطة البداية تساوي نقطة النهاية.']
            ]);
        }

        $base = 'https://router.project-osrm.org';
        $coords = "{$fromLng},{$fromLat};{$toLng},{$toLat}";
        $url = "$base/route/v1/{$profile}/{$coords}";
        $query = [
            'overview'     => 'full',      // يعيد خط المسار كاملاً
            'geometries'   => 'geojson',   // أسهل للاستخدام في الخرائط
            'steps'        => 'true',      // بدائل للمسار
            'annotations'  => 'distance,duration'
        ];

        $response = Http::timeout(10)->get($url, $query);

        if (!$response->ok()) {
            return response()->json([
                'message' => 'فشل طلب التوجيه من OSRM',
                'details' => $response->body(),
            ], 502);
        }

        $json = $response->json();

        if (!isset($json['routes'][0])) {
            return response()->json([
                'message' => 'لم يتم العثور على مسار مناسب',
            ], 404);
        }
        $best = $json['routes'][0];
        $result = [
            'distance_m' => $best['distance'],
            'distance_km' => round($best['distance'] / 1000, 3),
            'geometry'   => $best['geometry'],
            'waypoints'  => $json['waypoints'] ?? [],
            'provider'   => 'OSRM',
            'profile'    => $profile,
        ];

        return response()->json($result);
    }

    // Show All deliveries Active
    public function showAllActive(DeliveyShowAllRequest $request): JsonResponse
    {
        try {
            $admin = auth()->user();
            $delivery = $this->deliveryService->all($admin->restaurant_id);
            if (\count($delivery) == 0) {
                return $this->successResponse([], trans('locale.dontHaveDelivery'), 200);
            }
            $data = DeliveryResource::collection($delivery);
            return $this->successResponse($data, trans('locale.foundSuccessfully'), 200);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Add delivery
    public function create(AddRequest $request): JsonResponse
    {
        try {
            // ✅ SEPARATION OF CONCERNS: The controller's job is to get data from the HTTP request.
            $validatedData = $request->validated();
            $imageFile = $request->hasFile('image') ? $request->file('image') : null;

            // The service now handles all creation and file upload logic.
            $delivery = $this->deliveryService->create($validatedData, $imageFile);

            // Check if the service reported a failure.
            if (!$delivery) {
                return $this->messageErrorResponse(trans('locale.creationFailed'), 500);
            }

            // The resource receives a fully-loaded model, making it very fast.
            $data = DeliveryResource::make($delivery);

            return $this->successResponse($data, trans('locale.created'), 201); // 201 Created is the correct status code.

        } catch (\Throwable $th) {
            report($th);
            return $this->messageErrorResponse('An unexpected error occurred while creating the user.');
        }
    }

    // Update delivery
    public function update(UpdateRequest $request): JsonResponse
    {
        try {
            // ✅ SEPARATION OF CONCERNS: The controller just gets the data from the request.
            $validatedData = $request->validated();
            $imageFile = $request->file('image');

            // ✅ FIX: Manually find the User from the ID in the request body.
            // This matches how your API route is configured.
            $delivery = User::findOrFail($validatedData['id']);

            // Optional but recommended: Authorize that the admin can update this specific user.
            // Gate::authorize('update', $delivery);

            // The service now receives the correct, existing user model to update.
            $updatedDelivery = $this->deliveryService->update($delivery, $validatedData, $imageFile);

            // Check if the service reported a failure.
            if (!$updatedDelivery) {
                return $this->messageErrorResponse(trans('locale.invalidItem'), 422); // 422 is a better code for failed updates.
            }

            // The resource receives a fully-loaded model, making it very fast.
            $data = DeliveryResource::make($updatedDelivery);

            return $this->successResponse($data, trans('locale.updated'), 200);
        } catch (\Throwable $th) {
            report($th);
            return $this->messageErrorResponse('An unexpected error occurred while updating the user.');
        }
    }

    // Show delivery By Id
    public function showById(IdRequest $request)
    {
        try {
            $admin = auth()->user();
            $data_val = $request->validated();
            $delivery = $this->deliveryService->show($data_val['id']);
            $data = DeliveryResource::make($delivery);
            return $this->successResponse($data, trans('locale.foundSuccessfully'), 200);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Delete delivery
    public function delete(IdRequest $request)
    {
        try {
            $restaurant_id = auth()->user()->restaurant_id;
            $data_val = $request->validated();
            $delivery = $this->deliveryService->destroy($data_val['id'], $restaurant_id);
            if ($delivery == 0) {
                return $this->messageErrorResponse(trans('locale.invalidItem'), 403);
            }
            return $this->messageSuccessResponse(trans('locale.deleted'), 200);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Active Or DisActive delivery
    public function deactivate(IdRequest $request)
    {
        try {
            $data_val = $request->validated();
            $delivery = $this->deliveryService->show($data_val['id']);
            $data = $this->deliveryService->activeOrDesactive($delivery);
            if ($data == 0) {
                return $this->messageErrorResponse(trans('locale.invalidItem'), 403);
            }
            return $this->messageSuccessResponse(trans('locale.successfully'), 200);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Show All deliveries For Admin
    public function showOrderDelivery(IdRequest $request)
    {
        try {
            $user = auth()->user();
            $invoices = Invoice::with('orders')->whereDeliveryId($request->id)->paginate($request->input('per_page', 10));
            if (\count($invoices) == 0) {
                return $this->successResponse([], trans('locale.dontHaveOrder'), 200);
            }
            $data = InvoiceUserResource::collection($invoices);
            return $this->paginateSuccessResponse($data, trans('locale.foundSuccessfully'), 200);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // add Delivery Price
    public function addDeliveryPrice(DeliveryPriceRequest $request)
    {
        try {
            $admin = auth()->user();
            $data_val = $request->validated();
            $invoice = Invoice::whereId($data_val['id'])->first();
            $total = $invoice->total + $data_val['delivery_price'];
            Invoice::whereId($data_val['id'])->update([
                'delivery_price' => $data_val['delivery_price'],
                'total' => $total,
            ]);
            return $this->messageSuccessResponse(trans('locale.successfully'), 200);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }
}
