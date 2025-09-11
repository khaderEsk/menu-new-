<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        if($user->hasAnyRole(['superAdmin','citySuperAdmin', 'dataEntry', 'restaurantManager']))
        {
            if($user->restaurant_id == null)
                return response()->json(['status' => 400 ,'message'=> 'You dont have id Restaurant']);

        }
        return $next($request);
    }
}
