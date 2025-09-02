<?php

namespace App\Observers;

use App\Enum\InvoiceStatus;
use App\Events\NewOrder;
use App\Events\OrderUpdated;
use App\Http\Resources\InvoiceUserMobileResource;
use App\Models\Invoice;
use App\Notifications\ChangeStatusOrderNotification;
use App\Services\FirebaseService;
use App\Services\OsrmService;
use Carbon\Carbon;
use FFI;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;


class InvoiceObserver
// implements ShouldQueue
{
    public function __construct(
        private OsrmService     $osrmService,
        private FirebaseService $firebaseService
    ) {}

    /**
     * Handle the Invoice "updated" event.
     * This entire method will now run asynchronously in a background queue worker.
     */
    public function updated(Invoice $invoice): void
    {
        // --- Part 1: Your existing distance calculation logic ---
        // This part remains the same. It will now run safely in the background.
        if ($invoice->wasChanged('status') && $invoice->status == InvoiceStatus::APPROVED) {
            $restaurant = $invoice->orders->first()?->restaurant;
            $address = $invoice->address;
            if (!$restaurant || !$address || !$restaurant->latitude || !$address->latitude) {
                Log::warning("Missing data for invoice #{$invoice->id}. Cannot calculate route.");
                // return;
            }

            $routeData = $this->osrmService->getRoute(
                $restaurant->latitude,
                $restaurant->longitude,
                $address->latitude,
                $address->longitude
            );

            if (!$routeData) {
                Log::error("Failed to get route from OSRM for invoice #{$invoice->id}.");
                return;
            }

            $distanceInKm = round($routeData['distance'] / 1000, 2);
            $durationInMinutes = round($routeData['duration'] / 60, 2);

            $invoice->deliveryRoute()->updateOrCreate(
                ['invoice_id' => $invoice->id],
                [
                    'start_lat' => $restaurant->latitude,
                    'start_lon' => $restaurant->longitude,
                    'end_lat' => $address->latitude,
                    'end_lon' => $address->longitude,
                    'distance' => $distanceInKm,
                    'duration' => $durationInMinutes,
                ]
            );

            // 1. Calculate the delivery price
            $deliveryPrice = $distanceInKm * ($restaurant->price_km ?? 0);

            // 2. Update the invoice object with the new values
            $invoice->delivery_price = $deliveryPrice;
            $invoice->total += $deliveryPrice; // Adds the delivery price to the existing total
            $invoice->total_estimated_duration = 40 + $durationInMinutes;

            // 3. Save all changes to the invoice at once
            $invoice->saveQuietly();
        } elseif ($invoice->wasChanged('status') && $invoice->status == InvoiceStatus::PROCESSING) {
            $travelTime = $invoice->deliveryRoute?->duration ?? 0;
            $invoice->total_estimated_duration = 30 + $travelTime;
            $invoice->saveQuietly();
        } // 3. On 'Under Delivery': SET the time to just the travel time
        elseif ($invoice->wasChanged('status') && $invoice->status == InvoiceStatus::UNDER_DELIVERY) {
            $travelTime = $invoice->deliveryRoute?->duration ?? 0;
            $invoice->total_estimated_duration = $travelTime;
            $invoice->saveQuietly();
        } elseif ($invoice->wasChanged('status') && $invoice->status == InvoiceStatus::COMPLETED) {

            $invoice->total_estimated_duration = null;
            $deliveryRoute = $invoice->deliveryRoute()->update(
                [
                    'distance' => null,
                ]
            );
            $invoice->saveQuietly();
        }

        $freshInvoice = Invoice::with([
            'user',
            'delivery',
            'deliveryRoute',
            'orders',
            'address',
            'admin'
        ])->find($invoice->id);


        if ($freshInvoice) {
            $this->sendUserNotifications($freshInvoice);
            $this->sendDeliveryDriverUpdates($freshInvoice);
        }
    }

    private function calculateAndSaveRouteData(Invoice $invoice): void
    {
        $restaurant = $invoice->orders->first()?->restaurant;
        $address = $invoice->address;
        if (!$restaurant || !$address || !$restaurant->latitude || !$address->latitude) {
            Log::warning("Missing data for invoice #{$invoice->id}. Cannot calculate route.");
            return;
        }
        $routeData = $this->osrmService->getRoute($restaurant->latitude, $restaurant->longitude, $address->latitude, $address->longitude);
        if (!$routeData) {
            Log::error("Failed to get route from OSRM for invoice #{$invoice->id}.");
            return;
        }
        $distanceInKm = round($routeData['distance'] / 1000, 2);
        $durationInMinutes = round($routeData['duration'] / 60, 2);
        $invoice->deliveryRoute()->updateOrCreate(['invoice_id' => $invoice->id], ['start_lat' => $restaurant->latitude, 'start_lon' => $restaurant->longitude, 'end_lat' => $address->latitude, 'end_lon' => $address->longitude, 'distance' => $distanceInKm, 'duration' => $durationInMinutes]);
        $deliveryPrice = $distanceInKm * ($restaurant->price_km ?? 0);
        $invoice->delivery_price = $deliveryPrice;
        $invoice->total += $deliveryPrice;
        $invoice->total_estimated_duration = 40 + $durationInMinutes;
        $invoice->saveQuietly();
    }

    /**
     * A private helper to send notifications to the customer.
     */
    private function sendUserNotifications(Invoice $invoice): void
    {
        if (!$invoice->user_id) return;

        // --- Firebase Push Notification ---
        if ($invoice->user && $invoice->user->fcm_token) {
            $title = "Your Order Status Updated";
            $statusName = ucfirst(str_replace('_', ' ', strtolower($invoice->status->name)));
            $body = "Your order #{$invoice->num} is now {$statusName}.";
            $this->firebaseService->sendNotification($invoice->user->fcm_token, $title, $body, []);
            $this->firebaseService->sendNotification($invoice->admin->fcm_token, $title, $body, []);
            $invoice->user->notify(new ChangeStatusOrderNotification(
                title: 'تم تغير حالة الطلب',
                body: 'اصبح طلبك' . $statusName ?? null,
                status: $statusName,
                totalEstimatedDuration: $invoice->total_estimated_duration ?? null,
                price: $invoice->price ?? null,
                restaurant_id: $invoice->restaurant_id ?? null,
            ));
        }

        // --- Real-Time WebSocket Event ---
        // 1. Get the full list of the user's recent orders.
        $userOrders = Invoice::with(['orders.restaurant', 'address', 'user', 'delivery', 'deliveryRoute'])
            ->where('user_id', $invoice->user_id)
            ->whereDate('created_at', '>=', Carbon::yesterday())
            ->latest()
            ->get();

        $userOrders->each(function ($orderInvoice) {
            if (
                $orderInvoice->status == InvoiceStatus::UNDER_DELIVERY &&
                $orderInvoice->deliveryRoute &&
                $orderInvoice->address
            ) {
                // We use the coordinates already saved in the deliveryRoute for consistency.
                $routeData = $this->osrmService->getRoute(
                    (float)$orderInvoice->deliveryRoute->start_lat,
                    (float)$orderInvoice->deliveryRoute->start_lon,
                    (float)$orderInvoice->address->latitude,
                    (float)$orderInvoice->address->longitude
                );

                // Add the distance as a dynamic property so the resource can access it.
                $orderInvoice->distance_km = isset($routeData['distance']) ? round($routeData['distance'] / 1000, 2) : null;
            }
        });
        // dd($invoice->user_id);
        // 3. Dispatch the event with the now-corrected data.
        event(new NewOrder($userOrders, $invoice->user->type_id ?? 0));
    }

    /**
     * A private helper to send updates to the delivery driver.
     */
    private function sendDeliveryDriverUpdates(Invoice $invoice): void
    {
        if ($invoice->delivery_id) {
            $deliveryInvoices = Invoice::with('orders')
                ->whereIn('status', [InvoiceStatus::WAITING->value, InvoiceStatus::APPROVED->value, InvoiceStatus::PROCESSING->value, InvoiceStatus::UNDER_DELIVERY->value])
                ->where('restaurant_id', $invoice->restaurant_id)
                ->where('delivery_id', $invoice->delivery_id)
                ->get();

            event(new OrderUpdated($deliveryInvoices));
        }
    }
}
