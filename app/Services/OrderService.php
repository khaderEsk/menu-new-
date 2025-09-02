<?php

namespace App\Services;

use App\Enum\InvoiceStatus;
use App\Enum\OrderStatus;
use App\Models\Admin;
use App\Models\Component;
use App\Models\EmployeeTable;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\Order;
use App\Models\Size;
use App\Models\Table;
use App\Models\Topping;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(private FirebaseService $firebaseService) {}

    //======================================================================
    // PUBLIC API METHODS (Refactored & Complete)
    //======================================================================

    /**
     * Get a simple paginated list of orders for a restaurant.
     */
    public function paginate(int $restaurantId, int $perPage): LengthAwarePaginator
    {
        return Order::where('restaurant_id', $restaurantId)
            ->with(['table', 'translations']) // Eager-load for performance
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get a single order by its ID, fully loaded with relationships.
     */
    public function show(int $orderId, int $restaurantId)
    {
        // This guarantees that an admin can never access an order from another restaurant.
        return Order::with(['table', 'translations', 'invoice'])
            ->where('restaurant_id', $restaurantId)
            ->findOrFail($orderId);
    }

    /**
     * Get a filtered and paginated list of orders based on various criteria.
     */
    public function getFilteredOrders(Admin $admin, array $filters): LengthAwarePaginator|array
    {
        $query = Order::query()
            ->where('restaurant_id', $admin->restaurant_id)
            ->with(['table', 'translations']);

        $this->applyGenericFilters($query, $filters);

        if ($admin->hasRole('employee')) {
            $this->applyEmployeeFilters($query, $admin, $filters);
        }

        if (isset($filters['status']) && $filters['status'] === 'done') {
            $orders = $query->latest()->get();
            return $this->groupCompletedOrders($orders, $filters['per_page'] ?? 25);
        }

        return $query->latest()->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Main method for creating a batch of orders (e.g., from a customer's cart).
     */
    public function createOrdersFromRequest(array $data, Admin $currentUser): Table
    {
        $itemIds = array_column($data['data'], 'item_id');
        $itemsData = Item::with(['translations', 'category.translations'])->whereIn('id', $itemIds)->get()->keyBy('id');

        return DB::transaction(function () use ($data, $currentUser, $itemsData) {
            $table = Table::findOrFail($data['table_id']);
            $table->visited = 1;
            $table->save();
            $invoiceId = $this->findOrCreateInvoiceIdForTable($table, $data['invoice_id'] ?? null);

            foreach ($data['data'] as $orderItemData) {
                $item = $itemsData->get($orderItemData['item_id']);
                if (!$item) continue;;
                $price = $this->calculatePriceProduct($item, $orderItemData['toppings'] ?? [], $orderItemData['size_id'] ?? null);


                $order = $this->createSingleOrderRecord($orderItemData, $item, $price, $table->id, $invoiceId);

                $this->notifyRelevantEmployees($order, "new order");
            }

            if ($invoiceId) {
                $this->updateInvoiceTotals(Invoice::find($invoiceId));
            }

            $this->finalizeOrderCreation($currentUser, $table);
            return $table;
        });
    }

    /**
     * Main method for creating a single order (e.g., from a waiter's device).
     */
    public function createSingleOrderFromRequest(array $data): Order
    {
        $item = Item::with(['translations', 'category.translations'])->findOrFail($data['item_id']);

        if (!empty($data['invoice_id'])) {
            $invoice = Invoice::findOrFail($data['invoice_id']);
            $data['table_id'] = $invoice->table_id;
        }

        return DB::transaction(function () use ($data, $item) {
            $table = Table::findOrFail($data['table_id']);
            $invoiceId = $this->findOrCreateInvoiceIdForTable($table, $data['invoice_id'] ?? null);
            $price = $item->price;

            $order = $this->createSingleOrderRecord($data, $item, $price, $table->id, $invoiceId);
            $this->notifyRelevantEmployees($order, "new order");

            if ($invoiceId) {
                $this->updateInvoiceTotals(Invoice::find($invoiceId));
            }

            $this->finalizeOrderCreation(auth()->user(), $table);
            return $order;
        });
    }

    /**
     * Update an existing order.
     */
    public function update(Order $order, array $data): Order
    {
        return DB::transaction(function () use ($order, $data) {
            $updateData = Arr::only($data, ['count', 'status']);

            if (isset($data['item_id'])) {
                $item = Item::with(['translations', 'category.translations'])->findOrFail($data['item_id']);
                $updateData['price'] = $this->calculatePriceProduct($item, $data['toppings'] ?? [], $data['size_id'] ?? null);
                $order->fill([
                    'en' => ['name' => $item->translate('en')->name, 'type' => $item->category->translate('en')->name],
                    'ar' => ['name' => $item->translate('ar')->name, 'type' => $item->category->translate('ar')->name],
                ]);
            }

            $order->update($updateData);
            $this->addDetailsItem($order, $data['size_id'] ?? null, $data['components'] ?? [], $data['toppings'] ?? []);

            if ($order->invoice_id) {
                $this->updateInvoiceTotals($order->invoice);
            }

            return $order->load(['table', 'translations']);
        });
    }

    /**
     * Delete an order.
     */
    public function destroy(int $invoiceId, int $restaurantId): bool
    {
        // 1. ✅ SECURITY FIX & EFFICIENCY: Fetch the invoice in a single, secure query.
        // This finds the invoice AND verifies it belongs to the correct restaurant.
        $invoice = Invoice::where('id', $invoiceId)
            ->where('restaurant_id', $restaurantId)
            ->firstOrFail(); // Throws ModelNotFoundException if not found.

        // 2. ✅ BUSINESS LOGIC: Check if the invoice is in a deletable state.
        // This uses a helper method for clarity.
        if (!$this->isDeletable($invoice)) {
            // Throw a specific, catchable exception with a user-friendly message.
            throw ValidationException::withMessages([
                'invoice' => trans('locale.youCantDeleted'),
            ]);
        }

        // 3. Perform the deletion.
        // This also handles the cascading delete of related orders if set up in the database.
        return $invoice->delete();
    }

    public function updateOrderStatusForTable(int $tableId, string $newStatus, Admin $currentUser): int
    {
        // 1. Determine the 'from' status based on the desired 'to' status.
        $statusMap = [
            'accepted' => 'waiting',
            'preparation' => 'accepted',
            'done' => 'preparation',
        ];
        $fromStatus = $statusMap[$newStatus] ?? 'waiting';
        // dd($fromStatus);
        // 2. Find the table and the orders that need to be updated.
        $table = Table::findOrFail($tableId);
        $ordersToUpdate = $this->getOrdersForStatusUpdate($tableId, $fromStatus);
        // dd(count($ordersToUpdate));
        if ($ordersToUpdate->isEmpty()) {
            return 0; // No orders found to update.
        }

        // 3. Perform all actions within a single, safe database transaction.
        return DB::transaction(function () use ($ordersToUpdate, $table, $newStatus, $currentUser) {
            // 4. Send notifications BEFORE updating the status.
            // $this->notifyEmployeesForStatusChange($table, $newStatus, $currentUser);
            // 5. Create the employee time tracking records.
            $this->trackEmployeeTimeForOrders($ordersToUpdate, $currentUser);

            // 6. Perform the actual database update.
            return Order::whereIn('id', $ordersToUpdate->pluck('id'))->update(['status' => $newStatus]);
        });
    }

    public function getGroupedCompletedOrdersForExport(Admin $admin, array $filters): Collection
    {
        // 1. Start with a base query, eager-loading only what's needed for the report.
        $query = Order::query()
            ->where('restaurant_id', $admin->restaurant_id)
            ->with(['translations']); // Eager load for performance

        // 2. This report is ONLY for completed orders, so we force the status.
        $query->where('status', 'done');

        // 3. Apply all the same generic filters (date range, search, etc.).
        $this->applyGenericFilters($query, $filters);

        // 4. Apply the same employee-specific security filters.
        if ($admin->hasRole('employee')) {
            // We pass ['status' => 'done'] to ensure the correct logic path is triggered.
            $this->applyEmployeeFilters($query, $admin, ['status' => 'done']);
        }

        // 5. Get all matching orders from the database.
        $orders = $query->latest()->get();

        if ($orders->isEmpty()) {
            return collect(); // Return an empty collection if no data is found.
        }

        // 6. Group the results using our reusable helper method.
        return $this->groupOrdersForReport($orders);
    }
    public function updateOrderStatus(Order $order, OrderStatus $newStatus, Admin $currentUser, $count ): Order
    {
        // ✅ DATA INTEGRITY: Wrap the entire multi-step process in a transaction.
        return DB::transaction(function () use ($order, $newStatus, $currentUser , $count) {
            // 1. Update the order status.
            $order->status = $newStatus->value;
            $order->count = $count ? $count : $order->count;
            $order->save();

            // 2. Handle all side-effects using clean, private helper methods.
            $this->notifyEmployeesForStatusChange($order, $newStatus, $currentUser);
            $this->notifyCustomerOfStatusChange($order, $newStatus);
            $this->trackEmployeeTimeForOrderUpdate($order, $currentUser);
            $this->updateOrCreateInvoiceForUserOrder($order);

            return $order;
        });
    }


    //======================================================================
    // PRIVATE HELPER METHODS
    //======================================================================

    private function createSingleOrderRecord(array $orderData, Item $item, float $price, int $tableId, ?int $invoiceId): Order
    {

        $order = Order::create([
            'item_id' => $item->id,
            'price' => $price,
            'count' => $orderData['count'],
            'table_id' => $tableId,
            'restaurant_id' => $item->restaurant_id,
            'invoice_id' => $invoiceId,
            'status' => "accepted",
            'size' => $orderData['size_id'],
            'en' => ['name' => $item->translate('en')->name, 'type' => $item->category->translate('en')->name],
            'ar' => ['name' => $item->translate('ar')->name, 'type' => $item->category->translate('ar')->name],
        ]);
        $this->addDetailsItem($order, $orderData['size_id'] ?? null, $orderData['components'] ?? [], $orderData['toppings'] ?? []);
        return $order;
    }

    private function findOrCreateInvoiceIdForTable(Table $table, ?int $invoiceIdFromRequest): ?int
    {
        if ($invoiceIdFromRequest) return $invoiceIdFromRequest;
        $lastOrder = $table->orders()->with('invoice')->latest('updated_at')->first();
        if ($lastOrder && $lastOrder->invoice && in_array($lastOrder->invoice->status, ['Ordered', 'Not requested'])) {
            return $lastOrder->invoice_id;
        }
        return null;
    }

    private function finalizeOrderCreation(Admin $employee, Table $table): void
    {
        if ($employee->hasRole('employee')) {
            $diff = now()->diff($table->updated_at);
            $timeString = sprintf('%02d:%02d:%02d', $diff->h, $diff->i, $diff->s);
            \App\Models\EmployeeTable::create(['order_time' => $timeString, 'table_id' => $table->id, 'admin_id' => $employee->id, 'restaurant_id' => $employee->restaurant_id]);
        }
        $table->new_order = 1;
        $table->save();
    }

    public function calculatePriceProduct(Item $item, array $toppingIds, ?int $sizeId): float
    {
        $price = $sizeId ? Size::find($sizeId)->price ?? $item->price : $item->price;
        if (!empty($toppingIds)) {
            $price += Topping::whereIn('id', $toppingIds)->sum('price');
        }
        return $price;
    }

    public function addDetailsItem(Order $order, ?int $sizeId, array $componentIds, array $toppingIds): void
    {
        if (!empty($toppingIds)) $order->toppings = Topping::whereIn('id', $toppingIds)->get(['name', 'price'])->toJson();

        if (!empty($componentIds)) $order->components = Component::whereIn('id', $componentIds)->get(['name'])->toJson();
        if (!empty($sizeId)) $order->size = Size::where('id', $sizeId)->first(['price'])->toJson();

        $order->save();
    }

    private function notifyRelevantEmployees(Order $order, string $title): void
    {
        $body = "table number: {$order->table->number_table}, name: {$order->name}, count: {$order->count}";
        $payload = ['order_id' => $order->id, 'restaurant_id' => $order->restaurant_id, 'table_id' => $order->table_id];
        $employees = Admin::role('employee')->where('restaurant_id', $order->restaurant_id)->whereIn('type_id', [3, 4, 5, 8])->whereNotNull('fcm_token')->get();
        foreach ($employees as $employee) {
            $this->firebaseService->sendNotification($employee->fcm_token, $title, $body, $payload);
        }
    }

    private function updateInvoiceTotals(Invoice $invoice): void
    {
        $restaurant = $invoice->restaurant;
        $totalPrice = $invoice->orders()->sum(DB::raw('price * count'));
        $taxes = ['consumer_spending' => 0, 'local_administration' => 0, 'reconstruction' => 0];
        if ($restaurant->is_taxes == 1) {
            $taxes['consumer_spending'] = $totalPrice * $restaurant->consumer_spending / 100;
            $taxes['local_administration'] = $totalPrice * $restaurant->local_administration / 100;
            $taxes['reconstruction'] = $totalPrice * $restaurant->reconstruction / 100;
        }
        $finalTotal = $totalPrice + array_sum($taxes);
        $invoice->update(['price' => round($totalPrice, 0), 'consumer_spending' => round($taxes['consumer_spending'], 0), 'local_administration' => round($taxes['local_administration'], 0), 'reconstruction' => round($taxes['reconstruction'], 0), 'total' => round($finalTotal, 0)]);
    }

    private function applyGenericFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['search'])) $query->whereTranslationLike('name', "%{$filters['search']}%");
        if (!empty($filters['table_id'])) $query->where('table_id', $filters['table_id']);
        if (!empty($filters['invoice_id'])) $query->where('invoice_id', $filters['invoice_id']);
        if (!empty($filters['start_date'])) $query->whereDate('created_at', '>=', $filters['start_date']);
        if (!empty($filters['end_date'])) $query->whereDate('created_at', '<=', $filters['end_date']);
    }

    private function applyEmployeeFilters(Builder $query, Admin $admin, array $filters): void
    {
        $type = $admin->type->name;
        if (isset($filters['status']) && $filters['status'] === 'done') {
            if (in_array($type, ["restaurant manager", "admin", "accountant"])) $query->where('status', 'done');
            return;
        }
        $dateQuery = fn(Builder $q) => $q->whereDate('created_at', now()->toDateString())->orWhereDate('created_at', Carbon::yesterday()->toDateString());
        if ($admin->restaurant->accepted_by_waiter == 1) {
            if (in_array($type, ["chef", "bar"])) {
                $categoryNames = $admin->categories()->with('translations')->get()->pluck('translations.*.name')->flatten()->unique();
                $query->where($dateQuery)->whereIn('status', ['accepted', 'preparation'])->whereHas('translations', fn($q) => $q->whereIn('type', $categoryNames));
            } elseif (in_array($type, ["waiter", "shisha"])) {
                $query->where($dateQuery)->whereIn('status', ['waiting', 'accepted', 'preparation', 'done']);
            } else {
                $query->whereIn('status', ['waiting', 'preparation', 'done']);
            }
        } else {
            if (in_array($type, ["chef", "bar"])) $query->where($dateQuery)->whereIn('status', ['waiting', 'accepted', 'preparation']);
            elseif (in_array($type, ["waiter", "shisha"])) $query->where($dateQuery)->whereIn('status', ['waiting', 'accepted', 'preparation', 'done']);
        }
    }

    private function isDeletable(Invoice $invoice): bool
    {
        // Define all the statuses that PREVENT deletion.
        $nonDeletableStatuses = [
            InvoiceStatus::PROCESSING->value,
            InvoiceStatus::UNDER_DELIVERY->value,
            InvoiceStatus::COMPLETED->value,
        ];

        // If the invoice's status is in this array, it cannot be deleted.
        return !in_array($invoice->status, $nonDeletableStatuses);
    }

    private function getOrdersForStatusUpdate(int $tableId, string $fromStatus): Collection
    {
        // Note: The original query included Carbon::tomorrow(), which is unusual.
        // It has been preserved here to maintain existing logic.
        $dateQuery = function ($query) {
            $query->whereDate('created_at', '>=', Carbon::yesterday())
                ->whereDate('created_at', '<=', Carbon::tomorrow());
        };
        // dd($dateQuery);
        return Order::where('table_id', $tableId)
            ->where('status', $fromStatus)
            // ->where($dateQuery)
            ->get();
    }

    /**
     * A private helper to create EmployeeTable records for a collection of orders.
     * This centralizes the time tracking logic.
     */
    private function trackEmployeeTimeForOrders(Collection $orders, Admin $admin): void
    {
        $table = $orders->first()->table; // All orders are for the same table

        foreach ($orders as $order) {
            $diff = now()->diff($table->updated_at);
            $timeString = sprintf('%02d:%02d:%02d', $diff->h, $diff->i, $diff->s);

            EmployeeTable::create([
                'order_time' => $timeString,
                'table_id' => $table->id,
                'admin_id' => $admin->id,
                'restaurant_id' => $admin->restaurant_id,
            ]);
        }
    }

    private function groupOrdersForReport(Collection $orders): Collection
    {
        return $orders->groupBy(fn($item) => $item->name . '-' . $item->created_at->format('Y-m-d'))
            ->map(fn($items) => [
                // This structure must exactly match what the SalesInventoryExport expects.
                'price' => $items->sum('price'),
                'count' => $items->sum('count'),
                'created_at' => $items->first()->created_at->format('Y-m-d'),
                'name' => $items->first()->name,
                'status' => $items->first()->status,
                "name_en" => $items->first()->translate('en')->name,
                "name_ar" => $items->first()->translate('ar')->name,
                "type_en" => $items->first()->translate('en')->type,
                "type_ar" => $items->first()->translate('ar')->type,
                "translations" => $items->first()->translations,
            ])->values(); // Use values() to reset keys.
    }

    /**
     * I've also refactored the original groupCompletedOrders method
     * to use our new, reusable helper. This makes the service even cleaner.
     */
    private function groupCompletedOrders(Collection $orders, int $perPage): array
    {
        if ($orders->isEmpty()) {
            return ['data' => [], 'meta' => ['total' => 0, 'count' => 0, 'per_page' => $perPage, 'current_page' => 1, 'total_pages' => 0]];
        }

        $groupedItems = $this->groupOrdersForReport($orders); // Reusing the helper!

        // Manual Pagination logic remains the same.
        $currentPage = Paginator::resolveCurrentPage('page');
        $total = $groupedItems->count();
        $dataPage = $groupedItems->slice(($currentPage - 1) * $perPage, $perPage)->values();

        return [
            'data' => $dataPage,
            'meta' => [
                'total' => $total,
                'count' => $dataPage->count(),
                'per_page' => $perPage,
                'current_page' => $currentPage,
                'total_pages' => ceil($total / $perPage),
            ],
        ];
    }
    /**
     * A private helper to send notifications to the correct employees based on the new status.
     */
    private function notifyEmployeesForStatusChange(Order $order, OrderStatus $status, Admin $admin): void
    {
        if (!$order->table) return; // Only for in-restaurant orders

        $targetTypeIds = match ($status) {
            OrderStatus::ACCEPTED => [4, 8], // Chef and Bar
            OrderStatus::PREPARATION, OrderStatus::DONE => [5], // Waiter
            default => [],
        };

        if (empty($targetTypeIds)) return;

        $employees = Admin::role('employee')
            ->where('restaurant_id', $admin->restaurant_id)
            ->whereIn('type_id', $targetTypeIds)
            ->whereNotNull('fcm_token')
            ->get();

        $title = "Update Order";
        $body = "Table: {$order->table->number_table}, Item: {$order->name}, Qty: {$order->count}, Status: {$status->value}";

        foreach ($employees as $employee) {
            $this->firebaseService->sendNotification($employee->fcm_token, $title, $body, []);
        }
    }

    /**
     * A private helper to send a notification to the customer if they exist.
     */
    private function notifyCustomerOfStatusChange(Order $order, OrderStatus $status): void
    {
        // Eager load the user relationship if it's not already loaded
        $order->loadMissing('user');

        if ($order->user && $order->user->fcm_token) {
            if (in_array($status, [OrderStatus::ACCEPTED, OrderStatus::PREPARATION, OrderStatus::DONE])) {
                $title = 'Your Order Status Updated!';
                $body = "Your order for {$order->name} is now {$status->value}.";
                $this->firebaseService->sendNotification($order->user->fcm_token, $title, $body, []);
            }
        }
    }

    /**
     * A private helper to create the EmployeeTable time tracking record.
     */
    private function trackEmployeeTimeForOrderUpdate(Order $order, Admin $admin): void
    {
        if (!$order->table) return;

        EmployeeTable::create([
            'order_time' => Carbon::now()->diff($order->table->updated_at)->format('%H:%I:%S'),
            'table_id' => $order->table_id,
            'admin_id' => $admin->id,
            'restaurant_id' => $admin->restaurant_id,
        ]);
    }

    /**
     * A private helper to manage the invoice for user-placed orders.
     */
    private function updateOrCreateInvoiceForUserOrder(Order $order): void
    {
        if (is_null($order->user_id)) return;

        $invoice = Invoice::firstOrCreate(
            ['id' => $order->invoice_id, 'restaurant_id' => $order->restaurant_id],
            ['num' => Invoice::where('restaurant_id', $order->restaurant_id)->max('num') + 1]
        );

        if ($order->invoice_id === null) {
            $order->update(['invoice_id' => $invoice->id]);
        }

        $this->updateInvoiceTotals($invoice);
    }
}
