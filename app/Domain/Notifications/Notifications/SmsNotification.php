<?php

namespace App\Domain\Notifications\Notifications;

use App\Domain\Notifications\Channels\SmsChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SmsNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $message,
    ) {}

    public function via(object $notifiable): array
    {
        return [SmsChannel::class];
    }

    public function toSms(object $notifiable): string
    {
        return $this->message;
    }
}
