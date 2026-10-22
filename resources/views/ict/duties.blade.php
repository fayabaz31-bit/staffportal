@extends('layouts.app')

@section('title', 'Duty assignment | NfLV Staff Portal')

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
            <aside class="dashboard-rail" aria-label="Navigation">
                <p class="rail-label">DUTY ASSIGNMENT</p>
                <a class="rail-link" href="{{ route('dashboard') }}">Overview</a>
                @can('manage-accounts')
                    <a class="rail-link" href="{{ route('ict.accounts.index') }}">Account operations</a>
                @endcan
                <div class="rail-divider"></div>
                <div class="rail-bottom"><span class="status-dot"></span> SEPARATION OF DUTIES</div>
            </aside>

            <main class="dashboard-main">
                <div class="dashboard-kicker"><span>DUTIES / DESKS</span><span>{{ $account->staff_number }}</span></div>
                <section class="dashboard-welcome">
                    <p class="eyebrow eyebrow-dark">ASSIGNMENT CONTROL</p>
                    <h1>{{ $account->name }}</h1>
                    <p>
                        Role: <strong>{{ $account->role->label() }}</strong> ·
                        Department: <strong>{{ $account->staffProfile?->department?->name ?? 'Unassigned' }}</strong>
                    </p>
                </section>

                @if (session('status'))
                    <p class="profile-notice" role="status">{{ session('status') }}</p>
                @endif
                @if (session('error'))
                    <p class="profile-notice" role="alert" style="color: #b91c1c; border-color: #fecaca; background: #fef2f2;">{{ session('error') }}</p>
                @endif

                @php($conflicts = $account->separationOfDutyConflicts())
                @if (! empty($conflicts))
                    <p class="profile-notice" role="alert" style="color: #b45309; border-color: #fde68a; background: #fffbeb;">
                        <strong>Separation-of-duties warning:</strong> this account holds incompatible duties ({{ implode(', ', $conflicts) }}).
                        Withdraw one so a different officer approves the release.
                    </p>
                @endif

                <div style="display: grid; gap: 20px; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); margin-top: 16px;">
                    <section>
                        <h2 style="font-size: 13px; letter-spacing: 0.08em; text-transform: uppercase; color: #4b5563;">Current duties</h2>
                        @forelse ($account->duties as $duty)
                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px; padding: 8px 0; border-bottom: 1px solid #f3f4f6;">
                                <span style="font-size: 12px;">{{ $duty->label }}</span>
                                <form method="POST" action="{{ route('ict.accounts.duties.revoke', $account) }}">
                                    @csrf
                                    <input type="hidden" name="duty" value="{{ $duty->code }}">
                                    <button class="button" type="submit" style="cursor: pointer; min-height: 26px; padding: 0 8px; font-size: 10px; background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca;">Withdraw</button>
                                </form>
                            </div>
                        @empty
                            <p style="font-size: 12px; color: #6b7280;">No duties assigned.</p>
                        @endforelse
                    </section>

                    <section>
                        <h2 style="font-size: 13px; letter-spacing: 0.08em; text-transform: uppercase; color: #4b5563;">Assign a duty</h2>
                        @unless ($mayAssignAdministrative)
                            <p style="font-size: 11px; color: #92400e; background: #fffbeb; border: 1px solid #fde68a; padding: 8px; border-radius: 6px;">
                                You are assigning within your own unit. Account-wide administrative duties (accounts, audit, duty lead) can only be granted by ICT or an Institutional Superadmin.
                            </p>
                        @endunless
                        <form method="POST" action="{{ route('ict.accounts.duties.grant', $account) }}" style="margin-top: 8px;">
                            @csrf
                            <div class="field-group">
                                <label for="duty">Duty</label>
                                <select id="duty" name="duty">
                                    @foreach ($assignable as $unit => $unitDuties)
                                        <optgroup label="{{ ucfirst($unit) }}">
                                            @foreach ($unitDuties as $duty)
                                                @php($held = $account->hasDuty($duty->code))
                                                <option value="{{ $duty->code }}" @disabled($held)>{{ $duty->label }}{{ $held ? ' (already held)' : '' }}</option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                            </div>
                            <button class="button button-primary" type="submit">Assign duty</button>
                        </form>
                    </section>
                </div>
            </main>
        </div>
    </div>
@endsection
