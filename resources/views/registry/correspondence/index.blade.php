@extends('layouts.app')

@section('title', 'Registry correspondence | NfLV Staff Portal')

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
                @can('manage-organisation')
                    <a class="rail-link" href="{{ route('registry.organisation.index') }}">Organisation</a>
                @endcan
                <div class="rail-bottom"><span class="status-dot"></span> APPROVAL CONTROLLED</div>
            </aside>

            <main class="dashboard-main">
                <div class="dashboard-kicker">
                    <span>OFFICIAL CORRESPONDENCE</span>
                    <span>{{ $awaitingMe }} awaiting approval</span>
                </div>
                <section class="dashboard-welcome">
                    <p class="eyebrow eyebrow-dark">DOCUMENT CONTROL</p>
                    <h1>Registry correspondence</h1>
                    <p>Approval, issuance and delivery are separate states. An issued document can no longer be edited.</p>
                </section>

                @if (session('status'))
                    <p class="profile-notice" role="status">{{ session('status') }}</p>
                @endif

                <div class="directory-toolbar">
                    <form class="directory-search" method="GET" action="{{ route('registry.correspondence.index') }}">
                        <label class="sr-only" for="correspondence-search">Search correspondence</label>
                        <input id="correspondence-search" name="search" type="search" value="{{ $search }}" placeholder="Search by subject or reference">
                        <select name="status" aria-label="Filter by status">
                            <option value="">All statuses</option>
                            @foreach ($statusLabels as $value => $label)
                                <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <button class="button directory-search-button" type="submit">Filter</button>
                    </form>
                    @if ($canCreate)
                        <a class="button button-primary" href="{{ route('registry.correspondence.create') }}">Prepare correspondence</a>
                    @endif
                </div>

                <div class="directory-table-wrap">
                    <table class="directory-table">
                        <thead>
                            <tr>
                                <th scope="col">Document</th>
                                <th scope="col">Category</th>
                                <th scope="col">Recipient</th>
                                <th scope="col">Approval stage</th>
                                <th scope="col">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($documents as $document)
                                <tr>
                                    <td>
                                        <a class="table-link" href="{{ route('registry.correspondence.show', $document) }}"><strong>{{ $document->subject }}</strong></a>
                                        <span>{{ $document->reference_number }} · v{{ $document->current_version }}</span>
                                    </td>
                                    <td>{{ $document->category?->name ?? 'Uncategorised' }}</td>
                                    <td>{{ $document->recipient?->name ?? $document->recipient_name ?? '—' }}</td>
                                    <td>{{ $document->workflowInstance?->currentStep?->name ?? '—' }}</td>
                                    <td><span class="request-status status-{{ $document->status }}">{{ $document->statusLabel() }}</span></td>
                                </tr>
                            @empty
                                <tr><td class="directory-empty" colspan="5">No correspondence records are visible to you.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{ $documents->links() }}
            </main>
        </div>
    </div>
@endsection
