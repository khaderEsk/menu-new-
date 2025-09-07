<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\Notification;

class NotificationService
{
    // to show paginate Notification
    public function paginate($id,$num)
    {
        $admins = Notification::whereRestaurantId($id)->latest()->paginate($num);
        $notifications = Notification::whereRestaurantId($id)->latest()->paginate($num);
        foreach ($notifications as $notification) {
            $notificationsBeforeUpdate[] = $notification->toArray();
            $notification->update([
                'read_at' => now(),
            ]);
        }
        return $admins;
    }

}
