<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\Api\SuperAdmin\AdminController;
use App\Http\Controllers\Api\SuperAdmin\BackupController;
use App\Http\Controllers\Api\SuperAdmin\CityController;
use App\Http\Controllers\Api\SuperAdmin\EmojiController;
use App\Http\Controllers\Api\SuperAdmin\FontController;
use App\Http\Controllers\Api\SuperAdmin\ForgatPasswordController;
use App\Http\Controllers\Api\SuperAdmin\IpQrController;
use App\Http\Controllers\Api\SuperAdmin\MenuTemplateController;
use App\Http\Controllers\Api\SuperAdmin\PackageController;
use App\Http\Controllers\Api\SuperAdmin\RateController;
use App\Http\Controllers\Api\SuperAdmin\RestaurantController;
use App\Http\Controllers\Api\SuperAdmin\RoleController;
use App\Http\Controllers\Api\SuperAdmin\TypeController;
use App\Http\Controllers\Api\SuperAdmin\UserController;
use App\Http\Controllers\ArtisanController;
use App\Http\Controllers\Controller;
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
// Route::post('/login', [Controller::class, 'Login'])->middleware(['ensureTokenIsNotFromAnotherDevice']);

Route::middleware(['logs','auth:sanctum','role:superAdmin|citySuperAdmin|dataEntry','active'])->group(function () {

    // Cities
    Route::get('/show_cities', [CityController::class, 'showAll'])->middleware('can:city.index');
    Route::post('/active_or_not_city',[CityController::class,'activeOrNot'])->middleware('can:city.active');
    Route::post('/add_city', [CityController::class, 'create'])->middleware('can:city.add');
    Route::post('/update_city', [CityController::class, 'update'])->middleware('can:city.update');
    Route::delete('/delete_city',[CityController::class,'delete'])->middleware('can:city.delete');
    Route::get('/show_city_by_id', [CityController::class, 'showById'])->middleware('can:city.index');

    // City Super Admins ,Data Entries
    Route::get('/show_admins', [AdminController::class, 'showAll'])->middleware('can:super_admin.index');
    Route::post('/add_admin', [AdminController::class, 'create'])->middleware('can:super_admin.add');
    Route::post('/update_admin', [AdminController::class, 'update'])->middleware('can:super_admin.update');
    Route::post('/active_admin', [AdminController::class, 'deactivate'])->middleware('can:super_admin.active');
    Route::delete('/delete_admin', [AdminController::class, 'delete'])->middleware('can:super_admin.delete');
    Route::get('/show_admin', [AdminController::class, 'showById'])->middleware('can:super_admin.index');

    // Restaurant Manager
    Route::get('/restaurant_managers', [AdminController::class, 'showAllBoss'])->middleware('can:restaurant_manager.index');
    Route::post('/restaurant_manager', [AdminController::class, 'createBoss'])->middleware('can:restaurant_manager.add');
    Route::post('/update_restaurant_manager', [AdminController::class, 'updateBoss'])->middleware('can:restaurant_manager.update');
    Route::post('/active_restaurant_manager', [AdminController::class, 'deactivateBoss'])->middleware('can:restaurant_manager.deactivat');
    Route::delete('/delete_restaurant_manager', [AdminController::class, 'deleteBoss'])->middleware('can:restaurant_manager.delete');
    Route::get('/show_restaurant_manager', [AdminController::class, 'showByIdBoss'])->middleware('can:restaurant_manager.index');

    // Menu Template
    Route::get('/show_menu_forms', [MenuTemplateController::class, 'showAll'])->middleware('can:menu.index');
    Route::post('/add_menu_form', [MenuTemplateController::class, 'create'])->middleware('can:menu.add');
    Route::post('/deactivate_menu_form', [MenuTemplateController::class, 'deactivate'])->middleware('can:menu.active');
    Route::get('/show_menu_form_by_id', [MenuTemplateController::class, 'showById'])->middleware('can:menu.index');
    Route::delete('/delete_menu_form', [MenuTemplateController::class, 'delete'])->middleware('can:menu.delete');

    // Emoji
    Route::get('/show_emoji', [EmojiController::class, 'showAll'])->middleware('can:emoji.index');
    Route::post('/add_emoji', [EmojiController::class, 'create'])->middleware('can:emoji.add');
    Route::post('/update_emoji', [EmojiController::class, 'update'])->middleware('can:emoji.update');
    Route::post('/deactivate_emoji', [EmojiController::class, 'deactivate'])->middleware('can:emoji.active');
    Route::get('/show_emoji_by_id', [EmojiController::class, 'showById'])->middleware('can:emoji.index');
    Route::delete('/delete_emoji', [EmojiController::class, 'delete'])->middleware('can:emoji.delete');

    // Restaurant
    Route::get('/show_restaurants', [RestaurantController::class, 'showAll'])->middleware('can:restaurant.index');
    Route::post('/add_restaurant', [RestaurantController::class, 'create'])->middleware('can:restaurant.add');
    // Route::post('/add_admin_to_restaurant', [RestaurantController::class, 'createAdmin'])->middleware('can:admin_restaurant.add');
    Route::post('/add_admin_to_restaurant', [UserController::class, 'create'])->middleware('can:admin_restaurant.add');

    Route::post('/update_restaurant', [RestaurantController::class, 'update'])->middleware('can:restaurant.update');
    Route::get('/show_restaurant', [RestaurantController::class, 'showById'])->middleware('can:restaurant.index');
    Route::post('/deactivate_restaurant', [RestaurantController::class, 'deactivate'])->middleware('can:restaurant.active');
    Route::delete('/delete_restaurant', [RestaurantController::class, 'delete'])->middleware('can:restaurant.delete');
    // Route::get('/show_contracts', [RestaurantController::class, 'showContracts']);
    Route::post('/update_super_admin_restaurant_id', [RestaurantController::class, 'restaurantId'])->middleware('can:restaurant.update_super_admin_restaurant_id');
    Route::get('/show_contracts', [RestaurantController::class, 'ShowContracts'])->middleware('can:restaurant.index');

    // Add data entry or employee or admin Restaurant
    Route::get('/show_admins_restaurant', [UserController::class, 'showAll'])->middleware('can:admin_restaurant.index');
    // Route::post('/add_admin_restaurant', [UserController::class, 'create']);
    Route::post('/update_admin_restaurant', [UserController::class, 'update'])->middleware('can:admin_restaurant.update');
    Route::get('/show_admin_restaurant', [UserController::class, 'showById'])->middleware('can:admin_restaurant.index');
    Route::post('/active_admin_restaurant', [UserController::class, 'deactivate'])->middleware('can:admin_restaurant.active');
    Route::delete('/delete_admin_restaurant', [UserController::class, 'delete'])->middleware('can:admin_restaurant.delete');


    // Rates
    Route::get('/show_rates', [RateController::class, 'showAll'])->middleware('can:rate.index');
    Route::get('/excel', [RateController::class, 'export'])->name('export')->middleware('can:excel');


    // Package
    Route::get('/show_packages', [PackageController::class, 'showAll'])->middleware('can:package.index');
    Route::post('/add_package', [PackageController::class, 'create'])->middleware('can:package.add');
    Route::post('/update_package', [PackageController::class, 'update'])->middleware('can:package.update');
    Route::get('/show_package', [PackageController::class, 'showById'])->middleware('can:package.index');
    Route::post('/active_package', [PackageController::class, 'deactivate'])->middleware('can:package.active');
    Route::delete('/delete_package', [PackageController::class, 'delete'])->middleware('can:package.delete');
    Route::post('/add_subscription', [PackageController::class, 'createSubscription'])->middleware('can:package.add_subscription');
    Route::get('/show_restaurant_subscription', [PackageController::class, 'showByIdSubscription'])->middleware('can:package.show_restaurant_subscription');

    // Super Admin
    Route::post('/update_super_admin', [Controller::class, 'updateSuperAdmin']);

    Route::get('/logs', [ActivityLogController::class, 'index'])->middleware('can:logs');

    Route::delete('/logs_delete', [ActivityLogController::class, 'deleteOldRecords'])->middleware('can:logs');

    Route::post('/add_permissions', [RoleController::class, 'dataEntry']);

    Route::post('/add_type', [TypeController::class, 'create']);
    Route::delete('/delete_type', [TypeController::class, 'delete']);

    Route::get('/types', [TypeController::class, 'showAll']);

    Route::get('/show_fonts', [FontController::class, 'showAll']);

    Route::get('/show_qrs', [IpQrController::class, 'showAll']);
    Route::get('/show_qr', [IpQrController::class, 'showById']);
    Route::post('/add_qr', [IpQrController::class, 'create']);

});
    // permissions

    Route::get('/permissions', [RoleController::class, 'getPermissions'])->middleware(['auth:sanctum','logs']);
    Route::get('/roles', [RoleController::class, 'getRolesSuperAdmin'])->middleware(['auth:sanctum','logs']);

    Route::get('/permission', [RoleController::class, 'Permissions'])->middleware(['auth:sanctum','logs']);

    Route::middleware(['auth:sanctum','role:superAdmin'])->group(function () {

    Route::get('/backup/create', [BackupController::class, 'createBackup']);
    Route::get('/backup/download', [BackupController::class, 'downloadLatestBackup']);
    Route::get('/backup/storage', [BackupController::class, 'downloadStorage']);
    Route::get('/path', [BackupController::class, 'readFile']);
    Route::get('/write', [BackupController::class, 'writeFile']);
    Route::post('/upload', [BackupController::class, 'upload']);

    // Font
    Route::post('/add_font', [FontController::class, 'create']);
    Route::put('/update_font', [FontController::class, 'update']);
    Route::get('/show_font_by_id', [FontController::class, 'showById']);
    Route::delete('/delete_font', [FontController::class, 'delete']);

    Route::get('/storage/link', [ArtisanController::class, 'storageLink']);
    Route::get('/migrate', [ArtisanController::class, 'migrate']);
});


Route::post('/password/code', [ForgatPasswordController::class, 'sendResetCode']);
Route::post('/password/verify', [ForgatPasswordController::class, 'verifyCode']);
Route::post('/password/reset', [ForgatPasswordController::class, 'forgotPassword']);
