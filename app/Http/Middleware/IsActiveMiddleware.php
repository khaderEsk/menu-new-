<?php

namespace App\Http\Middleware;

use App\Models\Category;
use App\Models\Restaurant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Traits\ResponseTrait;
use Illuminate\Support\Facades\Log;

class IsActiveMiddleware
{
    use ResponseTrait;
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if($request->has('restaurant_id'))
            $restaurant = Restaurant::whereId($request->restaurant_id)->first();
        elseif($request->has('id'))
            $restaurant = Restaurant::whereId($request->id)->first();
        elseif($request->has('restaurant_name'))
        {
        $restaurant = Restaurant::where('name_url',$request->restaurant_name)->first();
        }
        elseif($request->has('category_id'))
        {
            $category = Category::whereId($request->category_id)->first();
            $restaurant = Restaurant::whereId($category->restaurant_id)->first();
        }

        if($request->has('restaurant_id') || $request->has('id') || $request->has('restaurant_name') || $request->has('category_id'))
        {
            if($restaurant)
            {
                if($restaurant->is_active == 0)
                    return $this->messageErrorResponse(trans('locale.restaurantIsOutOfService'),404);
            }
            else
                return $this->messageErrorResponse(trans('locale.linkError'),404);
        }
        else
            return $this->messageErrorResponse(trans('locale.linkError'),404);

        return $next($request);
    }
}
