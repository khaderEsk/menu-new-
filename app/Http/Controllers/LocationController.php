<?php

namespace App\Http\Controllers;

use App\Events\LocationUpdated;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function updateLocation(Request $request)
    {
        // بث الموقع عبر WebSocket
        event(new LocationUpdated($request->user_id,$request->longitude,$request->latitude,$request->token));
        return response()->json(['message' => 'Location updated successfully!']);
    }
}
