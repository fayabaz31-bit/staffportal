@extends('layouts.app')

@section('title', 'Audit records | NfLV Staff Portal')

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
            <aside class="dashboard-rail" aria-label="Audit navigation">
                <p class="rail-label">AUDIT &amp; OVERSIGHT</p>
                <a class="rail-link" href="{{ route('dashboard') }}">Overview</a>
                <a class="rail-link rail-link-active" href="{{ route('audit.index') }}"><span class="rail-dot"></span> Audit records</a>
                <a class="rail-link" href="{{ route('reports.index') }}">Read-only reports</a>
                <div class="rail-divider"></div>
                <div class="rail-bottom"><span class="status-dot"></span> READ-ONLY ACCESS</div>
            </aside>

            <main class="dashboard-main">
                <div class="dashboard-kicker"><span>AUDIT TRAIL</span><span>{{ $logs->total() }} entries</span></div>
                <section class="dashboard-welcome">
                    <p class="eyebrow eyebrow-dark">APPEND-ONLY RECORD</p>
                    <h1>Audit records</h1>
                    <p>Material and privileged events: role and account changes, approvals, memo publication, payslip batches, letter issuance and document downloads. Entries are append-only and exclude secrets and salary payloads.</p>
                </section>

                <form method="GET" action="{{ route('audit.index') }}" style="display: flex; gap: 8px; flex-wrap: wrap; align-items: flex-end; margin: 16px 0;">
                    <div class="field-group" style="margin: 0;">
                        <label for="action">Action</label>
                        <input id="action" name="action" type="text" value="{{ $actionFilter }}" placeholder="e.g. account.role_changed">
                    </div>
                    <div class="field-group" style="margin: 0;">
                        <label for="actor">Actor</label>
                        <input id="actor" name="actor" type="text" value="{{ $actorFilter }}" placeholder="Name or staff number">
                    </div>
                    <button class="button button-primary" type="submit">Filter</button>
                </form>

                <div class="directory-table-wrap">
                    <table class="directory-table">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>Actor</th>
                                <th>Action</th>
                                <th>Subject</th>
                                <th>Context</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($logs as $log)
                                <tr>
                                    <td style="font-size: 11px; color: #6b7280; white-space: nowrap;">{{ $log->created_at?->format('d M Y, H:i:s') }}</td>
                                    <td style="font-size: 12px;">
                                        @if ($log->actor)
                                            <strong>{{ $log->actor->name }}</strong>
                                            <span style="display: block; font-size: 10px; color: #6b7280;">{{ $log->actor->staff_number }}</span>
                                        @else
                                            <span style="color: #9ca3af;">System</span>
                                        @endif
                                    </td>
                                    <td>
                                        <code style="font-size: 11px; color: #173d35;">{{ $log->action }}</code>
                                    </td>
                                    <td style="font-size: 11px; color: #4b5563;">
                                        @if ($log->subject_type)
                                            {{ class_basename($log->subject_type) }} #{{ $log->subject_id }}
                                        @else
                                            <span style="color: #9ca3af;">&mdash;</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if (! empty($log->context))
                                            <code style="font-size: 11px; color: #4b5563;">{{ \Illuminate\Support\Str::limit(json_encode($log->context, JSON_UNESCAPED_SLASHES), 160) }}</code>
                                        @else
                                            <span style="color: #9ca3af;">&mdash;</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="directory-empty">No audit entries match the current filters.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($logs->hasPages())
                    <div style="margin-top: 16px;">
                        {{ $logs->links() }}
                    </div>
                @endif
            </main>
        </div>
    </div>
@endsection
