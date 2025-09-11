<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enum\InvoiceStatus;
use App\Exports\InvoiceExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CouponInvoiceRequest;
use App\Http\Requests\Invoice\AddRequest;
use App\Http\Requests\Invoice\IdRequest;
use App\Http\Requests\Invoice\ShowRequest;
use App\Http\Requests\Invoice\UpdateRequest;
use App\Http\Resources\InvoiceResources;
use App\Http\Resources\OrderResource;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\EmployeeTable;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\Table;
use App\Services\InvoiceService;
use App\Services\RecordCleanupService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class InvoiceController extends Controller
{
    public function __construct(private InvoiceService $invoiceService, private RecordCleanupService $recordCleanupService) {}

    public function showInvoice(IdRequest $request): JsonResponse
    {
        try {
            $admin = auth()->user();
            $validated = $request->validated();

            // 1. Call the single, clean service method to get all prepared data.
            $invoiceData = $this->invoiceService->getInvoiceDetails($validated['id'], $admin->restaurant_id);

            // 2. The service returns a structured array, so we just need to format the final response.
            $data = [
                'orders' => $invoiceData['orders'],
                'invoice' => $invoiceData['invoice'],
            ];

            return response()->json([
                'status' => true,
                'data' => $data,
                'message' => trans('locale.foundSuccessfully')
            ], 200);
        } catch (ModelNotFoundException $e) {
            // If the service couldn't find the invoice for this restaurant.
            return $this->messageErrorResponse(trans('locale.invalidItem'), 404);
        } catch (ValidationException $e) {
            // If the service found that not all orders were 'done'.
            return $this->messageErrorResponse($e->errors()['invoice'][0], 400);
        } catch (\Throwable $th) {
            report($th);
            return $this->messageErrorResponse('An unexpected error occurred.');
        }
    }

    public function showAll(Request $request): JsonResponse
    {
        try {
            $admin = auth()->user();

            // 1. Call the single, clean service method to get all the data.
            $result = $this->invoiceService->getFilteredInvoices(
                $admin->restaurant_id,
                $request->all() // Pass all request params as filters
            );

            $paginatedInvoices = $result['invoices'];
            $totalSum = $result['totalSum'];

            // 2. Check if the result is empty.
            if ($paginatedInvoices->isEmpty()) {
                return $this->successResponse([], trans('locale.dontHaveInvoices'), 200);
            }

            // 3. ✅ PRESERVE RESPONSE STRUCTURE: Build the final JSON response
            // to exactly match the original structure, including the custom 'total' field.
            $data = InvoiceResources::collection($paginatedInvoices);
            $meta = [
                'total' => $data->total(),
                'count' => $data->count(),
                'per_page' => $data->perPage(),
                'current_page' => $data->currentPage(),
                'total_pages' => $data->lastPage(),
            ];

            return response()->json([
                'status' => true,
                'data' => $data,
                'total' => $totalSum, // The custom total field for the mobile app
                'meta' => $meta,
                'message' => trans('locale.foundSuccessfully')
            ], 200);
        } catch (\Throwable $th) {
            report($th);
            return $this->messageErrorResponse('An error occurred while fetching invoices.');
        }
    }

    public function create(AddRequest $request)
    {
        try {
            $data = $request->validated();
            $restaurant_id = auth()->user()->restaurant_id;
            $data['status'] = 1;
            $invoice = $this->invoiceService->create($restaurant_id, $data);
            $data = InvoiceResources::make($invoice);
            return $this->successResponse($data, trans('locale.created'), 200);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    public function showById(IdRequest $request)
    {
        try {
            $admin = auth()->user();
            $invoice = $this->invoiceService->show($request->id, $admin->restaurant_id);
            $data = InvoiceResources::make($invoice);
            return $this->SuccessResponse($data, trans('locale.foundSuccessfully'), 200);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    public function createInvoiceTable(AddRequest $request)
    {
        try {
            $admin = auth()->user();
            $validatedData = $request->validated();
            // 1. Call the single, clean service method to perform the entire process.
            $invoice = $this->invoiceService->createFromTableOrders(
                $validatedData['table_id'],
                $admin->restaurant_id,
                $validatedData // Pass all validated data to the service
            );

            // 2. The service returns a complete invoice, ready for the resource.
            $data = InvoiceResources::make($invoice);
            return $this->successResponse($data, trans('locale.successfully'), 201); // 201 for created

        } catch (ValidationException $e) {
            // Catch the specific error for when no billable orders are found.
            return $this->messageErrorResponse($e->errors()['orders'][0], 200);
        } catch (\Throwable $th) {
            report($th);
            Log::info('عدد سجلات الجداول:', ['count' => \App\Models\Table::count()]);
            return $this->messageErrorResponse('An error occurred while creating the invoice.');
        }
    }

    public function createCouponInvoice(CouponInvoiceRequest $request): JsonResponse
    {
        try {
            $admin = auth()->user();
            // 1. Call the single, clean service method to perform the entire process.
            $invoice = Invoice::find($request->invoice_id);
            $coupon = Coupon::find($request->coupon_id);

            $discountAmount = ($invoice->total * $coupon->percent) / 100;
            $newTotal = $invoice->total - $discountAmount;

            $invoice->update([
                'discount' => $discountAmount,
                'total' => $newTotal
            ]);
            // $invoice->save();
            // 2. The service returns a complete invoice, ready for the resource.
            // return $this->successResponse("dsdsd", trans('locale.successfully'), 201);
            $data = InvoiceResources::make($invoice);
            return $this->successResponse($data, trans('locale.successfully'), 201); // 201 for created

        } catch (ValidationException $e) {
            // Catch the specific error for when no billable orders are found.
            return $this->messageErrorResponse($e->errors()['orders'][0], 200);
        } catch (\Throwable $th) {
            report($th);
            Log::info('عدد سجلات الجداول:', ['count' => \App\Models\Table::count()]);
            return $this->messageErrorResponse('An error occurred while creating the invoice.');
        }
    }
    public function update(UpdateRequest $request): JsonResponse
    {
        try {
            $admin = auth()->user();
            $validated = $request->validated();

            // 1. Call the single, clean service method to perform all actions.
            $invoice = $this->invoiceService->markAsPaid($validated['id'], $admin);

            // 2. The service returns a complete invoice, ready for the resource.
            $data = InvoiceResources::make($invoice);
            return $this->successResponse($data, trans('locale.updated'), 200);
        } catch (ModelNotFoundException $e) {
            // Catch the specific error for an invoice that doesn't exist for this restaurant.
            return $this->messageErrorResponse(trans('locale.invalidItem'), 404);
        } catch (ValidationException $e) {
            // Catch the specific error for an invoice that is already paid.
            return $this->messageErrorResponse($e->errors()['invoice'][0], 400);
        } catch (\Throwable $th) {
            report($th);
            return $this->messageErrorResponse('An unexpected error occurred while updating the invoice.');
        }
    }

    public function Received(UpdateRequest $request): JsonResponse
    {
        try {
            $admin = auth()->user();
            $validated = $request->validated();

            // 1. Call the single, clean service method to perform all actions.
            $this->invoiceService->markAsReceivedByAccountant($validated['id'], $admin);

            // 2. Return the simple success message, matching the original code's primary path.
            return $this->messageSuccessResponse(trans('locale.successfully'), 200);
        } catch (ModelNotFoundException $e) {
            // Catch the specific error for an invoice that doesn't exist for this restaurant.
            return $this->messageErrorResponse(trans('locale.invalidItem'), 404);
        } catch (ValidationException $e) {
            // Catch the specific error for an invoice that is already received.
            return $this->messageErrorResponse($e->errors()['invoice'][0], 400);
        } catch (AuthorizationException $e) {
            // Catch the specific error for when the user is not an accountant.
            return $this->messageErrorResponse($e->getMessage(), 403);
        } catch (\Throwable $th) {
            report($th);
            return $this->messageErrorResponse('An unexpected error occurred while updating the invoice.');
        }
    }
    public function export(Request $request)
    {
        try {
            $filters = [
                'restaurant_id' => auth()->user()->restaurant_id,
                'date' => $request->input('date'),
            ];

            // 1. Call the service to get the fully-loaded, efficient collection of invoices.
            $invoicesToExport = $this->invoiceService->getInvoicesForExport($filters);

            // Optional: Check if there's anything to export.
            if ($invoicesToExport->isEmpty()) {
                return $this->messageErrorResponse('No invoices found for the selected criteria.', 404);
            }

            // 2. Pass the collection directly to the export class.
            return Excel::download(new InvoiceExport($invoicesToExport), 'invoices-report.xlsx');
        } catch (\Throwable $th) {
            report($th);
            return $this->messageErrorResponse('An error occurred while generating the export.');
        }
    }
}
