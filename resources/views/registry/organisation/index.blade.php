@extends('layouts.app')

@section('title', 'Organisation structure | NfLV Staff Portal')

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
            <aside class="dashboard-rail" aria-label="Organisation navigation">
                <p class="rail-label">REGISTRY</p>
                <a class="rail-link" href="{{ route('dashboard') }}">Overview</a>
                <a class="rail-link" href="{{ route('assignments.index') }}">Duty assignments</a>
                <a class="rail-link" href="{{ route('registry.correspondence.index') }}">Correspondence</a>
                <a class="rail-link rail-link-active" href="{{ route('registry.organisation.index') }}"><span class="rail-dot"></span> Organisation</a>
                <div class="rail-bottom"><span class="status-dot"></span> CONFIGURATION</div>
            </aside>

            <main class="dashboard-main">
                <div class="dashboard-kicker">
                    <span>ORGANISATION STRUCTURE</span>
                    <span>{{ $units->count() }} units · {{ $reportingLines->count() }} reporting lines</span>
                </div>
                <section class="dashboard-welcome">
                    <p class="eyebrow eyebrow-dark">CONFIGURABLE HIERARCHY</p>
                    <h1>Units, reporting lines and delegations</h1>
                    <p>Reporting lines and acting appointments carry effective dates, so past decisions stay explainable after a transfer.</p>
                </section>

                @if (session('status'))
                    <p class="profile-notice" role="status">{{ session('status') }}</p>
                @endif
                @if ($errors->any())
                    <div class="profile-notice profile-notice-error" role="alert">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <section class="assignment-panel">
                    <h2>Organisational units</h2>
                    <div class="directory-table-wrap">
                        <table class="directory-table">
                            <thead>
                                <tr><th scope="col">Unit</th><th scope="col">Department</th><th scope="col">Head</th><th scope="col">Status</th></tr>
                            </thead>
                            <tbody>
                                @forelse ($units as $unit)
                                    <tr>
                                        <td><strong>{{ $unit->name }}</strong><span>{{ $unit->code }}@if ($unit->parent) · under {{ $unit->parent->name }}@endif</span></td>
                                        <td>{{ $unit->department?->name }}</td>
                                        <td>{{ $unit->head?->name ?? 'Not assigned' }}</td>
                                        <td><span class="directory-account-status directory-account-{{ $unit->is_active ? 'active' : 'deactivated' }}">{{ $unit->is_active ? 'Active' : 'Inactive' }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td class="directory-empty" colspan="4">No units configured yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <form class="panel-form panel-form-compact" method="POST" action="{{ route('registry.organisation.units.store') }}">
                        @csrf
                        <div class="form-grid">
                            <div class="form-field">
                                <label for="unit-department">Department</label>
                                <select id="unit-department" name="department_id" required>
                                    @foreach ($departments as $department)
                                        <option value="{{ $department->id }}">{{ $department->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-field">
                                <label for="unit-name">Unit name</label>
                                <input id="unit-name" name="name" type="text" maxlength="255" required placeholder="Personnel Unit">
                            </div>
                            <div class="form-field">
                                <label for="unit-code">Unit code</label>
                                <input id="unit-code" name="code" type="text" maxlength="40" required placeholder="REG-PERSONNEL">
                            </div>
                            <div class="form-field">
                                <label for="unit-head">Unit head</label>
                                <select id="unit-head" name="head_user_id">
                                    <option value="">Not assigned</option>
                                    @foreach ($officers as $officer)
                                        <option value="{{ $officer->id }}">{{ $officer->name }} — {{ $officer->staff_number }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <button class="button spend" type="submit">Add unit</button>
                    </form>
                </section>

                <section class="assignment-panel">
                    <h2>Reporting lines</h2>
                    <div class="directory-table-wrap">
                        <table class="directory-table">
                            <thead>
                                <tr><th scope="col">Officer</th><th scope="col">Reports to</th><th scope="col">Type</th><th scope="col">Effective</th><th scope="col">Action</th></tr>
                            </thead>
                            <tbody>
                                @forelse ($reportingLines as $line)
                                    <tr>
                                        <td>{{ $line->subordinate?->name }}</td>
                                        <td>{{ $line->supervisor?->name }}</td>
                                        <td>{{ str($line->relationship_type)->replace('_', ' ')->title() }}</td>
                                        <td>{{ $line->effective_from->format('d M Y') }} → {{ $line->effective_to?->format('d M Y') ?? 'open' }}</td>
                                        <td>
                                            <form class="inline-form" method="POST" action="{{ route('registry.organisation.reporting.end', $line) }}">
                                                @csrf
                                                @method('PATCH')
                                                <label class="sr-only" for="end-{{ $line->id }}">End date</label>
                                                <input id="end-{{ $line->id }}" name="effective_to" type="date" required>
                                                <button class="button" type="submit">End</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td class="directory-empty" colspan="5">No reporting lines configured. Duty assignment falls back to unit-lead scope until a line is recorded.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <form class="panel-form panel-form-compact" method="POST" action="{{ route('registry.organisation.reporting.store') }}">
                        @csrf
                        <div class="form-grid">
                            <div class="form-field">
                                <label for="reporting-subordinate">Officer</label>
                                <select id="reporting-subordinate" name="subordinate_id" required>
                                    @foreach ($officers as $officer)
                                        <option value="{{ $officer->id }}">{{ $officer->name }} — {{ $officer->staff_number }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-field">
                                <label for="reporting-supervisor">Reports to</label>
                                <select id="reporting-supervisor" name="supervisor_id" required>
                                    @foreach ($officers as $officer)
                                        <option value="{{ $officer->id }}">{{ $officer->name }} — {{ $officer->staff_number }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-field">
                                <label for="reporting-unit">Unit</label>
                                <select id="reporting-unit" name="organisational_unit_id">
                                    <option value="">Not unit-specific</option>
                                    @foreach ($units as $unit)
                                        <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-field">
                                <label for="reporting-type">Relationship</label>
                                <select id="reporting-type" name="relationship_type" required>
                                    <option value="line_manager">Line manager</option>
                                    <option value="acting">Acting appointment</option>
                                    <option value="delegated">Delegated authority</option>
                                </select>
                            </div>
                            <div class="form-field">
                                <label for="reporting-from">Effective from</label>
                                <input id="reporting-from" name="effective_from" type="date" value="{{ now()->toDateString() }}" required>
                            </div>
                            <div class="form-field">
                                <label for="reporting-to">Effective to</label>
                                <input id="reporting-to" name="effective_to" type="date">
                            </div>
                        </div>
                        <button class="button spend" type="submit">Record reporting line</button>
                    </form>
                </section>

                <section class="assignment-panel">
                    <h2>Active delegations</h2>
                    <div class="directory-table-wrap">
                        <table class="directory-table">
                            <thead>
                                <tr><th scope="col">Delegator</th><th scope="col">Delegate</th><th scope="col">Scope</th><th scope="col">Period</th><th scope="col">Action</th></tr>
                            </thead>
                            <tbody>
                                @forelse ($delegations as $delegation)
                                    <tr>
                                        <td>{{ $delegation->delegator?->name }}</td>
                                        <td>{{ $delegation->delegate?->name }}</td>
                                        <td>{{ $delegationScopes[$delegation->scope] ?? $delegation->scope }}</td>
                                        <td>{{ $delegation->starts_at->format('d M Y') }} → {{ $delegation->ends_at->format('d M Y') }}</td>
                                        <td>
                                            <form method="POST" action="{{ route('registry.organisation.delegations.revoke', $delegation) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button class="button review-reject" type="submit">Revoke</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td class="directory-empty" colspan="5">No active delegations.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <form class="panel-form panel-form-compact" method="POST" action="{{ route('registry.organisation.delegations.store') }}">
                        @csrf
                        <div class="form-grid">
                            <div class="form-field">
                                <label for="delegator">Delegating officer</label>
                                <select id="delegator" name="delegator_id" required>
                                    @foreach ($officers as $officer)
                                        <option value="{{ $officer->id }}">{{ $officer->name }} — {{ $officer->staff_number }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-field">
                                <label for="delegate">Acting officer</label>
                                <select id="delegate" name="delegate_id" required>
                                    @foreach ($officers as $officer)
                                        <option value="{{ $officer->id }}">{{ $officer->name }} — {{ $officer->staff_number }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-field">
                                <label for="delegation-scope">Scope</label>
                                <select id="delegation-scope" name="scope" required>
                                    @foreach ($delegationScopes as $scope => $label)
                                        <option value="{{ $scope }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-field">
                                <label for="delegation-unit">Unit</label>
                                <select id="delegation-unit" name="organisational_unit_id">
                                    <option value="">Institution-wide</option>
                                    @foreach ($units as $unit)
                                        <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-field">
                                <label for="delegation-start">Starts</label>
                                <input id="delegation-start" name="starts_at" type="datetime-local" required>
                            </div>
                            <div class="form-field">
                                <label for="delegation-end">Ends</label>
                                <input id="delegation-end" name="ends_at" type="datetime-local" required>
                            </div>
                        </div>
                        <div class="form-field">
                            <label for="delegation-reason">Reason</label>
                            <input id="delegation-reason" name="reason" type="text" maxlength="500" placeholder="Leave cover, acting appointment, etc.">
                        </div>
                        <button class="button spend" type="submit">Record delegation</button>
                    </form>
                </section>
            </main>
        </div>
    </div>
@endsection
