<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Events\LocationUpdated;
use Illuminate\Support\Facades\Log;

class LocationDriver
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
    }

    /**
     * Handle the event.
     */
    public function handle(LocationUpdated $event)
    {
        Log::info('📍 Location updated for user ' , [
            'latitude' => $event->latitude,
            'longitude' => $event->longitude,
        ]);
    }
}


