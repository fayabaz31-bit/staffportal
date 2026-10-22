<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Generic queued email notification (PRD §4.8, F7).
 *
 * Deliberately contains no potentially sensitive detail (no salary figures,
 * memo bodies, leave reasons or document references) because email is a less
 * controlled channel. Staff are directed to sign in to view the item. Queued so
 * delivery failures are retryable and visible in `failed_jobs`.
 */
class PortalEmailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $category,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('NfLV Staff Portal: new notification')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('There is a new '.str_replace('_', ' ', $this->category).' item waiting in your secure portal inbox.')
            ->line('For your security it is not included in this email. Please sign in to view it.')
            ->action('Open the staff portal', route('dashboard'))
            ->line('If you did not expect this message, please contact ICT support.');
    }
}
