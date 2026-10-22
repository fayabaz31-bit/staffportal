<?php

namespace App\Observers;

use App\Models\User;

/**
 * Keeps a user's duties aligned with their role baseline.
 *
 * Roles are the broad unit an officer belongs to; duties are the specific
 * assignments they perform. Re-deriving duties whenever a role changes keeps
 * the two in step without the account console having to orchestrate it (and
 * makes AC-10 "role change takes immediate effect" true for duties as well).
 */
class UserObserver
{
    public function created(User $user): void
    {
        $user->syncRoleBaselineDuties();
    }

    public function updated(User $user): void
    {
        if ($user->wasChanged('role')) {
            $user->syncRoleBaselineDuties();
        }
    }
}
