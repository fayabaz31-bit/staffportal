@extends('layouts.app')

@section('title', 'Service Requests | NfLV Staff Portal')

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
                <p class="rail-label">SELF-SERVICE</p>
                <a class="rail-link rail-link-active" href="{{ route('service-requests.index') }}"><span class="rail-dot"></span> Service Requests</a>
                <a class="rail-link" href="{{ route('leave.index') }}">Leave Applications</a>
                <a class="rail-link" href="{{ route('payslips.index') }}">My Payslips</a>
                <a class="rail-link" href="{{ route('memos.index') }}">Correspondence</a>
                <a class="rail-link" href="{{ route('profile.show') }}">My Profile</a>
                <div class="rail-divider"></div>
                <div class="rail-bottom"><span class="status-dot"></span> SERVICE DESK ACTIVE</div>
            </aside>

            <main class="dashboard-main">
                <div class="dashboard-kicker"><span>SERVICE DESK</span><span>Registry, Bursary & ICT Helpdesk</span></div>
                <section class="dashboard-welcome" style="display: flex; justify-content: space-between; align-items: flex-end;">
                    <div>
                        <p class="eyebrow eyebrow-dark">STAFF SUPPORT</p>
                        <h1>My Service Requests</h1>
                        <p>Track your submitted enquiries, certificate requests, salary clarifications, and support tickets.</p>
                    </div>
                    <div>
                        <a href="{{ route('service-requests.create') }}" class="button button-primary" style="text-decoration: none; display: inline-flex; align-items: center; gap: 6px; padding: 10px 20px; font-size: 12px;">
                            <span>+</span> Submit New Request
                        </a>
                    </div>
                </section>

                @if (session('status'))
                    <p class="profile-notice" role="status">{{ session('status') }}</p>
                @endif

                <!-- Filter Tabs -->
                <div style="display: flex; gap: 8px; margin: 20px 0 16px 0; border-bottom: 1px solid #e5e7eb; padding-bottom: 10px; font-size: 12px;">
                    <a href="{{ route('service-requests.index') }}" style="text-decoration: none; padding: 6px 12px; border-radius: 4px; font-weight: 600; {{ !$currentFilter ? 'background: #173d35; color: #ffffff;' : 'color: #4b5563; background: #f3f4f6;' }}">
                        All Requests
                    </a>
                    <a href="{{ route('service-requests.index', ['status' => 'open']) }}" style="text-decoration: none; padding: 6px 12px; border-radius: 4px; font-weight: 600; {{ $currentFilter === 'open' ? 'background: #173d35; color: #ffffff;' : 'color: #4b5563; background: #f3f4f6;' }}">
                        Open
                    </a>
                    <a href="{{ route('service-requests.index', ['status' => 'in_progress']) }}" style="text-decoration: none; padding: 6px 12px; border-radius: 4px; font-weight: 600; {{ $currentFilter === 'in_progress' ? 'background: #173d35; color: #ffffff;' : 'color: #4b5563; background: #f3f4f6;' }}">
                        In Progress
                    </a>
                    <a href="{{ route('service-requests.index', ['status' => 'resolved']) }}" style="text-decoration: none; padding: 6px 12px; border-radius: 4px; font-weight: 600; {{ $currentFilter === 'resolved' ? 'background: #173d35; color: #ffffff;' : 'color: #4b5563; background: #f3f4f6;' }}">
                        Resolved
                    </a>
                    <a href="{{ route('service-requests.index', ['status' => 'closed']) }}" style="text-decoration: none; padding: 6px 12px; border-radius: 4px; font-weight: 600; {{ $currentFilter === 'closed' ? 'background: #173d35; color: #ffffff;' : 'color: #4b5563; background: #f3f4f6;' }}">
                        Closed
                    </a>
                </div>

                <div class="directory-table-wrap">
                    <table class="directory-table">
                        <thead>
                            <tr>
                                <th>Tracking ID</th>
                                <th>Unit / Category</th>
                                <th>Subject</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Last Updated</th>
                                <th style="text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($requests as $srv)
                                <tr>
                                    <td>
                                        <strong style="color: #173d35; font-size: 13px;">{{ $srv->tracking_reference }}</strong>
                                        @if ($srv->is_confidential)
                                            <span style="display: block; font-size: 9px; color: #dc2626; font-weight: 700; text-transform: uppercase;">Confidential</span>
                                        @endif
                                    </td>
                                    <td>
                                        <strong style="text-transform: uppercase; font-size: 11px;">{{ $srv->unit }}</strong>
                                        <span style="display: block; font-size: 11px; color: #6b7280;">{{ str_replace('_', ' ', $srv->category) }}</span>
                                    </td>
                                    <td style="max-width: 250px;">
                                        <strong>{{ $srv->subject }}</strong>
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
                                    <td style="font-size: 11px; color: #6b7280;">
                                        {{ $srv->updated_at->format('d M Y, H:i') }}
                                    </td>
                                    <td style="text-align: right;">
                                        <a href="{{ route('service-requests.show', $srv) }}" class="button" style="display: inline-flex; min-height: 32px; padding: 0 12px; font-size: 11px; text-decoration: none; background: #f3f4f6; color: #173d35; border: 1px solid #d1d5db;">
                                            View Thread &rarr;
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="directory-empty">
                                        No service requests found. Click "+ Submit New Request" to create one.
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
