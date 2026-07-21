<?php

namespace App\Domain\Notifications\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * The Notifications domain only depends on Core (see the domain diagram) —
 * it knows nothing about students, payments or vehicles. Turning a domain
 * event into a human-readable title/message/link is the job of the bridging
 * listeners in app/Listeners (same pattern as RecordVehicleExpenseInLedger
 * for Fleet -> Finance), which build one of these and hand it off.
 */
class AlertNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $title,
        public readonly string $message,
        public readonly ?string $link = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'link' => $this->link,
        ];
    }
}
