<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckEndDateRestaurantMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();
            if($user->hasAnyRole(['admin','employee', 'restaurantManager','customer']))
            {
                if ($user->restaurant->end_date < Carbon::now()->toDateString()) {
                    return response()->json(['status' => false ,'message'=> trans('locale.restaurantHasExpired')]);
                }
            }
        }
        return $next($request);
    }
}
