@extends('layouts.app')

@section('title', isset($memo) ? 'Edit memo draft | NfLV Staff Portal' : 'Create memo | NfLV Staff Portal')

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
            <aside class="dashboard-rail" aria-label="Correspondence navigation">
                <p class="rail-label">CORRESPONDENCE</p>
                <a class="rail-link" href="{{ route('memos.index') }}">Inbox</a>
                <a class="rail-link rail-link-active" href="{{ route('memos.create') }}"><span class="rail-dot"></span> Create memo</a>
                <div class="rail-bottom"><span class="status-dot"></span> NORMAL CLASSIFICATION</div>
            </aside>

            <main class="dashboard-main">
                <div class="dashboard-kicker"><span>STAFF CORRESPONDENCE</span><a class="back-link" href="{{ route('memos.index') }}">Back to correspondence</a></div>
                <section class="dashboard-welcome">
                    <p class="eyebrow eyebrow-dark">{{ isset($memo) ? 'DRAFT' : 'NEW MEMO' }}</p>
                    <h1>{{ isset($memo) ? 'Edit draft' : 'Create memo' }}</h1>
                    <p>Published memos cannot be edited. A correction requires a new memo or withdrawal.</p>
                </section>

                @if ($errors->any())
                    <div class="profile-notice profile-notice-error" role="alert">
                        <strong>Review the memo details.</strong>
                        <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                @endif

                <section class="profile-section memo-compose" aria-labelledby="memo-details-heading">
                    <div class="section-heading"><div><p class="eyebrow eyebrow-dark">DRAFT WORKFLOW</p><h2 id="memo-details-heading">Memo details</h2></div><span>NO LIVE EMAIL SENT</span></div>
                    <form class="profile-form" method="POST" action="{{ isset($memo) ? route('memos.update', $memo) : route('memos.store') }}">
                        @csrf
                        @if (isset($memo))
                            @method('PATCH')
                        @endif
                        <div class="profile-form-grid">
                            <div class="field-group profile-address-field">
                                <label for="title">Subject</label>
                                <input id="title" name="title" type="text" value="{{ old('title', $memo->title ?? '') }}" maxlength="255" required>
                                @error('title')<p class="field-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="field-group profile-address-field">
                                <label for="body">Message</label>
                                <textarea id="body" name="body" rows="8" maxlength="50000" required>{{ old('body', $memo->body ?? '') }}</textarea>
                                @error('body')<p class="field-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="field-group">
                                <label for="classification">Classification</label>
                                <select id="classification" name="classification" required>
                                    <option value="normal" @selected(old('classification', $memo->classification ?? 'normal') === 'normal')>Normal</option>
                                </select>
                                <p class="field-help">Restricted and confidential classifications are unavailable until institutional approval rules are configured.</p>
                                @error('classification')<p class="field-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="field-group">
                                <label for="audience_type">Recipients</label>
                                <select id="audience_type" name="audience_type" required>
                                    <option value="individual" @selected(old('audience_type', $memo->audience_type ?? '') === 'individual')>Selected staff members</option>
                                    <option value="department" @selected(old('audience_type', $memo->audience_type ?? '') === 'department')>Department</option>
                                    @if (auth()->user()->role !== \App\UserRole::HeadOfDepartment)
                                        <option value="institution" @selected(old('audience_type', $memo->audience_type ?? '') === 'institution')>Institution-wide</option>
                                    @endif
                                </select>
                                @error('audience_type')<p class="field-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="field-group">
                                <label for="department_id">Department audience</label>
                                <select id="department_id" name="department_id">
                                    <option value="">Choose a department</option>
                                    @foreach ($departments as $department)
                                        <option value="{{ $department->id }}" @selected((string) old('department_id', $memo->audience_department_id ?? '') === (string) $department->id)>{{ $department->name }}</option>
                                    @endforeach
                                </select>
                                @error('department_id')<p class="field-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="field-group">
                                <label for="recipient_ids">Individual recipients</label>
                                <select id="recipient_ids" name="recipient_ids[]" multiple size="5">
                                    @foreach ($recipients as $recipient)
                                        <option value="{{ $recipient->id }}" @selected(in_array($recipient->id, old('recipient_ids', $memo->audience_user_ids ?? []), false))>{{ $recipient->name }} · {{ $recipient->staff_number }} · {{ $recipient->staffProfile?->department?->name }}</option>
                                    @endforeach
                                </select>
                                @error('recipient_ids')<p class="field-error">{{ $message }}</p>@enderror
                                @error('recipient_ids.*')<p class="field-error">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <label class="remember-option memo-ack-option" for="requires_acknowledgement">
                            <input id="requires_acknowledgement" name="requires_acknowledgement" type="checkbox" value="1" @checked(old('requires_acknowledgement', $memo->requires_acknowledgement ?? false))>
                            <span>Require recipients to acknowledge this memo</span>
                        </label>

                        <div class="import-safety-note">
                            <span class="eyebrow eyebrow-dark">RECIPIENT SNAPSHOT</span>
                            <p>Recipients are fixed when published. Staff who join a department later will not gain access to this memo.</p>
                        </div>

                        <div class="manual-entry-actions">
                            <a class="text-button" href="{{ route('memos.index') }}">Cancel</a>
                            <button class="button button-primary" type="submit">Save draft <span aria-hidden="true">&#8594;</span></button>
                        </div>
                    </form>
                </section>
            </main>
        </div>
    </div>
@endsection