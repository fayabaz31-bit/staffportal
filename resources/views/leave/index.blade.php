@extends('layouts.app')

@section('title', 'Leave Applications | NfLV Staff Portal')

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
                <a class="rail-link rail-link-active" href="{{ route('leave.index') }}"><span class="rail-dot"></span> Leave Portal</a>
                <a class="rail-link" href="{{ route('payslips.index') }}">My Payslips</a>
                <a class="rail-link" href="{{ route('memos.index') }}">Correspondence</a>
                <a class="rail-link" href="{{ route('profile.show') }}">My Profile</a>
                <div class="rail-divider"></div>
                <div class="rail-bottom"><span class="status-dot"></span> LEAVE ENGINE ACTIVE</div>
            </aside>

            <main class="dashboard-main">
                <div class="dashboard-kicker"><span>LEAVE & ENTITLEMENTS</span><span>Calendar Year {{ $currentYear }}</span></div>
                <section class="dashboard-welcome">
                    <p class="eyebrow eyebrow-dark">STAFF SELF-SERVICE</p>
                    <h1>Leave Management</h1>
                    <p>Track your verified annual leave balances, submit vacation or sick leave requests, and monitor supervisor, HR, and Director approval stages.</p>
                </section>

                @if ($hasUnconfiguredPolicies)
                    <div style="background-color: #fffbeb; border: 1px solid #fef3c7; border-left: 4px solid #f59e0b; padding: 14px 18px; margin: 18px 0; border-radius: 4px;">
                        <strong style="color: #92400e; font-size: 13px; display: block; margin-bottom: 4px;">Institutional Policy Notice:</strong>
                        <p style="margin: 0; font-size: 12px; color: #78350f; line-height: 1.5;">
                            One or more leave categories for your profile are currently marked <strong>UNCONFIGURED / DRAFT</strong> pending ratification of institutional leave schedules by Management. You may submit applications for preliminary review, but final approval and official letter issuance remain blocked until official policy parameters are ratified.
                        </p>
                    </div>
                @endif

                @if (session('status'))
                    <p class="profile-notice" role="status">{{ session('status') }}</p>
                @endif
                @if (session('error'))
                    <p class="field-error" style="margin: 12px 0; font-size: 13px;" role="alert">{{ session('error') }}</p>
                @endif
                @if ($errors->any())
                    <div style="background-color: #fef2f2; border: 1px solid #fecaca; border-left: 4px solid #ef4444; padding: 12px 16px; margin: 14px 0; border-radius: 4px;">
                        <strong style="color: #991b1b; font-size: 12px;">Please correct the following errors:</strong>
                        <ul style="margin: 6px 0 0 16px; font-size: 12px; color: #b91c1c;">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Entitlements Summary Cards -->
                <section style="margin-top: 24px;">
                    <div class="section-heading">
                        <div>
                            <p class="eyebrow eyebrow-dark">ENTITLEMENT BALANCES</p>
                            <h2>{{ $currentYear }} Annual Allocations</h2>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-top: 14px;">
                        @forelse ($entitlements as $entitlement)
                            <div style="background: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; box-shadow: 0 1px 2px rgba(0,0,0,0.03);">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                                    <strong style="font-size: 14px; color: #173d35;">{{ $entitlement->leaveType->name }}</strong>
                                    @if ($entitlement->is_policy_configured)
                                        <span style="font-size: 9px; font-weight: 600; padding: 2px 6px; background-color: #ecfdf5; color: #065f46; border-radius: 9999px;">CONFIGURED</span>
                                    @else
                                        <span style="font-size: 9px; font-weight: 600; padding: 2px 6px; background-color: #fef3c7; color: #92400e; border-radius: 9999px;">UNCONFIGURED</span>
                                    @endif
                                </div>
                                <div style="font-size: 26px; font-weight: 700; color: #111827; margin: 4px 0;">
                                    {{ $entitlement->remainingDays() }} <span style="font-size: 13px; font-weight: normal; color: #6b7280;">days left</span>
                                </div>
                                <div style="font-size: 11px; color: #6b7280; border-top: 1px solid #f3f4f6; padding-top: 8px; margin-top: 8px; display: flex; justify-content: space-between;">
                                    <span>Allocated: <strong>{{ $entitlement->allocated_days }}</strong></span>
                                    <span>Used: <strong>{{ $entitlement->used_days }}</strong></span>
                                    <span>Pending: <strong>{{ $entitlement->pending_days }}</strong></span>
                                </div>
                            </div>
                        @empty
                            <div style="grid-column: 1 / -1; padding: 18px; background: #f9fafb; border: 1px dashed #d1d5db; border-radius: 6px; text-align: center; font-size: 13px; color: #6b7280;">
                                No leave entitlements configured for {{ $currentYear }}. Please contact HR Registry.
                            </div>
                        @endforelse
                    </div>
                </section>

                <!-- Apply for Leave Section -->
                <section style="margin-top: 36px; background: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 24px;">
                    <div class="section-heading" style="margin-bottom: 16px;">
                        <div>
                            <p class="eyebrow eyebrow-dark">NEW REQUEST</p>
                            <h2>Submit Leave Application</h2>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('leave.store') }}" enctype="multipart/form-data">
                        @csrf
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
                            <div>
                                <label for="leave_type_id" style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 6px;">
                                    Leave Category <span style="color: #ef4444;">*</span>
                                </label>
                                <select id="leave_type_id" name="leave_type_id" required style="width: 100%; border: 1px solid #d1d5db; border-radius: 6px; padding: 8px 12px; font-size: 13px;">
                                    <option value="">Select category...</option>
                                    @foreach ($availableLeaveTypes as $type)
                                        <option value="{{ $type->id }}" {{ old('leave_type_id') == $type->id ? 'selected' : '' }}>
                                            {{ $type->name }} ({{ $type->requires_attachment ? 'Evidence Required' : 'Standard' }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="start_date" style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 6px;">
                                    Start Date <span style="color: #ef4444;">*</span>
                                </label>
                                <input type="date" id="start_date" name="start_date" value="{{ old('start_date') }}" required min="{{ now()->toDateString() }}" style="width: 100%; border: 1px solid #d1d5db; border-radius: 6px; padding: 8px 12px; font-size: 13px;">
                            </div>

                            <div>
                                <label for="end_date" style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 6px;">
                                    End Date <span style="color: #ef4444;">*</span>
                                </label>
                                <input type="date" id="end_date" name="end_date" value="{{ old('end_date') }}" required min="{{ now()->toDateString() }}" style="width: 100%; border: 1px solid #d1d5db; border-radius: 6px; padding: 8px 12px; font-size: 13px;">
                            </div>
                        </div>

                        <div style="margin-top: 16px;">
                            <label for="reason" style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 6px;">
                                Reason / Handover Plan <span style="color: #ef4444;">*</span>
                            </label>
                            <textarea id="reason" name="reason" rows="3" required placeholder="Specify purpose of leave, emergency contacts, or work handover arrangements..." style="width: 100%; border: 1px solid #d1d5db; border-radius: 6px; padding: 8px 12px; font-size: 13px;">{{ old('reason') }}</textarea>
                        </div>

                        <div style="margin-top: 16px;">
                            <label for="attachment" style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 6px;">
                                Supporting Evidence / Medical Certificate (PDF, PNG, JPG up to 5MB)
                            </label>
                            <input type="file" id="attachment" name="attachment" accept=".pdf,.jpg,.jpeg,.png" style="font-size: 12px; color: #4b5563;">
                            <p style="font-size: 11px; color: #6b7280; margin: 4px 0 0 0;">
                                Note: Duration calculation automatically counts business days only (Monday through Friday), excluding weekends.
                            </p>
                        </div>

                        <div style="margin-top: 20px; display: flex; justify-content: flex-end;">
                            <button type="submit" class="button button-primary" style="padding: 10px 24px; font-size: 12px;">
                                Submit Leave Application
                            </button>
                        </div>
                    </form>
                </section>

                <!-- My Applications History -->
                <section style="margin-top: 36px;" aria-labelledby="applications-heading">
                    <div class="section-heading">
                        <div>
                            <p class="eyebrow eyebrow-dark">HISTORY & AUDIT</p>
                            <h2 id="applications-heading">My Applications</h2>
                        </div>
                    </div>

                    <div class="directory-table-wrap" style="margin-top: 16px;">
                        <table class="directory-table">
                            <thead>
                                <tr>
                                    <th>Ref / Category</th>
                                    <th>Duration</th>
                                    <th>Dates</th>
                                    <th>Stage & Status</th>
                                    <th>Decision Info</th>
                                    <th style="text-align: right;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($applications as $app)
                                    <tr>
                                        <td>
                                            <strong>{{ $app->leaveType->name }}</strong>
                                            <span style="font-size: 11px; color: #6b7280;">App #{{ $app->id }}</span>
                                        </td>
                                        <td>
                                            <strong>{{ $app->days_requested }}</strong> working day(s)
                                        </td>
                                        <td>
                                            {{ $app->start_date->format('d M Y') }} &rarr; {{ $app->end_date->format('d M Y') }}
                                        </td>
                                        <td>
                                            @if ($app->status === 'approved')
                                                <span style="display: inline-block; padding: 2px 8px; border-radius: 9999px; font-size: 10px; font-weight: 700; background: #ecfdf5; color: #065f46;">APPROVED</span>
                                            @elseif ($app->status === 'rejected')
                                                <span style="display: inline-block; padding: 2px 8px; border-radius: 9999px; font-size: 10px; font-weight: 700; background: #fef2f2; color: #991b1b;">REJECTED</span>
                                            @elseif ($app->status === 'returned')
                                                <span style="display: inline-block; padding: 2px 8px; border-radius: 9999px; font-size: 10px; font-weight: 700; background: #fef3c7; color: #92400e;">RETURNED FOR CORRECTION</span>
                                            @elseif ($app->status === 'cancelled')
                                                <span style="display: inline-block; padding: 2px 8px; border-radius: 9999px; font-size: 10px; font-weight: 700; background: #f3f4f6; color: #4b5563;">CANCELLED</span>
                                            @else
                                                <span style="display: inline-block; padding: 2px 8px; border-radius: 9999px; font-size: 10px; font-weight: 700; background: #eff6ff; color: #1e40af;">
                                                    PENDING ({{ strtoupper($app->current_stage) }})
                                                </span>
                                            @endif
                                        </td>
                                        <td style="font-size: 11px; color: #4b5563;">
                                            @if ($app->isApproved() && $app->letter)
                                                <span>Ref: <strong>{{ $app->letter->reference_number }}</strong></span><br>
                                                <small>Approved by {{ $app->approver?->name ?? 'Director' }}</small>
                                            @elseif ($app->isRejected())
                                                <span style="color: #b91c1c;">Reason: {{ $app->rejection_reason }}</span>
                                            @elseif ($app->isReturned())
                                                <span style="color: #92400e;">Remarks: {{ $app->return_reason }}</span>
                                            @else
                                                <span>Awaiting review by {{ ucfirst($app->current_stage) }}</span>
                                            @endif
                                        </td>
                                        <td style="text-align: right;">
                                            @if ($app->isApproved() && $app->letter)
                                                <a class="button button-primary" style="display: inline-flex; min-height: 32px; padding: 0 12px; font-size: 11px; text-decoration: none;" href="{{ route('letters.download', $app->letter) }}">
                                                    Download Letter
                                                </a>
                                            @elseif ($app->isPending())
                                                <form method="POST" action="{{ route('leave.cancel', $app) }}" onsubmit="return confirm('Are you sure you want to cancel this leave application?');" style="display: inline;">
                                                    @csrf
                                                    <button type="submit" class="text-button" style="color: #dc2626; font-size: 11px;">
                                                        Cancel Request
                                                    </button>
                                                </form>
                                            @else
                                                <span style="color: #9ca3af; font-size: 11px;">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="directory-empty">
                                            No leave applications submitted yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($applications->hasPages())
                        <div style="margin-top: 16px;">
                            {{ $applications->links() }}
                        </div>
                    @endif
                </section>
            </main>
        </div>
    </div>
@endsection
