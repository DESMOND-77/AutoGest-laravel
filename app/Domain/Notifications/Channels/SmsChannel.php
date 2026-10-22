<?php

namespace App\Domain\Notifications\Channels;

use App\Domain\Notifications\Contracts\SmsGateway;
use Illuminate\Notifications\Notification;

/**
 * A standard Laravel notification channel - return SmsChannel::class from a
 * Notification's via() and implement toSms() on it, the same shape as the
 * built-in 'mail'/'database' channels. Works with on-demand notifications
 * (Notification::route('sms', $phoneNumber)->notify(...)) since Student
 * isn't a Notifiable model (many students have no user account), and with
 * routeNotificationForSms() on any model that is Notifiable.
 */
class SmsChannel
{
    public function __construct(
        private readonly SmsGateway $gateway,
    ) {}

    public function send(object $notifiable, Notification $notification): void
    {
        $to = $notifiable->routeNotificationFor('sms', $notification);

        if (! $to) {
            return;
        }

        $this->gateway->send($to, $notification->toSms($notifiable));
    }
}
