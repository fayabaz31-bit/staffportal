@extends('layouts.app')

@section('title', strtoupper($unit) . ' Service Queue | NfLV Staff Portal')

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
            <aside class="dashboard-rail" aria-label="Staff navigation">
                <p class="rail-label">UNIT DESK</p>
                <a class="rail-link rail-link-active" href="{{ route('unit.service-requests.index', $unit) }}"><span class="rail-dot"></span> {{ strtoupper($unit) }} Queue</a>
                <a class="rail-link" href="{{ route('service-requests.index') }}">My Personal Requests</a>
                <a class="rail-link" href="{{ route('dashboard') }}">Dashboard Overview</a>
                <a class="rail-link" href="{{ route('memos.index') }}">Correspondence</a>
                <div class="rail-divider"></div>
                <div class="rail-bottom"><span class="status-dot"></span> UNIT ACTIVE</div>
            </aside>

            <main class="dashboard-main">
                <div class="dashboard-kicker"><span>OPERATIONAL QUEUE</span><span>{{ strtoupper($unit) }} Administrative Desk</span></div>
                <section class="dashboard-welcome">
                    <p class="eyebrow eyebrow-dark">{{ strtoupper($unit) }} QUEUE</p>
                    <h1>Assigned Service Requests</h1>
                    <p>Manage, assign, investigate, and resolve service requests routed to the {{ strtoupper($unit) }} unit.</p>
                </section>

                @if (session('status'))
                    <p class="profile-notice" role="status">{{ session('status') }}</p>
                @endif

                <!-- Filters -->
                <div style="display: flex; gap: 8px; margin: 20px 0 16px 0; border-bottom: 1px solid #e5e7eb; padding-bottom: 10px; font-size: 12px; flex-wrap: wrap;">
                    <a href="{{ route('unit.service-requests.index', $unit) }}" style="text-decoration: none; padding: 6px 12px; border-radius: 4px; font-weight: 600; {{ !$currentStatus ? 'background: #173d35; color: #ffffff;' : 'color: #4b5563; background: #f3f4f6;' }}">
                        All Tickets ({{ $requests->total() }})
                    </a>
                    <a href="{{ route('unit.service-requests.index', [$unit, 'status' => 'open']) }}" style="text-decoration: none; padding: 6px 12px; border-radius: 4px; font-weight: 600; {{ $currentStatus === 'open' ? 'background: #173d35; color: #ffffff;' : 'color: #4b5563; background: #f3f4f6;' }}">
                        Open
                    </a>
                    <a href="{{ route('unit.service-requests.index', [$unit, 'status' => 'in_progress']) }}" style="text-decoration: none; padding: 6px 12px; border-radius: 4px; font-weight: 600; {{ $currentStatus === 'in_progress' ? 'background: #173d35; color: #ffffff;' : 'color: #4b5563; background: #f3f4f6;' }}">
                        In Progress
                    </a>
                    <a href="{{ route('unit.service-requests.index', [$unit, 'status' => 'escalated']) }}" style="text-decoration: none; padding: 6px 12px; border-radius: 4px; font-weight: 600; {{ $currentStatus === 'escalated' ? 'background: #173d35; color: #ffffff;' : 'color: #4b5563; background: #f3f4f6;' }}">
                        Escalated
                    </a>
                    <a href="{{ route('unit.service-requests.index', [$unit, 'status' => 'resolved']) }}" style="text-decoration: none; padding: 6px 12px; border-radius: 4px; font-weight: 600; {{ $currentStatus === 'resolved' ? 'background: #173d35; color: #ffffff;' : 'color: #4b5563; background: #f3f4f6;' }}">
                        Resolved
                    </a>
                </div>

                <div class="directory-table-wrap">
                    <table class="directory-table">
                        <thead>
                            <tr>
                                <th>Ref & Flags</th>
                                <th>Applicant</th>
                                <th>Category / Subject</th>
                                <th>Priority</th>
                                <th>Assigned To</th>
                                <th>Status</th>
                                <th style="text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($requests as $srv)
                                <tr>
                                    <td>
                                        <strong style="color: #173d35; font-size: 12px;">{{ $srv->tracking_reference }}</strong>
                                        @if ($srv->is_confidential)
                                            <span style="display: block; font-size: 9px; font-weight: 700; color: #dc2626; text-transform: uppercase;">CONFIDENTIAL</span>
                                        @endif
                                    </td>
                                    <td>
                                        <strong>{{ $srv->user->name }}</strong>
                                        <span style="display: block; font-size: 11px; color: #6b7280;">{{ $srv->user->profile?->department?->name ?? 'N/A' }}</span>
                                    </td>
                                    <td>
                                        <span style="font-size: 11px; color: #6b7280; text-transform: uppercase;">{{ str_replace('_', ' ', $srv->category) }}</span>
                                        <strong style="display: block; font-size: 12px;">{{ $srv->subject }}</strong>
                                    </td>
                                    <td>
                                        @if ($srv->priority === 'urgent')
                                            <span style="font-size: 10px; font-weight: 700; color: #dc2626; background: #fee2e2; padding: 2px 6px; border-radius: 4px;">URGENT</span>
                                        @elseif ($srv->priority === 'high')
                                            <span style="font-size: 10px; font-weight: 700; color: #b45309; background: #fef3c7; padding: 2px 6px; border-radius: 4px;">HIGH</span>
                                        @else
                                            <span style="font-size: 10px; font-weight: 600; color: #4b5563; background: #f3f4f6; padding: 2px 6px; border-radius: 4px;">STANDARD</span>
                                        @endif
                                    </td>
                                    <td style="font-size: 11px;">
                                        {{ $srv->assignedTo?->name ?? 'Unassigned' }}
                                    </td>
                                    <td>
                                        @if ($srv->status === 'resolved')
                                            <span style="font-size: 10px; font-weight: 700; color: #047857; background: #d1fae5; padding: 2px 8px; border-radius: 9999px;">RESOLVED</span>
                                        @elseif ($srv->status === 'closed')
                                            <span style="font-size: 10px; font-weight: 700; color: #4b5563; background: #e5e7eb; padding: 2px 8px; border-radius: 9999px;">CLOSED</span>
                                        @elseif ($srv->status === 'in_progress')
                                            <span style="font-size: 10px; font-weight: 700; color: #1d4ed8; background: #dbeafe; padding: 2px 8px; border-radius: 9999px;">IN PROGRESS</span>
                                        @elseif ($srv->status === 'escalated')
                                            <span style="font-size: 10px; font-weight: 700; color: #b91c1c; background: #fee2e2; padding: 2px 8px; border-radius: 9999px;">ESCALATED</span>
                                        @else
                                            <span style="font-size: 10px; font-weight: 700; color: #0369a1; background: #e0f2fe; padding: 2px 8px; border-radius: 9999px;">OPEN</span>
                                        @endif
                                    </td>
                                    <td style="text-align: right;">
                                        <a href="{{ route('service-requests.show', $srv) }}" class="button button-primary" style="display: inline-flex; min-height: 32px; padding: 0 12px; font-size: 11px; text-decoration: none;">
                                            Manage Ticket
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="directory-empty">
                                        No service requests found for this filter.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($requests->hasPages())
                    <div style="margin-top: 16px;">
                        {{ $requests->links() }}
                    </div>
                @endif
            </main>
        </div>
    </div>
@endsection
