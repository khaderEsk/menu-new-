<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChooseTableRequest;
use App\Http\Requests\Restaurant\IdRequest;
use App\Http\Requests\Restaurant\ShowByNameRequest;
use App\Http\Resources\CustomerRestaurantResource;
use App\Http\Resources\RestaurantResource;
use App\Models\Customer;
use App\Models\Restaurant;
use App\Models\Table;
use App\Services\RestaurantService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;
use Throwable;

class RestaurantController extends Controller
{
    public function __construct(private RestaurantService $restaurantService) {}

    // // Show restaurant By Id
    // public function showById(IdRequest $request)
    // {
    //     $data = $request->validated();
    //     $restaurant = $this->restaurantService->show($data['id']);
    //     $data = RestaurantResource::make($restaurant);
    //     return $this->successResponse($data,"Restaurant Found Successfully",200);
    // }

    // Show restaurant By Id
    public function showByIdOrName(ShowByNameRequest $request)
    {
        try {
            $data = $request->validated();
            $restaurant = $this->restaurantService->showByName($data);

            if ($restaurant->end_date < Carbon::now()->toDateString())
                return $this->messageErrorResponse(trans('locale.restaurantHasExpired'), 400);

            if ($request->has('qr_code')) {
                $qr = $request->input('qr_code');
                if ($qr == "takeout") {
                    $visit = $restaurant->visited;
                    $restaurant->update([
                        'visited' => $visit + 1,
                    ]);
                    $tables = Table::whereRestaurantId($restaurant->id)->latest()->get();
                    $restaurant['available_tables'] = $tables;
                    $data = RestaurantResource::make($restaurant);
                    return $this->successResponse($data, trans('locale.restaurantFound'), 200);
                }
                // $qr_code = Crypt::decryptString($qr);
                // $table = Table::where('num', $qr_code)->first();
                $table = Table::whereRestaurantId($restaurant->id)->where('number_table', $qr)->first();

                if (!$table)
                    return $this->messageErrorResponse(trans('locale.invalidQRCode'), 404);

                $user = Customer::create([
                    'user_name' => Str::random(10),
                    'password' => Hash::make('password'),
                    'restaurant_id' => $restaurant->id,
                    'table_id' => $table->id,
                ]);

                $user->assignRole(['customer']);
                // -------------------------------------

                $expiration = now()->addHours(2);
                $tokenResult = $user->createToken('auth_token');
                $token = $tokenResult->plainTextToken;

                // $tokenId = explode('|', $token)[0];
                // PersonalAccessToken::where('id', $tokenId)->update(['expires_at' => $expiration]);

                // -----------------------------
                // $token = $user->createToken('auth_token')->plainTextToken;
                $visit = $restaurant->visited;
                $restaurant->update([
                    'visited' => $visit + 1,
                ]);
                if ($request->has('user_takeout'))
                    $restaurant['user_takeout'] = 1;

                $restaurant['token'] = $token;
                $restaurant['table_id'] = $table->id;
                $data = CustomerRestaurantResource::make($restaurant);
                return $this->successResponse($data, trans('locale.restaurantFound'), 200);
            }
            $visit = $restaurant->visited;
            $restaurant->update([
                'visited' => $visit + 1,
            ]);
            $tables = Table::whereRestaurantId($restaurant->id)->latest()->get();
            $restaurant['available_tables'] = $tables;
            $data = RestaurantResource::make($restaurant);
            return $this->successResponse($data, trans('locale.restaurantFound'), 200);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Choose Table
    public function chooseTable(ChooseTableRequest $request)
    {
        $data = $request->validated();
        $restaurant = $this->restaurantService->showByName($data);
        if ($restaurant->is_table == 1 && $restaurant->is_order == 1) {
            $table = Table::whereRestaurantId($data['id'])->whereId($data['table_id'])->first();
            if (!$table)
                return $this->messageErrorResponse(trans('locale.invalidItem'), 404);

            $user = Customer::create([
                'user_name' => Str::random(10),
                'password' => Hash::make('password'),
                'restaurant_id' => $restaurant->id,
                'table_id' => $data['table_id'],
            ]);
            $user->assignRole(['customer']);
            $expiration = now()->addHours(2);
            $tokenResult = $user->createToken('auth_token');
            $token = $tokenResult->plainTextToken;
            $data = [
                'token' => $token,
                'table_id' => $table->id,
            ];
            return $this->successResponse($data, "Restaurant Found Successfully", 200);
        }
        return $this->messageErrorResponse(trans('locale.invalidItem'), 404);
    }
}
