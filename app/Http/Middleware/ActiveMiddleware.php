<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ActiveMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {

        if (Auth::check()) {
            $user = Auth::user();
            if ($user->is_active == 0) {
                return response()->json(['status' => 401 ,'message'=> 'Account Blocked'],401);
            }
        }
        return $next($request);
    }

}
