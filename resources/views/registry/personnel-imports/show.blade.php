@extends('layouts.app')

@section('title', 'Personnel import preview | NfLV Staff Portal')

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
                    <button class="text-button" type="submit">Switch demo role</button>
                </form>
            </div>
        </header>

        <div class="dashboard-layout">
            <aside class="dashboard-rail" aria-label="Registry navigation">
                <p class="rail-label">HR / REGISTRY</p>
                <a class="rail-link" href="{{ route('dashboard') }}">Overview</a>
                <a class="rail-link" href="{{ route('registry.personnel.index') }}">Staff directory</a>
                <a class="rail-link rail-link-active" href="{{ route('registry.personnel-imports.create') }}"><span class="rail-dot"></span> Personnel import</a>
                <a class="rail-link" href="{{ route('registry.profile-change-requests.index') }}">Profile changes</a>
                <div class="rail-bottom"><span class="status-dot"></span> PREVIEW ONLY</div>
            </aside>

            <main class="dashboard-main">
                <div class="dashboard-kicker"><span>IMPORT PREVIEW · #{{ $batch->id }}</span><a class="back-link" href="{{ route('registry.personnel.index') }}">Back to directory</a></div>
                <section class="dashboard-welcome">
                    <p class="eyebrow eyebrow-dark">{{ $batch->original_filename }}</p>
                    <h1>Review import</h1>
                    <p>No records are created until you commit a clean preview.</p>
                </section>

                @if ($errors->any())
                    <div class="profile-notice profile-notice-error" role="alert"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                @endif
                @if (session('status'))
                    <p class="profile-notice" role="status">{{ session('status') }}</p>
                @endif

                <div class="import-summary">
                    <div><span class="detail-label">ROWS</span><strong>{{ $batch->row_count }}</strong></div>
                    <div><span class="detail-label">READY</span><strong>{{ $batch->valid_row_count }}</strong></div>
                    <div><span class="detail-label">ROWS WITH ERRORS</span><strong>{{ $batch->error_row_count }}</strong></div>
                    <div><span class="detail-label">BATCH STATUS</span><strong>{{ str($batch->status)->replace('_', ' ')->upper() }}</strong></div>
                </div>

                @foreach ($groupedRows as $departmentCode => $rows)
                    <div class="directory-group-heading">
                        <span class="eyebrow eyebrow-dark">DEPARTMENT</span>
                        <h2>{{ \App\DepartmentCatalog::name($departmentCode) ?? $departmentCode }}</h2>
                        <span>{{ $rows->count() }} {{ $rows->count() === 1 ? 'row' : 'rows' }}</span>
                    </div>
                    <div class="directory-table-wrap import-preview-table-wrap">
                        <table class="directory-table import-preview-table">
                            <thead><tr><th scope="col">Line</th><th scope="col">Staff number</th><th scope="col">Name</th><th scope="col">Designation</th><th scope="col">Result</th><th scope="col">Issues</th></tr></thead>
                            <tbody>
                                @foreach ($rows as $row)
                                    <tr>
                                        <td>{{ $row['line_number'] }}</td>
                                        <td>{{ $row['data']['staff_number'] ?? '—' }}</td>
                                        <td>{{ $row['data']['name'] ?? '—' }}</td>
                                        <td>{{ $row['data']['designation'] ?? '—' }}</td>
                                        <td><span class="directory-account-status directory-account-{{ $row['status'] === 'valid' ? 'active' : 'suspended' }}">{{ $row['status'] === 'valid' ? 'Ready to import' : 'Needs correction' }}</span></td>
                                        <td>@if ($row['errors'] === [])<span class="muted-dash">—</span>@else<ul class="import-error-list">@foreach ($row['errors'] as $error)<li>{{ $error }}</li>@endforeach</ul>@endif</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endforeach

                <div class="import-commit-row">
                    <a class="text-button" href="{{ route('registry.personnel-imports.create') }}">Discard and upload another</a>
                    @if ($batch->status === \App\Models\PersonnelImportBatch::STATUS_PREVIEW && $batch->error_row_count === 0)
                        <form method="POST" action="{{ route('registry.personnel-imports.commit', $batch) }}">
                            @csrf
                            <button class="button button-primary" type="submit">Commit {{ $batch->valid_row_count }} staff records <span aria-hidden="true">&#8594;</span></button>
                        </form>
                    @else
                        <span class="commit-blocked">Fix every row error and upload a new preview before commit.</span>
                    @endif
                </div>
            </main>
        </div>
    </div>
@endsection