@extends('layouts.app')

@section('title', $memo->title.' | NfLV Staff Portal')

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
                    <button class="text-button" type="submit">Switch demo role</button>
                </form>
            </div>
        </header>

        <div class="dashboard-layout">
            <aside class="dashboard-rail" aria-label="Correspondence navigation">
                <p class="rail-label">CORRESPONDENCE</p>
                <a class="rail-link" href="{{ route('memos.index') }}">Inbox</a>
                @if ($isAuthor)
                    <a class="rail-link" href="{{ route('memos.create') }}">Create memo</a>
                @endif
                <div class="rail-bottom"><span class="status-dot"></span> {{ str($memo->status)->upper() }}</div>
            </aside>

            <main class="dashboard-main">
                <div class="dashboard-kicker"><span>OFFICIAL CORRESPONDENCE</span><a class="back-link" href="{{ route('memos.index') }}">Back to inbox</a></div>
                @if (session('status'))
                    <p class="profile-notice" role="status">{{ session('status') }}</p>
                @endif
                @if (session('error'))
                    <p class="profile-notice profile-notice-error" role="alert">{{ session('error') }}</p>
                @endif

                <article class="memo-document">
                    <div class="memo-document-meta"><span>{{ $memo->classification === 'normal' ? 'NORMAL' : 'UNCONFIGURED' }}</span><span>{{ $memo->published_at?->format('d M Y · H:i') ?? 'DRAFT' }}</span></div>
                    <h1>{{ $memo->title }}</h1>
                    <p class="memo-author">From {{ $memo->author->name }}</p>
                    <div class="memo-body">{{ $memo->body }}</div>
                    @if ($memo->requires_acknowledgement)
                        <div class="memo-ack-panel">
                            @if ($recipient?->acknowledged_at)
                                <p class="profile-notice" role="status">Acknowledged {{ $recipient->acknowledged_at->format('d M Y · H:i') }}.</p>
                            @elseif ($recipient)
                                <p>This memo requires acknowledgement.</p>
                                <form method="POST" action="{{ route('memos.acknowledge', $memo) }}">
                                    @csrf
                                    <button class="button button-primary" type="submit">Acknowledge receipt <span aria-hidden="true">&#8594;</span></button>
                                </form>
                            @endif
                        </div>
                    @endif
                    @if ($isAuthor)
                        <div class="memo-author-actions">
                            @if ($memo->status === \App\Models\Memo::STATUS_DRAFT)
                                <a class="text-button" href="{{ route('memos.edit', $memo) }}">Edit draft</a>
                                <form method="POST" action="{{ route('memos.publish', $memo) }}">
                                    @csrf
                                    <button class="button button-primary" type="submit">Publish memo <span aria-hidden="true">&#8594;</span></button>
                                </form>
                            @elseif ($memo->status === \App\Models\Memo::STATUS_PUBLISHED)
                                <form method="POST" action="{{ route('memos.withdraw', $memo) }}">
                                    @csrf
                                    <label class="sr-only" for="withdrawal_reason">Reason for withdrawal</label>
                                    <input id="withdrawal_reason" name="withdrawal_reason" type="text" maxlength="1000" placeholder="Reason for withdrawal" required>
                                    <button class="button review-reject" type="submit">Withdraw memo</button>
                                </form>
                            @elseif ($memo->status === \App\Models\Memo::STATUS_WITHDRAWN)
                                <p class="memo-withdrawn-note">Withdrawn {{ $memo->withdrawn_at?->format('d M Y · H:i') }}. Reason: {{ $memo->withdrawal_reason }}</p>
                            @endif
                        </div>
                    @endif
                </article>
            </main>
        </div>
    </div>
@endsection