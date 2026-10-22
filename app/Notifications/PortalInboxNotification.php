<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * In-app inbox notification (PRD §4.8).
 *
 * Delivered synchronously to the `database` channel so the inbox reflects the
 * event immediately, even when no queue worker is running.
 */
class PortalInboxNotification extends Notification
{
    public function __construct(
        public readonly string $title,
        public readonly string $message,
        public readonly string $url,
        public readonly string $category,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, string>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
            'category' => $this->category,
        ];
    }
}
