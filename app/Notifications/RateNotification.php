<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RateNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */

    protected $title;
    protected $body;
    protected $phone;
    protected $restaurant_id;
    protected $rate;
    protected $note;
    public function __construct(
        string $title,
        string $body,
        string $phone,
        string $restaurant_id,
        string $rate,
        string $note
    ) {
        $this->title = $title;
        $this->body = $body;
        $this->phone = $phone;
        $this->restaurant_id = $restaurant_id;
        $this->rate = $rate;
        $this->note = $note;
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

            'title'             => $this->title,
            'body'              => $this->body,
            'phone'             => $this->phone,
            'restaurant_id'     => $this->restaurant_id,
            'rate'              => $this->rate,
            'note'              => $this->note,
        ];
    }
}
