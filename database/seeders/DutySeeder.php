<?php

namespace Database\Seeders;

use App\DutyCatalog;
use App\Models\Duty;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Creates the duty catalogue and seeds the role-baseline duties for accounts
 * that have none. Accounts that already hold duties are left untouched so that
 * deliberate per-officer refinements survive re-seeding.
 *
 * Baseline *changes* (for example the separation-of-duties split between
 * payslip upload and release, OD-19) are applied to existing accounts by a
 * data migration, not by re-seeding.
 */
class DutySeeder extends Seeder
{
    public function run(): void
    {
        foreach (array_keys(DutyCatalog::all()) as $code) {
            Duty::updateOrCreate(
                ['code' => $code],
                DutyCatalog::definition($code) + ['is_active' => true],
            );
        }

        User::query()->each(function (User $user): void {
            if (! $user->duties()->exists()) {
                $user->syncRoleBaselineDuties();
            }
        });
    }
}
