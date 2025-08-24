<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class OrderUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $orders;
    /**
     * Create a new event instance.
     */
    public function __construct($orders)
    {
        $this->orders = $orders;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): Channel
    {
        if ($this->orders->isEmpty()) {
            return new Channel('default_channel');
        }
        $firstTable = $this->orders->first();

        // if ($firstTable->status == 'Ordered')
        //     $status = "processing";

        // elseif ($firstTable->status == 'Under delivery')
        //     $status = "under_delivery";

        // elseif ($firstTable->status == 'Paid' || $firstTable->status == 'Received')
        //     $status = "accepted";
        // else
        $status = $firstTable->status->value;
        Log::info(['status111' => $status]);
        $channelName = 'all-orders.' . $status . '.' . $firstTable->delivery_id;

        return new Channel($channelName);
    }

    public function broadcastWith()
    {
        $payload = ['orders' => $this->orders];
        return $payload;
    }
}
