@extends('layouts.app')

@section('title', 'Account operations | NfLV Staff Portal')

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
            <aside class="dashboard-rail" aria-label="ICT navigation">
                <p class="rail-label">ICT OPERATIONS</p>
                <a class="rail-link" href="{{ route('dashboard') }}">Overview</a>
                <a class="rail-link rail-link-active" href="{{ route('ict.accounts.index') }}"><span class="rail-dot"></span> Account operations</a>
                <a class="rail-link" href="{{ route('ict.service-requests.index') }}">ICT helpdesk</a>
                <a class="rail-link" href="{{ route('audit.index') }}">Security events</a>
                <div class="rail-divider"></div>
                <div class="rail-bottom"><span class="status-dot"></span> ICT ACCOUNT CONSOLE</div>
            </aside>

            <main class="dashboard-main">
                <div class="dashboard-kicker"><span>ACCOUNT OPERATIONS</span><span>{{ $users->total() }} accounts</span></div>
                <section class="dashboard-welcome">
                    <p class="eyebrow eyebrow-dark">IDENTITY &amp; ACCESS</p>
                    <h1>Account operations</h1>
                    <p>Activate, suspend or deactivate accounts and manage role assignments. Every change is recorded in the audit trail. Private documents are never visible here.</p>
                </section>

                @if (session('status'))
                    <p class="profile-notice" role="status">{{ session('status') }}</p>
                @endif
                @if (session('error'))
                    <p class="profile-notice" role="alert" style="color: #b91c1c; border-color: #fecaca; background: #fef2f2;">{{ session('error') }}</p>
                @endif
                @if (session('temporaryPassword'))
                    <div class="profile-notice" role="status" style="border-color: #fde68a; background: #fffbeb;">
                        <strong>Temporary password (shown once):</strong>
                        <code style="font-size: 14px;">{{ session('temporaryPassword') }}</code>
                        <span style="display: block; font-size: 11px; margin-top: 4px; color: #92400e;">Share this out-of-band and ask the staff member to change it after signing in.</span>
                    </div>
                @endif

                <form method="GET" action="{{ route('ict.accounts.index') }}" style="display: flex; gap: 8px; flex-wrap: wrap; align-items: flex-end; margin: 16px 0;">
                    <div class="field-group" style="margin: 0;">
                        <label for="search">Search</label>
                        <input id="search" name="search" type="text" value="{{ $search }}" placeholder="Name, staff number or email">
                    </div>
                    <div class="field-group" style="margin: 0;">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="">All</option>
                            @foreach (['active', 'suspended', 'deactivated'] as $status)
                                <option value="{{ $status }}" @selected($statusFilter === $status)>{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field-group" style="margin: 0;">
                        <label for="role">Role</label>
                        <select id="role" name="role">
                            <option value="">All</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->value }}" @selected($roleFilter === $role->value)>{{ $role->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button class="button button-primary" type="submit">Filter</button>
                </form>

                <div class="directory-table-wrap">
                    <table class="directory-table">
                        <thead>
                            <tr>
                                <th>Staff member</th>
                                <th>Department</th>
                                <th>Status</th>
                                <th>Role assignment</th>
                                <th>Duties / assignments</th>
                                <th style="text-align: right;">Credentials</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($users as $account)
                                <tr>
                                    <td>
                                        <strong>{{ $account->name }}</strong>
                                        <span style="display: block; font-size: 11px; color: #6b7280;">{{ $account->staff_number }} · {{ $account->email ?? 'no email' }}</span>
                                    </td>
                                    <td style="font-size: 12px; color: #4b5563;">{{ $account->staffProfile?->department?->name ?? 'Unassigned' }}</td>
                                    <td>
                                        @if ($account->status === 'active')
                                            <span style="font-size: 10px; font-weight: 700; color: #047857; background: #d1fae5; padding: 2px 8px; border-radius: 9999px;">ACTIVE</span>
                                        @elseif ($account->status === 'suspended')
                                            <span style="font-size: 10px; font-weight: 700; color: #b45309; background: #fef3c7; padding: 2px 8px; border-radius: 9999px;">SUSPENDED</span>
                                        @else
                                            <span style="font-size: 10px; font-weight: 700; color: #4b5563; background: #e5e7eb; padding: 2px 8px; border-radius: 9999px;">DEACTIVATED</span>
                                        @endif
                                        <form method="POST" action="{{ route('ict.accounts.status', $account) }}" style="margin-top: 6px; display: flex; gap: 4px;">
                                            @csrf
                                            <select name="status" aria-label="Set status for {{ $account->name }}" style="font-size: 11px; padding: 4px;">
                                                @foreach (['active', 'suspended', 'deactivated'] as $status)
                                                    <option value="{{ $status }}" @selected($account->status === $status)>{{ ucfirst($status) }}</option>
                                                @endforeach
                                            </select>
                                            <button class="button" type="submit" style="cursor: pointer; min-height: 28px; padding: 0 8px; font-size: 10px;">Apply</button>
                                        </form>
                                    </td>
                                    <td>
                                        <span style="font-size: 12px; font-weight: 600;">{{ $account->role->label() }}</span>
                                        <form method="POST" action="{{ route('ict.accounts.role', $account) }}" style="margin-top: 6px; display: flex; gap: 4px;">
                                            @csrf
                                            <select name="role" aria-label="Set role for {{ $account->name }}" style="font-size: 11px; padding: 4px; max-width: 190px;">
                                                @foreach ($roles as $role)
                                                    <option value="{{ $role->value }}" @selected($account->role === $role)>{{ $role->label() }}</option>
                                                @endforeach
                                            </select>
                                            <button class="button" type="submit" style="cursor: pointer; min-height: 28px; padding: 0 8px; font-size: 10px;">Apply</button>
                                        </form>
                                    </td>
                                    <td style="min-width: 240px;">
                                        @php($heldDuties = $account->dutyCodes())
                                        @if (empty($heldDuties))
                                            <span style="font-size: 11px; color: #6b7280;">No duties assigned</span>
                                        @else
                                            <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                                                @foreach ($heldDuties as $code)
                                                    <span style="display: inline-flex; align-items: center; gap: 4px; font-size: 10px; font-weight: 600; color: #173d35; background: #ecfdf5; border: 1px solid #a7f3d0; padding: 2px 6px; border-radius: 9999px;">
                                                        {{ $code }}
                                                        <form method="POST" action="{{ route('ict.accounts.duties.revoke', $account) }}" style="display: inline;">
                                                            @csrf
                                                            <input type="hidden" name="duty" value="{{ $code }}">
                                                            <button type="submit" title="Withdraw {{ $code }}" aria-label="Withdraw {{ $code }}" style="border: 0; background: transparent; color: #b91c1c; cursor: pointer; font-size: 12px; line-height: 1; padding: 0;">&times;</button>
                                                        </form>
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endif
                                        <a href="{{ route('ict.accounts.duties.index', $account) }}" style="display: inline-block; margin-top: 6px; font-size: 11px; color: #173d35; text-decoration: underline;">Manage duties</a>
                                    </td>
                                    <td style="text-align: right;">
                                        <form method="POST" action="{{ route('ict.accounts.password', $account) }}">
                                            @csrf
                                            <button class="button" type="submit" style="cursor: pointer; min-height: 32px; padding: 0 12px; font-size: 11px; background: #f3f4f6; color: #173d35; border: 1px solid #d1d5db;">
                                                Issue temporary password
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="directory-empty">No accounts match the current filters.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($users->hasPages())
                    <div style="margin-top: 16px;">
                        {{ $users->links() }}
                    </div>
                @endif
            </main>
        </div>
    </div>
@endsection
