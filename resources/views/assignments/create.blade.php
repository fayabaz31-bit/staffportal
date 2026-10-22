@extends('layouts.app')

@section('title', 'Assign a duty | NfLV Staff Portal')

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
            <aside class="dashboard-rail" aria-label="Duty navigation">
                <p class="rail-label">ASSIGNED WORK</p>
                <a class="rail-link" href="{{ route('dashboard') }}">Overview</a>
                <a class="rail-link" href="{{ route('assignments.index') }}">My duties</a>
                <a class="rail-link rail-link-active" href="{{ route('assignments.create') }}"><span class="rail-dot"></span> Assign a duty</a>
                <div class="rail-bottom"><span class="status-dot"></span> SUPERVISOR SCOPE</div>
            </aside>

            <main class="dashboard-main">
                <div class="dashboard-kicker">
                    <span>NEW ASSIGNMENT</span>
                    <a class="back-link" href="{{ route('assignments.index', ['scope' => 'given']) }}">Back to duties</a>
                </div>
                <section class="dashboard-welcome">
                    <p class="eyebrow eyebrow-dark">SUPERVISOR ACTION</p>
                    <h1>Assign a duty</h1>
                    <p>You may assign work only to officers you supervise, directly or through your configured unit.</p>
                </section>

                @if ($errors->any())
                    <div class="profile-notice profile-notice-error" role="alert">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form class="panel-form" method="POST" action="{{ route('assignments.store') }}">
                    @csrf

                    <div class="form-field">
                        <label for="title">Title</label>
                        <input id="title" name="title" type="text" value="{{ old('title') }}" maxlength="255" required>
                    </div>

                    <div class="form-field">
                        <label for="assigned_to">Assign to</label>
                        <select id="assigned_to" name="assigned_to" required>
                            <option value="">Select an officer</option>
                            @foreach ($assignable as $officer)
                                <option value="{{ $officer->id }}" @selected(old('assigned_to', $defaultAssignee) == $officer->id)>
                                    {{ $officer->name }} — {{ $officer->staff_number }}@if ($officer->staffProfile?->department) · {{ $officer->staffProfile->department->name }}@endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-grid">
                        <div class="form-field">
                            <label for="priority">Priority</label>
                            <select id="priority" name="priority" required>
                                @foreach ($priorities as $value => $label)
                                    <option value="{{ $value }}" @selected(old('priority', 'normal') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-field">
                            <label for="due_at">Deadline</label>
                            <input id="due_at" name="due_at" type="datetime-local" value="{{ old('due_at') }}">
                        </div>

                        <div class="form-field">
                            <label for="confidentiality">Confidentiality</label>
                            <select id="confidentiality" name="confidentiality" required>
                                <option value="internal" @selected(old('confidentiality') === 'internal')>Internal</option>
                                <option value="confidential" @selected(old('confidentiality') === 'confidential')>Confidential</option>
                                <option value="restricted" @selected(old('confidentiality') === 'restricted')>Restricted</option>
                            </select>
                        </div>

                        <div class="form-field">
                            <label for="department_id">Department</label>
                            <select id="department_id" name="department_id">
                                <option value="">Use the officer's department</option>
                                @foreach ($departments as $department)
                                    <option value="{{ $department->id }}" @selected(old('department_id') == $department->id)>{{ $department->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-field">
                        <label for="instructions">Instructions</label>
                        <textarea id="instructions" name="instructions" rows="6" maxlength="5000" required>{{ old('instructions') }}</textarea>
                    </div>

                    <div class="form-field">
                        <label for="required_deliverables">Required deliverables</label>
                        <textarea id="required_deliverables" name="required_deliverables" rows="3" maxlength="3000" placeholder="What must be produced before the work can be accepted">{{ old('required_deliverables') }}</textarea>
                    </div>

                    <button class="button button-primary" type="submit">Assign duty</button>
                </form>
            </main>
        </div>
    </div>
@endsection
