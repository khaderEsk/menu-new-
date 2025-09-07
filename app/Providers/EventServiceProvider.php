<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use App\Events\LocationUpdated;
use App\Listeners\LocationDriver;
use App\Models\Invoice;
use App\Observers\InvoiceObserver;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        LocationUpdated::class => [
            LocationDriver::class,
        ],
        \App\Events\InvoiceStatusUpdated::class => [
            \App\Listeners\HandleInvoiceStatusUpdated::class,
        ],

    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        Invoice::observe(InvoiceObserver::class);
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
