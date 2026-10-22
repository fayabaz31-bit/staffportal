@extends('layouts.app')

@section('title', 'Payslip Batches | Bursary Administration')

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
                <a class="rail-link rail-link-active" href="{{ route('bursary.payslips.index') }}"><span class="rail-dot"></span> Payslip Batches</a>
                <a class="rail-link" href="{{ route('bursary.payslips.create') }}">Stage New Batch</a>
                <div class="rail-divider"></div>
                <a class="rail-link" href="{{ route('dashboard') }}">Main Dashboard</a>
                <div class="rail-bottom"><span class="status-dot"></span> BURSARY PRIVILEGED ACCESS</div>
            </aside>

            <main class="dashboard-main">
                <div class="dashboard-kicker"><span>BURSARY PAYROLL DESK</span><span>{{ $batches->total() }} batch record(s)</span></div>
                <section class="dashboard-welcome">
                    <p class="eyebrow eyebrow-dark">PAYROLL OUTPUTS</p>
                    <h1>Payslip Batches</h1>
                    <p>Stage, dry-run validate, and authorize release of monthly staff remuneration outputs.</p>
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

                <div class="directory-toolbar">
                    <div>
                        <p class="eyebrow eyebrow-dark" style="margin: 0;">RECONCILIATION SUMMARY</p>
                    </div>
                    <a class="button button-primary directory-import-button" href="{{ route('bursary.payslips.create') }}" style="text-decoration: none;">
                        Stage New Batch
                    </a>
                </div>

                <div class="directory-table-wrap">
                    <table class="directory-table">
                        <thead>
                            <tr>
                                <th>Payroll Period</th>
                                <th>Uploaded By</th>
                                <th>Files / Matched</th>
                                <th>Status</th>
                                <th>Upload Date</th>
                                <th style="text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($batches as $batch)
                                <tr>
                                    <td>
                                        <strong>{{ $batch->formattedPeriod() }}</strong>
                                        <span>Period: {{ $batch->payroll_period }}</span>
                                    </td>
                                    <td>
                                        <strong>{{ $batch->uploader->name }}</strong>
                                        <span>{{ $batch->original_filename }}</span>
                                    </td>
                                    <td>
                                        <strong>{{ $batch->matched_files }} / {{ $batch->total_files }} matched</strong>
                                        @if ($batch->error_count > 0)
                                            <span style="color: #a73f34; font-weight: 700;">{{ $batch->error_count }} error(s)</span>
                                        @else
                                            <span style="color: #34704e;">0 errors</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($batch->isPublished())
                                            <span class="directory-account-status directory-account-active">PUBLISHED</span>
                                        @elseif ($batch->isQuarantined())
                                            <span class="directory-account-status directory-account-suspended">QUARANTINED</span>
                                        @elseif ($batch->isWithdrawn())
                                            <span class="directory-account-status directory-account-deactivated">WITHDRAWN</span>
                                        @else
                                            <span class="directory-account-status" style="background: #fff8e1; color: #8a6727;">STAGED</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $batch->created_at->format('d M Y, H:i') }}
                                    </td>
                                    <td style="text-align: right;">
                                        <a class="directory-add-button" style="display: inline-flex; align-items: center; padding: 0 12px; text-decoration: none;" href="{{ route('bursary.payslips.show', $batch) }}">
                                            Review Batch &rarr;
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="directory-empty">
                                        No payslip batches have been uploaded yet. Click <strong>Stage New Batch</strong> to begin.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($batches->hasPages())
                    <div style="margin-top: 16px;">
                        {{ $batches->links() }}
                    </div>
                @endif
            </main>
        </div>
    </div>
@endsection
