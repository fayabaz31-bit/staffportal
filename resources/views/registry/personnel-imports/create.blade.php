@extends('layouts.app')

@section('title', 'Personnel import | NfLV Staff Portal')

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
                <div class="rail-bottom"><span class="status-dot"></span> PREVIEW BEFORE COMMIT</div>
            </aside>

            <main class="dashboard-main">
                <div class="dashboard-kicker"><span>PERSONNEL</span><a class="back-link" href="{{ route('registry.personnel.index') }}">Back to directory</a></div>
                <section class="dashboard-welcome">
                    <p class="eyebrow eyebrow-dark">CONTROLLED IMPORT</p>
                    <h1>Import personnel</h1>
                    <p>Upload a CSV to validate staff identifiers and department mappings before any account is created.</p>
                </section>

                @if ($errors->any())
                    <div class="profile-notice profile-notice-error" role="alert">
                        <strong>The file could not be previewed.</strong>
                        <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                @endif

                <section class="profile-section import-section" aria-labelledby="import-file-heading">
                    <div class="section-heading"><div><p class="eyebrow eyebrow-dark">STEP 1</p><h2 id="import-file-heading">Choose CSV file</h2></div><a class="template-link" href="{{ route('registry.personnel-imports.template') }}">Download CSV template</a></div>
                    <p class="import-note">CSV must contain the template headers. Maximum 500 data rows and 2 MB. Preview never creates or updates accounts.</p>
                    <form class="import-upload-form" method="POST" action="{{ route('registry.personnel-imports.store') }}" enctype="multipart/form-data">
                        @csrf
                        <label for="personnel-file">Personnel CSV</label>
                        <input id="personnel-file" name="file" type="file" accept=".csv,text/csv" required>
                        <button class="button button-primary" type="submit">Validate preview <span aria-hidden="true">&#8594;</span></button>
                    </form>
                    <div class="import-field-list">
                        <span>Required: staff_number, name, department_code, designation, employment_status</span>
                        <span>Optional: email, phone_number, personal_email, residential_address</span>
                    </div>
                </section>

                <section class="import-safety-note" aria-label="Import safety">
                    <span class="eyebrow eyebrow-dark">ACCOUNT SAFETY</span>
                    <p>Committed accounts are created as suspended staff accounts. Registry cannot activate them; ICT provisions access separately. Imported roles cannot be assigned through this file.</p>
                </section>
            </main>
        </div>
    </div>
@endsection