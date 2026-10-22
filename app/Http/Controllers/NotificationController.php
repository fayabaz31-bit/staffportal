<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * Display the authenticated staff member's in-app notification inbox.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('notifications.index', [
            'notifications' => $user->notifications()->paginate(20),
            'unreadCount' => $user->unreadNotifications()->count(),
        ]);
    }

    /**
     * Mark a single notification as read and follow its deep link.
     */
    public function markRead(Request $request, DatabaseNotification $notification): RedirectResponse
    {
        $user = $request->user();

        abort_unless(
            $notification->notifiable_type === $user->getMorphClass()
            && (int) $notification->notifiable_id === $user->id,
            404,
        );

        if ($notification->read_at === null) {
            $notification->markAsRead();
        }

        $target = (string) ($notification->data['url'] ?? route('notifications.index'));

        // Only ever follow internal, application-generated destinations.
        if (! $this->isInternalDestination($target)) {
            $target = route('notifications.index');
        }

        return redirect()->to($target);
    }

    /**
     * Accept a relative application path or an absolute URL on this application's
     * own origin, and reject anything else (open-redirect defence).
     */
    private function isInternalDestination(string $target): bool
    {
        if (str_starts_with($target, '//')) {
            return false;
        }

        if (str_starts_with($target, '/')) {
            return true;
        }

        $root = rtrim(url('/'), '/');

        return $target === $root || str_starts_with($target, $root.'/');
    }

    /**
     * Mark every unread notification as read.
     */
    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('status', 'All notifications have been marked as read.');
    }
}
