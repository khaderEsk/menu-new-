<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::middleware('api')
                // ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('api')
                ->prefix('superAdmin_api')
                ->group(base_path('routes/api/superAdmin.php'));

            Route::middleware('api')
                ->prefix('admin_api')
                ->group(base_path('routes/api/admin.php'));

            Route::middleware('api')
                ->prefix('citySuper_api')
                ->group(base_path('routes/api/citySuper.php'));

            Route::middleware('api')
                ->prefix('customer_api')
                ->group(base_path('routes/api/customer.php'));

            Route::middleware('api')
                ->prefix('user_api')
                ->group(base_path('routes/api/user.php'));

            Route::middleware('api')
                ->prefix('delivery_api')
                ->group(base_path('routes/api/delivery.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
