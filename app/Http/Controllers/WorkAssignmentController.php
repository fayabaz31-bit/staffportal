<?php

namespace App\Http\Controllers;

use App\Models\AssignmentAttachment;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\ReferenceSequence;
use App\Models\User;
use App\Models\WorkAssignment;
use App\Notifications\PortalNotifier;
use App\Support\AssignmentWorkflow;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Duty assignment and tracking.
 *
 * "Duty" here is a piece of assigned work; the permission catalogue lives in
 * `App\DutyCatalog` and is unrelated to this controller's records.
 */
class WorkAssignmentController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $scope = $request->string('scope')->toString() ?: 'mine';
        $status = $request->string('status')->toString();
        $search = trim($request->string('search')->toString());

        $query = WorkAssignment::query()
            ->with(['assignee', 'assigner', 'department'])
            ->latest('id');

        match ($scope) {
            'given' => $query->where('assigned_by', $user->id),
            'team' => $query->whereIn('assigned_to', AssignmentWorkflow::supervisedUserIds($user)),
            default => $query->where('assigned_to', $user->id),
        };

        if (array_key_exists($status, WorkAssignment::statusLabels())) {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $query->where(function (Builder $query) use ($search): void {
                $query->where('title', 'like', '%'.$search.'%')
                    ->orWhere('reference_number', 'like', '%'.$search.'%');
            });
        }

        $assignments = $query->paginate(15)->withQueryString();

        $counts = [
            'mine' => WorkAssignment::query()->where('assigned_to', $user->id)->open()->count(),
            'given' => WorkAssignment::query()->where('assigned_by', $user->id)->open()->count(),
            'team' => WorkAssignment::query()->whereIn('assigned_to', AssignmentWorkflow::supervisedUserIds($user))->open()->count(),
            'overdue' => WorkAssignment::query()->where('assigned_to', $user->id)->overdue()->count(),
        ];

        return view('assignments.index', [
            'assignments' => $assignments,
            'scope' => $scope,
            'status' => $status,
            'search' => $search,
            'counts' => $counts,
            'canAssign' => AssignmentWorkflow::supervisedUserIds($user) !== [],
        ]);
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        $assignable = User::query()
            ->whereIn('id', AssignmentWorkflow::supervisedUserIds($user))
            ->where('status', 'active')
            ->with('staffProfile.department')
            ->orderBy('name')
            ->get();

        abort_if($assignable->isEmpty(), 403, 'You do not supervise any active officer.');

        return view('assignments.create', [
            'assignable' => $assignable,
            'priorities' => WorkAssignment::priorityLabels(),
            'departments' => Department::query()->ordered()->get(),
            'defaultAssignee' => $request->integer('assigned_to') ?: null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'instructions' => ['required', 'string', 'max:5000'],
            'assigned_to' => ['required', Rule::exists('users', 'id')->where('status', 'active')],
            'priority' => ['required', Rule::in(array_keys(WorkAssignment::priorityLabels()))],
            'confidentiality' => ['required', Rule::in(['internal', 'confidential', 'restricted'])],
            'due_at' => ['nullable', 'date', 'after:now'],
            'required_deliverables' => ['nullable', 'string', 'max:3000'],
            'department_id' => ['nullable', Rule::exists('departments', 'id')],
        ]);

        $assignee = User::query()->findOrFail($validated['assigned_to']);
        $actor = $request->user();

        abort_unless(AssignmentWorkflow::canAssign($actor, $assignee), 403, 'You may not assign a duty to that officer.');

        $assignment = DB::transaction(function () use ($validated, $actor, $assignee): WorkAssignment {
            $year = (int) now()->year;
            $sequence = ReferenceSequence::next(
                'work_assignment',
                $year,
                WorkAssignment::query()->whereYear('created_at', $year)->count(),
            );

            $assignment = WorkAssignment::query()->create([
                'reference_number' => sprintf('NFLV/REG/DUTY/%d/%04d', $year, $sequence),
                'title' => trim($validated['title']),
                'instructions' => $validated['instructions'],
                'department_id' => $validated['department_id'] ?? $assignee->profile?->department_id,
                'organisational_unit_id' => $assignee->profile?->organisational_unit_id,
                'assigned_by' => $actor->id,
                'assigned_to' => $assignee->id,
                'priority' => $validated['priority'],
                'status' => WorkAssignment::STATUS_ASSIGNED,
                'confidentiality' => $validated['confidentiality'],
                'required_deliverables' => $validated['required_deliverables'] ?? null,
                'due_at' => $validated['due_at'] ?? null,
            ]);

            $assignment->events()->create([
                'actor_id' => $actor->id,
                'action' => 'assign',
                'from_status' => null,
                'to_status' => WorkAssignment::STATUS_ASSIGNED,
                'comments' => null,
                'created_at' => now(),
            ]);

            AuditLog::record('assignment.created', $assignment, [
                'reference_number' => $assignment->reference_number,
                'assignee_id' => $assignee->id,
            ]);

            return $assignment;
        });

        PortalNotifier::send(
            $assignee,
            'New duty assigned',
            'You have been assigned "'.$assignment->title.'" ('.$assignment->reference_number.').',
            route('assignments.show', $assignment),
            'assignment',
        );

        return redirect()->route('assignments.show', $assignment)
            ->with('status', 'Duty assigned with reference '.$assignment->reference_number.'.');
    }

    public function show(WorkAssignment $assignment): View
    {
        $user = request()->user();

        abort_unless(AssignmentWorkflow::canView($user, $assignment), 403);

        $assignment->load([
            'assignee.staffProfile.department',
            'assigner',
            'department',
            'organisationalUnit',
            'comments.author',
            'events.actor',
            'attachments.uploader',
        ]);

        return view('assignments.show', [
            'assignment' => $assignment,
            'isAssignee' => $user->id === $assignment->assigned_to,
            'canSupervise' => AssignmentWorkflow::canSupervise($user, $assignment),
            'timeline' => $assignment->events->sortBy('created_at'),
        ]);
    }

    public function transition(Request $request, WorkAssignment $assignment): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'string', 'max:24'],
            'comments' => ['nullable', 'string', 'max:2000'],
            'due_at' => ['nullable', 'date'],
            'assigned_to' => ['nullable', Rule::exists('users', 'id')->where('status', 'active')],
            'concurrency_version' => ['nullable', 'integer'],
        ]);

        $workflow = new AssignmentWorkflow($assignment);

        $updated = $workflow->apply($request->user(), $validated['action'], $validated);

        $workflow->notify($updated, $validated['action']);

        return redirect()->route('assignments.show', $updated)
            ->with('status', 'Duty updated: '.$updated->statusLabel().'.');
    }

    public function upload(Request $request, WorkAssignment $assignment): RedirectResponse
    {
        abort_unless(AssignmentWorkflow::canView($request->user(), $assignment), 403);

        if (! $assignment->isOpen()) {
            abort(422, 'A closed duty cannot accept new attachments.');
        }

        $validated = $request->validate([
            'file' => [
                'required',
                'file',
                'max:10240',
                'mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg,txt',
            ],
        ]);

        $file = $validated['file'];
        $contents = (string) file_get_contents($file->getRealPath());
        $hash = hash('sha256', $contents);
        $path = 'assignments/'.$assignment->id.'/'.$hash.'.'.$file->getClientOriginalExtension();

        Storage::disk('private')->put($path, $contents);

        $assignment->attachments()->create([
            'uploaded_by' => $request->user()->id,
            'original_name' => basename($file->getClientOriginalName()),
            'storage_path' => $path,
            'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
            'size' => strlen($contents),
            'sha256' => $hash,
            'created_at' => now(),
        ]);

        AuditLog::record('assignment.attachment_uploaded', $assignment, [
            'reference_number' => $assignment->reference_number,
            'sha256' => $hash,
        ]);

        return back()->with('status', 'Supporting document attached.');
    }

    public function download(WorkAssignment $assignment, AssignmentAttachment $attachment): StreamedResponse
    {
        abort_unless(AssignmentWorkflow::canView(request()->user(), $assignment), 403);
        abort_unless($attachment->work_assignment_id === $assignment->id, 404);
        abort_unless(Storage::disk('private')->exists($attachment->storage_path), 404);

        AuditLog::record('assignment.attachment_downloaded', $assignment, [
            'reference_number' => $assignment->reference_number,
            'attachment_id' => $attachment->id,
        ]);

        return Storage::disk('private')->download($attachment->storage_path, $attachment->original_name);
    }
}
