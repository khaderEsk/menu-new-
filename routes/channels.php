<?php

use App\Models\Invoice;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});


Broadcast::channel('restaurant{restaurant_id}', function ($user, $restaurant_id) {
    // التحقق من أن المستخدم ينتمي للمطعم المعين
    return $user->restaurant_id === (int) $restaurant_id;
});

Broadcast::channel('locationUpdated', function () {
    return true;
});

Broadcast::channel('order.{orderId}', function ($user, $orderId) {
    if($user->role == 0)
        $order = Invoice::whereUserId($user->id)->whereId($orderId)->first();
    else
        $order = Invoice::whereDeliveryId($user->id)->whereId($orderId)->first();

    if (!$order)
        return false;

    return (int) $order->id === (int) $orderId;
});

Broadcast::channel('all-orders.{status}.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});


Broadcast::channel('new-orders.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
    // return true;
});
