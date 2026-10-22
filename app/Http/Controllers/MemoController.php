<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Memo;
use App\Models\MemoRecipient;
use App\Models\User;
use App\Notifications\PortalNotifier;
use App\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MemoController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $receivedMemos = MemoRecipient::query()
            ->with('memo.author')
            ->where('user_id', $user->id)
            ->whereHas('memo', fn (Builder $query) => $query->where('status', Memo::STATUS_PUBLISHED))
            ->latest('delivered_at')
            ->paginate(15, ['*'], 'inbox_page');
        $authoredMemos = $user->can('manage-memos')
            ? $user->authoredMemos()->latest()->limit(25)->get()
            : collect();

        return view('memos.index', [
            'receivedMemos' => $receivedMemos,
            'authoredMemos' => $authoredMemos,
            'canManageMemos' => $user->can('manage-memos'),
        ]);
    }

    public function create(Request $request): View
    {
        return view('memos.form', $this->formData($request->user()));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedDraft($request);
        $this->assertAudienceScope($request->user(), $data);

        $memo = Memo::create([
            'author_id' => $request->user()->id,
            'title' => trim($data['title']),
            'body' => trim($data['body']),
            'classification' => Memo::CLASSIFICATION_NORMAL,
            'status' => Memo::STATUS_DRAFT,
            'audience_type' => $data['audience_type'],
            'audience_department_id' => $data['audience_type'] === 'department' ? $data['department_id'] : null,
            'audience_user_ids' => $data['audience_type'] === 'individual' ? array_map('intval', $data['recipient_ids']) : null,
            'requires_acknowledgement' => $request->boolean('requires_acknowledgement'),
        ]);

        return redirect()->route('memos.show', $memo)->with('status', 'Draft saved. Review the recipients before publishing.');
    }

    public function edit(Request $request, Memo $memo): View
    {
        $this->assertDraftAuthor($request->user(), $memo);

        return view('memos.form', $this->formData($request->user()) + ['memo' => $memo]);
    }

    public function update(Request $request, Memo $memo): RedirectResponse
    {
        $this->assertDraftAuthor($request->user(), $memo);
        $data = $this->validatedDraft($request);
        $this->assertAudienceScope($request->user(), $data);

        $memo->update([
            'title' => trim($data['title']),
            'body' => trim($data['body']),
            'classification' => Memo::CLASSIFICATION_NORMAL,
            'audience_type' => $data['audience_type'],
            'audience_department_id' => $data['audience_type'] === 'department' ? $data['department_id'] : null,
            'audience_user_ids' => $data['audience_type'] === 'individual' ? array_map('intval', $data['recipient_ids']) : null,
            'requires_acknowledgement' => $request->boolean('requires_acknowledgement'),
        ]);

        return redirect()->route('memos.show', $memo)->with('status', 'Draft updated.');
    }

    public function show(Request $request, Memo $memo): View
    {
        $user = $request->user();
        $recipient = $memo->recipients()->where('user_id', $user->id)->first();
        $isAuthor = $memo->author_id === $user->id && $user->can('manage-memos');

        if ($memo->status === Memo::STATUS_DRAFT) {
            abort_unless($isAuthor, 404);
        } elseif ($memo->status === Memo::STATUS_PUBLISHED) {
            abort_unless($isAuthor || $recipient !== null, 404);

            if ($recipient !== null && $recipient->viewed_at === null) {
                $recipient->update(['viewed_at' => now()]);
            }
        } else {
            abort_unless($isAuthor, 404);
        }

        return view('memos.show', [
            'memo' => $memo->load('author'),
            'recipient' => $recipient,
            'isAuthor' => $isAuthor,
        ]);
    }

    public function publish(Request $request, Memo $memo): RedirectResponse
    {
        $recipientIds = DB::transaction(function () use ($request, $memo): array {
            $lockedMemo = Memo::query()->lockForUpdate()->findOrFail($memo->id);
            $this->assertDraftAuthor($request->user(), $lockedMemo);

            if ($lockedMemo->status !== Memo::STATUS_DRAFT) {
                abort(409);
            }

            $this->assertAudienceScope($request->user(), [
                'audience_type' => $lockedMemo->audience_type,
                'department_id' => $lockedMemo->audience_department_id,
            ]);

            $recipients = $this->resolveRecipients($lockedMemo);

            if ($recipients->isEmpty()) {
                throw ValidationException::withMessages([
                    'audience' => 'The selected audience has no active staff accounts with personnel profiles.',
                ]);
            }

            $now = now();
            MemoRecipient::insert($recipients->map(fn (User $user): array => [
                'memo_id' => $lockedMemo->id,
                'user_id' => $user->id,
                'delivered_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all());

            $lockedMemo->update([
                'status' => Memo::STATUS_PUBLISHED,
                'published_at' => $now,
            ]);

            return $recipients->modelKeys();
        });

        $recipients = User::query()->whereIn('id', $recipientIds)->get();

        PortalNotifier::sendMany(
            $recipients,
            'New memo: '.$memo->title,
            'A new '.$memo->audience_type.' memo has been published to you.',
            route('memos.show', $memo),
            'memo',
        );

        AuditLog::record('memo.published', $memo, [
            'recipients' => count($recipientIds),
            'audience_type' => $memo->audience_type,
            'requires_acknowledgement' => (bool) $memo->requires_acknowledgement,
        ]);

        return redirect()->route('memos.show', $memo)->with('status', count($recipientIds).' recipients snapshot-published.');
    }

    public function acknowledge(Request $request, Memo $memo): RedirectResponse
    {
        abort_unless($memo->status === Memo::STATUS_PUBLISHED && $memo->requires_acknowledgement, 404);

        $recipient = $memo->recipients()->where('user_id', $request->user()->id)->firstOrFail();
        $recipient->update(['acknowledged_at' => $recipient->acknowledged_at ?? now()]);

        return redirect()->route('memos.show', $memo)->with('status', 'Acknowledgement recorded.');
    }

    public function withdraw(Request $request, Memo $memo): RedirectResponse
    {
        $validated = $request->validate([
            'withdrawal_reason' => ['required', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($request, $memo, $validated): void {
            $lockedMemo = Memo::query()->lockForUpdate()->findOrFail($memo->id);
            abort_unless($lockedMemo->author_id === $request->user()->id && $request->user()->can('manage-memos'), 404);
            abort_unless($lockedMemo->status === Memo::STATUS_PUBLISHED, 409);

            $lockedMemo->update([
                'status' => Memo::STATUS_WITHDRAWN,
                'withdrawn_by' => $request->user()->id,
                'withdrawn_at' => now(),
                'withdrawal_reason' => trim($validated['withdrawal_reason']),
            ]);
        });

        AuditLog::record('memo.withdrawn', $memo, [
            'reason' => trim($validated['withdrawal_reason']),
        ]);

        return redirect()->route('memos.show', $memo)->with('status', 'Memo withdrawn and retained in the author record.');
    }

    /** @return array<string, mixed> */
    private function formData(User $user): array
    {
        $departments = Department::query()->where('is_active', true)->orderBy('name');
        $recipients = User::query()
            ->where('status', 'active')
            ->whereHas('staffProfile')
            ->with('staffProfile.department')
            ->orderBy('name');

        if ($user->role === UserRole::HeadOfDepartment) {
            $departmentId = $user->staffProfile?->department_id;
            $departments->whereKey($departmentId);
            $recipients->whereHas('staffProfile', fn (Builder $query) => $query->where('department_id', $departmentId));
        }

        return [
            'departments' => $departments->get(),
            'recipients' => $recipients->get(),
        ];
    }

    /** @return array<string, mixed> */
    private function validatedDraft(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:50000'],
            'classification' => ['required', Rule::in([Memo::CLASSIFICATION_NORMAL])],
            'audience_type' => ['required', Rule::in(['individual', 'department', 'institution'])],
            'department_id' => ['nullable', 'required_if:audience_type,department', Rule::exists('departments', 'id')->where('is_active', true)],
            'recipient_ids' => ['nullable', 'required_if:audience_type,individual', 'array', 'min:1'],
            'recipient_ids.*' => ['integer', Rule::exists('users', 'id')->where('status', 'active')],
            'requires_acknowledgement' => ['sometimes', 'boolean'],
        ]);
    }

    /** @param array<string, mixed> $data */
    private function assertAudienceScope(User $user, array $data): void
    {
        if ($user->role !== UserRole::HeadOfDepartment) {
            return;
        }

        $departmentId = $user->staffProfile?->department_id;

        if ($data['audience_type'] !== 'department' || $departmentId === null
            || (int) ($data['department_id'] ?? 0) !== $departmentId) {
            throw ValidationException::withMessages([
                'department_id' => 'Department heads may only distribute memos to their own department.',
            ]);
        }
    }

    /** @return Collection<int, User> */
    private function resolveRecipients(Memo $memo): Collection
    {
        $query = User::query()
            ->where('status', 'active')
            ->whereHas('staffProfile');

        return match ($memo->audience_type) {
            'individual' => $query->whereIn('id', $memo->audience_user_ids ?? [])->orderBy('id')->get(),
            'department' => $query->whereHas('staffProfile', fn (Builder $profileQuery) => $profileQuery->where('department_id', $memo->audience_department_id))
                ->orderBy('id')->get(),
            'institution' => $query->orderBy('id')->get(),
            default => collect(),
        };
    }

    private function assertDraftAuthor(User $user, Memo $memo): void
    {
        abort_unless($memo->author_id === $user->id && $memo->status === Memo::STATUS_DRAFT, 404);
    }
}
