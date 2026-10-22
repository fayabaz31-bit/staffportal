<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\ProfileChangeRequest;
use App\Models\StaffProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProfileChangeRequestController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'field_key' => ['required', Rule::in(['name', 'department', 'designation', 'employment_status'])],
            'proposed_value' => ['required', 'string', 'max:255'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        if ($validated['field_key'] === 'department' && ! Department::query()
            ->where('name', $validated['proposed_value'])
            ->where('is_active', true)
            ->exists()) {
            throw ValidationException::withMessages([
                'proposed_value' => 'Choose an active department from the list.',
            ]);
        }

        $user = $request->user();
        $profile = $user->staffProfile()->firstOrCreate([], ['employment_status' => 'unknown']);

        DB::transaction(function () use ($validated, $user, $profile): void {
            $lockedProfile = StaffProfile::query()->lockForUpdate()->findOrFail($profile->id);
            $alreadyPending = ProfileChangeRequest::query()
                ->whereBelongsTo($user)
                ->where('field_key', $validated['field_key'])
                ->where('status', ProfileChangeRequest::STATUS_PENDING)
                ->exists();

            if ($alreadyPending) {
                throw ValidationException::withMessages([
                    'field_key' => 'A request for this field is already awaiting review.',
                ]);
            }

            $lockedProfile->load('department');
            $currentValue = match ($validated['field_key']) {
                'name' => $user->name,
                'department' => $lockedProfile->department?->name,
                'designation' => $lockedProfile->designation,
                'employment_status' => $lockedProfile->employment_status,
            };

            ProfileChangeRequest::create([
                'user_id' => $user->id,
                'requested_by' => $user->id,
                'field_key' => $validated['field_key'],
                'current_value' => $currentValue,
                'proposed_value' => trim($validated['proposed_value']),
                'reason' => trim($validated['reason']),
                'status' => ProfileChangeRequest::STATUS_PENDING,
            ]);
        });

        return redirect()->route('profile.show')->with('status', 'Correction request submitted for HR review.');
    }
}
