<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckVersion
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $version = $request->header('App-Version');
        // return response()->json(['request' => $request->version]);
        // $result = str_replace('.', '', $version);

        $url = "https://menuback.le.sy/storage/apk/menu_rating.apk";
        $max = '2.0.0';
        $min = '1.0.0';

        if(!$version)
            return $next($request);
        else
        {
            if($version >= $min && $version <= $max)
                return $next($request);

            $data = ['new_version' => $url];
            return response()->json(['status' => 426,'message'=> 'you must update the version','data' => $data],426);
        }
        return $next($request);
    }
}
