@extends('layouts.app')

@section('title', 'Leave Approvals Queue | NfLV Staff Portal')

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
            <aside class="dashboard-rail" aria-label="Approvals navigation">
                <p class="rail-label">{{ $role->label() }}</p>
                <a class="rail-link rail-link-active" href="{{ route('approvals.leave.index') }}"><span class="rail-dot"></span> Leave Queue</a>
                <a class="rail-link" href="{{ route('dashboard') }}">Dashboard Overview</a>
                <a class="rail-link" href="{{ route('memos.index') }}">Correspondence</a>
                <a class="rail-link" href="{{ route('profile.show') }}">My Profile</a>
                <div class="rail-divider"></div>
                <div class="rail-bottom"><span class="status-dot"></span> APPROVAL ENGINE</div>
            </aside>

            <main class="dashboard-main">
                <div class="dashboard-kicker"><span>WORKFLOW WORKSPACE</span><span>Stage: {{ strtoupper($role->value) }}</span></div>
                <section class="dashboard-welcome">
                    <p class="eyebrow eyebrow-dark">{{ $role->label() }}</p>
                    <h1>Leave Verification & Approvals</h1>
                    <p>Review and act upon pending staff leave applications assigned to your operational authority.</p>
                </section>

                @if (session('status'))
                    <p class="profile-notice" role="status">{{ session('status') }}</p>
                @endif
                @if (session('error'))
                    <div style="background-color: #fef2f2; border: 1px solid #fecaca; border-left: 4px solid #ef4444; padding: 12px 16px; margin: 14px 0; border-radius: 4px;">
                        <strong style="color: #991b1b; font-size: 13px;">Action Blocked:</strong>
                        <p style="margin: 4px 0 0 0; font-size: 12px; color: #b91c1c;">{{ session('error') }}</p>
                    </div>
                @endif
                @if ($errors->any())
                    <div style="background-color: #fef2f2; border: 1px solid #fecaca; border-left: 4px solid #ef4444; padding: 12px 16px; margin: 14px 0; border-radius: 4px;">
                        <strong style="color: #991b1b; font-size: 12px;">Validation errors:</strong>
                        <ul style="margin: 6px 0 0 16px; font-size: 12px; color: #b91c1c;">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Pending Queue -->
                <section style="margin-top: 24px;" aria-labelledby="queue-heading">
                    <div class="section-heading">
                        <div>
                            <p class="eyebrow eyebrow-dark">ACTION REQUIRED</p>
                            <h2 id="queue-heading">Pending Applications ({{ $pendingApplications->total() }})</h2>
                        </div>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 20px; margin-top: 18px;">
                        @forelse ($pendingApplications as $app)
                            <article style="background: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 1px solid #f3f4f6; padding-bottom: 12px; margin-bottom: 14px;">
                                    <div>
                                        <h3 style="margin: 0; font-size: 15px; color: #111827;">
                                            {{ $app->user->name }}
                                            <span style="font-size: 12px; font-weight: normal; color: #6b7280;">({{ $app->user->staff_number }})</span>
                                        </h3>
                                        <p style="margin: 2px 0 0 0; font-size: 12px; color: #4b5563;">
                                            Department: <strong>{{ $app->user->profile?->department?->name ?? 'N/A' }}</strong> &bull; Submitted: {{ $app->created_at->format('d M Y, H:i') }}
                                        </p>
                                    </div>
                                    <div style="text-align: right;">
                                        <span style="display: inline-block; padding: 3px 10px; border-radius: 9999px; font-size: 11px; font-weight: 700; background: #eff6ff; color: #1e40af;">
                                            STAGE: {{ strtoupper($app->current_stage) }}
                                        </span>
                                    </div>
                                </div>

                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; font-size: 12px; margin-bottom: 14px;">
                                    <div>
                                        <span style="color: #6b7280; display: block;">Leave Category:</span>
                                        <strong style="color: #173d35; font-size: 13px;">{{ $app->leaveType->name }}</strong>
                                    </div>
                                    <div>
                                        <span style="color: #6b7280; display: block;">Dates & Duration:</span>
                                        <strong>{{ $app->start_date->format('d M Y') }} &rarr; {{ $app->end_date->format('d M Y') }}</strong>
                                        <span style="color: #4b5563;">({{ $app->days_requested }} working days)</span>
                                    </div>
                                    <div>
                                        <span style="color: #6b7280; display: block;">Supporting Evidence:</span>
                                        @if ($app->attachment_path)
                                            <span style="color: #059669; font-weight: 600;">Document Attached</span>
                                        @else
                                            <span style="color: #9ca3af;">None Provided</span>
                                        @endif
                                    </div>
                                </div>

                                <div style="background: #f9fafb; border-radius: 6px; padding: 12px; margin-bottom: 16px; font-size: 12px; color: #374151;">
                                    <strong style="display: block; font-size: 11px; color: #6b7280; text-transform: uppercase; margin-bottom: 4px;">Reason / Handover Note:</strong>
                                    {{ $app->reason }}
                                </div>

                                <!-- Action Form -->
                                <form method="POST" action="{{ route('approvals.leave.action', $app) }}" style="border-top: 1px solid #f3f4f6; padding-top: 14px;">
                                    @csrf
                                    <input type="hidden" name="concurrency_version" value="{{ $app->concurrency_version }}">

                                    <div style="margin-bottom: 12px;">
                                        <label for="comments_{{ $app->id }}" style="display: block; font-size: 11px; font-weight: 600; color: #4b5563; margin-bottom: 4px;">
                                            Decision Notes / Justification (Mandatory for Reject & Return):
                                        </label>
                                        <input type="text" id="comments_{{ $app->id }}" name="comments" placeholder="Enter comments, handover confirmations, or return reason..." style="width: 100%; border: 1px solid #d1d5db; border-radius: 6px; padding: 7px 12px; font-size: 12px;">
                                    </div>

                                    <div style="display: flex; gap: 8px; justify-content: flex-end; align-items: center;">
                                        @if ($role === \App\UserRole::HeadOfDepartment || $role === \App\UserRole::InstitutionalSuperadmin)
                                            @if ($app->current_stage === 'supervisor')
                                                <button type="submit" name="action" value="recommend" class="button button-primary" style="padding: 6px 16px; font-size: 11px;">
                                                    Recommend Application &rarr;
                                                </button>
                                            @endif
                                        @endif

                                        @if ($role === \App\UserRole::HrRegistryAdmin || $role === \App\UserRole::InstitutionalSuperadmin)
                                            @if ($app->current_stage === 'hr')
                                                <button type="submit" name="action" value="verify" class="button button-primary" style="padding: 6px 16px; font-size: 11px;">
                                                    Verify & Advance to Director &rarr;
                                                </button>
                                            @endif
                                        @endif

                                        @if ($role === \App\UserRole::Director || $role === \App\UserRole::InstitutionalSuperadmin)
                                            @if ($app->current_stage === 'director')
                                                <button type="submit" name="action" value="approve" class="button button-primary" style="background-color: #059669; border-color: #059669; padding: 6px 18px; font-size: 11px;">
                                                    Approve Leave & Issue Letter
                                                </button>
                                            @endif
                                        @endif

                                        <button type="submit" name="action" value="return" class="button" style="background-color: #fffbeb; color: #92400e; border: 1px solid #fde68a; padding: 6px 14px; font-size: 11px;">
                                            Return for Correction
                                        </button>

                                        <button type="submit" name="action" value="reject" class="button" style="background-color: #fef2f2; color: #991b1b; border: 1px solid #fecaca; padding: 6px 14px; font-size: 11px;">
                                            Reject Request
                                        </button>
                                    </div>
                                </form>
                            </article>
                        @empty
                            <div style="padding: 32px; background: #ffffff; border: 1px dashed #d1d5db; border-radius: 8px; text-align: center; color: #6b7280; font-size: 13px;">
                                No pending leave applications require your review at this time.
                            </div>
                        @endforelse
                    </div>

                    @if ($pendingApplications->hasPages())
                        <div style="margin-top: 16px;">
                            {{ $pendingApplications->links() }}
                        </div>
                    @endif
                </section>

                <!-- Recent Decisions Audit -->
                @if ($recentActions->isNotEmpty())
                    <section style="margin-top: 36px;">
                        <div class="section-heading">
                            <div>
                                <p class="eyebrow eyebrow-dark">ACTION HISTORY</p>
                                <h2>Your Recent Decisions</h2>
                            </div>
                        </div>

                        <div class="directory-table-wrap" style="margin-top: 14px;">
                            <table class="directory-table">
                                <thead>
                                    <tr>
                                        <th>Staff / App</th>
                                        <th>Action</th>
                                        <th>Stage</th>
                                        <th>Notes</th>
                                        <th>Timestamp</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($recentActions as $act)
                                        <tr>
                                            <td>
                                                <strong>{{ $act->application->user->name }}</strong>
                                                <span style="font-size: 11px; color: #6b7280;">App #{{ $act->application_id }} &bull; {{ $act->application->leaveType->name }}</span>
                                            </td>
                                            <td>
                                                <strong style="text-transform: uppercase; font-size: 11px; color: #173d35;">{{ $act->action }}</strong>
                                            </td>
                                            <td style="font-size: 11px; text-transform: uppercase;">
                                                {{ $act->stage }}
                                            </td>
                                            <td style="font-size: 11px; color: #4b5563;">
                                                {{ $act->comments ?? '—' }}
                                            </td>
                                            <td style="font-size: 11px; color: #6b7280;">
                                                {{ $act->created_at->format('d M Y, H:i') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </section>
                @endif
            </main>
        </div>
    </div>
@endsection
