@extends('layouts.app')

@section('title', 'Reconciliation Review | Bursary Administration')

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
            <aside class="dashboard-rail" aria-label="Bursary navigation">
                <p class="rail-label">BURSARY OPERATIONS</p>
                <a class="rail-link" href="{{ route('bursary.payslips.index') }}">&larr; All Batches</a>
                <a class="rail-link rail-link-active" href="{{ route('bursary.payslips.show', $batch) }}"><span class="rail-dot"></span> Batch Details</a>
                <div class="rail-divider"></div>
                <div class="rail-bottom"><span class="status-dot"></span> RECONCILIATION AUDIT</div>
            </aside>

            <main class="dashboard-main">
                <div class="dashboard-kicker"><span>RECONCILIATION REPORT</span><span>Period {{ $batch->payroll_period }}</span></div>
                <section class="dashboard-welcome">
                    <p class="eyebrow eyebrow-dark">BATCH AUDIT</p>
                    <h1>{{ $batch->formattedPeriod() }} Payroll Batch</h1>
                    <p>Uploaded by {{ $batch->uploader->name }} on {{ $batch->created_at->format('d M Y, H:i') }}.</p>
                </section>

                @if (session('status'))
                    <p class="profile-notice" role="status">{{ session('status') }}</p>
                @endif
                @if (session('warning'))
                    <p class="field-error" style="margin: 12px 0; padding: 12px; background: #fff4f2; border: 1px solid #f0d5d1; font-size: 13px;" role="alert">{{ session('warning') }}</p>
                @endif
                @if (session('error'))
                    <p class="field-error" style="margin: 12px 0; font-size: 13px;" role="alert">{{ session('error') }}</p>
                @endif

                <div class="import-summary" style="margin-top: 18px;">
                    <div>
                        <span class="eyebrow eyebrow-dark" style="font-size: 9px;">BATCH STATUS</span>
                        @if ($batch->isPublished())
                            <strong style="color: #34704e;">PUBLISHED</strong>
                        @elseif ($batch->isQuarantined())
                            <strong style="color: #a73f34;">QUARANTINED</strong>
                        @elseif ($batch->isWithdrawn())
                            <strong style="color: #655f56;">WITHDRAWN</strong>
                        @else
                            <strong style="color: #8a6727;">STAGED FOR APPROVAL</strong>
                        @endif
                    </div>
                    <div>
                        <span class="eyebrow eyebrow-dark" style="font-size: 9px;">TOTAL FILES</span>
                        <strong>{{ $batch->total_files }}</strong>
                    </div>
                    <div>
                        <span class="eyebrow eyebrow-dark" style="font-size: 9px;">MATCHED STAFF</span>
                        <strong style="color: #34704e;">{{ $batch->matched_files }}</strong>
                    </div>
                    <div>
                        <span class="eyebrow eyebrow-dark" style="font-size: 9px;">ERRORS / EXCEPTIONS</span>
                        <strong style="{{ $batch->error_count > 0 ? 'color: #a73f34;' : 'color: #34704e;' }}">{{ $batch->error_count }}</strong>
                    </div>
                </div>

                @php
                    $summary = $batch->reconciliation_summary ?? [];
                    $errorsList = $summary['errors'] ?? [];
                    $matchedList = $summary['matched'] ?? [];
                @endphp

                @if ($batch->isQuarantined() || $batch->error_count > 0)
                    <section style="margin-top: 24px; padding: 18px; border-left: 4px solid #b2493d; background: #fff5f3;">
                        <h2 style="margin: 0; color: #8e2b20; font-size: 16px;">Batch Quarantined (Release Blocked)</h2>
                        <p style="margin: 6px 0 0; color: #5c2018; font-size: 12px; line-height: 1.55;">
                            In accordance with PRD §7.2 and requirement PAY-03, batches with unresolved material errors cannot be published to staff accounts. Please review the errors below.
                        </p>

                        <div class="directory-table-wrap" style="margin-top: 14px;">
                            <table class="directory-table">
                                <thead>
                                    <tr>
                                        <th>File</th>
                                        <th>Validation Failure Reason</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($errorsList as $err)
                                        <tr>
                                            <td style="font-weight: 700; color: #a73f34;">
                                                {{ $err['filename'] ?? 'Unknown file' }}
                                            </td>
                                            <td style="color: #8e2b20;">
                                                {{ $err['error'] ?? 'Unknown error' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div style="margin-top: 16px;">
                            <form method="POST" action="{{ route('bursary.payslips.destroy', $batch) }}" onsubmit="return confirm('Are you sure you want to discard this quarantined batch?');">
                                @csrf
                                @method('DELETE')
                                <button class="button review-reject" type="submit" style="min-height: 38px; font-size: 11px;">
                                    Discard Quarantined Batch
                                </button>
                            </form>
                        </div>
                    </section>
                @endif

                @if (! empty($matchedList))
                    <section style="margin-top: 28px;">
                        <div class="section-heading">
                            <div>
                                <p class="eyebrow eyebrow-dark">RECONCILIATION PREVIEW</p>
                                <h2>Validated Staff Payslips ({{ count($matchedList) }})</h2>
                            </div>
                        </div>

                        <div class="directory-table-wrap" style="margin-top: 14px;">
                            <table class="directory-table">
                                <thead>
                                    <tr>
                                        <th>Staff Member</th>
                                        <th>Staff ID</th>
                                        <th>Department</th>
                                        <th>Original File</th>
                                        <th>File Size</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($matchedList as $item)
                                        <tr>
                                            <td>
                                                <strong>{{ $item['name'] }}</strong>
                                            </td>
                                            <td>
                                                <code>{{ $item['staff_number'] }}</code>
                                            </td>
                                            <td>
                                                {{ $item['department'] }}
                                            </td>
                                            <td>
                                                {{ $item['filename'] }}
                                            </td>
                                            <td>
                                                {{ number_format(($item['size'] ?? 0) / 1024, 0) }} KB
                                            </td>
                                            <td>
                                                <span class="directory-account-status directory-account-active">MATCHED</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </section>
                @endif

                @if ($batch->isStaged())
                    <section style="margin-top: 28px; padding: 22px; border: 1px solid var(--line); background: var(--white);">
                        <p class="eyebrow eyebrow-dark">AUTHORIZATION REQUIRED</p>
                        <h2 style="margin: 6px 0 0; color: var(--pine-deep); font-size: 18px;">Approve and Release Payslips</h2>
                        <p style="margin: 6px 0 16px; color: var(--muted); font-size: 12px; line-height: 1.55;">
                            Approving this batch will atomically transfer all {{ $batch->matched_files }} payslip files into encrypted private storage and make them immediately visible in each staff member's private document vault.
                        </p>

                        <form method="POST" action="{{ route('bursary.payslips.publish', $batch) }}" onsubmit="return confirm('Authorize publication of {{ $batch->matched_files }} payslips for {{ $batch->formattedPeriod() }}?');">
                            @csrf
                            <button class="button button-primary" type="submit">
                                Approve & Publish {{ $batch->matched_files }} Payslips &rarr;
                            </button>
                        </form>
                    </section>
                @endif

                @if ($batch->isPublished())
                    <section style="margin-top: 28px; padding: 22px; border: 1px solid #d5e0d8; background: #f6faf7;">
                        <p class="eyebrow eyebrow-dark" style="color: #34704e;">PUBLICATION CONFIRMED</p>
                        <h2 style="margin: 6px 0 0; color: var(--pine-deep); font-size: 18px;">Batch Released to Staff Accounts</h2>
                        <p style="margin: 6px 0 0; color: var(--muted); font-size: 12px; line-height: 1.55;">
                            Released on <strong>{{ $batch->published_at?->format('d M Y, H:i') }}</strong> by <strong>{{ $batch->publisher?->name ?? 'Authorized Officer' }}</strong>.
                        </p>

                        <div style="margin-top: 20px; padding-top: 18px; border-top: 1px solid var(--line);">
                            <h3 style="margin: 0; color: #a73f34; font-size: 13px;">Emergency Withdrawal</h3>
                            <p style="margin: 4px 0 12px; color: var(--muted); font-size: 11px;">
                                If an error was discovered after publication, you may withdraw the entire batch. Staff access will be cut off immediately.
                            </p>

                            <form method="POST" action="{{ route('bursary.payslips.withdraw', $batch) }}" onsubmit="return confirm('Are you sure you want to withdraw this entire published batch?');">
                                @csrf
                                <div class="field-group" style="max-width: 520px; margin-bottom: 12px;">
                                    <label for="withdrawal_reason">Documented Reason for Withdrawal *</label>
                                    <input id="withdrawal_reason" type="text" name="withdrawal_reason" placeholder="e.g. Discovered incorrect tax withholding calculation" required minlength="5" />
                                </div>
                                <button class="button review-reject" type="submit" style="min-height: 38px; font-size: 11px;">
                                    Withdraw Batch & Revoke Access
                                </button>
                            </form>
                        </div>
                    </section>
                @endif

                @if ($batch->isWithdrawn())
                    <section style="margin-top: 28px; padding: 20px; border-left: 4px solid #655f56; background: #f0eeeb;">
                        <p class="eyebrow eyebrow-dark" style="color: #655f56;">WITHDRAWAL RECORDED</p>
                        <h2 style="margin: 4px 0 0; font-size: 16px; color: #3b3732;">Batch Withdrawn from Circulation</h2>
                        <p style="margin: 6px 0 0; font-size: 12px; color: #524d45;">
                            Withdrawn on {{ $batch->withdrawn_at?->format('d M Y, H:i') }} by {{ $batch->withdrawnBy?->name ?? 'Bursary Officer' }}.<br />
                            <strong>Reason:</strong> {{ $batch->withdrawal_reason }}
                        </p>
                    </section>
                @endif
            </main>
        </div>
    </div>
@endsection
