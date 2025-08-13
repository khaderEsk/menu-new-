<?php

namespace App\Services;

use App\Enum\InvoiceStatus;
use App\Http\Resources\InvoiceResources;
use App\Models\Admin;
use App\Models\Customer;
use App\Models\EmployeeTable;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceService
{
    public function __construct(private OsrmService $osrmService) {}

    // to show paginate invoice active
    public function paginate($id, $num)
    {
        $invoices = Invoice::whereRestaurantId($id)->latest()->paginate($num);
        return $invoices;
    }

    // to create invoice
    public function create(int $restaurantId, array $data): Invoice
    {
        return DB::transaction(function () use ($restaurantId, $data) {
            $data['restaurant_id'] = $restaurantId;
            $maxNum = Invoice::where('restaurant_id', $restaurantId)->max('num');
            $data['num'] = ($maxNum ?? 0) + 1;
            return Invoice::create($data);
        });
    }

    // to update invoice
    public function markAsPaid(int $invoiceId, Admin $currentUser): Invoice
    {
        // ✅ DATA INTEGRITY: Wrap the entire multi-step process in a transaction.
        return DB::transaction(function () use ($invoiceId, $currentUser) {
            // 1. ✅ SECURITY: Securely fetch the invoice, ensuring it belongs to the admin's restaurant.
            $invoice = Invoice::where('id', $invoiceId)
                ->where('restaurant_id', $currentUser->restaurant_id)
                ->firstOrFail();

            // 2. ✅ BUSINESS LOGIC: Check if the invoice is already paid.
            if ($invoice->status === InvoiceStatus::COMPLETED->value) {
                throw ValidationException::withMessages([
                    'invoice' => trans('locale.ThisInvoiceIsPaid'),
                ]);
            }

            // 3. Update the invoice status and assign the admin who completed it.
            $invoice->update([
                'status' => InvoiceStatus::COMPLETED->value,
                'admin_id' => $currentUser->id,
            ]);

            // 4. Handle side-effects using clean, private helper methods.
            $this->trackWaiterTimeForInvoice($invoice, $currentUser);
            $this->revokeCustomerTokensForInvoice($invoice);

            // 5. Return the fully loaded invoice, ready for the resource.
            return $invoice->load(['table', 'admin', 'orders']);
        });
    }

    /**
     * A private helper to create the EmployeeTable time tracking record if the user is a waiter.
     */
    private function trackWaiterTimeForInvoice(Invoice $invoice, Admin $admin): void
    {
        // This logic only runs if the user is an employee and specifically a 'waiter'.
        if ($admin->hasRole('employee') && $admin->type?->name === 'waiter' && $invoice->table) {
            $diff = now()->diff($invoice->table->updated_at);
            $timeString = sprintf('%02d:%02d:%02d', $diff->h, $diff->i, $diff->s);

            EmployeeTable::create([
                'order_time' => $timeString,
                'table_id' => $invoice->table_id,
                'admin_id' => $admin->id,
                'restaurant_id' => $admin->restaurant_id,
            ]);
        }
    }

    /**
     * A private helper to find all customers associated with an invoice's orders and delete their auth tokens.
     */
    private function revokeCustomerTokensForInvoice(Invoice $invoice): void
    {
        // Get a unique list of customer IDs from all orders on the invoice.
        $customerIds = $invoice->orders()
            ->whereNotNull('customer_id')
            ->distinct()
            ->pluck('customer_id');

        if ($customerIds->isNotEmpty()) {
            // Find all customers and delete their tokens.
            Customer::whereIn('id', $customerIds)->get()->each(function ($customer) {
                $customer->tokens()->delete();
            });
        }
    }

    // to update invoice
    public function markAsReceivedByAccountant(int $invoiceId, Admin $currentUser): Invoice
    {
        // 1. ✅ AUTHORIZATION: First, ensure the user is an accountant.
        if ($currentUser->type?->name !== 'accountant') {
            // Throw a specific exception that the controller can catch.
            throw new AuthorizationException(trans('locale.youCantDoThisOperation'));
        }

        // 2. ✅ DATA INTEGRITY: Wrap the entire multi-step process in a transaction.
        return DB::transaction(function () use ($invoiceId, $currentUser) {
            // 3. ✅ SECURITY: Securely fetch the invoice, ensuring it belongs to the accountant's restaurant.
            $invoice = Invoice::with('table') // Eager-load the table for the time tracking step
                ->where('id', $invoiceId)
                ->where('restaurant_id', $currentUser->restaurant_id)
                ->firstOrFail();

            // 4. ✅ BUSINESS LOGIC: Check if the invoice is already completed.
            if ($invoice->status === InvoiceStatus::COMPLETED->value) {
                throw ValidationException::withMessages([
                    'invoice' => trans('locale.ThisInvoiceIsReceived'),
                ]);
            }

            // 5. Update the invoice status.
            $invoice->update(['status' => InvoiceStatus::COMPLETED->value]);

            // 6. Handle the time tracking side-effect using a clean helper method.
            $this->trackAccountantTimeForInvoice($invoice, $currentUser);

            return $invoice;
        });
    }

    /**
     * A private helper to create the EmployeeTable time tracking record for the accountant.
     */
    private function trackAccountantTimeForInvoice(Invoice $invoice, Admin $accountant): void
    {
        // Ensure the invoice has a table to track time against.
        if (!$invoice->table) {
            return;
        }

        $diff = now()->diff($invoice->table->updated_at);
        $timeString = sprintf('%02d:%02d:%02d', $diff->h, $diff->i, $diff->s);

        EmployeeTable::create([
            'order_time' => $timeString,
            'table_id' => $invoice->table_id,
            'admin_id' => $accountant->id,
            'restaurant_id' => $accountant->restaurant_id,
        ]);
    }

    // to update status invoice
    public function updateStatus($data)
    {
        $invoice = Invoice::whereId($data['id'])->update($data);
        return $invoice;
    }

    // to show a invoice
    public function show(int $invoiceId, int $restaurantId)
    {
        return Invoice::with(['orders', 'table', 'admin'])
            ->where('restaurant_id', $restaurantId)
            ->findOrFail($invoiceId);
    }

    // to delete a invoice
    public function destroy($id, $restaurant_id)
    {
        return Invoice::whereRestaurantId($restaurant_id)->whereId($id)->forceDelete();
    }

    public function activeOrDesactive($data, $admin)
    {
        if ($data['is_active'] == 1) {
            $invoice = Invoice::whereSuperAdminId($admin)->whereId($data['id'])->update([
                'is_active' => 0,
            ]);
        } else {
            $invoice = Invoice::whereSuperAdminId($admin)->whereId($data['id'])->update([
                'is_active' => 1,
            ]);
        }
        return $invoice;
    }

    public function search($data, $num)
    {
        $invoice = Invoice::whereTranslationLike('name', "%$data%")->latest()->paginate($num);
        return $invoice;
    }

    // to accept order by delivery
    public function acceptOrder($user_id, $data)
    {
        $orders = Invoice::whereId($data['invoice_id'])->update([
            'user_id' => $user_id,
            'accepted' => $data['accept'],
        ]);
        return $orders;
    }

    // to show order by delivery
    public function showOrder($id, $user_id, $num)
    {
        $invoices = Invoice::with('orders')->whereRestaurantId($id)->whereDeliveryId($user_id)->latest()->paginate($num);
        return $invoices;
    }

    /**
     * @throws ValidationException
     */
    public function getInvoiceDetails(int $invoiceId, int $restaurantId): array
    {
        // 1. ✅ FIX: Securely fetch the invoice and its orders without the strict date constraint.
        $invoice = Invoice::with(['orders.translations'])
            ->where('id', $invoiceId)
            ->where('restaurant_id', $restaurantId)
            ->firstOrFail();

        $restaurant = Restaurant::find($restaurantId);

        // 2. ✅ BUSINESS LOGIC: Check if the invoice meets the specific conditions for an update.
        // This logic now correctly checks the date range and status.
        $isRecent = Carbon::parse($invoice->created_at)->isBetween(Carbon::yesterday(), Carbon::tomorrow());

        if ($invoice->status == 2 && $isRecent) {
            $this->validateAllOrdersAreDone($invoice->orders);
            $this->recalculateAndSaveInvoiceTotals($invoice, $restaurant);
        }

        // 3. ✅ DATA FORMATTING: Group and format the orders exactly as the original code did.
        $formattedOrders = $this->groupAndFormatOrdersForResponse($invoice->orders);

        // 4. Return all the prepared data in a structured array.
        return [
            'orders' => $formattedOrders,
            'invoice' => InvoiceResources::make($invoice->fresh()), // Use fresh() to get updated totals
        ];
    }

    /**
     * A private helper to check if all orders on an invoice are 'done'.
     * Throws a specific, catchable exception if not.
     * @throws ValidationException
     */
    private function validateAllOrdersAreDone(Collection $orders): void
    {
        $allDone = $orders->every(fn($order) => $order->status === 'done');

        if (!$allDone) {
            throw ValidationException::withMessages([
                'invoice' => trans('locale.youCantRequestInvoice'),
            ]);
        }
    }

    /**
     * A private helper to recalculate and update the invoice totals.
     */
    public function recalculateAndSaveInvoiceTotals(Invoice $invoice): void
    {
        $restaurant = $invoice->restaurant;
        // Efficiently calculate the sum directly from the database relationship
        $sum = $invoice->orders()->sum(DB::raw('price * count'));

        if ($restaurant->is_taxes == 1) {
            $consumer_spending = $sum * $restaurant->consumer_spending / 100;
            $local_administration = $sum * $restaurant->local_administration / 100;
            $reconstruction = $sum * $restaurant->reconstruction / 100;
            $total = $sum + $consumer_spending + $local_administration + $reconstruction;
        } else {
            $consumer_spending = 1;
            $local_administration = 1;
            $reconstruction = 1;
            $total = $sum;
        }

        $invoice->update([
            'price' => round($sum, 0),
            'consumer_spending' => round($consumer_spending, 0),
            'local_administration' => round($local_administration, 0),
            'reconstruction' => round($reconstruction, 0),
            'total' => round($total, 0),
        ]);
    }

    /**
     * A private helper to group and format orders for the final JSON response.
     */
    private function groupAndFormatOrdersForResponse(Collection $orders): array
    {
        if ($orders->isEmpty()) {
            return [];
        }

        $groupedItems = $orders->groupBy(fn($item) => $item->name . '-' . $item->created_at->format('Y-m-d'))
            ->map(function ($items) {
                $firstItem = $items->first();
                $total = $firstItem->price * $items->sum('count');

                return [
                    'total' => number_format($total),
                    'count' => $items->sum('count'),
                    'created_at' => $firstItem->created_at->format('Y-m-d'),
                    'name' => $firstItem->name,
                    'status' => $firstItem->status,
                    'price' => number_format($firstItem->price),
                    "name_en" => $firstItem->translate('en')->name,
                    "name_ar" => $firstItem->translate('ar')->name,
                    "type_en" => $firstItem->translate('en')->type,
                    "type_ar" => $firstItem->translate('ar')->type,
                    "translations" => $firstItem->translations,
                ];
            });

        return array_values($groupedItems->toArray());
    }

    public function getFilteredInvoices(int $restaurantId, array $filters): array
    {
        // 1. Start with a base query, secured to the restaurant_id.
        $query = Invoice::query()
            ->where('restaurant_id', $restaurantId)
            ->with(['table', 'admin']); // Eager-load relationships for the resource

        // 2. Apply all the dynamic filters from the request.
        $this->applyInvoiceFilters($query, $filters);

        // 3. ✅ EFFICIENCY: Calculate the total sum of the 'total' column for the *filtered* query.
        // This is thousands of times faster than looping in PHP.
        $totalSum = $query->sum('total');

        // 4. Now, execute the paginated query.
        $paginatedInvoices = $query->latest()->paginate($filters['per_page'] ?? 10);

        // 5. Return both the paginated results and the calculated sum.
        return [
            'invoices' => $paginatedInvoices,
            'totalSum' => $totalSum,
        ];
    }

    /**
     * A private helper method to apply all the filters to the query builder.
     * This keeps the main method clean and organized.
     */
    private function applyInvoiceFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['table_id'])) {
            $query->where('table_id', $filters['table_id']);
        }
        if (!empty($filters['admin_id'])) {
            $query->where('admin_id', $filters['admin_id']);
        }
        if (isset($filters['status'])) { // Use isset to allow for status '0'
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['date'])) {
            $query->whereDate('created_at', $filters['date']);
        }
        if (!empty($filters['from_date']) && !empty($filters['to_date'])) {
            if ($filters['from_date'] <= $filters['to_date']) {
                $query->whereBetween('created_at', [$filters['from_date'], $filters['to_date']]);
            }
        }
    }

    public function createFromTableOrders(int $tableId, int $restaurantId, array $invoiceData): Invoice
    {
        // ✅ DATA INTEGRITY: Wrap the entire multi-step process in a transaction.
        return DB::transaction(function () use ($tableId, $restaurantId, $invoiceData) {
            // 1. Find all orders for the table that don't have an invoice yet.
            $ordersToInvoice = Order::where('restaurant_id', $restaurantId)
                ->where('table_id', $tableId)
                ->whereNull('invoice_id')
                ->whereDate('created_at', now()->toDateString())
                ->get();

            // 2. ✅ BUSINESS LOGIC: If there are no orders, throw a catchable exception.
            if ($ordersToInvoice->isEmpty()) {
                throw ValidationException::withMessages([
                    'orders' => trans('locale.dontHaveOrders'),
                ]);
            }

            // 3. Create the initial invoice record using our existing 'create' method.
            // We pass the status and any other data from the controller.
            $invoiceData['status'] = 2; // As per original logic
            $invoice = $this->create($restaurantId, $invoiceData);

            // 4. ✅ EFFICIENCY: Link all found orders to the new invoice with a single query.
            Order::whereIn('id', $ordersToInvoice->pluck('id'))->update(['invoice_id' => $invoice->id]);

            // 5. ✅ REUSABILITY: Recalculate and save the final totals using a helper method.
            $this->recalculateAndSaveInvoiceTotals($invoice);

            // 6. Return the complete and updated invoice.
            return $invoice->load(['table', 'admin', 'orders']);
        });
    }

    public function getInvoicesForExport(array $filters): Collection
    {
        $query = Invoice::query();

        $query->with(['admin', 'table']);

        // Apply the restaurant_id filter for security.
        if (isset($filters['restaurant_id'])) {
            $query->where('restaurant_id', $filters['restaurant_id']);
        }

        // Apply the date filter if it exists.
        if (isset($filters['date'])) {
            $query->whereDate('created_at', $filters['date']);
        }

        return $query->get();
    }

    public function getInvoicesForUser(User $user, array $filters): LengthAwarePaginator
    {
        // 1. Start with a base query, eager-loading all relationships needed by the resource.
        // This prevents N+1 database query problems.
        $query = Invoice::where('user_id', $user->id)
            ->with(['orders.restaurant', 'address', 'user', 'delivery', 'deliveryRoute']);

        // 2. Apply the filtering logic based on the 'type' parameter.
        $this->applyInvoiceTypeFilter($query, $filters['type'] ?? null);

        // 3. Execute the paginated query.
        $invoices = $query->latest()->paginate($filters['per_page'] ?? 15);

        // 4. ✅ PERFORMANCE FIX: After fetching the data, efficiently calculate distances
        // only for the 'under delivery' invoices on the current page.
        $this->calculateDistancesForUnderDeliveryInvoices($invoices->getCollection());

        return $invoices;
    }

    /**
     * A private helper to apply the status filters based on the 'type' parameter.
     */
    private function applyInvoiceTypeFilter(Builder $query, ?string $type): void
    {
        if ($type === 'orders') {
            // "orders" type means completed or rejected invoices.
            $query->whereIn('status', [InvoiceStatus::COMPLETED->value, InvoiceStatus::REJECTED->value]);
        } elseif ($type === 'current') {
            // Any other type means active, recent invoices.
            $query->whereIn('status', [
                InvoiceStatus::WAITING->value,
                InvoiceStatus::APPROVED->value,
                InvoiceStatus::PROCESSING->value,
                InvoiceStatus::UNDER_DELIVERY->value,
            ])->where(function ($dateQuery) {
                // $dateQuery->whereDate('created_at', now()->toDateString())
                //     ->orWhereDate('created_at', Carbon::yesterday()->toDateString());
            });
        }
        // If no 'type' is provided, no status filter is applied, and all invoices are returned.
    }

    /**
     * A private helper to iterate over a collection and calculate OSRM distances.
     */
    private function calculateDistancesForUnderDeliveryInvoices(Collection $invoices): void
    {
        $invoices->each(function ($invoice) {
            // Only calculate for invoices that are under delivery and have all necessary data.
            if ($invoice->status == InvoiceStatus::UNDER_DELIVERY && $this->hasAllCoordinates($invoice)) {
                $routeData = $this->osrmService->getRoute(
                    (float)$invoice->deliveryRoute->start_lat,
                    (float)$invoice->deliveryRoute->start_lon,
                    (float)$invoice->address->latitude,
                    (float)$invoice->address->longitude
                );

                // Add the distance and duration as dynamic properties to the invoice model.
                // The resource can now access these directly.
                $invoice->distance_km = isset($routeData['distance']) ? round($routeData['distance'] / 1000, 2) : null;
                $invoice->duration_min = isset($routeData['duration']) ? round($routeData['duration'] / 60, 2) : null;
            }
        });
    }

    /**
     * A small helper to check if an invoice has all the required location data.
     */
    private function hasAllCoordinates(Invoice $invoice): bool
    {
        return $invoice->deliveryRoute &&
            isset($invoice->deliveryRoute->start_lat, $invoice->deliveryRoute->start_lon) &&
            $invoice->address &&
            isset($invoice->address->latitude, $invoice->address->longitude);
    }
}
