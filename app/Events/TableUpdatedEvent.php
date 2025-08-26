<?php

namespace App\Events;

use App\Models\Order;
use App\Models\Table;
use Carbon\Carbon;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Support\Facades\Log;

class TableUpdatedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $tables;

    public function __construct($tables)
    {
        $this->tables = $tables;
    }

    public function broadcastOn(): Channel
    {
        $firstTableRestaurantId = $this->tables['data'][0]['restaurant_id'] ?? null;


        if (!$firstTableRestaurantId) {
            return new Channel('default_channel');
        }
        log::info($firstTableRestaurantId);
        return new Channel('restaurant' . $firstTableRestaurantId);
    }

    public function broadcastWith()
    {
        Log::info($this->tables);
        log::info($this->tables);
        return $this->tables;
        // return [$this->tables];
        // return['tables' => $this->tables];
    }
}
