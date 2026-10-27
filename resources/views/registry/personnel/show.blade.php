@extends('layouts.app')

@section('title', $staffProfile->user->name.' | Staff profile | NfLV Staff Portal')

@php($profile = $staffProfile)

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
                <a class="rail-link rail-link-active" href="{{ route('registry.personnel.index') }}"><span class="rail-dot"></span> Staff directory</a>
                @can('manage-personnel-records')
                    <a class="rail-link" href="{{ route('registry.personnel-imports.create') }}">Personnel import</a>
                @endcan
                @can('any-registry-approval')
                    <a class="rail-link" href="{{ route('registry.profile-change-requests.index') }}">Profile changes</a>
                @endcan
                <div class="rail-bottom"><span class="status-dot"></span> FULL PERSONNEL RECORD</div>
            </aside>

            <main class="dashboard-main">
                <div class="dashboard-kicker"><span>PERSONNEL</span><a class="back-link" href="{{ route('registry.personnel.index') }}">Back to directory</a></div>
                <section class="dashboard-welcome">
                    <p class="eyebrow eyebrow-dark">FULL PERSONNEL RECORD</p>
                    <h1>{{ $profile->user->name }}</h1>
                    <p>{{ $profile->user->staff_number }} · {{ $profile->designation ?? 'Designation not recorded' }}</p>
                </section>

                <p class="profile-notice">Full personnel records are restricted to authorised Registry leadership. This view is recorded in the audit trail.</p>

                @if ($profile->passportUrl())
                    <section class="profile-section" aria-labelledby="photo-heading">
                        <div class="section-heading"><div><p class="eyebrow eyebrow-dark">PHOTO</p><h2 id="photo-heading">Passport photograph</h2></div><span>ON FILE</span></div>
                        <div class="profile-photo-wrap">
                            <img src="{{ $profile->passportUrl() }}" alt="Passport photograph of {{ $profile->user->name }}" class="profile-passport-photo">
                        </div>
                    </section>
                @endif

                <section class="profile-section" aria-labelledby="official-heading">
                    <div class="section-heading"><div><p class="eyebrow eyebrow-dark">OFFICIAL RECORD</p><h2 id="official-heading">Employment details</h2></div><span>READ ONLY</span></div>
                    <dl class="profile-details">
                        <div><dt>Staff number</dt><dd>{{ $profile->user->staff_number }}</dd></div>
                        <div><dt>Full name</dt><dd>{{ $profile->user->name }}</dd></div>
                        <div><dt>Institutional email</dt><dd>{{ $profile->user->email ?? 'No institutional email' }}</dd></div>
                        <div><dt>Department</dt><dd>{{ $profile->department?->name ?? 'Not assigned' }}</dd></div>
                        <div><dt>Designation</dt><dd>{{ $profile->designation ?? 'Not recorded' }}</dd></div>
                        <div><dt>Unit</dt><dd>{{ $profile->unit ?? $profile->organisationalUnit?->name ?? 'Not recorded' }}</dd></div>
                        <div><dt>Grade level</dt><dd>{{ $profile->grade_level ?? 'Not recorded' }}</dd></div>
                        <div><dt>Employment status</dt><dd>{{ str($profile->employment_status)->replace('_', ' ')->title() }}</dd></div>
                        <div><dt>Date of first appointment</dt><dd>{{ $profile->date_of_first_appointment?->format('j M Y') ?? 'Not recorded' }}</dd></div>
                        <div><dt>Date of birth</dt><dd>{{ $profile->date_of_birth?->format('j M Y') ?? 'Not recorded' }}</dd></div>
                        <div><dt>Gender</dt><dd>{{ $profile->gender ? str($profile->gender)->replace('_', ' ')->title() : 'Not recorded' }}</dd></div>
                        <div><dt>Marital status</dt><dd>{{ $profile->marital_status ? str($profile->marital_status)->replace('_', ' ')->title() : 'Not recorded' }}</dd></div>
                        <div><dt>State of origin</dt><dd>{{ $profile->state_of_origin ?? 'Not recorded' }}</dd></div>
                    </dl>
                </section>

                <section class="profile-section" aria-labelledby="contact-heading">
                    <div class="section-heading"><div><p class="eyebrow eyebrow-dark">PERSONAL INFORMATION</p><h2 id="contact-heading">Contact and next of kin</h2></div><span>CONFIDENTIAL</span></div>
                    <dl class="profile-details">
                        <div><dt>Mobile number</dt><dd>{{ $profile->phone_number ?? 'Not recorded' }}</dd></div>
                        <div><dt>Personal email</dt><dd>{{ $profile->personal_email ?? 'Not recorded' }}</dd></div>
                        <div><dt>Next of kin</dt><dd>{{ $profile->next_of_kin ?? 'Not recorded' }}</dd></div>
                        <div><dt>Next of kin phone</dt><dd>{{ $profile->next_of_kin_phone ?? 'Not recorded' }}</dd></div>
                        <div class="profile-detail-wide"><dt>Residential address</dt><dd>{{ $profile->residential_address ?? 'Not recorded' }}</dd></div>
                    </dl>
                </section>

                <section class="profile-section" aria-labelledby="qualifications-heading">
                    <div class="section-heading"><div><p class="eyebrow eyebrow-dark">QUALIFICATIONS & PENSION</p><h2 id="qualifications-heading">Educational, professional, PENCOM and PFA</h2></div><span>CONFIDENTIAL</span></div>
                    <dl class="profile-details">
                        <div class="profile-detail-wide"><dt>Educational qualifications</dt><dd>{!! nl2br(e($profile->educational_qualifications ?? 'Not recorded')) !!}</dd></div>
                        <div class="profile-detail-wide"><dt>Professional qualifications</dt><dd>{!! nl2br(e($profile->professional_qualifications ?? 'Not recorded')) !!}</dd></div>
                        <div><dt>PENCOM number</dt><dd>{{ $profile->pencom_number ?? 'Not recorded' }}</dd></div>
                        <div><dt>PFA name</dt><dd>{{ $profile->pfa_name ?? 'Not recorded' }}</dd></div>
                    </dl>
                </section>

                @can('manage-personnel-records')
                    <section class="profile-section" aria-labelledby="documents-upload-heading">
                        <div class="section-heading"><div><p class="eyebrow eyebrow-dark">DOCUMENTS</p><h2 id="documents-upload-heading">Upload certificates and other documents</h2></div><span>REGISTRY</span></div>
                        <form class="profile-form" method="POST" action="{{ route('registry.personnel.documents.upload', $profile) }}" enctype="multipart/form-data">
                            @csrf
                            <div class="profile-form-grid">
                                <div class="field-group">
                                    <label for="doc_title">Document title</label>
                                    <input id="doc_title" name="title" type="text" maxlength="150" required>
                                </div>
                                <div class="field-group">
                                    <label for="doc_type">Document type</label>
                                    <select id="doc_type" name="type" required>
                                        <option value="certificate">Certificate</option>
                                        <option value="id_card">ID card</option>
                                        <option value="appointment_letter">Appointment letter</option>
                                        <option value="promotion_letter">Promotion letter</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div class="field-group profile-address-field">
                                    <label for="doc_file">File (PDF, JPG, PNG, DOC, DOCX, max 5MB)</label>
                                    <input id="doc_file" name="document" type="file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required>
                                </div>
                                <div class="field-group profile-address-field">
                                    <label for="doc_notes">Notes</label>
                                    <textarea id="doc_notes" name="notes" rows="2" maxlength="500"></textarea>
                                </div>
                            </div>
                            <button class="button button-primary profile-submit" type="submit">Upload document <span aria-hidden="true">&#8594;</span></button>
                        </form>
                    </section>
                @endcan

                <section class="profile-section" aria-labelledby="documents-list-heading">
                    <div class="section-heading"><div><p class="eyebrow eyebrow-dark">DOCUMENTS</p><h2 id="documents-list-heading">Uploaded documents</h2></div><span>{{ $profile->documents->count() }} FILES</span></div>
                    @forelse ($profile->documents as $document)
                        <article class="request-history-item">
                            <div>
                                <span class="detail-label">{{ str($document->type)->replace('_', ' ')->upper() }}</span>
                                <h3>{{ $document->title }}</h3>
                                <p>{{ $document->file_name }}@if ($document->file_size) · {{ number_format($document->file_size / 1024, 1) }} KB @endif</p>
                                @if ($document->notes)<p class="reviewer-note">{{ $document->notes }}</p>@endif
                                @if ($document->uploader)<p>Uploaded by {{ $document->uploader->name }} · {{ $document->created_at->format('j M Y, H:i') }}</p>@endif
                            </div>
                            <a class="directory-profile-link" href="{{ $document->downloadUrl() }}" target="_blank" rel="noopener">View / Download</a>
                        </article>
                    @empty
                        <p class="profile-empty">No documents uploaded yet.</p>
                    @endforelse
                </section>

                <section class="profile-section" aria-labelledby="account-heading">
                    <div class="section-heading"><div><p class="eyebrow eyebrow-dark">ACCOUNT</p><h2 id="account-heading">Portal access</h2></div><span>READ ONLY</span></div>
                    <dl class="profile-details">
                        <div><dt>Account status</dt><dd><span class="directory-account-status directory-account-{{ $profile->user->status }}">{{ str($profile->user->status)->replace('_', ' ')->title() }}</span></dd></div>
                        <div><dt>System role</dt><dd>{{ $profile->user->role->label() }}</dd></div>
                        <div><dt>Record updated</dt><dd>{{ $profile->updated_at?->format('j M Y, H:i') ?? 'Not recorded' }}</dd></div>
                    </dl>
                </section>
            </main>
        </div>
    </div>
@endsection
