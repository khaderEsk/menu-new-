<?php

namespace App\Jobs;

use App\Mail\SendCodeMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendEmailTolUser implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    protected $email;
    protected $name;
    protected $randomNumber;
    public function __construct(
        string $email,
        string $name,
        string $randomNumber
    ) {
        $this->email = $email;
        $this->name = $name;
        $this->randomNumber = $randomNumber;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        Mail::to($this->email)->send(new SendCodeMail($this->name, $this->randomNumber));
    }
}
