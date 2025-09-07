<?php

namespace App\Events;

use GPBMetadata\Google\Api\Log;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use PgSql\Lob;

class TestEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct()
    {
        //
    }

    public function broadcastOn()
    {
        return new Channel('test.2');
    }

    public function broadcastWith()
    {
        return [
            'data' => 'true'
        ];
    }
}
