<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GeneralNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    protected $title;
    protected $from_date;
    protected $to_date;
    protected $restaurant_id;
    public function __construct(
        string $title,
        string $from_date,
        string $to_date,
        string $restaurant_id
    ) {
        $this->title = $title;
        $this->from_date = $from_date;
        $this->to_date = $to_date;
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
            'title' => $this->title,
            'from_date' => $this->from_date,
            'to_date' => $this->to_date,
            'restaurant_id' => $this->restaurant_id,
            'url' => url('/notifications'),
            'icon' => 'fas fa-bullhorn',
        ];
    }
}
