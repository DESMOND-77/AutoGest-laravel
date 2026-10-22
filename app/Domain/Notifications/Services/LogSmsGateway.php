<?php

namespace App\Domain\Notifications\Services;

use App\Domain\Notifications\Contracts\SmsGateway;
use Illuminate\Support\Facades\Log;

/**
 * Default SMS "gateway": writes to the log instead of sending anything.
 * This is deliberately the default (see SmsGateway's docblock) - safe for
 * every environment until a real provider is chosen and configured via
 * SMS_DRIVER, and it means every SMS-triggering code path can be built,
 * wired to real events, and tested today without depending on credentials
 * that don't exist yet.
 */
class LogSmsGateway implements SmsGateway
{
    public function send(string $to, string $message): void
    {
        Log::info('SMS (log driver - no SMS gateway configured)', [
            'to' => $to,
            'message' => $message,
        ]);
    }
}
