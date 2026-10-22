@extends('layouts.app')

@section('title', 'Duty assignments | NfLV Staff Portal')

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
            <aside class="dashboard-rail" aria-label="Duty navigation">
                <p class="rail-label">ASSIGNED WORK</p>
                <a class="rail-link" href="{{ route('dashboard') }}">Overview</a>
                <a class="rail-link rail-link-active" href="{{ route('assignments.index') }}"><span class="rail-dot"></span> My duties</a>
                @can('manage-correspondence')
                    <a class="rail-link" href="{{ route('registry.correspondence.index') }}">Correspondence</a>
                @endcan
                @can('manage-organisation')
                    <a class="rail-link" href="{{ route('registry.organisation.index') }}">Organisation</a>
                @endcan
                <div class="rail-bottom"><span class="status-dot"></span> SCOPED TO YOUR ROLE</div>
            </aside>

            <main class="dashboard-main">
                <div class="dashboard-kicker">
                    <span>DUTY ASSIGNMENTS</span>
                    <span>{{ $counts['overdue'] }} overdue</span>
                </div>
                <section class="dashboard-welcome">
                    <p class="eyebrow eyebrow-dark">REGISTRY WORK QUEUE</p>
                    <h1>Duty assignments</h1>
                    <p>Assigned work, review decisions and correction history. Completing a duty does not by itself approve any related official document.</p>
                </section>

                @if (session('status'))
                    <p class="profile-notice" role="status">{{ session('status') }}</p>
                @endif

                <nav class="scope-tabs" aria-label="Duty scopes">
                    @foreach (['mine' => 'Assigned to me', 'given' => 'Assigned by me', 'team' => 'My team'] as $key => $label)
                        <a @class(['scope-tab', 'scope-tab-active' => $scope === $key])
                           href="{{ route('assignments.index', ['scope' => $key, 'status' => $status, 'search' => $search]) }}">
                            {{ $label }} <span>{{ $counts[$key] }}</span>
                        </a>
                    @endforeach
                </nav>

                <div class="directory-toolbar">
                    <form class="directory-search" method="GET" action="{{ route('assignments.index') }}">
                        <input type="hidden" name="scope" value="{{ $scope }}">
                        <label class="sr-only" for="assignment-search">Search duties</label>
                        <input id="assignment-search" name="search" type="search" value="{{ $search }}" placeholder="Search by title or reference">
                        <select name="status" aria-label="Filter by status">
                            <option value="">All statuses</option>
                            @foreach (\App\Models\WorkAssignment::statusLabels() as $value => $label)
                                <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <button class="button directory-search-button" type="submit">Filter</button>
                    </form>
                    @if ($canAssign)
                        <a class="button button-primary" href="{{ route('assignments.create') }}">Assign a duty</a>
                    @endif
                </div>

                <div class="directory-table-wrap">
                    <table class="directory-table">
                        <thead>
                            <tr>
                                <th scope="col">Duty</th>
                                <th scope="col">Officer</th>
                                <th scope="col">Priority</th>
                                <th scope="col">Deadline</th>
                                <th scope="col">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($assignments as $assignment)
                                <tr @class(['assignment-row-overdue' => $assignment->isOverdue()])>
                                    <td>
                                        <a class="table-link" href="{{ route('assignments.show', $assignment) }}"><strong>{{ $assignment->title }}</strong></a>
                                        <span>{{ $assignment->reference_number }}</span>
                                    </td>
                                    <td>{{ $assignment->assignee?->name ?? 'Unassigned' }}</td>
                                    <td><span class="priority priority-{{ $assignment->priority }}">{{ ucfirst($assignment->priority) }}</span></td>
                                    <td>
                                        @if ($assignment->due_at !== null)
                                            {{ $assignment->due_at->format('d M Y') }}
                                            @if ($assignment->isOverdue())
                                                <span class="overdue-flag">Overdue</span>
                                            @endif
                                        @else
                                            No deadline
                                        @endif
                                    </td>
                                    <td><span class="request-status status-{{ $assignment->status }}">{{ $assignment->statusLabel() }}</span></td>
                                </tr>
                            @empty
                                <tr><td class="directory-empty" colspan="5">No duties in this view.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{ $assignments->links() }}
            </main>
        </div>
    </div>
@endsection
