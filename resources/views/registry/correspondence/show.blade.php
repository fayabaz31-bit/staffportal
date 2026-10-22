@extends('layouts.app')

@section('title', $document->subject.' | NfLV Staff Portal')

@section('content')
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
            <aside class="dashboard-rail" aria-label="Correspondence navigation">
                <p class="rail-label">REGISTRY</p>
                <a class="rail-link" href="{{ route('dashboard') }}">Overview</a>
                <a class="rail-link" href="{{ route('assignments.index') }}">Duty assignments</a>
                <a class="rail-link rail-link-active" href="{{ route('registry.correspondence.index') }}"><span class="rail-dot"></span> Correspondence</a>
                <div class="rail-bottom"><span class="status-dot"></span> {{ $document->statusLabel() }}</div>
            </aside>

            <main class="dashboard-main">
                <div class="dashboard-kicker">
                    <span>{{ $document->reference_number }}</span>
                    <a class="back-link" href="{{ route('registry.correspondence.index') }}">Back to correspondence</a>
                </div>

                <section class="dashboard-welcome">
                    <p class="eyebrow eyebrow-dark">{{ strtoupper($document->category?->name ?? 'REGISTRY CORRESPONDENCE') }}</p>
                    <h1>{{ $document->subject }}</h1>
                    <p>
                        Originator {{ $document->originator?->name }} · Recipient
                        {{ $document->recipient?->name ?? $document->recipient_name ?? 'not specified' }}
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
                    <div><span class="detail-label">Status</span><strong>{{ $document->statusLabel() }}</strong></div>
                    <div><span class="detail-label">Version</span><strong>v{{ $document->current_version }}</strong></div>
                    <div><span class="detail-label">Approval stage</span><strong>{{ $document->workflowInstance?->currentStep?->name ?? 'Not submitted' }}</strong></div>
                    <div><span class="detail-label">Approved</span><strong>{{ $document->approved_at?->format('d M Y, H:i') ?? '—' }}</strong></div>
                    <div><span class="detail-label">Issued</span><strong>{{ $document->issued_at?->format('d M Y, H:i') ?? '—' }}</strong></div>
                    <div><span class="detail-label">Delivery</span><strong>{{ str($document->delivery_status)->replace('_', ' ')->title() }}</strong></div>
                </div>

                @if ($document->workflowInstance !== null)
                    <ol class="approval-chain" aria-label="Configured approval chain">
                        @foreach ($document->workflowInstance->version->steps as $step)
                            <li @class([
                                'approval-chain-step',
                                'approval-chain-current' => $document->workflowInstance->current_step_id === $step->id && $document->workflowInstance->status === 'in_progress',
                                'approval-chain-done' => $document->workflowInstance->actions->where('workflow_step_id', $step->id)->whereIn('action', ['approve', 'recommend', 'review', 'forward', 'escalate'])->isNotEmpty(),
                            ])>
                                {{ $step->name }}
                                <small>{{ ucfirst($step->action_required) }}</small>
                            </li>
                        @endforeach
                    </ol>
                    <p class="form-hint">Workflow version {{ $document->workflowInstance->version->version }}, pinned when this document was submitted. Later configuration changes do not rewrite it.</p>
                    <p class="form-hint">Institution-reserved matters and final signing authority remain <strong>awaiting administrative configuration</strong>. The chain above is the configured pilot route, not a declaration of institutional policy.</p>
                @endif

                <section class="assignment-panel">
                    <h2>Current content</h2>
                    <div class="correspondence-body">{!! nl2br(e(strip_tags($latestVersion?->body_html ?? ''))) !!}</div>
                </section>

                @if ($canEdit)
                    <section class="assignment-panel">
                        <h2>Revise draft</h2>
                        <form class="panel-form" method="POST" action="{{ route('registry.correspondence.revise', $document) }}">
                            @csrf
                            @method('PATCH')
                            <div class="form-field">
                                <label for="revise-subject">Subject</label>
                                <input id="revise-subject" name="subject" type="text" value="{{ $document->subject }}" maxlength="255" required>
                            </div>
                            <div class="form-field">
                                <label for="revise-body">Body</label>
                                <textarea id="revise-body" name="body" rows="10" maxlength="50000" required>{{ $latestVersion?->body_html }}</textarea>
                            </div>
                            <button class="button" type="submit">Save new version</button>
                        </form>
                    </section>
                @endif

                <section class="assignment-panel">
                    <h2>Workflow actions</h2>
                    <div class="assignment-actions">
                        @if ($canSubmit)
                            <form method="POST" action="{{ route('registry.correspondence.submit', $document) }}">
                                @csrf
                                <button class="button button-primary" type="submit">
                                    {{ $document->status === \App\Models\CorrespondenceDocument::STATUS_RETURNED ? 'Resubmit' : 'Submit for approval' }}
                                </button>
                            </form>
                        @endif

                        @if ($canDecide)
                            <form method="POST" action="{{ route('registry.correspondence.decide', $document) }}">
                                @csrf
                                <input type="hidden" name="action" value="approve">
                                <label class="sr-only" for="approve-note">Approval note</label>
                                <input id="approve-note" name="comments" type="text" maxlength="3000" placeholder="Optional approval note">
                                <button class="button review-approve" type="submit">Approve</button>
                            </form>
                            <form method="POST" action="{{ route('registry.correspondence.decide', $document) }}">
                                @csrf
                                <input type="hidden" name="action" value="return">
                                <label class="sr-only" for="return-note">Correction required</label>
                                <input id="return-note" name="comments" type="text" maxlength="3000" placeholder="What needs correcting" required>
                                <button class="button" type="submit">Return for correction</button>
                            </form>
                            <form method="POST" action="{{ route('registry.correspondence.decide', $document) }}">
                                @csrf
                                <input type="hidden" name="action" value="reject">
                                <label class="sr-only" for="reject-note">Reason for rejection</label>
                                <input id="reject-note" name="comments" type="text" maxlength="3000" placeholder="Reason for rejection" required>
                                <button class="button review-reject" type="submit">Reject</button>
                            </form>
                        @endif

                        @if ($canIssue)
                            <form method="POST" action="{{ route('registry.correspondence.issue', $document) }}">
                                @csrf
                                <label class="sr-only" for="delivery-status">Delivery status</label>
                                <select id="delivery-status" name="delivery_status" required>
                                    <option value="not_sent">Not yet delivered</option>
                                    <option value="handed_over">Handed over</option>
                                    <option value="collected">Collected</option>
                                    <option value="posted">Posted</option>
                                </select>
                                <button class="button button-primary" type="submit">Issue document</button>
                            </form>
                        @endif

                        @if ($document->isIssued())
                            <a class="button" href="{{ route('registry.correspondence.download', $document) }}">Download issued PDF</a>
                        @endif

                        @if ($canWithdraw)
                            <form method="POST" action="{{ route('registry.correspondence.withdraw', $document) }}">
                                @csrf
                                <label class="sr-only" for="withdraw-reason">Reason for withdrawal</label>
                                <input id="withdraw-reason" name="reason" type="text" maxlength="1000" placeholder="Reason for withdrawal" required>
                                <button class="button review-reject" type="submit">Withdraw</button>
                            </form>
                        @endif
                    </div>
                    @if (! $canDecide && ! $canSubmit && ! $canIssue && ! $canWithdraw && ! $document->isIssued())
                        <p class="profile-empty">No action is required from you at this stage.</p>
                    @endif
                </section>

                <section class="assignment-panel">
                    <h2>Attachments</h2>
                    @if ($document->attachments->isEmpty())
                        <p class="profile-empty">No attachments. Supporting evidence is stored privately and audited on download.</p>
                    @else
                        @foreach ($document->attachments as $attachment)
                            <div class="attachment-row">
                                <a href="{{ route('registry.correspondence.attachments.download', [$document, $attachment]) }}">{{ $attachment->original_name }}</a>
                                <span>{{ number_format($attachment->size / 1024, 0) }} KB · {{ $attachment->uploader?->name ?? 'Unknown' }} · {{ $attachment->created_at?->format('d M Y, H:i') }}</span>
                            </div>
                        @endforeach
                    @endif

                    @if ($canEdit)
                        <form class="inline-form" method="POST" action="{{ route('registry.correspondence.attachments.store', $document) }}" enctype="multipart/form-data">
                            @csrf
                            <label class="sr-only" for="attachment-file">Add attachment</label>
                            <input id="attachment-file" name="file" type="file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required>
                            <button class="button" type="submit">Upload attachment</button>
                        </form>
                        <p class="form-hint">PDF, image or Word document, up to 10 MB. Files are stored on the private disk.</p>
                    @endif
                </section>

                <section class="assignment-panel">
                    <h2>Version history</h2>
                    <ol class="activity-timeline">
                        @foreach ($document->versions->sortByDesc('version') as $version)
                            <li>
                                <span class="activity-action">Version {{ $version->version }}</span>
                                <span class="activity-actor">{{ $version->author?->name ?? 'Unknown' }}</span>
                                <span class="activity-time">{{ $version->created_at?->format('d M Y, H:i') }}</span>
                                @if ($version->version === $document->current_version)
                                    <span class="request-status status-approved">Current</span>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                </section>

                @if ($document->workflowInstance !== null)
                    <section class="assignment-panel">
                        <h2>Decision history</h2>
                        <ol class="activity-timeline">
                            @foreach ($document->workflowInstance->actions as $action)
                                <li>
                                    <span class="activity-action">{{ str($action->action)->title() }}</span>
                                    <span class="activity-actor">{{ $action->actor?->name ?? 'System' }}</span>
                                    <span class="activity-time">{{ $action->created_at?->format('d M Y, H:i') }}</span>
                                    @if ($action->step)
                                        <span class="form-hint">{{ $action->step->name }}</span>
                                    @endif
                                    @if ($action->comments)
                                        <p>{{ $action->comments }}</p>
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    </section>
                @endif
            </main>
        </div>
    </div>
@endsection
