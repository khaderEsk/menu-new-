<?php

namespace App\Events;

use App\Http\Resources\InvoiceUserResource;
use App\Http\Resources\OrderResource;
use App\Models\Invoice;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class NewOrder implements ShouldBroadcast, ShouldHandleEventsAfterCommit
{
    use Dispatchable, SerializesModels, InteractsWithSockets;

    public $userOrders;
    public $userTypeId;
    public $firstInvoice;

    public function __construct($userOrders, $userTypeId)
    {
        Log::info($userOrders);
        Log::info($userTypeId);
        $this->userOrders = $userOrders;
        $this->userTypeId = $userTypeId;
        $this->firstInvoice = $this->userOrders->first();
    }

    public function broadcastOn(): Channel
    {
        // ✅ This safety check prevents errors if no invoices are found.
        if ($this->firstInvoice && $this->firstInvoice->id) {
            return new Channel('order.' . $this->firstInvoice->id);
        }

        return new Channel('default-channel');
    }

    public function broadcastAs(): string
    {
        // ✅ This safety check also prevents errors if no invoices are found.
        return 'order.' . $this->firstInvoice->id;
    }

    public function broadcastWith(): array
    {
        // 1. Get the IDs from the simple collection we received.
        $invoiceIds = $this->userOrders->pluck('id');

        // 2. Re-query the database to get a fresh Eloquent Collection
        $freshUserOrders = Invoice::whereIn('id', $invoiceIds)
            ->with('deliveryRoute')
            ->latest()
            ->get();

        // 3. Now use this fresh and complete collection to build the resource.
        InvoiceUserResource::$userTypeId = $this->userTypeId;
        $payload = ['data' => InvoiceUserResource::collection($freshUserOrders)->resolve()];

        Log::info('Broadcasting NewOrder event with payload:', $payload);

        return $payload;
    }
}
