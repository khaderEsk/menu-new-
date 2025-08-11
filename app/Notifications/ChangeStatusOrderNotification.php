<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ChangeStatusOrderNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    protected $title;
    protected $body;
    protected $status;
    protected $totalEstimatedDuration;
    protected $price;
    protected $restaurant_id;
    public function __construct(
        string $title,
        string $body,
        string $status,
        string $totalEstimatedDuration,
        string $price,
        string $restaurant_id
    ) {
        $this->title = $title;
        $this->body = $body;
        $this->status = $status;
        $this->totalEstimatedDuration = $totalEstimatedDuration;
        $this->price = $price;
        $this->restaurant_id = $restaurant_id;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     */

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title'
            => $this->title,

            'body'
            => $this->body,

            'status'
            => $this->status,

            'totalEstimatedDuration'
            => $this->totalEstimatedDuration,

            'price'
            => $this->price,

            'restaurant_id'
            => $this->restaurant_id
        ];
    }
}
