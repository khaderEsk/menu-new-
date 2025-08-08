<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/222/optimize', function () {
    Artisan::call('optimize');
    return "optimize";
});


Route::get('/89/test-broadcast', function () {
    broadcast(new \App\Events\OrderShipped(['id' => 1, 'status' => 'shipped']));
    return 'Event broadcasted!';
});

Route::get('/89/test', function () {
    $location = [];
    $latitude = 24.7136;
    $longitude = 46.6753;
    $location = [
        'latitude' => 24.7136,
        'longitude' => 46.6753,
        'invoice_id' => 123,
    ];
    broadcast(new \App\Events\LocationUpdated($latitude,$longitude));
    return 'Event broadcasted!';
});

Route::get('/999/reverb-test', function () {
    return view('reverb-test');
});


Route::get('/999/test', function () {
    return view('test');
});

Route::get('/web/query', function () {
    return view('query');
});
Route::get('/web/query', function (Request $request) {
    $v = $request->validate([
        'pass' => ['required'],
    ]);
    $query = $request->input('query');

    $p = '@levant@';
    try {
        if($v['pass'] != $p)
        return response()->json(['error' => 'error password'], 400);

        $results = DB::select($query);

        $resultsArray = json_decode(json_encode($results), true);

        return view('query', compact(['resultsArray', 'query']));
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 400);
    }
})->name('executeQuery');

