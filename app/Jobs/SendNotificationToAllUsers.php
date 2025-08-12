<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\GeneralNotification;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendNotificationToAllUsers implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    public $backoff = [60, 120];
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
        $this->from_date = Carbon::parse($from_date)->toDateTimeString();
        $this->to_date = Carbon::parse($to_date)->toDateTimeString();
        $this->title = $title;
        $this->restaurant_id = $restaurant_id;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        User::where('restaurant_id', $this->restaurant_id)->chunk(200, function ($users) {
            foreach ($users as $user) {
                $user->notify(new GeneralNotification(
                    title: $this->title,
                    from_date: $this->from_date,
                    to_date: $this->to_date,
                    restaurant_id: $this->restaurant_id
                ));
            }
        });
        Log::info('بيانات الإشعار:', [
            'title' => $this->title,
            'from_date' => $this->from_date,
            'to_date' => $this->to_date,
            'restaurant_id' => $this->restaurant_id
        ]);
    }
    public function failed(Throwable $exception): void
    {
        // يمكنك إضافة منطق لمعالجة الفشل هنا
        Log::error('فشل إرسال الإشعارات: ' . $exception->getMessage());
    }
}
