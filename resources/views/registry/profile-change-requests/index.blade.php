@extends('layouts.app')

@section('title', 'Profile change review | NfLV Staff Portal')

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
            <aside class="dashboard-rail" aria-label="Registry navigation">
                <p class="rail-label">HR / REGISTRY</p>
                <a class="rail-link" href="{{ route('dashboard') }}">Overview</a>
                <a class="rail-link rail-link-active" href="{{ route('registry.profile-change-requests.index') }}"><span class="rail-dot"></span> Profile changes</a>
                <div class="rail-bottom"><span class="status-dot"></span> AUTHORIZED REVIEW</div>
            </aside>

            <main class="dashboard-main">
                <div class="dashboard-kicker"><span>PROFILE CORRECTIONS · {{ strtoupper($stageLabel) }}</span><a class="back-link" href="{{ route('dashboard') }}">Back to dashboard</a></div>
                <section class="dashboard-welcome">
                    <p class="eyebrow eyebrow-dark">REGISTRY CHAIN OF COMMAND</p>
                    <h1>Change requests</h1>
                    <p>
                        Registry corrections move from the officer to the Head of HR, then to the Registrar.
                        Matters the institution reserves to the Director are escalated one final step.
                    </p>
                </section>

                @if (session('status'))
                    <p class="profile-notice" role="status">{{ session('status') }}</p>
                @endif
                @if (session('error'))
                    <p class="profile-notice profile-notice-error" role="alert">{{ session('error') }}</p>
                @endif

                <ol class="approval-chain" aria-label="Registry approval chain">
                    <li @class(['approval-chain-step', 'approval-chain-current' => $stage === \App\Models\ProfileChangeRequest::STAGE_HEAD_OF_HR])>Head of HR review</li>
                    <li @class(['approval-chain-step', 'approval-chain-current' => $stage === \App\Models\ProfileChangeRequest::STAGE_REGISTRAR])>Registrar approval</li>
                    <li @class(['approval-chain-step', 'approval-chain-current' => $stage === \App\Models\ProfileChangeRequest::STAGE_DIRECTOR])>Director sign-off</li>
                </ol>

                @forelse ($changeRequests as $changeRequest)
                    <article class="review-request">
                        <header class="review-request-header">
                            <div>
                                <span class="detail-label">{{ $changeRequest->user->staff_number }} · {{ $changeRequest->user->staffProfile?->department?->name ?? 'Department not recorded' }}</span>
                                <h2>{{ $changeRequest->user->name }}</h2>
                            </div>
                            <span class="request-status request-status-pending">{{ strtoupper($stageLabel) }}</span>
                        </header>
                        <dl class="review-request-details">
                            <div><dt>Field</dt><dd>{{ str($changeRequest->field_key)->replace('_', ' ')->title() }}</dd></div>
                            <div><dt>Current record</dt><dd>{{ $changeRequest->current_value ?: 'Not recorded' }}</dd></div>
                            <div><dt>Requested value</dt><dd>{{ $changeRequest->proposed_value }}</dd></div>
                            <div><dt>Reason</dt><dd>{{ $changeRequest->reason }}</dd></div>
                            @if ($changeRequest->headReviewer !== null)
                                <div><dt>Head of HR</dt><dd>{{ $changeRequest->headReviewer->name }}@if ($changeRequest->head_note) — {{ $changeRequest->head_note }}@endif</dd></div>
                            @endif
                        </dl>

                        @if ($canHeadReview && $changeRequest->current_stage === \App\Models\ProfileChangeRequest::STAGE_HEAD_OF_HR)
                            <div class="review-actions">
                                <form method="POST" action="{{ route('registry.profile-change-requests.head-review', $changeRequest) }}">
                                    @csrf
                                    <label class="sr-only" for="head-note-{{ $changeRequest->id }}">Head of HR note</label>
                                    <input id="head-note-{{ $changeRequest->id }}" name="head_note" type="text" maxlength="1000" placeholder="Optional review note">
                                    <button class="button review-approve" type="submit">Review and forward to Registrar</button>
                                </form>
                                <form method="POST" action="{{ route('registry.profile-change-requests.reject', $changeRequest) }}">
                                    @csrf
                                    <label class="sr-only" for="reject-note-{{ $changeRequest->id }}">Reason for rejection</label>
                                    <input id="reject-note-{{ $changeRequest->id }}" name="reviewer_note" type="text" maxlength="1000" placeholder="Reason for rejection" required>
                                    <button class="button review-reject" type="submit">Reject</button>
                                </form>
                            </div>
                        @elseif ($canApproveAsRegistrar && $changeRequest->current_stage === \App\Models\ProfileChangeRequest::STAGE_REGISTRAR)
                            <div class="review-actions">
                                <form method="POST" action="{{ route('registry.profile-change-requests.registrar', $changeRequest) }}">
                                    @csrf
                                    <label class="sr-only" for="registrar-note-{{ $changeRequest->id }}">Registrar note</label>
                                    <input id="registrar-note-{{ $changeRequest->id }}" name="reviewer_note" type="text" maxlength="1000" placeholder="Optional approval note">
                                    <button class="button review-approve" type="submit">Approve as Registrar</button>
                                </form>
                                @if ($canEscalate)
                                    <form method="POST" action="{{ route('registry.profile-change-requests.registrar', $changeRequest) }}">
                                        @csrf
                                        <input type="hidden" name="escalate_to_director" value="1">
                                        <button class="button" type="submit">Escalate to Director</button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('registry.profile-change-requests.reject', $changeRequest) }}">
                                    @csrf
                                    <label class="sr-only" for="reject-note-{{ $changeRequest->id }}">Reason for rejection</label>
                                    <input id="reject-note-{{ $changeRequest->id }}" name="reviewer_note" type="text" maxlength="1000" placeholder="Reason for rejection" required>
                                    <button class="button review-reject" type="submit">Reject</button>
                                </form>
                            </div>
                        @elseif ($canEscalate && $changeRequest->current_stage === \App\Models\ProfileChangeRequest::STAGE_DIRECTOR)
                            <div class="review-actions">
                                <form method="POST" action="{{ route('registry.profile-change-requests.director', $changeRequest) }}">
                                    @csrf
                                    <label class="sr-only" for="director-note-{{ $changeRequest->id }}">Director note</label>
                                    <input id="director-note-{{ $changeRequest->id }}" name="reviewer_note" type="text" maxlength="1000" placeholder="Optional sign-off note">
                                    <button class="button review-approve" type="submit">Sign off as Director</button>
                                </form>
                                <form method="POST" action="{{ route('registry.profile-change-requests.reject', $changeRequest) }}">
                                    @csrf
                                    <label class="sr-only" for="reject-note-{{ $changeRequest->id }}">Reason for rejection</label>
                                    <input id="reject-note-{{ $changeRequest->id }}" name="reviewer_note" type="text" maxlength="1000" placeholder="Reason for rejection" required>
                                    <button class="button review-reject" type="submit">Reject</button>
                                </form>
                            </div>
                        @else
                            <p class="review-waiting">This request is with the Registry chain of command. No action is required from you.</p>
                        @endif
                    </article>
                @empty
                    <p class="profile-empty">No profile change requests are awaiting your stage of review.</p>
                @endforelse

                {{ $changeRequests->links() }}
            </main>
        </div>
    </div>
@endsection