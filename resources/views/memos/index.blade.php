@extends('layouts.app')

@section('title', 'Correspondence | NfLV Staff Portal')

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
                <a class="rail-link rail-link-active" href="{{ route('memos.index') }}"><span class="rail-dot"></span> Inbox</a>
                @if ($canManageMemos)
                    <a class="rail-link" href="{{ route('memos.create') }}">Create memo</a>
                    <div class="rail-divider"></div>
                    <p class="rail-label">AUTHORED BY ME</p>
                    <a class="rail-link" href="{{ route('memos.create') }}">New draft</a>
                @endif
                <div class="rail-bottom"><span class="status-dot"></span> RECIPIENT-SCOPED ACCESS</div>
            </aside>

            <main class="dashboard-main">
                <div class="dashboard-kicker"><span>STAFF CORRESPONDENCE</span><span>{{ $receivedMemos->total() }} received</span></div>
                <section class="dashboard-welcome">
                    <p class="eyebrow eyebrow-dark">MEMOS AND CIRCULARS</p>
                    <h1>Correspondence</h1>
                    <p>Published messages are visible only to their snapshotted recipients.</p>
                </section>

                @if (session('status'))
                    <p class="profile-notice" role="status">{{ session('status') }}</p>
                @endif

                <section class="memo-list-section" aria-labelledby="inbox-heading">
                    <div class="section-heading"><div><p class="eyebrow eyebrow-dark">DELIVERED TO YOU</p><h2 id="inbox-heading">Inbox</h2></div></div>
                    @forelse ($receivedMemos as $memoRecipient)
                        <a class="memo-list-item" href="{{ route('memos.show', $memoRecipient->memo) }}">
                            <div class="memo-list-copy">
                                <div class="memo-list-meta"><span>{{ $memoRecipient->memo->published_at?->format('d M Y') }}</span><span>NORMAL</span></div>
                                <h3>{{ $memoRecipient->memo->title }}</h3>
                                <p>From {{ $memoRecipient->memo->author->name }}</p>
                            </div>
                            <span class="memo-read-status {{ $memoRecipient->viewed_at ? 'is-read' : '' }}">{{ $memoRecipient->viewed_at ? 'VIEWED' : 'NEW' }}</span>
                        </a>
                    @empty
                        <p class="profile-empty">No correspondence has been delivered to your account.</p>
                    @endforelse
                    {{ $receivedMemos->links() }}
                </section>

                @if ($canManageMemos)
                    <section class="memo-list-section" aria-labelledby="authored-heading">
                        <div class="section-heading"><div><p class="eyebrow eyebrow-dark">YOUR WORK</p><h2 id="authored-heading">Authored memos</h2></div><a class="template-link" href="{{ route('memos.create') }}">Create memo</a></div>
                        @forelse ($authoredMemos as $memo)
                            <a class="memo-list-item" href="{{ route('memos.show', $memo) }}">
                                <div class="memo-list-copy">
                                    <div class="memo-list-meta"><span>{{ $memo->created_at->format('d M Y') }}</span><span class="memo-status">{{ str($memo->status)->upper() }}</span></div>
                                    <h3>{{ $memo->title }}</h3>
                                    <p>{{ str($memo->audience_type)->title() }} audience</p>
                                </div>
                                <span class="memo-read-status">{{ $memo->recipients_count ?? $memo->recipients()->count() }} RECIPIENTS</span>
                            </a>
                        @empty
                            <p class="profile-empty">You have not created any memos.</p>
                        @endforelse
                    </section>
                @endif
            </main>
        </div>
    </div>
@endsection