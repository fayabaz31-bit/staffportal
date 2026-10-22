<?php

namespace App\Http\Controllers;

use App\DutyCatalog;
use App\Models\AuditLog;
use App\Models\Delegation;
use App\Models\Department;
use App\Models\OrganisationalUnit;
use App\Models\ReportingRelationship;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Configuration surface for the organisational hierarchy.
 *
 * Units, reporting lines and delegations are data, so Registry leadership can be
 * changed without editing application code (Master Prompt §17).
 */
class OrganisationController extends Controller
{
    public function index(): View
    {
        Gate::authorize('manage-organisation');

        return view('registry.organisation.index', [
            'units' => OrganisationalUnit::query()
                ->with(['department', 'parent', 'head'])
                ->orderBy('department_id')
                ->orderBy('sort_order')
                ->get(),
            'departments' => Department::query()->ordered()->get(),
            'officers' => User::query()->where('status', 'active')->orderBy('name')->get(['id', 'name', 'staff_number']),
            'reportingLines' => ReportingRelationship::query()
                ->with(['subordinate', 'supervisor', 'organisationalUnit'])
                ->effectiveOn()
                ->orderBy('subordinate_id')
                ->get(),
            'delegations' => Delegation::query()
                ->with(['delegator', 'delegate', 'organisationalUnit'])
                ->active()
                ->latest('starts_at')
                ->get(),
            'delegationScopes' => [
                DutyCatalog::REGISTRY_HEAD_REVIEW => 'Head of HR review',
                DutyCatalog::REGISTRY_REGISTRAR_APPROVE => 'Registrar approval',
                DutyCatalog::REGISTRY_DIRECTOR_ESCALATION => 'Director escalation',
                '*' => 'All delegated duties',
            ],
        ]);
    }

    public function storeUnit(Request $request): RedirectResponse
    {
        Gate::authorize('manage-organisation');

        $validated = $request->validate([
            'department_id' => ['required', Rule::exists('departments', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:40', 'alpha_dash', Rule::unique('organisational_units', 'code')],
            'parent_id' => ['nullable', Rule::exists('organisational_units', 'id')],
            'head_user_id' => ['nullable', Rule::exists('users', 'id')->where('status', 'active')],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $unit = OrganisationalUnit::query()->create([
            'department_id' => $validated['department_id'],
            'name' => $validated['name'],
            'code' => strtoupper($validated['code']),
            'parent_id' => $validated['parent_id'] ?? null,
            'head_user_id' => $validated['head_user_id'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => true,
        ]);

        AuditLog::record('organisation.unit_created', $unit, [
            'code' => $unit->code,
            'department_id' => $unit->department_id,
        ]);

        return back()->with('status', 'Unit "'.$unit->name.'" created.');
    }

    public function updateUnit(Request $request, OrganisationalUnit $unit): RedirectResponse
    {
        Gate::authorize('manage-organisation');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'head_user_id' => ['nullable', Rule::exists('users', 'id')->where('status', 'active')],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $unit->update([
            'name' => $validated['name'],
            'head_user_id' => $validated['head_user_id'] ?? null,
            'sort_order' => $validated['sort_order'] ?? $unit->sort_order,
            'is_active' => $request->boolean('is_active', $unit->is_active),
        ]);

        AuditLog::record('organisation.unit_updated', $unit, ['code' => $unit->code]);

        return back()->with('status', 'Unit updated.');
    }

    public function storeReporting(Request $request): RedirectResponse
    {
        Gate::authorize('manage-organisation');

        $validated = $request->validate([
            'subordinate_id' => ['required', Rule::exists('users', 'id')->where('status', 'active')],
            'supervisor_id' => ['required', Rule::exists('users', 'id')->where('status', 'active'), 'different:subordinate_id'],
            'organisational_unit_id' => ['nullable', Rule::exists('organisational_units', 'id')],
            'relationship_type' => ['required', Rule::in(['line_manager', 'acting', 'delegated'])],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'is_primary' => ['nullable', 'boolean'],
        ]);

        $overlap = ReportingRelationship::query()
            ->where('subordinate_id', $validated['subordinate_id'])
            ->where('relationship_type', $validated['relationship_type'])
            ->where(function ($query) use ($validated): void {
                $query->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $validated['effective_from']);
            })
            ->when(
                isset($validated['effective_to']),
                fn ($query) => $query->where('effective_from', '<=', $validated['effective_to']),
            )
            ->exists();

        if ($overlap) {
            throw ValidationException::withMessages([
                'effective_from' => 'An overlapping reporting line of this type already exists for that officer.',
            ]);
        }

        $line = ReportingRelationship::query()->create([
            'subordinate_id' => $validated['subordinate_id'],
            'supervisor_id' => $validated['supervisor_id'],
            'organisational_unit_id' => $validated['organisational_unit_id'] ?? null,
            'relationship_type' => $validated['relationship_type'],
            'effective_from' => $validated['effective_from'],
            'effective_to' => $validated['effective_to'] ?? null,
            'is_primary' => $request->boolean('is_primary', true),
            'created_by' => $request->user()->id,
        ]);

        AuditLog::record('organisation.reporting_created', $line, [
            'subordinate_id' => $line->subordinate_id,
            'supervisor_id' => $line->supervisor_id,
        ]);

        return back()->with('status', 'Reporting line recorded.');
    }

    public function endReporting(Request $request, ReportingRelationship $relationship): RedirectResponse
    {
        Gate::authorize('manage-organisation');

        $validated = $request->validate([
            'effective_to' => ['required', 'date', 'after_or_equal:'.$relationship->effective_from->toDateString()],
        ]);

        $relationship->update(['effective_to' => $validated['effective_to']]);

        AuditLog::record('organisation.reporting_ended', $relationship, [
            'effective_to' => $validated['effective_to'],
        ]);

        return back()->with('status', 'Reporting line closed on '.$validated['effective_to'].'.');
    }

    public function storeDelegation(Request $request): RedirectResponse
    {
        Gate::authorize('manage-organisation');

        $validated = $request->validate([
            'delegator_id' => ['required', Rule::exists('users', 'id')->where('status', 'active')],
            'delegate_id' => ['required', Rule::exists('users', 'id')->where('status', 'active'), 'different:delegator_id'],
            'organisational_unit_id' => ['nullable', Rule::exists('organisational_units', 'id')],
            'scope' => ['required', 'string', 'max:80'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        if (! array_key_exists($validated['scope'], [
            DutyCatalog::REGISTRY_HEAD_REVIEW => true,
            DutyCatalog::REGISTRY_REGISTRAR_APPROVE => true,
            DutyCatalog::REGISTRY_DIRECTOR_ESCALATION => true,
            '*' => true,
        ])) {
            throw ValidationException::withMessages(['scope' => 'That delegation scope is not recognised.']);
        }

        $delegation = Delegation::query()->create([
            'delegator_id' => $validated['delegator_id'],
            'delegate_id' => $validated['delegate_id'],
            'organisational_unit_id' => $validated['organisational_unit_id'] ?? null,
            'scope' => $validated['scope'],
            'starts_at' => $validated['starts_at'],
            'ends_at' => $validated['ends_at'],
            'reason' => $validated['reason'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        AuditLog::record('organisation.delegation_created', $delegation, [
            'scope' => $delegation->scope,
            'delegate_id' => $delegation->delegate_id,
            'starts_at' => $delegation->starts_at->toDateTimeString(),
            'ends_at' => $delegation->ends_at->toDateTimeString(),
        ]);

        return back()->with('status', 'Delegation recorded for the stated period.');
    }

    public function revokeDelegation(Request $request, Delegation $delegation): RedirectResponse
    {
        Gate::authorize('manage-organisation');

        abort_if($delegation->revoked_at !== null, 422, 'This delegation is already revoked.');

        $delegation->update([
            'revoked_at' => now(),
            'revoked_by' => $request->user()->id,
        ]);

        AuditLog::record('organisation.delegation_revoked', $delegation, [
            'scope' => $delegation->scope,
            'delegate_id' => $delegation->delegate_id,
        ]);

        return back()->with('status', 'Delegation revoked.');
    }
}
