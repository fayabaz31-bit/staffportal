@extends('layouts.app')

@section('title', 'My Payslips | NfLV Staff Portal')

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
            <aside class="dashboard-rail" aria-label="Payslips navigation">
                <p class="rail-label">DOCUMENTS</p>
                <a class="rail-link rail-link-active" href="{{ route('payslips.index') }}"><span class="rail-dot"></span> My Payslips</a>
                <a class="rail-link" href="{{ route('leave.index') }}">Leave Applications</a>
                <a class="rail-link" href="{{ route('memos.index') }}">Correspondence</a>
                <a class="rail-link" href="{{ route('profile.show') }}">My Profile</a>
                <div class="rail-divider"></div>
                <div class="rail-bottom"><span class="status-dot"></span> PRIVATE DOCUMENT VAULT</div>
            </aside>

            <main class="dashboard-main">
                <div class="dashboard-kicker"><span>FINANCIAL VAULT</span><span>{{ $payslips->total() }} published record(s)</span></div>
                <section class="dashboard-welcome">
                    <p class="eyebrow eyebrow-dark">BURSARY OUTPUTS</p>
                    <h1>My Payslips</h1>
                    <p>Official monthly remuneration records issued and authorized by the Bursary.</p>
                </section>

                @if (session('status'))
                    <p class="profile-notice" role="status">{{ session('status') }}</p>
                @endif
                @if (session('error'))
                    <p class="field-error" style="margin: 12px 0; font-size: 13px;" role="alert">{{ session('error') }}</p>
                @endif

                <section class="memo-list-section" aria-labelledby="payslips-heading">
                    <div class="section-heading">
                        <div>
                            <p class="eyebrow eyebrow-dark">HISTORICAL RECORDS</p>
                            <h2 id="payslips-heading">Payslip Vault</h2>
                        </div>
                    </div>

                    <div class="directory-table-wrap" style="margin-top: 18px;">
                        <table class="directory-table">
                            <thead>
                                <tr>
                                    <th>Payroll Period</th>
                                    <th>Release Date</th>
                                    <th>File Size</th>
                                    <th>Downloads</th>
                                    <th style="text-align: right;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($payslips as $payslip)
                                    <tr>
                                        <td>
                                            <strong>{{ $payslip->formattedPeriod() }}</strong>
                                            <span>Period: {{ $payslip->payroll_period }}</span>
                                        </td>
                                        <td>
                                            {{ $payslip->published_at?->format('d M Y') ?? '—' }}
                                        </td>
                                        <td>
                                            {{ $payslip->formattedFileSize() }}
                                        </td>
                                        <td>
                                            {{ $payslip->download_count }} time(s)
                                        </td>
                                        <td style="text-align: right;">
                                            <a class="button button-primary" style="display: inline-flex; min-height: 36px; padding: 0 14px; font-size: 11px; text-decoration: none;" href="{{ route('payslips.download', $payslip) }}">
                                                Download PDF
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="directory-empty">
                                            No published payslips are currently available for your staff account.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($payslips->hasPages())
                        <div style="margin-top: 16px;">
                            {{ $payslips->links() }}
                        </div>
                    @endif

                    <div class="import-safety-note" style="margin-top: 24px;">
                        <p class="eyebrow eyebrow-dark">CONFIDENTIALITY GUARANTEE</p>
                        <p>All payslips are stored on an encrypted private storage disk and are only accessible by your authenticated account. For queries regarding deductions or remuneration figures, please contact the Bursary.</p>
                    </div>
                </section>
            </main>
        </div>
    </div>
@endsection
