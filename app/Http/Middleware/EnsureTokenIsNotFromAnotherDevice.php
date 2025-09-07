<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use App\Models\SuperAdmin;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Str;

class EnsureTokenIsNotFromAnotherDevice
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $credentials = $request->only('user_name', 'password');
        $deviceType = $request->header('User-Agent');
        if (!empty($credentials['user_name'])) {
            $superAdmin = SuperAdmin::where('user_name', $credentials['user_name'])->first();
            if ($superAdmin) {
                if (Str::contains($deviceType, 'Android'))
                    $superAdmin->tokens()->where('platform','like','%'. 'Android'.'%')->delete();

                $superAdmin->tokens()->where('platform', $deviceType)->delete();
            }

            else {
                $admin = Admin::where('user_name', $credentials['user_name'])->first();
                if ($admin) {
                    $admin->tokens()->where('platform', $deviceType)->delete();
                }
            }
        }
        return $next($request);
    }
}
