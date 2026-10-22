@extends('layouts.app')

@section('title', 'Notifications | NfLV Staff Portal')

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
                <a class="rail-link" href="{{ route('dashboard') }}">Overview</a>
                <a class="rail-link rail-link-active" href="{{ route('notifications.index') }}"><span class="rail-dot"></span> Notifications @if ($unreadCount > 0)<small>{{ $unreadCount }} NEW</small>@endif</a>
                <a class="rail-link" href="{{ route('profile.show') }}">My Profile</a>
                <a class="rail-link" href="{{ route('leave.index') }}">Leave Applications</a>
                <a class="rail-link" href="{{ route('payslips.index') }}">My Payslips</a>
                <a class="rail-link" href="{{ route('memos.index') }}">Correspondence</a>
                <a class="rail-link" href="{{ route('service-requests.index') }}">Service Requests</a>
                <div class="rail-divider"></div>
                <div class="rail-bottom"><span class="status-dot"></span> INBOX ACTIVE</div>
            </aside>

            <main class="dashboard-main">
                <div class="dashboard-kicker"><span>NOTIFICATIONS</span><span>{{ $unreadCount }} unread</span></div>
                <section class="dashboard-welcome" style="display: flex; justify-content: space-between; align-items: flex-end;">
                    <div>
                        <p class="eyebrow eyebrow-dark">IN-APP INBOX</p>
                        <h1>Notifications</h1>
                        <p>Secure in-app notices for correspondence, payslips, leave, letters and service requests.</p>
                    </div>
                    @if ($unreadCount > 0)
                        <form method="POST" action="{{ route('notifications.read-all') }}">
                            @csrf
                            <button class="button" type="submit" style="cursor: pointer;">Mark all as read</button>
                        </form>
                    @endif
                </section>

                @if (session('status'))
                    <p class="profile-notice" role="status">{{ session('status') }}</p>
                @endif

                <div class="directory-table-wrap">
                    <table class="directory-table">
                        <thead>
                            <tr>
                                <th>Notification</th>
                                <th>Detail</th>
                                <th>Category</th>
                                <th>Received</th>
                                <th style="text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($notifications as $notification)
                                @php($data = $notification->data)
                                <tr style="{{ $notification->read_at ? '' : 'background: #f9fafb;' }}">
                                    <td>
                                        <strong>{{ $data['title'] ?? 'Portal notification' }}</strong>
                                        @if ($notification->read_at === null)
                                            <span style="display: inline-block; font-size: 9px; color: #dc2626; font-weight: 700; text-transform: uppercase; margin-left: 6px;">New</span>
                                        @endif
                                    </td>
                                    <td style="max-width: 320px; color: #4b5563;">{{ $data['message'] ?? '' }}</td>
                                    <td style="text-transform: capitalize; font-size: 11px; color: #6b7280;">{{ str_replace('_', ' ', $data['category'] ?? 'general') }}</td>
                                    <td style="font-size: 11px; color: #6b7280;">{{ $notification->created_at->format('d M Y, H:i') }}</td>
                                    <td style="text-align: right;">
                                        <form method="POST" action="{{ route('notifications.read', $notification) }}">
                                            @csrf
                                            <button class="button" type="submit" style="cursor: pointer; display: inline-flex; min-height: 32px; padding: 0 12px; font-size: 11px; background: #f3f4f6; color: #173d35; border: 1px solid #d1d5db;">
                                                Open &rarr;
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="directory-empty">You have no notifications yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($notifications->hasPages())
                    <div style="margin-top: 16px;">
                        {{ $notifications->links() }}
                    </div>
                @endif
            </main>
        </div>
    </div>
@endsection
