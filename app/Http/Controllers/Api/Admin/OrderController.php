<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enum\InvoiceStatus;
use App\Enum\OrderStatus;
use App\Events\TableOrderStatusUpdated;
use App\Events\TableUpdatedEvent;
use App\Exports\SalesInventoryExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Order\AddRequest;
use App\Http\Requests\Order\AddRequest2;
use App\Http\Requests\Order\IdRequest;
use App\Http\Requests\Order\ShowRequest;
use App\Http\Requests\Order\UpdateRequest;
use App\Http\Requests\Table\IdRequest as TableIdRequest;
use App\Http\Resources\OrderResource;
use App\Http\Resources\OrderResource2;
use App\Http\Resources\TableResource;
use App\Models\Admin;
use App\Models\EmployeeTable;
use App\Models\Invoice;
use App\Models\ItemTranslation;
use App\Models\Order;
use App\Models\OrderTranslation;
use App\Models\Restaurant;
use App\Models\Table;
use App\Services\FirebaseService;
use App\Services\InvoiceService;
use App\Services\OrderService;
use Carbon\Carbon;
use GPBMetadata\Google\Api\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log as FacadesLog;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class OrderController extends Controller
{
    public function __construct(private OrderService $orderService, private InvoiceService $invoiceService, private FirebaseService $firebaseService) {}

    // Show All orders For Admin
    public function showAll(ShowRequest $request): JsonResponse
    {
        try {
            $admin = auth()->user()->load('type', 'categories.translations'); // Pre-load relationships for the service
            $filters = $request->validated();

            // 1. Call the service to get the processed data.
            $result = $this->orderService->getFilteredOrders($admin, $filters);

            // 2. Check if the result is the custom grouped data.
            if (isset($result['meta'])) {
                if (empty($result['data'])) {
                    return $this->successResponse([], trans('locale.dontHaveOrder'), 200);
                }
                // The service already formatted the response, so we return it directly.
                return response()->json([
                    'status' => true,
                    'data' => $result['data'],
                    'meta' => $result['meta'],
                    'message' => trans('locale.ordersFound')
                ]);
            }

            // 3. Otherwise, it's a standard paginator.
            if ($result->isEmpty()) {
                return $this->successResponse([], trans('locale.dontHaveOrder'), 200);
            }

            $data = OrderResource::collection($result);
            return $this->paginateSuccessResponse($data, trans('locale.ordersFound'), 200);
        } catch (\Throwable $th) {
            report($th);
            return $this->messageErrorResponse('An error occurred while fetching orders.');
        }
    }

    // Add order
    public function create(AddRequest $request): JsonResponse
    {
        try {
            $admin = auth()->user();
            $data = $request->validated();
            // 1. Prepare the data for the service.
            // The controller is responsible for getting IDs from the request context.
            if ($request->has('invoice_id')) {
                $invoice = Invoice::findOrFail($data['invoice_id']);
                $data['table_id'] = $invoice->table_id;
                $data['restaurant_id'] = $invoice->restaurant_id;
            } else {
                $data['restaurant_id'] = $admin->restaurant_id;
            }
            // 2. Call the service to perform the entire creation process.
            // The service returns the updated table model.
            $updatedTable = $this->orderService->createOrdersFromRequest($data, $admin);

            // 3. Dispatch the event.
            // It's cleaner to just send the updated table data.
            // The frontend can then update its state for that specific table.
            event(new TableUpdatedEvent(TableResource::make($updatedTable)));

            // 4. Return the simple success message as per the original code.
            return $this->messageSuccessResponse(trans('locale.created'), 201); // 201 is better for creation

        } catch (\Throwable $th) {
            report($th);
            FacadesLog::info($th);
            return $this->messageErrorResponse('An error occurred while creating the order.');
        }
    }

    // Add order2
    public function create2(AddRequest2 $request): JsonResponse
    {
        try {
            $validatedData = $request->validated();

            // If restaurant_id is not provided, set it from the authenticated user.
            if (empty($validatedData['restaurant_id'])) {
                $validatedData['restaurant_id'] = auth()->user()->restaurant_id;
            }

            // Call the new, clean service method.
            $this->orderService->createSingleOrderFromRequest($validatedData);

            // Return the simple success message as per the original code.
            return $this->messageSuccessResponse(trans('locale.created'), 201); // 201 is better for creation

        } catch (\Throwable $th) {
            report($th);
            return $this->messageErrorResponse('An error occurred while creating the order.');
        }
    }

    // Show order By Id
    public function showById(IdRequest $request): JsonResponse
    {
        try {
            $admin = auth()->user();
            $validated = $request->validated();

            // ✅ BUG FIX: Call the service with the correct arguments.
            // Pass the order ID from the request and the admin's restaurant ID for security.
            $order = $this->orderService->show($validated['id'], $admin->restaurant_id);

            // The resource receives a fully-loaded and secure model.
            $data = OrderResource::make($order);
            return $this->successResponse($data, trans('locale.orderFound'), 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // It's good practice to catch this specific exception for a clear "not found" message.
            return $this->messageErrorResponse(trans('locale.orderNotFound'), 404);
        } catch (\Throwable $th) {
            report($th);
            return $this->messageErrorResponse('An error occurred while fetching the order.');
        }
    }

    // Delete order
    public function delete(IdRequest $request): JsonResponse
    {
        try {
            $admin = auth()->user();
            $validated = $request->validated();

            // Call the secure and robust service method.
            $this->invoiceService->destroy($validated['id'], $admin->restaurant_id);

            return $this->messageSuccessResponse(trans('locale.deleted'), 200);
        } catch (ModelNotFoundException $e) {
            // Catch the specific error for an invoice that doesn't exist.
            return $this->messageErrorResponse(trans('locale.invalidItem'), 404);
        } catch (ValidationException $e) {
            // Catch the specific error for an invoice that cannot be deleted.
            return $this->messageErrorResponse($e->errors()['invoice'][0], 400);
        } catch (\Throwable $th) {
            report($th);
            return $this->messageErrorResponse('An unexpected error occurred.');
        }
    }

    public function acceptOrders(TableIdRequest $request): JsonResponse
    {
        try {

            $admin = auth()->user();
            $validated = $request->validated();
            // 1. Determine the target status. Default to 'accepted' if not provided.
            $newStatus = $validated['status'] ?? 'accepted';
            $tableId = $validated['id'];
            // 2. Call the single, clean service method to perform all actions.
            $updatedCount = $this->orderService->updateOrderStatusForTable($tableId, $newStatus, $admin);
            // 3. Check if any orders were actually updated.
            if ($updatedCount == 0) {
                return $this->messageErrorResponse(trans('locale.doNotHaveOrders'), 404); // 404 is better if no orders are found
            }
            // 4. Dispatch the event with the paginated list of all tables (preserving original behavior).
            $tables = Table::where('restaurant_id', $admin->restaurant_id)->paginate(15);
            $paginatedResource = $this->paginateSuccessResponse(TableResource::collection($tables), '', 200);
            event(new TableUpdatedEvent($paginatedResource->getData(true)));

            // 5. Return the simple success message.
            return $this->messageSuccessResponse(trans('locale.successfully'), 200);
        } catch (\Throwable $th) {
            report($th);
            return $this->messageErrorResponse('An unexpected error occurred while updating orders.');
        }
    }

    public function exportSalesInventory(ShowRequest $request)
    {
        try {
            $admin = auth()->user()->load('type'); // Eager-load for the service
            $filters = $request->validated();

            // 1. Call the dedicated service method to get the prepared data collection.
            $exportData = $this->orderService->getGroupedCompletedOrdersForExport($admin, $filters);

            // 2. Check if there is any data to export.
            if ($exportData->isEmpty()) {
                // Return a JSON response indicating no data, as in the original code.
                return $this->messageErrorResponse(trans('locale.dontHaveOrder'), 404);
            }

            // 3. Pass the clean collection to the export class and download the file.
            $export = new SalesInventoryExport($exportData);
            return Excel::download($export, 'sales-inventory-report.xlsx');
        } catch (\Throwable $th) {
            report($th);
            return $this->messageErrorResponse('An error occurred while generating the export.');
        }
    }

    public function update(UpdateRequest $request): JsonResponse
    {
        try {
            $admin = $request->user();
            $validated = $request->validated();
            $newStatus = OrderStatus::fromString($validated['status']); // Convert string to Enum
            $count = $validated['count'];
            // Manually find the Order from the ID in the request body.
            $order = Order::findOrFail($validated['id']);

            // 1. Call the single, clean service method to perform all actions.
            $this->orderService->updateOrderStatus($order, $newStatus, $admin , $count);

            // 2. Broadcast the table updates (this is a response-related side effect).
            $this->broadcastTableUpdates($admin->restaurant_id, $request->input('per_page', 50));

            // 3. Return the simple success message.
            return $this->messageSuccessResponse(trans('locale.successfully'), 200);
        } catch (\Throwable $th) {
            report($th);
            return $this->messageErrorResponse('An error occurred while updating the order.');
        }
    }

    /**
     * A private helper to handle broadcasting the TableUpdatedEvent.
     */
    private function broadcastTableUpdates(int $restaurantId, int $perPage): void
    {
        $tables = Table::where('restaurant_id', $restaurantId)->paginate($perPage);
        $paginatedResource = $this->paginateSuccessResponse(TableResource::collection($tables), '', 200);
        event(new TableUpdatedEvent($paginatedResource->getData(true)));
    }
}
