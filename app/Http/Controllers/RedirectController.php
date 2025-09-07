<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use App\Models\Table;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Throwable;

class RedirectController extends Controller
{
    public function handleRedirect($code)
    {

        try{
            $newUrl = '';

            $table = Table::where('num', $code)->first();
            if (!$table)
                return $this->messageErrorResponse(trans('locale.invalidQRCode'),404);
            $visit = $table->visited;
            $table->update([
                'visited' => $visit + 1,
            ]);
            $restaurant = Restaurant::whereId($table->restaurant_id)->first();

            if ($restaurant->end_date < Carbon::now()->toDateString())
            {
                return $this->messageErrorResponse(trans('locale.restaurantHasExpired'),400);
            }

            $appUrl = env('APP_URL_FRONT');

            $tableNumber = $table->number_table;
            $restaurantName =  $restaurant->name_url;
            // $newUrl = $appUrl."/customer_api/show_restaurant_by_name_or_id?restaurant_name=".$restaurantName."&qr_code=".$tableNumber;
            $newUrl = $appUrl."/".$restaurantName."/".$tableNumber;
            return redirect()->away($newUrl);

        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    public function qrTakeout($code)
    {
        try{
            $newUrl = '';

            $restaurant = Restaurant::whereId($code)->first();
            if (!$restaurant)
                return $this->messageErrorResponse(trans('locale.invalidQRCode'),404);


            if ($restaurant->end_date < Carbon::now()->toDateString())
            {
                return $this->messageErrorResponse(trans('locale.restaurantHasExpired'),400);
            }

            $appUrl = env('APP_URL_FRONT');

            $restaurantName =  $restaurant->name_url;
            // $newUrl = $appUrl."/customer_api/show_restaurant_by_name_or_id?restaurant_name=".$restaurantName."&qr_code=".$tableNumber;
            $newUrl = $appUrl."/".$restaurantName."/takeout";
            return redirect()->away($newUrl);

        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }
}
