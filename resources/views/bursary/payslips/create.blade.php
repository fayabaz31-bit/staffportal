@extends('layouts.app')

@section('title', 'Stage Payslip Batch | Bursary Administration')

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
                <a class="rail-link" href="{{ route('bursary.payslips.index') }}">Payslip Batches</a>
                <a class="rail-link rail-link-active" href="{{ route('bursary.payslips.create') }}"><span class="rail-dot"></span> Stage New Batch</a>
                <div class="rail-divider"></div>
                <a class="rail-link" href="{{ route('dashboard') }}">Main Dashboard</a>
                <div class="rail-bottom"><span class="status-dot"></span> BURSARY PRIVILEGED ACCESS</div>
            </aside>

            <main class="dashboard-main">
                <div class="dashboard-kicker"><span>BURSARY PAYROLL DESK</span><span>BATCH INGESTION</span></div>
                <section class="dashboard-welcome">
                    <p class="eyebrow eyebrow-dark">STAGE PAYSLIPS</p>
                    <h1>Upload Payslip Batch</h1>
                    <p>Upload monthly payroll outputs as a ZIP archive or individual PDF. All files will be dry-run validated against staff numbers.</p>
                </section>

                @if ($errors->any())
                    <div class="field-error" style="margin: 16px 0; padding: 14px; background: #fff5f3; border: 1px solid #fed7d2; font-size: 13px;" role="alert">
                        <strong>Upload could not proceed:</strong>
                        <ul style="margin: 6px 0 0; padding-left: 18px;">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <section class="import-section" style="max-width: 680px;">
                    <form class="signin-form" method="POST" action="{{ route('bursary.payslips.store') }}" enctype="multipart/form-data">
                        @csrf

                        <div class="field-group">
                            <label for="payroll_period">Payroll Period (Year and Month) *</label>
                            <input id="payroll_period" type="text" name="payroll_period" value="{{ old('payroll_period', now()->format('Y-m')) }}" placeholder="YYYY-MM (e.g. 2026-09)" required pattern="\d{4}-(?:0[1-9]|1[0-2])" />
                            <p style="margin: 3px 0 0; color: #707c73; font-size: 11px;">Specify the remuneration period in format <code>YYYY-MM</code> (e.g., 2026-09 for September 2026).</p>
                        </div>

                        <div class="field-group" style="margin-top: 10px;">
                            <label for="batch_file">Payslip Batch File (.ZIP or .PDF) *</label>
                            <input id="batch_file" type="file" name="batch_file" accept=".zip,.pdf" required style="padding-top: 10px;" />
                        </div>

                        <div class="import-safety-note" style="margin-top: 14px;">
                            <p class="eyebrow eyebrow-dark">BURSARY FILE CONVENTION</p>
                            <p style="margin: 6px 0 0; font-size: 12px; color: #45554b; line-height: 1.55;">
                                <strong>File Naming:</strong> Inside the ZIP archive, individual PDF payslips must be named after the staff member's identifier (e.g. <code>NFLV-0001.pdf</code> or <code>NFLV-0001_2026-09.pdf</code>).<br />
                                <strong>Validation:</strong> The system automatically extracts, tests each file's PDF structure, verifies that the staff identifier exists and is active, and quarantines any mismatch.
                            </p>
                        </div>

                        <div style="display: flex; gap: 12px; align-items: center; margin-top: 18px;">
                            <button class="button button-primary" type="submit">
                                Upload & Dry-Run Validate Batch &rarr;
                            </button>
                            <a class="text-button" href="{{ route('bursary.payslips.index') }}" style="text-decoration: none; padding: 10px;">
                                Cancel
                            </a>
                        </div>
                    </form>
                </section>
            </main>
        </div>
    </div>
@endsection
