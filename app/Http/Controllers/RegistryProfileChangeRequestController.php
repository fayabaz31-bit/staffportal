<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\ProfileChangeRequest;
use App\Models\StaffProfile;
use App\Models\User;
use App\Notifications\PortalNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Registry chain of command for profile corrections (OD-20).
 *
 * Routine Registry work rises junior officer → Head of HR → Registrar. The
 * Registrar heads the Registry department and gives final Registry approval;
 * matters the institution reserves to the Director are escalated one step
 * further before they take effect.
 *
 * A change request is only applied to the official record once it reaches its
 * final approval stage, and the queue each officer sees is scoped to the stage
 * they are authorised to act at.
 */
class RegistryProfileChangeRequestController extends Controller
{
    private const string ABILITY_REVIEW = 'review-profile-change-requests';

    private const string ABILITY_ANY = 'any-registry-approval';

    private const string ABILITY_HEAD = 'head-review-registry';

    private const string ABILITY_REGISTRAR = 'approve-as-registrar';

    private const string ABILITY_ESCALATE = 'escalate-to-director';

    public function index(Request $request): View
    {
        Gate::authorize(self::ABILITY_REVIEW);

        $user = $request->user();

        $changeRequests = ProfileChangeRequest::query()
            ->with(['user.staffProfile.department', 'requester', 'headReviewer', 'registrarApprover'])
            ->where('status', ProfileChangeRequest::STATUS_PENDING)
            ->whereIn('current_stage', $this->actionableStages($user))
            ->oldest()
            ->paginate(20);

        return view('registry.profile-change-requests.index', [
            'changeRequests' => $changeRequests,
            'stage' => $this->primaryStage($user),
            'stageLabel' => $this->stageLabel($this->primaryStage($user)),
            'canHeadReview' => $user->can(self::ABILITY_HEAD),
            'canApproveAsRegistrar' => $user->can(self::ABILITY_REGISTRAR),
            'canEscalate' => $user->can(self::ABILITY_ESCALATE),
        ]);
    }

    /**
     * First-line review by the Head of HR. This stage does not yet change the
     * official record; it forwards the request to the Registrar.
     */
    public function headReview(Request $request, ProfileChangeRequest $profileChangeRequest): RedirectResponse
    {
        Gate::authorize(self::ABILITY_HEAD);

        $validated = $request->validate([
            'head_note' => ['nullable', 'string', 'max:1000'],
            'escalate_to_director' => ['nullable', 'boolean'],
        ]);

        $escalate = $request->boolean('escalate_to_director');

        DB::transaction(function () use ($request, $profileChangeRequest, $validated, $escalate): void {
            $changeRequest = ProfileChangeRequest::query()
                ->lockForUpdate()
                ->findOrFail($profileChangeRequest->id);

            abort_unless(
                $changeRequest->status === ProfileChangeRequest::STATUS_PENDING
                && $changeRequest->current_stage === ProfileChangeRequest::STAGE_HEAD_OF_HR,
                409,
            );

            $changeRequest->update([
                'current_stage' => ProfileChangeRequest::STAGE_REGISTRAR,
                'head_reviewed_by' => $request->user()->id,
                'head_reviewed_at' => now(),
                'head_note' => $validated['head_note'] ?? null,
                'requires_director' => $escalate,
            ]);
        });

        AuditLog::record('profile_change.head_reviewed', $profileChangeRequest, [
            'field_key' => $profileChangeRequest->field_key,
            'escalated' => $escalate,
        ]);

        return redirect()->route('registry.profile-change-requests.index')
            ->with('status', 'Reviewed and forwarded to the Registrar.');
    }

    /**
     * Registrar decision. Routine matters are approved here; reserved matters
     * are escalated to the Director instead.
     */
    public function registrarDecision(Request $request, ProfileChangeRequest $profileChangeRequest): RedirectResponse
    {
        Gate::authorize(self::ABILITY_REGISTRAR);

        $validated = $request->validate([
            'reviewer_note' => ['nullable', 'string', 'max:1000'],
            'escalate_to_director' => ['nullable', 'boolean'],
        ]);

        $escalate = $request->boolean('escalate_to_director');

        if ($escalate) {
            Gate::authorize(self::ABILITY_ESCALATE);

            DB::transaction(function () use ($request, $profileChangeRequest): void {
                $changeRequest = ProfileChangeRequest::query()
                    ->lockForUpdate()
                    ->findOrFail($profileChangeRequest->id);

                abort_unless($changeRequest->current_stage === ProfileChangeRequest::STAGE_REGISTRAR, 409);

                $changeRequest->update([
                    'current_stage' => ProfileChangeRequest::STAGE_DIRECTOR,
                    'requires_director' => true,
                    'escalated_by' => $request->user()->id,
                    'escalated_at' => now(),
                ]);
            });

            AuditLog::record('profile_change.escalated', $profileChangeRequest, [
                'field_key' => $profileChangeRequest->field_key,
            ]);

            return redirect()->route('registry.profile-change-requests.index')
                ->with('status', 'Escalated to the Director for institutional sign-off.');
        }

        return $this->applyDecision(
            $request,
            $profileChangeRequest,
            ProfileChangeRequest::STAGE_REGISTRAR,
            'reviewer_note',
            $validated['reviewer_note'] ?? null,
        );
    }

    /**
     * Director sign-off for matters the Registrar escalated.
     */
    public function directorDecision(Request $request, ProfileChangeRequest $profileChangeRequest): RedirectResponse
    {
        Gate::authorize(self::ABILITY_ESCALATE);

        $validated = $request->validate([
            'reviewer_note' => ['nullable', 'string', 'max:1000'],
        ]);

        return $this->applyDecision(
            $request,
            $profileChangeRequest,
            ProfileChangeRequest::STAGE_DIRECTOR,
            'reviewer_note',
            $validated['reviewer_note'] ?? null,
        );
    }

    public function reject(Request $request, ProfileChangeRequest $profileChangeRequest): RedirectResponse
    {
        Gate::authorize(self::ABILITY_ANY);

        $validated = $request->validate([
            'reviewer_note' => ['required', 'string', 'max:1000'],
        ]);

        $actionable = $this->actionableStages($request->user());

        DB::transaction(function () use ($request, $profileChangeRequest, $validated, $actionable): void {
            $changeRequest = ProfileChangeRequest::query()
                ->lockForUpdate()
                ->findOrFail($profileChangeRequest->id);

            abort_unless(
                $changeRequest->status === ProfileChangeRequest::STATUS_PENDING
                && in_array($changeRequest->current_stage, $actionable, true),
                409,
            );

            $changeRequest->update([
                'status' => ProfileChangeRequest::STATUS_REJECTED,
                'current_stage' => ProfileChangeRequest::STAGE_COMPLETED,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'reviewer_note' => trim($validated['reviewer_note']),
            ]);
        });

        PortalNotifier::send(
            $profileChangeRequest->user,
            'Profile correction not approved',
            'Your requested profile correction was reviewed and not approved. See the reviewer note for details.',
            route('profile.show'),
            'profile',
        );

        AuditLog::record('profile_change.rejected', $profileChangeRequest, [
            'field_key' => $profileChangeRequest->field_key,
        ]);

        return redirect()->route('registry.profile-change-requests.index')->with('status', 'Profile correction rejected.');
    }

    /**
     * Apply the approved change to the official record and close the request.
     *
     * Only reached from a final approval stage (Registrar, or Director after
     * escalation), and only while the request is still pending at that stage.
     */
    private function applyDecision(
        Request $request,
        ProfileChangeRequest $profileChangeRequest,
        string $requiredStage,
        string $noteKey,
        ?string $note,
    ): RedirectResponse {
        $approved = DB::transaction(function () use ($request, $profileChangeRequest, $requiredStage, $note): bool {
            $changeRequest = ProfileChangeRequest::query()
                ->lockForUpdate()
                ->findOrFail($profileChangeRequest->id);

            abort_unless(
                $changeRequest->status === ProfileChangeRequest::STATUS_PENDING
                && $changeRequest->current_stage === $requiredStage,
                409,
            );

            $user = User::query()->lockForUpdate()->findOrFail($changeRequest->user_id);
            $profile = StaffProfile::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->firstOrFail();
            $profile->load('department');

            $currentValue = match ($changeRequest->field_key) {
                'name' => $user->name,
                'department' => $profile->department?->name,
                'designation' => $profile->designation,
                'employment_status' => $profile->employment_status,
                default => null,
            };

            if ((string) ($currentValue ?? '') !== (string) ($changeRequest->current_value ?? '')) {
                $changeRequest->update([
                    'status' => ProfileChangeRequest::STATUS_REJECTED,
                    'current_stage' => ProfileChangeRequest::STAGE_COMPLETED,
                    'reviewed_by' => $request->user()->id,
                    'reviewed_at' => now(),
                    'reviewer_note' => 'The official record changed after this request was submitted.',
                ]);

                return false;
            }

            match ($changeRequest->field_key) {
                'name' => $user->update(['name' => $changeRequest->proposed_value]),
                'department' => $this->updateDepartment($profile, $changeRequest->proposed_value),
                'designation' => $profile->update(['designation' => $changeRequest->proposed_value]),
                'employment_status' => $profile->update(['employment_status' => $changeRequest->proposed_value]),
                default => abort(422),
            };

            $changeRequest->update([
                'status' => ProfileChangeRequest::STATUS_APPROVED,
                'current_stage' => ProfileChangeRequest::STAGE_COMPLETED,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'reviewer_note' => $note,
                'registrar_approved_by' => $requiredStage === ProfileChangeRequest::STAGE_REGISTRAR
                    ? $request->user()->id
                    : $changeRequest->registrar_approved_by,
                'registrar_approved_at' => $requiredStage === ProfileChangeRequest::STAGE_REGISTRAR
                    ? now()
                    : $changeRequest->registrar_approved_at,
            ]);

            return true;
        });

        if (! $approved) {
            AuditLog::record('profile_change.rejected_stale', $profileChangeRequest, [
                'field_key' => $profileChangeRequest->field_key,
            ]);

            return redirect()->route('registry.profile-change-requests.index')
                ->with('error', 'The request was closed because the official record changed.');
        }

        PortalNotifier::send(
            $profileChangeRequest->user,
            'Profile correction approved',
            'Your requested profile correction has been reviewed and applied.',
            route('profile.show'),
            'profile',
        );

        AuditLog::record('profile_change.approved', $profileChangeRequest, [
            'field_key' => $profileChangeRequest->field_key,
            'final_stage' => $requiredStage,
        ]);

        return redirect()->route('registry.profile-change-requests.index')->with('status', 'Profile correction approved.');
    }

    /**
     * The stages this officer may act at, used to scope their queue so a junior
     * officer never sees work sitting with the Head of HR or Registrar.
     *
     * @return list<string>
     */
    private function actionableStages(User $user): array
    {
        $stages = [];

        if ($user->can(self::ABILITY_HEAD)) {
            $stages[] = ProfileChangeRequest::STAGE_HEAD_OF_HR;
        }

        if ($user->can(self::ABILITY_REGISTRAR)) {
            $stages[] = ProfileChangeRequest::STAGE_REGISTRAR;
        }

        if ($user->can(self::ABILITY_ESCALATE)) {
            $stages[] = ProfileChangeRequest::STAGE_DIRECTOR;
        }

        return $stages;
    }

    /**
     * The most senior stage this officer acts at, for the queue heading. A
     * Registry officer who only prepares requests gets the neutral stage, so no
     * decision buttons are rendered for work that is not theirs to decide.
     */
    private function primaryStage(User $user): string
    {
        if ($user->can(self::ABILITY_ESCALATE)) {
            return ProfileChangeRequest::STAGE_DIRECTOR;
        }

        if ($user->can(self::ABILITY_REGISTRAR)) {
            return ProfileChangeRequest::STAGE_REGISTRAR;
        }

        if ($user->can(self::ABILITY_HEAD)) {
            return ProfileChangeRequest::STAGE_HEAD_OF_HR;
        }

        return 'officer';
    }

    private function stageLabel(string $stage): string
    {
        return match ($stage) {
            ProfileChangeRequest::STAGE_HEAD_OF_HR => 'Head of HR review',
            ProfileChangeRequest::STAGE_REGISTRAR => 'Registrar approval',
            ProfileChangeRequest::STAGE_DIRECTOR => 'Director sign-off',
            default => 'Registry officer',
        };
    }

    private function updateDepartment(StaffProfile $profile, string $departmentName): void
    {
        $department = Department::query()
            ->where('name', $departmentName)
            ->where('is_active', true)
            ->firstOrFail();

        $profile->update(['department_id' => $department->id]);
    }
}
