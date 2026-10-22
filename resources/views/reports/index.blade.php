@extends('layouts.app')

@section('title', 'Operational Reports | NfLV Staff Portal')

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
                <p class="rail-label">{{ $role->label() }}</p>
                <a class="rail-link rail-link-active" href="{{ route('reports.index') }}"><span class="rail-dot"></span> Operational Reports</a>
                <a class="rail-link" href="{{ route('dashboard') }}">Dashboard Overview</a>
                <a class="rail-link" href="{{ route('memos.index') }}">Correspondence</a>
                <a class="rail-link" href="{{ route('profile.show') }}">My Profile</a>
                <div class="rail-divider"></div>
                <div class="rail-bottom"><span class="status-dot"></span> AUDITED ACCESS</div>
            </aside>

            <main class="dashboard-main">
                <div class="dashboard-kicker"><span>REPORTING SUITE</span><span>Permission-Scoped Analytics</span></div>
                <section class="dashboard-welcome">
                    <p class="eyebrow eyebrow-dark">{{ $role->label() }}</p>
                    <h1>Institutional & Operational Reports</h1>
                    <p>Review real-time institutional metrics and generate permitted data exports. All export events are logged to the tamper-resistant audit registry.</p>
                </section>

                <!-- Summary Metrics Grid -->
                <section style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-top: 24px;">
                    @if (isset($stats['total_staff']))
                        <div style="background: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 18px;">
                            <span class="detail-label">TOTAL REGISTERED STAFF</span>
                            <div style="font-size: 26px; font-weight: 700; color: #173d35; margin-top: 4px;">{{ $stats['total_staff'] }}</div>
                            <span style="font-size: 11px; color: #059669; font-weight: 600;">{{ $stats['active_staff'] }} active</span>
                        </div>
                    @endif

                    @if (isset($stats['pending_leave']))
                        <div style="background: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 18px;">
                            <span class="detail-label">PENDING LEAVE APPLICATIONS</span>
                            <div style="font-size: 26px; font-weight: 700; color: #d97706; margin-top: 4px;">{{ $stats['pending_leave'] }}</div>
                            <span style="font-size: 11px; color: #6b7280;">{{ $stats['approved_leave'] }} approved</span>
                        </div>
                    @endif

                    @if (isset($stats['dept_staff']))
                        <div style="background: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 18px;">
                            <span class="detail-label">DEPARTMENT PERSONNEL</span>
                            <div style="font-size: 26px; font-weight: 700; color: #173d35; margin-top: 4px;">{{ $stats['dept_staff'] }}</div>
                            <span style="font-size: 11px; color: #d97706;">{{ $stats['dept_pending_leave'] }} leave pending</span>
                        </div>
                    @endif

                    @if (isset($stats['payslip_batches']))
                        <div style="background: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 18px;">
                            <span class="detail-label">BURSARY PAYSLIP BATCHES</span>
                            <div style="font-size: 26px; font-weight: 700; color: #173d35; margin-top: 4px;">{{ $stats['payslip_batches'] }}</div>
                            <span style="font-size: 11px; color: #059669;">{{ $stats['published_batches'] }} released</span>
                        </div>
                    @endif

                    <div style="background: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 18px;">
                        <span class="detail-label">UNRESOLVED SERVICE REQUESTS</span>
                        <div style="font-size: 26px; font-weight: 700; color: #2563eb; margin-top: 4px;">{{ $stats['open_requests'] }}</div>
                        <span style="font-size: 11px; color: #059669;">{{ $stats['resolved_requests'] }} resolved</span>
                    </div>
                </section>

                <!-- Exportable Reports Section -->
                <section style="margin-top: 36px;">
                    <div class="section-heading">
                        <div>
                            <p class="eyebrow eyebrow-dark">DATA EXPORTS</p>
                            <h2>Authorized Scoped Exports</h2>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-top: 18px;">
                        <!-- Personnel Summary -->
                        @if (in_array($role, [\App\UserRole::HrRegistryAdmin, \App\UserRole::HeadOfDepartment, \App\UserRole::Director, \App\UserRole::InstitutionalSuperadmin, \App\UserRole::AuditorReadonly], true))
                            <div style="background: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; display: flex; flex-direction: column; justify-content: space-between;">
                                <div>
                                    <h3 style="margin: 0 0 6px 0; font-size: 15px; color: #173d35;">Personnel Register Export</h3>
                                    <p style="margin: 0 0 14px 0; font-size: 12px; color: #4b5563; line-height: 1.5;">
                                        Comprehensive staff register scoped to your authorized department or institution. Excludes confidential salary records.
                                    </p>
                                </div>
                                <form method="GET" action="{{ route('reports.export') }}">
                                    <input type="hidden" name="report_type" value="personnel_summary">
                                    <button type="submit" class="button button-primary" style="width: 100%; padding: 8px; font-size: 11px;">
                                        Export Personnel CSV &darr;
                                    </button>
                                </form>
                            </div>
                        @endif

                        <!-- Leave Summary -->
                        @if (in_array($role, [\App\UserRole::HrRegistryAdmin, \App\UserRole::HeadOfDepartment, \App\UserRole::Director, \App\UserRole::InstitutionalSuperadmin, \App\UserRole::AuditorReadonly], true))
                            <div style="background: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; display: flex; flex-direction: column; justify-content: space-between;">
                                <div>
                                    <h3 style="margin: 0 0 6px 0; font-size: 15px; color: #173d35;">Leave Utilization Summary</h3>
                                    <p style="margin: 0 0 14px 0; font-size: 12px; color: #4b5563; line-height: 1.5;">
                                        Export of leave applications, categories, requested duration, and status transitions across authorized departments.
                                    </p>
                                </div>
                                <form method="GET" action="{{ route('reports.export') }}">
                                    <input type="hidden" name="report_type" value="leave_summary">
                                    <button type="submit" class="button button-primary" style="width: 100%; padding: 8px; font-size: 11px;">
                                        Export Leave Summary CSV &darr;
                                    </button>
                                </form>
                            </div>
                        @endif

                        <!-- Service Requests Summary -->
                        <div style="background: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; display: flex; flex-direction: column; justify-content: space-between;">
                            <div>
                                <h3 style="margin: 0 0 6px 0; font-size: 15px; color: #173d35;">Service Desk Operations</h3>
                                <p style="margin: 0 0 14px 0; font-size: 12px; color: #4b5563; line-height: 1.5;">
                                    Audit report of submitted enquiries, priority metrics, resolution timelines, and assigned unit queues.
                                </p>
                            </div>
                            <form method="GET" action="{{ route('reports.export') }}">
                                <input type="hidden" name="report_type" value="service_requests_summary">
                                <button type="submit" class="button button-primary" style="width: 100%; padding: 8px; font-size: 11px;">
                                    Export Service Desk CSV &darr;
                                </button>
                            </form>
                        </div>

                        <!-- Payslip Batch Reconciliation -->
                        @if (in_array($role, [\App\UserRole::BursaryAdmin, \App\UserRole::InstitutionalSuperadmin, \App\UserRole::AuditorReadonly], true))
                            <div style="background: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; display: flex; flex-direction: column; justify-content: space-between;">
                                <div>
                                    <h3 style="margin: 0 0 6px 0; font-size: 15px; color: #173d35;">Payroll Batch Reconciliation</h3>
                                    <p style="margin: 0 0 14px 0; font-size: 12px; color: #4b5563; line-height: 1.5;">
                                        Bursary audit export of batch metadata, matching totals, quarantine rates, and publication timestamps.
                                    </p>
                                </div>
                                <form method="GET" action="{{ route('reports.export') }}">
                                    <input type="hidden" name="report_type" value="payslip_reconciliation">
                                    <button type="submit" class="button button-primary" style="width: 100%; padding: 8px; font-size: 11px;">
                                        Export Reconciliation CSV &darr;
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>
                </section>
            </main>
        </div>
    </div>
@endsection
