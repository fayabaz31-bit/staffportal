@extends('layouts.app')

@section('title', 'My profile | NfLV Staff Portal')

@section('content')
    <div class="dashboard-page">
        <header class="dashboard-header">
            <a class="wordmark dashboard-wordmark" href="{{ route('dashboard') }}"><span class="wordmark-caption">Staff Portal</span></a>
            <div class="account-tools">
                <span class="account-name">{{ $user->name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="text-button" type="submit">Sign out</button>
                </form>
            </div>
        </header>

        <div class="dashboard-layout">
            <aside class="dashboard-rail" aria-label="Profile navigation">
                <p class="rail-label">YOUR ACCOUNT</p>
                <a class="rail-link" href="{{ route('dashboard') }}">Overview</a>
                <a class="rail-link rail-link-active" href="{{ route('profile.show') }}"><span class="rail-dot"></span> My profile</a>
                <div class="rail-bottom"><span class="status-dot"></span> PRIVATE PROFILE</div>
            </aside>

            <main class="dashboard-main">
                <div class="dashboard-kicker"><span>PERSONAL RECORD</span><a class="back-link" href="{{ route('dashboard') }}">Back to dashboard</a></div>
                <section class="dashboard-welcome">
                    <p class="eyebrow eyebrow-dark">DEMO DATA</p>
                    <h1>My profile</h1>
                    <p>Official employment details are read-only here. Request a correction for HR review.</p>
                </section>

                @if (session('status'))
                    <p class="profile-notice" role="status">{{ session('status') }}</p>
                @endif
                @if (session('error'))
                    <p class="profile-notice profile-notice-error" role="alert">{{ session('error') }}</p>
                @endif

                @if ($profile->passportUrl())
                    <section class="profile-section" aria-labelledby="my-photo-heading">
                        <div class="section-heading"><div><p class="eyebrow eyebrow-dark">PHOTO</p><h2 id="my-photo-heading">My passport photograph</h2></div><span>ON FILE</span></div>
                        <div class="profile-photo-wrap">
                            <img src="{{ $profile->passportUrl() }}" alt="Your passport photograph" class="profile-passport-photo">
                        </div>
                    </section>
                @endif

                <section class="profile-section" aria-labelledby="official-heading">
                    <div class="section-heading"><div><p class="eyebrow eyebrow-dark">OFFICIAL RECORD</p><h2 id="official-heading">Employment details</h2></div><span>READ ONLY</span></div>
                    <dl class="profile-details">
                        <div><dt>Staff ID</dt><dd>{{ $user->staff_number }}</dd></div>
                        <div><dt>Name</dt><dd>{{ $user->name }}</dd></div>
                        <div><dt>Department</dt><dd>{{ $profile->department?->name ?? 'Not assigned' }}</dd></div>
                        <div><dt>Designation</dt><dd>{{ $profile->designation ?? 'Not recorded' }}</dd></div>
                        <div><dt>Employment status</dt><dd>
                            @php
                                $employmentLabel = match($profile->employment_status) {
                                    'employed' => 'Permanent',
                                    'seconded' => 'Contract',
                                    'on_leave' => 'Probation',
                                    'sabbatical' => 'Sabbatical',
                                    'retired' => 'Retired',
                                    'terminated' => 'Terminated',
                                    default => str($profile->employment_status)->replace('_', ' ')->title(),
                                };
                            @endphp
                            {{ $employmentLabel }}
                        </dd></div>
                        <div><dt>Institutional email</dt><dd>{{ $user->email }}</dd></div>
                        <div><dt>State of origin</dt><dd>{{ $profile->state_of_origin ?? 'Not recorded' }}</dd></div>
                    </dl>
                </section>

                <section class="profile-section" aria-labelledby="contact-heading">
                    <div class="section-heading"><div><p class="eyebrow eyebrow-dark">SELF-EDITABLE</p><h2 id="contact-heading">Contact, bio-data, qualifications and passport</h2></div><span>PERSONAL INFORMATION</span></div>
                    <form class="profile-form" method="POST" action="{{ route('profile.contact.update') }}" enctype="multipart/form-data">
                        @csrf
                        @method('PATCH')
                        <div class="profile-form-grid">
                            <div class="field-group">
                                <label for="phone_number">Mobile number</label>
                                <input id="phone_number" name="phone_number" type="tel" value="{{ old('phone_number', $profile->phone_number) }}" autocomplete="tel">
                                @error('phone_number')<p class="field-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="field-group">
                                <label for="personal_email">Personal email</label>
                                <input id="personal_email" name="personal_email" type="email" value="{{ old('personal_email', $profile->personal_email) }}" autocomplete="email">
                                @error('personal_email')<p class="field-error">{{ $message }}</p>@enderror
                            </div>

                            <div class="field-group">
                                <label>PENCOM number</label>
                                <input type="text" value="{{ $profile->pencom_number ?? 'Not assigned' }}" readonly>
                            </div>
                            <div class="field-group">
                                <label for="pfa_name">PFA name</label>
                                <input id="pfa_name" name="pfa_name" type="text" value="{{ old('pfa_name', $profile->pfa_name) }}" maxlength="128">
                                @error('pfa_name')<p class="field-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="field-group profile-address-field">
                                <label for="residential_address">Residential address</label>
                                <textarea id="residential_address" name="residential_address" rows="3">{{ old('residential_address', $profile->residential_address) }}</textarea>
                                @error('residential_address')<p class="field-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="field-group profile-address-field">
                                <label for="educational_qualifications">Educational qualifications</label>
                                <textarea id="educational_qualifications" name="educational_qualifications" rows="3" maxlength="2000">{{ old('educational_qualifications', $profile->educational_qualifications) }}</textarea>
                                @error('educational_qualifications')<p class="field-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="field-group profile-address-field">
                                <label for="professional_qualifications">Professional qualifications</label>
                                <textarea id="professional_qualifications" name="professional_qualifications" rows="3" maxlength="2000">{{ old('professional_qualifications', $profile->professional_qualifications) }}</textarea>
                                @error('professional_qualifications')<p class="field-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="field-group profile-address-field">
                                <label for="passport_photo">Update passport photograph (JPG/JPEG/PNG, max 2MB)</label>
                                <input id="passport_photo" name="passport_photo" type="file" accept=".jpg,.jpeg,.png">
                                @error('passport_photo')<p class="field-error">{{ $message }}</p>@enderror
                            </div>
                        </div>
                        <button class="button button-primary profile-submit" type="submit">Save profile details <span aria-hidden="true">&#8594;</span></button>
                    </form>
                </section>

                <section class="profile-section" aria-labelledby="my-docs-upload-heading">
                    <div class="section-heading"><div><p class="eyebrow eyebrow-dark">MY DOCUMENTS</p><h2 id="my-docs-upload-heading">Upload certificates and other relevant documents</h2></div><span>SELF SERVICE</span></div>
                    <form class="profile-form" method="POST" action="{{ route('profile.documents.upload') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="profile-form-grid">
                            <div class="field-group">
                                <label for="my_doc_title">Document title</label>
                                <input id="my_doc_title" name="title" type="text" maxlength="150" required>
                            </div>
                            <div class="field-group">
                                <label for="my_doc_type">Document type</label>
                                <select id="my_doc_type" name="type" required>
                                    <option value="certificate">Certificate</option>
                                    <option value="id_card">ID card</option>
                                    <option value="appointment_letter">Appointment letter</option>
                                    <option value="promotion_letter">Promotion letter</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="field-group profile-address-field">
                                <label for="my_doc_file">File (PDF, JPG, PNG, DOC, DOCX, max 5MB)</label>
                                <input id="my_doc_file" name="document" type="file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required>
                            </div>
                            <div class="field-group profile-address-field">
                                <label for="my_doc_notes">Notes</label>
                                <textarea id="my_doc_notes" name="notes" rows="2" maxlength="500"></textarea>
                            </div>
                        </div>
                        <button class="button button-primary profile-submit" type="submit">Upload document <span aria-hidden="true">&#8594;</span></button>
                    </form>
                </section>

                <section class="profile-section" aria-labelledby="my-docs-list-heading">
                    <div class="section-heading"><div><p class="eyebrow eyebrow-dark">MY DOCUMENTS</p><h2 id="my-docs-list-heading">Uploaded documents</h2></div><span>{{ $profile->documents->count() }} FILES</span></div>
                    @forelse ($profile->documents as $document)
                        <article class="request-history-item">
                            <div>
                                <span class="detail-label">{{ str($document->type)->replace('_', ' ')->upper() }}</span>
                                <h3>{{ $document->title }}</h3>
                                <p>{{ $document->file_name }}@if ($document->file_size) · {{ number_format($document->file_size / 1024, 1) }} KB @endif</p>
                                @if ($document->notes)<p class="reviewer-note">{{ $document->notes }}</p>@endif
                            </div>
                            <a class="directory-profile-link" href="{{ $document->downloadUrl() }}" target="_blank" rel="noopener">View / Download</a>
                        </article>
                    @empty
                        <p class="profile-empty">No documents uploaded yet.</p>
                    @endforelse
                </section>

                <section class="profile-section" aria-labelledby="request-heading">
                    <div class="section-heading"><div><p class="eyebrow eyebrow-dark">HR REVIEW REQUIRED</p><h2 id="request-heading">Request an official correction</h2></div><span>CHANGES ARE NOT IMMEDIATE</span></div>
                    <form class="profile-form" method="POST" action="{{ route('profile.change-requests.store') }}">
                        @csrf
                        <div class="profile-form-grid">
                            <div class="field-group">
                                <label for="field_key">Official field</label>
                                <select id="field_key" name="field_key" required>
                                    <option value="">Choose a field</option>
                                    <option value="name" @selected(old('field_key') === 'name')>Name</option>
                                    <option value="department" @selected(old('field_key') === 'department')>Department</option>
                                    <option value="designation" @selected(old('field_key') === 'designation')>Designation</option>
                                    <option value="employment_status" @selected(old('field_key') === 'employment_status')>Employment status</option>
                                </select>
                                @error('field_key')<p class="field-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="field-group">
                                <label for="proposed_value">Requested value</label>
                                <input id="proposed_value" name="proposed_value" type="text" list="department-options" value="{{ old('proposed_value') }}" maxlength="255" required>
                                <datalist id="department-options">
                                    @foreach ($departments as $department)
                                        <option value="{{ $department->name }}"></option>
                                    @endforeach
                                </datalist>
                                @error('proposed_value')<p class="field-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="field-group profile-address-field">
                                <label for="reason">Reason for correction</label>
                                <textarea id="reason" name="reason" rows="3" maxlength="1000" required>{{ old('reason') }}</textarea>
                                @error('reason')<p class="field-error">{{ $message }}</p>@enderror
                            </div>
                        </div>
                        <button class="button button-primary profile-submit" type="submit">Submit for HR review <span aria-hidden="true">&#8594;</span></button>
                    </form>
                </section>

                <section class="profile-section" aria-labelledby="history-heading">
                    <div class="section-heading"><div><p class="eyebrow eyebrow-dark">REQUEST HISTORY</p><h2 id="history-heading">My correction requests</h2></div><span>{{ $changeRequests->count() }} TOTAL</span></div>
                    @forelse ($changeRequests as $changeRequest)
                        <article class="request-history-item">
                            <div>
                                <span class="detail-label">{{ str($changeRequest->field_key)->replace('_', ' ')->upper() }}</span>
                                <h3>{{ $changeRequest->current_value ?: 'Not recorded' }} <span aria-hidden="true">&#8594;</span> {{ $changeRequest->proposed_value }}</h3>
                                <p>{{ $changeRequest->reason }}</p>
                                @if ($changeRequest->reviewer_note)
                                    <p class="reviewer-note">HR note: {{ $changeRequest->reviewer_note }}</p>
                                @endif
                            </div>
                            <span class="request-status request-status-{{ $changeRequest->status }}">{{ str($changeRequest->status)->upper() }}</span>
                        </article>
                    @empty
                        <p class="profile-empty">No correction requests yet.</p>
                    @endforelse
                </section>
            </main>
        </div>
    </div>
@endsection
