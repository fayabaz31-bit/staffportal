@extends('layouts.app')

@section('title', 'Team duties | NfLV Staff Portal')

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
                <p class="rail-label">TEAM DUTIES</p>
                <a class="rail-link" href="{{ route('dashboard') }}">Overview</a>
                @can('manage-accounts')
                    <a class="rail-link" href="{{ route('ict.accounts.index') }}">Account operations</a>
                @endcan
                <div class="rail-divider"></div>
                <div class="rail-bottom"><span class="status-dot"></span> ASSIGNMENT CONTROL</div>
            </aside>

            <main class="dashboard-main">
                <div class="dashboard-kicker">
                    <span>TEAM DUTIES / DESKS</span>
                    <span>{{ strtoupper($unit ?? 'INSTITUTION') }}</span>
                </div>
                <section class="dashboard-welcome">
                    <p class="eyebrow eyebrow-dark">ASSIGNMENT CONTROL</p>
                    <h1>Assign duties to your team</h1>
                    <p>
                        @if ($managesInstitutionWide)
                            You may manage duties for any account in the institution.
                        @else
                            You may assign and withdraw duties for colleagues in your own unit. Account-wide
                            administrative duties can only be granted by ICT or an Institutional Superadmin.
                        @endif
                    </p>
                </section>

                @if (session('status'))
                    <p class="profile-notice" role="status">{{ session('status') }}</p>
                @endif
                @if (session('error'))
                    <p class="profile-notice" role="alert" style="color: #b91c1c; border-color: #fecaca; background: #fef2f2;">{{ session('error') }}</p>
                @endif

                @if ($team->isEmpty())
                    <p class="profile-notice" role="status">
                        No colleagues are available for you to manage. A unit lead can assign duties only to
                        colleagues who share the same department and unit.
                    </p>
                @else
                    <div class="dashboard-service-grid" style="margin-top: 16px;">
                        @foreach ($team as $member)
                            <article class="dashboard-service">
                                <span class="service-index">{{ $member->staffProfile?->department?->name ?? 'UNASSIGNED DEPARTMENT' }}</span>
                                <h3>{{ $member->name }}</h3>
                                <p style="font-size: 11px; color: #6b7280;">
                                    {{ $member->staff_number }} · {{ $member->role->label() }}
                                </p>
                                @php($held = $member->dutyCodes())
                                @if (empty($held))
                                    <p style="font-size: 11px; color: #6b7280;">No duties assigned</p>
                                @else
                                    <div style="display: flex; flex-wrap: wrap; gap: 4px; margin: 6px 0;">
                                        @foreach ($held as $code)
                                            <span style="font-size: 10px; font-weight: 600; color: #173d35; background: #ecfdf5; border: 1px solid #a7f3d0; padding: 2px 6px; border-radius: 9999px;">{{ $code }}</span>
                                        @endforeach
                                    </div>
                                @endif
                                <a class="service-action" href="{{ route('ict.accounts.duties.index', $member) }}">
                                    Manage duties <span aria-hidden="true">&#8594;</span>
                                </a>
                            </article>
                        @endforeach
                    </div>
                @endif

                <footer class="dashboard-footer">NfLV STAFF PORTAL <span>·</span> Every duty change is recorded in the audit trail.</footer>
            </main>
        </div>
    </div>
@endsection
