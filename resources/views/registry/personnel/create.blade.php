@extends('layouts.app')

@section('title', 'Add staff member | NfLV Staff Portal')

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
                <a class="rail-link rail-link-active" href="{{ route('registry.personnel.create') }}"><span class="rail-dot"></span> Add staff member</a>
                <a class="rail-link" href="{{ route('registry.personnel-imports.create') }}">Personnel import</a>
                <a class="rail-link" href="{{ route('registry.profile-change-requests.index') }}">Profile changes</a>
                <div class="rail-bottom"><span class="status-dot"></span> ICT PROVISIONING REQUIRED</div>
            </aside>

            <main class="dashboard-main">
                <div class="dashboard-kicker"><span>PERSONNEL</span><a class="back-link" href="{{ route('registry.personnel.index') }}">Back to directory</a></div>
                <section class="dashboard-welcome">
                    <p class="eyebrow eyebrow-dark">MANUAL ENTRY</p>
                    <h1>Add staff member</h1>
                    <p>Enter an individual personnel record. New accounts remain suspended until ICT provisions access.</p>
                </section>

                @if ($errors->any())
                    <div class="profile-notice profile-notice-error" role="alert">
                        <strong>Review the highlighted fields.</strong>
                        <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                @endif

                <section class="profile-section" aria-labelledby="official-heading">
                    <div class="section-heading"><div><p class="eyebrow eyebrow-dark">OFFICIAL RECORD</p><h2 id="official-heading">Personnel details</h2></div><span>REQUIRED</span></div>
                    <form class="profile-form" method="POST" action="{{ route('registry.personnel.store') }}">
                        @csrf
                        <div class="profile-form-grid">
                            <div class="field-group">
                                <label for="staff_number">Staff number</label>
                                <input id="staff_number" name="staff_number" type="text" value="{{ old('staff_number') }}" maxlength="32" autocomplete="off" required>
                                @error('staff_number')<p class="field-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="field-group">
                                <label for="name">Full name</label>
                                <input id="name" name="name" type="text" value="{{ old('name') }}" maxlength="255" autocomplete="name" required>
                                @error('name')<p class="field-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="field-group">
                                <label for="email">Institutional email <span class="optional-label">OPTIONAL</span></label>
                                <input id="email" name="email" type="email" value="{{ old('email') }}" maxlength="255" autocomplete="email">
                                @error('email')<p class="field-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="field-group">
                                <label for="department_id">Department</label>
                                <select id="department_id" name="department_id" required>
                                    <option value="">Choose department</option>
                                    @foreach ($departments as $department)
                                        <option value="{{ $department->id }}" @selected((string) old('department_id') === (string) $department->id)>{{ $department->name }} · {{ $department->code }}</option>
                                    @endforeach
                                </select>
                                @error('department_id')<p class="field-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="field-group">
                                <label for="designation">Designation</label>
                                <input id="designation" name="designation" type="text" value="{{ old('designation') }}" maxlength="255" required>
                                @error('designation')<p class="field-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="field-group">
                                <label for="employment_status">Employment status</label>
                                <select id="employment_status" name="employment_status" required>
                                    <option value="">Choose status</option>
                                    @foreach (['employed' => 'Employed', 'on_leave' => 'On leave', 'seconded' => 'Seconded', 'retired' => 'Retired', 'terminated' => 'Terminated'] as $status => $label)
                                        <option value="{{ $status }}" @selected(old('employment_status', 'employed') === $status)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('employment_status')<p class="field-error">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="section-heading contact-section-heading"><div><p class="eyebrow eyebrow-dark">PERSONAL INFORMATION</p><h2>Contact details</h2></div><span>OPTIONAL</span></div>
                        <div class="profile-form-grid">
                            <div class="field-group">
                                <label for="phone_number">Mobile number</label>
                                <input id="phone_number" name="phone_number" type="tel" value="{{ old('phone_number') }}" maxlength="40" autocomplete="tel">
                                @error('phone_number')<p class="field-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="field-group">
                                <label for="personal_email">Personal email</label>
                                <input id="personal_email" name="personal_email" type="email" value="{{ old('personal_email') }}" maxlength="255">
                                @error('personal_email')<p class="field-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="field-group profile-address-field">
                                <label for="residential_address">Residential address</label>
                                <textarea id="residential_address" name="residential_address" rows="3" maxlength="1000">{{ old('residential_address') }}</textarea>
                                @error('residential_address')<p class="field-error">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="section-heading contact-section-heading"><div><p class="eyebrow eyebrow-dark">PERSONNEL DETAILS</p><h2>Service and personal record</h2></div><span>OPTIONAL</span></div>
                        <div class="profile-form-grid">
                            <div class="field-group">
                                <label for="gender">Gender</label>
                                <select id="gender" name="gender">
                                    <option value="">Not recorded</option>
                                    @foreach (['male' => 'Male', 'female' => 'Female', 'other' => 'Other'] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('gender') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('gender')<p class="field-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="field-group">
                                <label for="marital_status">Marital status</label>
                                <select id="marital_status" name="marital_status">
                                    <option value="">Not recorded</option>
                                    @foreach (['single' => 'Single', 'married' => 'Married', 'divorced' => 'Divorced', 'widowed' => 'Widowed', 'other' => 'Other'] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('marital_status') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('marital_status')<p class="field-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="field-group">
                                <label for="grade_level">Grade level</label>
                                <input id="grade_level" name="grade_level" type="text" value="{{ old('grade_level') }}" maxlength="40" placeholder="e.g. GL 08">
                                @error('grade_level')<p class="field-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="field-group">
                                <label for="unit">Unit</label>
                                <input id="unit" name="unit" type="text" value="{{ old('unit') }}" maxlength="120" placeholder="e.g. Personnel Unit">
                                @error('unit')<p class="field-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="field-group">
                                <label for="date_of_first_appointment">Date of first appointment</label>
                                <input id="date_of_first_appointment" name="date_of_first_appointment" type="date" value="{{ old('date_of_first_appointment') }}">
                                @error('date_of_first_appointment')<p class="field-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="field-group">
                                <label for="date_of_birth">Date of birth</label>
                                <input id="date_of_birth" name="date_of_birth" type="date" value="{{ old('date_of_birth') }}">
                                @error('date_of_birth')<p class="field-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="field-group">
                                <label for="next_of_kin">Next of kin</label>
                                <input id="next_of_kin" name="next_of_kin" type="text" value="{{ old('next_of_kin') }}" maxlength="255">
                                @error('next_of_kin')<p class="field-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="field-group">
                                <label for="next_of_kin_phone">Next of kin phone</label>
                                <input id="next_of_kin_phone" name="next_of_kin_phone" type="tel" value="{{ old('next_of_kin_phone') }}" maxlength="40">
                                @error('next_of_kin_phone')<p class="field-error">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="import-safety-note">
                            <span class="eyebrow eyebrow-dark">ACCESS CONTROL</span>
                            <p>This form cannot assign roles or activate accounts. The new record is created as a suspended staff account; ICT provisions access separately.</p>
                        </div>

                        <div class="manual-entry-actions">
                            <a class="text-button" href="{{ route('registry.personnel.index') }}">Cancel</a>
                            <button class="button button-primary" type="submit">Create staff record <span aria-hidden="true">&#8594;</span></button>
                        </div>
                    </form>
                </section>
            </main>
        </div>
    </div>
@endsection