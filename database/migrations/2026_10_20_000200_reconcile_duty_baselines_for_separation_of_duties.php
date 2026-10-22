<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Reconcile existing duty assignments with the OD-19 policy decision:
 *
 *  - Each unit's administrative role leads its own unit, so accounts in the
 *    Registry, Bursary and ICT admin roles gain the `duty.lead` duty.
 *  - Payslip release is separated from payslip upload: no account retained
 *    from the previous role baseline may keep `bursary.payslip_release` while
 *    it also holds `bursary.payslip_upload`. The release duty is withdrawn and
 *    must be granted to a different officer.
 *
 * Duties granted individually (recorded with an `assigned_by`) are preserved.
 */
return new class extends Migration
{
    /**
     * Role => baseline duty codes, matching App\DutyCatalog::baselineFor for the
     * roles whose baselines changed. Kept literal on purpose: migrations must
     * not depend on application classes that can change under them.
     *
     * @var array<string, list<string>>
     */
    private const ADDED_BASELINES = [
        'hr_registry_admin' => ['duty.lead'],
        'bursary_admin' => ['duty.lead'],
        'ict_superadmin' => ['duty.lead'],
        'head_of_department' => ['duty.lead'],
        'institutional_superadmin' => ['duty.lead'],
    ];

    public function up(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('duty_user') || ! DB::getSchemaBuilder()->hasTable('duties')) {
            return;
        }

        $this->ensureDutyExists('duty.lead', 'Duty lead (assign duties in own unit)', 'ict');

        $dutyIds = DB::table('duties')->pluck('id', 'code');

        foreach (self::ADDED_BASELINES as $role => $codes) {
            $userIds = DB::table('users')->where('role', $role)->pluck('id');

            foreach ($userIds as $userId) {
                foreach ($codes as $code) {
                    $dutyId = $dutyIds[$code] ?? null;

                    if ($dutyId === null) {
                        continue;
                    }

                    $exists = DB::table('duty_user')
                        ->where('user_id', $userId)
                        ->where('duty_id', $dutyId)
                        ->exists();

                    if (! $exists) {
                        DB::table('duty_user')->insert([
                            'user_id' => $userId,
                            'duty_id' => $dutyId,
                            'assigned_by' => null,
                            'assigned_at' => now(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }

        // Separation of duties: an uploader must not also hold release.
        $uploadId = $dutyIds['bursary.payslip_upload'] ?? null;
        $releaseId = $dutyIds['bursary.payslip_release'] ?? null;

        if ($uploadId !== null && $releaseId !== null) {
            $uploaderIds = DB::table('duty_user')->where('duty_id', $uploadId)->pluck('user_id');

            DB::table('duty_user')
                ->where('duty_id', $releaseId)
                ->whereIn('user_id', $uploaderIds)
                ->whereNull('assigned_by')
                ->delete();
        }
    }

    public function down(): void
    {
        // Additive reconciliation; no safe automatic reversal.
    }

    /**
     * Make sure a duty row exists so assignments can reference it even on a
     * fresh install where the catalogue seeder has not run yet.
     */
    private function ensureDutyExists(string $code, string $label, string $unit): void
    {
        if (DB::table('duties')->where('code', $code)->exists()) {
            return;
        }

        DB::table('duties')->insert([
            'code' => $code,
            'label' => $label,
            'unit' => $unit,
            'description' => 'Assign and withdraw duties for colleagues in the same department and unit. Cannot grant account-wide administrative duties.',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
