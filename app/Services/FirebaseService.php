<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Contract\Messaging;
use Throwable;

class FirebaseService
{
    private Messaging $messaging;
    public function __construct(Messaging $messaging)
    {
        $this->messaging = $messaging;
    }

    public function sendNotification($token, $title, $body, $data = [])
    {
        if (empty($token)) {
            Log::info('Attempted to send notification to an empty token.');
            return false;
        }

        $message = CloudMessage::withTarget('token', $token)
            ->withNotification(['title' => $title, 'body' => $body])
            ->withData($data);

        try {
            $this->messaging->send($message);
            return true;
        } catch (Throwable $e) {
            // Log any kind of error (network, invalid token, etc.)
            Log::error("Failed to send FCM notification to token: {$token}", [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
