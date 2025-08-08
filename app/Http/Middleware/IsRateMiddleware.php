<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class IsRateMiddleware
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
                if ($user->restaurant->is_rate == 0) {
                    return response()->json(['status' => 400 ,'message'=> trans('locale.youDontSubscriptionRate')]);
                }
            }
        }
        return $next($request);
    }
}
