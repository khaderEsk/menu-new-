<?php

use App\Http\Controllers\Api\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Api\Admin\NotificationController;
use App\Http\Controllers\Api\Customer\AdvertisementController;
use App\Http\Controllers\Api\Customer\AuthController;
use App\Http\Controllers\Api\Customer\CategoryController;
use App\Http\Controllers\Api\Customer\InvoiceController;
use App\Http\Controllers\Api\Customer\ItemController;
use App\Http\Controllers\Api\Customer\NewsController;
use App\Http\Controllers\Api\Customer\OrderController;
use App\Http\Controllers\Api\Customer\RateController;
use App\Http\Controllers\Api\Customer\RestaurantController;
use App\Http\Controllers\Api\Customer\TableController;
use App\Http\Controllers\Controller;
use App\Http\Resources\ComponentResource;
use App\Http\Resources\SizeResource;
use App\Http\Resources\ToppingResource;
use App\Models\Component;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Size;
use App\Models\Topping;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/login', [Controller::class, 'Login']);

Route::middleware(['checkEndDate', 'auth:sanctum'])->group(function () {

    // Rat
    // Route::get('/show_rates', [RateController::class, 'showAll']);
    Route::post('/add_rate', [RateController::class, 'create']);

    // Order
    Route::get('/show_orders', [OrderController::class, 'showAll']);
    Route::post('/add_order', [OrderController::class, 'create']);
    Route::post('/add2_order', [OrderController::class, 'create2']);
    Route::post('/update_order', [OrderController::class, 'update']);
    Route::delete('/delete_order', [OrderController::class, 'delete']);
    Route::get('/show_order', [OrderController::class, 'showById']);


    Route::get('/notifications', [NotificationController::class, 'showAll']);

    Route::post('/add_address_to_invoice', [InvoiceController::class, 'invoiceAddress']);

    Route::get('/show_orders_invoice', [InvoiceController::class, 'invoices']);
});

Route::middleware(['activeCustomer'])->group(function () {
    // restaurants
    // Route::get('/show_restaurant_by_name', [RestaurantController::class, 'showByName']);
    Route::get('/show_restaurant_by_name_or_id', [RestaurantController::class, 'showByIdOrName']);
    // Route::get('/show_restaurant', [RestaurantController::class, 'showById']);
    Route::post('/choose_table', [RestaurantController::class, 'chooseTable']);

    // Category
    Route::get('/show_restaurant_categories', [CategoryController::class, 'showAll']);

    // Item

    Route::get('/show_items', [ItemController::class, 'showAll']);

    // Advertisement
    Route::get('/show_advertisements', [AdvertisementController::class, 'showAll']);
    Route::get('/show_advertisement', [AdvertisementController::class, 'showById']);

    // News
    Route::get('/show_news', [NewsController::class, 'showAll']);
    Route::get('/show_news_by_id', [NewsController::class, 'showById']);

    // // show table
    // Route::get('/auth_customer', [AuthController::class, 'auth']);
});
Route::get('/show_category_subs_items', [AdminCategoryController::class, 'showCategoryAndSubsAndItems']);
