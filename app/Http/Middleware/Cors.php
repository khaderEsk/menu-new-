<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class Cors
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (str_contains($request->path(), 'backup/storage')) {
            Log::info('Request matches the download path.');
            return $next($request);
        }
        elseif (str_contains($request->path(), 'backup/download')) {
            Log::info('Request matches the download path.');
            return $next($request);
        }
        elseif (str_contains($request->path(), 'excel')) {
            Log::info('Request matches the download path.');
            return $next($request);
        }
        Log::info('Request does not match the download path.');
        return $next($request)
            ->header('Access-Control-Allow-Credentials', 'true')
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', '*')
            ->header('Access-Control-Max-Age', '3600')
            ->header('Access-Control-Allow-Headers', 'X-Requested-With, Origin, X-Csrftoken, Content-Type, Accept, Authorization,language');

    }
}
