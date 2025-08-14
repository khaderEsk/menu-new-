<?php

use App\Http\Controllers\Admin\OrderRequestController;
use App\Http\Controllers\Api\Admin\AdminController;
use App\Http\Controllers\Api\Admin\AdvertisementController;
use App\Http\Controllers\Api\Admin\CategoryController;
use App\Http\Controllers\Api\Admin\CouponController;
use App\Http\Controllers\Api\Admin\DeliveryController;
use App\Http\Controllers\Api\Admin\ForgatPasswordController;
use App\Http\Controllers\Api\Admin\InvoiceController;
use App\Http\Controllers\Api\Admin\ItemController;
use App\Http\Controllers\Api\Admin\NewsController;
use App\Http\Controllers\Api\Admin\NotificationController;
use App\Http\Controllers\Api\Admin\OrderController;
use App\Http\Controllers\Api\Admin\RateController;
use App\Http\Controllers\Api\Admin\RestaurantController;
use App\Http\Controllers\Api\RouteController;
use App\Http\Controllers\Api\Admin\ServiceController;
use App\Http\Controllers\Api\Admin\TableController;
use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\Admin\UserTakeoutController;
use App\Http\Controllers\Api\SuperAdmin\RoleController;
use App\Http\Controllers\Api\SuperAdmin\TypeController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\LocationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
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

Route::group(['middleware' => ['logs', 'version', 'auth:sanctum', 'active', 'role:admin|superAdmin|citySuperAdmin|dataEntry|restaurantManager']], function () {

    Route::get('/show_restaurants', [RestaurantController::class, 'showMyRestaurants'])->middleware('can:my_restaurants');
    Route::post('/update_super_admin_restaurant_id', [RestaurantController::class, 'restaurantId'])->middleware('can:restaurantId');
});


// Route::post('/login', [Controller::class, 'Login'])->middleware(['version','ensureTokenIsNotFromAnotherDevice']);
Route::post('/login', [Controller::class, 'Login'])->middleware(['version']);


Route::group(['middleware' => ['checkEndDate', 'logs', 'version', 'auth:sanctum', 'active', 'role:admin|superAdmin|citySuperAdmin|dataEntry|employee|restaurantManager', 'checkRole']], function () {

    // Category
    Route::get('/show_admin_categories', [CategoryController::class, 'showAll'])->middleware('can:category.index');
    Route::post('/add_category', [CategoryController::class, 'create'])->middleware('can:category.add');
    Route::post('/update_category', [CategoryController::class, 'update'])->middleware('can:category.update');
    Route::post('/deactivate_category', [CategoryController::class, 'deactivate'])->middleware('can:category.active');
    Route::post('/reorder_categories', [CategoryController::class, 'reOrder'])->middleware('can:reorder');
    Route::delete('/delete_category', [CategoryController::class, 'delete'])->middleware('can:category.delete');

    Route::get('/show_categories_sub', [CategoryController::class, 'showAllCategoriesAndSub'])->middleware('can:category.index');
    Route::get('/show_category_subs_items', [CategoryController::class, 'showCategoryAndSubsAndItems'])->middleware('can:category.index');
    Route::get('/show_categories_sub_in_item', [CategoryController::class, 'showAllCategoriesAndSubInItem'])->middleware('can:category.index');

    // Items
    Route::get('/show_items', [ItemController::class, 'showAll'])->middleware('can:item.index');
    Route::post('/add_item', [ItemController::class, 'create'])->middleware('can:item.add');
    Route::post('/update_item', [ItemController::class, 'update'])->middleware('can:item.update');
    Route::post('/deactivate_item', [ItemController::class, 'deactivate'])->middleware('can:item.active');
    Route::post('/reorder_items', [ItemController::class, 'reOrder'])->middleware('can:reorder');
    Route::delete('/delete_item', [ItemController::class, 'delete'])->middleware('can:item.delete');

    // Services
    Route::get('/show_services', [ServiceController::class, 'showAll'])->middleware('can:service.index');
    Route::post('/add_service', [ServiceController::class, 'create'])->middleware('can:service.add');
    Route::post('/update_service', [ServiceController::class, 'update'])->middleware('can:service.update');
    Route::get('/show_service', [ServiceController::class, 'showById'])->middleware('can:service.index');
    Route::delete('/delete_service', [ServiceController::class, 'delete'])->middleware('can:service.delete');
    Route::post('/add_service_to_order', [ServiceController::class, 'serviceToOrder'])->middleware('can:service.add');

    // Admins
    Route::post('/update_admin', [AdminController::class, 'update'])->name('admin.profile.update');;
    Route::post('/update_restaurant_admin', [AdminController::class, 'updateRestaurantAdmin'])->middleware('can:update_restaurant_admin');
    Route::get('show_admin', [AdminController::class, 'showById']);

    // order
    Route::group(['middleware' => ['isOrder', 'isTable']], function () {
        Route::get('show_orders', [OrderController::class, 'showAll'])->middleware('can:order.index');
        Route::post('/add_order', [OrderController::class, 'create'])->middleware('can:order.add');
        Route::post('/add_order2', [OrderController::class, 'create2'])->middleware('can:order.add');

        Route::get('/excel_sales_inventory', [OrderController::class, 'exportSalesInventory'])->middleware('can:order.index');

        Route::post('/update_order', [OrderController::class, 'update'])->middleware('can:order.update');
        Route::get('/show_order', [OrderController::class, 'showById'])->middleware('can:order.index');
        Route::delete('/delete_order', [OrderController::class, 'delete'])->middleware('can:order.delete');
        // Invoice
        Route::get('/show_invoices', [InvoiceController::class, 'showAll'])->middleware('can:order.index');
        Route::post('/add_invoice', [InvoiceController::class, 'create'])->middleware('can:order.add');
        Route::get('/show_invoice', [InvoiceController::class, 'showById'])->middleware('can:order.index');
        Route::delete('/delete_invoices', [InvoiceController::class, 'deleteOldRecords'])->middleware('can:order.index');
        Route::get('/excel_invoice', [InvoiceController::class, 'export'])->middleware('can:order.index');

        Route::get('/show_orders_invoice', [InvoiceController::class, 'showInvoice'])->middleware('can:order.index');
        Route::post('/add_invoice_to_table', [InvoiceController::class, 'createInvoiceTable'])->middleware('can:order.add');
        Route::patch('/update_status_invoice_paid', [InvoiceController::class, 'update'])->middleware('can:order.update');

        Route::patch('/update_status_invoice_received', [InvoiceController::class, 'Received'])->middleware('can:order.update');

        Route::put('/accept_orders', [OrderController::class, 'acceptOrders'])->middleware('can:order.update');
    });

    Route::get('/types', [TypeController::class, 'showAll']);
    // Add data entry or employee or admin
    Route::get('/show_users', [UserController::class, 'showAll'])->middleware('can:user.index');
    Route::post('/add_user', [UserController::class, 'create'])->middleware('can:user.add');
    Route::post('/update_user', [UserController::class, 'update'])->middleware('can:user.update');
    Route::get('/show_user', [UserController::class, 'showById'])->middleware('can:user.index');
    Route::post('/active_user', [UserController::class, 'deactivate'])->middleware('can:user.active');
    Route::delete('/delete_user', [UserController::class, 'delete'])->middleware('can:user.delete');

    // Advertisements
    Route::group(['middleware' => 'isAdvertisement'], function () {
        Route::get('/show_advertisements', [AdvertisementController::class, 'showAll'])->middleware('can:advertisement.index');
        Route::post('/add_advertisement', [AdvertisementController::class, 'create']);
        Route::post('/update_advertisement', [AdvertisementController::class, 'update'])->middleware('can:advertisement.update');
        Route::get('/show_advertisement', [AdvertisementController::class, 'showById'])->middleware('can:advertisement.index');
        Route::delete('/delete_advertisement', [AdvertisementController::class, 'delete'])->middleware('can:advertisement.delete');
    });

    // News
    Route::group(['middleware' => 'isNews'], function () {
        Route::get('/show_news', [NewsController::class, 'showAll'])->middleware('can:news.index');
        Route::post('/add_news', [NewsController::class, 'create'])->middleware('can:news.add');
        Route::post('/update_news', [NewsController::class, 'update'])->middleware('can:news.update');
        Route::get('/show_news_by_id', [NewsController::class, 'showById'])->middleware('can:news.index');
        Route::delete('/delete_news', [NewsController::class, 'delete'])->middleware('can:news.delete');
    });

    // Rates
    Route::group(['middleware' => 'isRate'], function () {
        Route::get('/show_rates', [RateController::class, 'showAll'])->middleware('can:rate.index');
        Route::get('/excel', [RateController::class, 'export'])->middleware('can:excel');
    });

    // Notifications
    Route::get('/show_notifications', [NotificationController::class, 'showAll'])->middleware('can:notifications.index');

    // tables
    Route::group(['middleware' => 'isTable'], function () {
        Route::get('/show_tables', [TableController::class, 'showAll'])->middleware('can:table.index');
        Route::post('/add_table', [TableController::class, 'create']);
        Route::post('/update_table', [TableController::class, 'update'])->middleware('can:table.update');
        Route::get('/show_table', [TableController::class, 'showById'])->middleware('can:table.index');
        Route::delete('/delete_table', [TableController::class, 'delete'])->middleware('can:table.delete');

        Route::get('/show_orders_request', [OrderRequestController::class, 'showAll'])->middleware('can:table.index');
        Route::get('/accept', [TableController::class, 'accept']);
    });

    Route::post('/update_fcm_token', [Controller::class, 'fcmToken']);

    Route::get('/show_employee_details', [UserController::class, 'detail']);
    // -----------------------------------------------------------------------------------------------------------------

});

Route::get('/roles', [RoleController::class, 'getRolesAdmin'])->middleware(['auth:sanctum', 'logs']);

Route::post('/table/{id}/update_status', [TableController::class, 'updateStatus']);


Route::group(['middleware' => ['checkEndDate', 'version', 'auth:sanctum', 'active', 'role:admin|superAdmin|citySuperAdmin|dataEntry|employee|restaurantManager', 'checkRole']], function () {
    // user takeout
    Route::get('/show_users_takeout', [UserTakeoutController::class, 'showAll'])->middleware('can:user.index');
    // Route::post('/add_user_takeout', [UserTakeoutController::class, 'create'])->middleware('can:user.add');
    Route::post('/update_user_takeout', [UserTakeoutController::class, 'update'])->middleware('can:user.update');
    Route::post('/active_user_takeout', [UserTakeoutController::class, 'deactivate'])->middleware('can:user.active');
    Route::delete('/delete_user_takeout', [UserTakeoutController::class, 'delete'])->middleware('can:user.delete');
    Route::get('/show_user_takeout', [UserTakeoutController::class, 'showById'])->middleware('can:user.index');
    Route::get('/show_orders_user', [UserTakeoutController::class, 'showOrderUser'])->middleware('can:user.index');

    Route::get('/show_orders_takeout', [UserTakeoutController::class, 'showOrdersTakeout'])->middleware('can:order.index');
    Route::post('/add_delivery_price', [DeliveryController::class, 'addDeliveryPrice'])->middleware('can:order.update');
    Route::delete('/delete_order_takeout', [UserTakeoutController::class, 'deleteOrderTakeout'])->middleware('can:order.delete');

    Route::post('/give_order_to_delivery', [UserTakeoutController::class, 'giveOrderToDelivery'])->middleware('can:delivery.update');
    Route::post('/rejected_order', [UserTakeoutController::class, 'rejectedOrder'])->middleware('can:delivery.update');

    // Delivery
    Route::get('/show_deliveries_sites', [DeliveryController::class, 'showAllSites']);
    Route::get('/show_deliveries', [DeliveryController::class, 'showAll'])->middleware('can:delivery.index');
    Route::post('/add_delivery', [DeliveryController::class, 'create'])->middleware('can:delivery.add');
    Route::post('/update_delivery', [DeliveryController::class, 'update'])->middleware('can:delivery.update');
    Route::post('/active_delivery', [DeliveryController::class, 'deactivate'])->middleware('can:delivery.active');
    Route::delete('/delete_delivery', [DeliveryController::class, 'delete'])->middleware('can:delivery.delete');
    Route::get('/show_delivery', [DeliveryController::class, 'showById'])->middleware('can:delivery.index');
    Route::get('/show_orders_delivery', [DeliveryController::class, 'showOrderDelivery'])->middleware('can:delivery.index');
    Route::get('/show_deliveries_active', [DeliveryController::class, 'showAllActive'])->middleware('can:delivery.index');
    Route::get('/route/{id}', [DeliveryController::class, 'route']);

    Route::post('/update_takeout', [UserTakeoutController::class, 'updateStatusOrder']);

    // send notifications
    Route::get('/send_notifications', [NotificationController::class, 'sendNotification'])->middleware('can:notifications.index');
    Route::get('/show_address', [UserTakeoutController::class, 'showAddress'])->middleware('can:user.index');

    // Coupon
    Route::get('/show_coupons', [CouponController::class, 'showAll'])->middleware('can:coupon.index');
    Route::post('/add_coupon', [CouponController::class, 'create'])->middleware('can:coupon.add');
    Route::post('/update_coupon', [CouponController::class, 'update'])->middleware('can:coupon.update');
    Route::post('/deactivate_coupon', [CouponController::class, 'deactivate'])->middleware('can:coupon.active');
    Route::delete('/delete_coupon', [CouponController::class, 'delete'])->middleware('can:coupon.delete');
    Route::get('/show_coupon', [CouponController::class, 'showById'])->middleware('can:coupon.index');

    Route::get('/show_similar_items', [ItemController::class, 'showSimilarItems'])->middleware('can:item.index');
    Route::get('/show_waiters', [UserController::class, 'showWaiters']);
});

Route::post('/update-location', [LocationController::class, 'updateLocation']);


Route::post('/password/code', [ForgatPasswordController::class, 'sendResetCode']);
Route::post('/password/verify', [ForgatPasswordController::class, 'verifyCode']);
Route::post('/password/reset', [ForgatPasswordController::class, 'forgotPassword']);
