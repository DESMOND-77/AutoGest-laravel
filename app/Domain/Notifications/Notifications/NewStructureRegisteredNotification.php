<?php

namespace App\Domain\Notifications\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * The Notifications domain only depends on Core (see the domain diagram) -
 * takes plain scalars, not a Structure model, for the same reason
 * AlertNotification does: turning a domain event into a human-readable
 * message is the bridging listener's job (app/Listeners), not this class's.
 *
 * Unlike AlertNotification (in-app only, reused across many low-stakes
 * events like a payment being recorded), a pending tenant activation
 * request needs an actual email — a super-admin isn't expected to be
 * sitting on the platform dashboard waiting for one to show up. Kept as
 * its own class rather than adding a 'mail' channel to AlertNotification,
 * since that would start emailing on every existing AlertNotification use
 * site (payments, student registrations, dossier reviews) — a much bigger
 * behavioral change than this one event calls for.
 */
class NewStructureRegisteredNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $structureName,
        public readonly string $structureEmail,
        public readonly string $link,
    ) {}

    // $link is expected absolute (route(..., absolute: true), the default) —
    // it's used both as the mailed action button's href and the in-app
    // notification's link, and only the former requires an absolute URL.

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nouvelle auto-école en attente de validation')
            ->greeting('Bonjour '.$notifiable->name.',')
            ->line('Une nouvelle auto-école vient de s\'inscrire sur la plateforme et attend votre validation.')
            ->line('Établissement : '.$this->structureName)
            ->line('E-mail de l\'administrateur : '.$this->structureEmail)
            ->action('Voir les établissements', $this->link)
            ->line('Merci de valider ou de rejeter cette demande dès que possible.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Nouvelle auto-école en attente',
            'message' => "{$this->structureName} attend votre validation.",
            'link' => $this->link,
        ];
    }
}
