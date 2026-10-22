<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Sends the in-app inbox record immediately and dispatches the generic email
 * through the queue (PRD §4.8/F7). Email dispatch is best-effort: a delivery
 * failure must never roll back or hide the authoritative in-app record.
 */
class PortalNotifier
{
    public static function send(User $user, string $title, string $message, string $url, string $category): void
    {
        $user->notify(new PortalInboxNotification($title, $message, $url, $category));

        try {
            $user->notify(new PortalEmailNotification($category));
        } catch (\Throwable $exception) {
            Log::warning('Portal notification email failed to queue.', [
                'user_id' => $user->id,
                'category' => $category,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * @param  iterable<int, User>  $users
     */
    public static function sendMany(iterable $users, string $title, string $message, string $url, string $category): int
    {
        $count = 0;

        foreach ($users as $user) {
            self::send($user, $title, $message, $url, $category);
            $count++;
        }

        return $count;
    }
}
