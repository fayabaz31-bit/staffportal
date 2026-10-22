@extends('layouts.app')

@section('title', $assignment->title.' | NfLV Staff Portal')

@section('content')
    @php
        $open = $assignment->isOpen();
    @endphp
    <div class="dashboard-page">
        <header class="dashboard-header">
            <a class="wordmark dashboard-wordmark" href="{{ route('dashboard') }}">
                <span class="wordmark-name">NfLV</span>
                <span class="wordmark-divider" aria-hidden="true"></span>
                <span class="wordmark-caption">Staff Portal</span>
            </a>
            <div class="account-tools">
                <span class="account-name">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="text-button" type="submit">Sign out</button>
                </form>
            </div>
        </header>

        <div class="dashboard-layout">
            <aside class="dashboard-rail" aria-label="Duty navigation">
                <p class="rail-label">ASSIGNED WORK</p>
                <a class="rail-link" href="{{ route('dashboard') }}">Overview</a>
                <a class="rail-link rail-link-active" href="{{ route('assignments.index') }}"><span class="rail-dot"></span> My duties</a>
                @can('manage-correspondence')
                    <a class="rail-link" href="{{ route('registry.correspondence.index') }}">Correspondence</a>
                @endcan
                <div class="rail-bottom"><span class="status-dot"></span> {{ strtoupper($assignment->reference_number) }}</div>
            </aside>

            <main class="dashboard-main">
                <div class="dashboard-kicker">
                    <span>{{ $assignment->reference_number }}</span>
                    <a class="back-link" href="{{ route('assignments.index') }}">Back to duties</a>
                </div>

                <section class="dashboard-welcome">
                    <p class="eyebrow eyebrow-dark">{{ strtoupper($assignment->department?->name ?? 'ASSIGNED WORK') }}</p>
                    <h1>{{ $assignment->title }}</h1>
                    <p>
                        Assigned to {{ $assignment->assignee?->name ?? 'Unassigned' }} by {{ $assignment->assigner?->name ?? 'Unknown' }}.
                        @if ($assignment->due_at !== null)
                            Deadline {{ $assignment->due_at->format('d M Y, H:i') }}.
                        @endif
                    </p>
                </section>

                @if (session('status'))
                    <p class="profile-notice" role="status">{{ session('status') }}</p>
                @endif
                @if ($errors->any())
                    <div class="profile-notice profile-notice-error" role="alert">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="assignment-summary">
                    <div><span class="detail-label">Status</span><strong>{{ $assignment->statusLabel() }}</strong></div>
                    <div><span class="detail-label">Priority</span><strong>{{ ucfirst($assignment->priority) }}</strong></div>
                    <div><span class="detail-label">Confidentiality</span><strong>{{ ucfirst($assignment->confidentiality) }}</strong></div>
                    <div><span class="detail-label">Created</span><strong>{{ $assignment->created_at?->format('d M Y') }}</strong></div>
                    <div><span class="detail-label">Deadline</span><strong>{{ $assignment->due_at?->format('d M Y, H:i') ?? 'None' }}</strong>@if ($assignment->isOverdue()) <span class="overdue-flag">Overdue</span>@endif</div>
                </div>

                <section class="assignment-panel">
                    <h2>Instructions</h2>
                    <div class="assignment-body">{!! nl2br(e($assignment->instructions)) !!}</div>
                    @if ($assignment->required_deliverables)
                        <h3>Required deliverables</h3>
                        <div class="assignment-body">{!! nl2br(e($assignment->required_deliverables)) !!}</div>
                    @endif
                </section>

                @if ($open)
                    <section class="assignment-panel">
                        <h2>Actions</h2>
                        <div class="assignment-actions">
                            @if ($isAssignee && $assignment->status === \App\Models\WorkAssignment::STATUS_ASSIGNED)
                                <form method="POST" action="{{ route('assignments.transition', $assignment) }}">
                                    @csrf
                                    <input type="hidden" name="action" value="acknowledge">
                                    <input type="hidden" name="concurrency_version" value="{{ $assignment->concurrency_version }}">
                                    <button class="button button-primary" type="submit">Acknowledge</button>
                                </form>
                            @endif

                            @if ($isAssignee && in_array($assignment->status, ['assigned', 'acknowledged', 'returned', 'on_hold'], true))
                                <form method="POST" action="{{ route('assignments.transition', $assignment) }}">
                                    @csrf
                                    <input type="hidden" name="action" value="{{ $assignment->status === 'on_hold' ? 'resume' : 'start' }}">
                                    <input type="hidden" name="concurrency_version" value="{{ $assignment->concurrency_version }}">
                                    <button class="button" type="submit">{{ $assignment->status === 'on_hold' ? 'Resume work' : 'Start work' }}</button>
                                </form>
                            @endif

                            @if ($isAssignee && in_array($assignment->status, ['acknowledged', 'in_progress', 'returned'], true))
                                <form method="POST" action="{{ route('assignments.transition', $assignment) }}">
                                    @csrf
                                    <input type="hidden" name="action" value="submit">
                                    <input type="hidden" name="concurrency_version" value="{{ $assignment->concurrency_version }}">
                                    <label class="sr-only" for="submit-note">Submission note</label>
                                    <input id="submit-note" name="comments" type="text" maxlength="2000" placeholder="Optional submission note">
                                    <button class="button button-primary" type="submit">Submit for review</button>
                                </form>
                            @endif

                            @if ($canSupervise && in_array($assignment->status, ['submitted', 'resubmitted'], true))
                                <form method="POST" action="{{ route('assignments.transition', $assignment) }}">
                                    @csrf
                                    <input type="hidden" name="action" value="complete">
                                    <input type="hidden" name="concurrency_version" value="{{ $assignment->concurrency_version }}">
                                    <button class="button review-approve" type="submit">Accept and complete</button>
                                </form>
                                <form method="POST" action="{{ route('assignments.transition', $assignment) }}">
                                    @csrf
                                    <input type="hidden" name="action" value="return">
                                    <input type="hidden" name="concurrency_version" value="{{ $assignment->concurrency_version }}">
                                    <label class="sr-only" for="return-note">Correction required</label>
                                    <input id="return-note" name="comments" type="text" maxlength="2000" placeholder="What needs correcting" required>
                                    <button class="button review-reject" type="submit">Return for correction</button>
                                </form>
                            @endif

                            @if ($canSupervise && in_array($assignment->status, ['assigned', 'acknowledged', 'in_progress'], true))
                                <form method="POST" action="{{ route('assignments.transition', $assignment) }}">
                                    @csrf
                                    <input type="hidden" name="action" value="hold">
                                    <input type="hidden" name="concurrency_version" value="{{ $assignment->concurrency_version }}">
                                    <label class="sr-only" for="hold-note">Reason for hold</label>
                                    <input id="hold-note" name="comments" type="text" maxlength="2000" placeholder="Reason for putting on hold" required>
                                    <button class="button" type="submit">Put on hold</button>
                                </form>
                            @endif

                            @if ($canSupervise)
                                <form method="POST" action="{{ route('assignments.transition', $assignment) }}">
                                    @csrf
                                    <input type="hidden" name="action" value="extend">
                                    <input type="hidden" name="concurrency_version" value="{{ $assignment->concurrency_version }}">
                                    <label class="sr-only" for="extend-due">New deadline</label>
                                    <input id="extend-due" name="due_at" type="datetime-local" required>
                                    <input name="comments" type="text" maxlength="2000" placeholder="Reason for the change" required>
                                    <button class="button" type="submit">Change deadline</button>
                                </form>
                                <form method="POST" action="{{ route('assignments.transition', $assignment) }}">
                                    @csrf
                                    <input type="hidden" name="action" value="cancel">
                                    <input type="hidden" name="concurrency_version" value="{{ $assignment->concurrency_version }}">
                                    <label class="sr-only" for="cancel-note">Reason for cancellation</label>
                                    <input id="cancel-note" name="comments" type="text" maxlength="2000" placeholder="Reason for cancellation" required>
                                    <button class="button review-reject" type="submit">Cancel duty</button>
                                </form>
                            @endif
                        </div>
                    </section>
                @endif

                <section class="assignment-panel">
                    <h2>Comments</h2>
                    @forelse ($assignment->comments->sortBy('created_at') as $comment)
                        <article class="comment-row">
                            <header>
                                <strong>{{ $comment->author?->name ?? 'System' }}</strong>
                                <span>{{ $comment->created_at?->format('d M Y, H:i') }} · {{ ucfirst($comment->kind) }}</span>
                            </header>
                            <p>{{ $comment->body }}</p>
                        </article>
                    @empty
                        <p class="profile-empty">No comments yet.</p>
                    @endforelse

                    <form class="inline-form" method="POST" action="{{ route('assignments.transition', $assignment) }}">
                        @csrf
                        <input type="hidden" name="action" value="comment">
                        <input type="hidden" name="concurrency_version" value="{{ $assignment->concurrency_version }}">
                        <label class="sr-only" for="comment-body">Add a comment</label>
                        <input id="comment-body" name="comments" type="text" maxlength="2000" placeholder="Add a comment for the record">
                        <button class="button" type="submit" @disabled(! $open || ! ($isAssignee || $canSupervise))>Comment</button>
                    </form>
                    <p class="form-hint">A comment records activity without changing the duty status.</p>
                </section>

                <section class="assignment-panel">
                    <h2>Supporting documents</h2>
                    @forelse ($assignment->attachments as $attachment)
                        <div class="attachment-row">
                            <a href="{{ route('assignments.attachments.download', [$assignment, $attachment]) }}">{{ $attachment->original_name }}</a>
                            <span>{{ number_format($attachment->size / 1024, 1) }} KB · uploaded by {{ $attachment->uploader?->name ?? 'Unknown' }}</span>
                        </div>
                    @empty
                        <p class="profile-empty">No supporting documents attached.</p>
                    @endforelse

                    @if ($open && ($isAssignee || $canSupervise))
                        <form class="inline-form" method="POST" action="{{ route('assignments.attachments.store', $assignment) }}" enctype="multipart/form-data">
                            @csrf
                            <label class="sr-only" for="assignment-file">Attach a document</label>
                            <input id="assignment-file" name="file" type="file" required accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg,.txt">
                            <button class="button" type="submit">Attach</button>
                        </form>
                        <p class="form-hint">Documents are stored privately and only visible to the officer and supervising chain.</p>
                    @endif
                </section>

                <section class="assignment-panel">
                    <h2>Activity history</h2>
                    <ol class="activity-timeline">
                        @foreach ($timeline as $event)
                            <li>
                                <span class="activity-action">{{ str($event->action)->replace('_', ' ')->title() }}</span>
                                <span class="activity-actor">{{ $event->actor?->name ?? 'System' }}</span>
                                <span class="activity-time">{{ $event->created_at?->format('d M Y, H:i') }}</span>
                                @if ($event->comments)
                                    <p>{{ $event->comments }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                </section>
            </main>
        </div>
    </div>
@endsection
