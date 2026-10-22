@extends('layouts.app')

@section('title', 'Staff dashboard | NfLV Staff Portal')

@php
    $dutyCodes = $user->dutyCodes();
@endphp

@section('content')
    <div class="dashboard-page">
        <header class="dashboard-header">
            <a class="wordmark dashboard-wordmark" href="{{ route('dashboard') }}">
                <span class="wordmark-name">NfLV</span>
                <span class="wordmark-divider" aria-hidden="true"></span>
                <span class="wordmark-caption">Staff Portal</span>
            </a>
            <div class="account-tools">
                <a class="text-button" href="{{ route('notifications.index') }}">Notifications @if ($unreadCount > 0)<span aria-hidden="true">({{ $unreadCount }})</span>@endif</a>
                <span class="account-name">{{ $user->name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="text-button" type="submit">
                        @env(['local', 'testing'])
                            Switch demo role
                        @else
                            Sign out
                        @endenv
                    </button>
                </form>
            </div>
        </header>

        <div class="dashboard-layout">
            <aside class="dashboard-rail" aria-label="Role dashboard navigation">
                <p class="rail-label">{{ $role->label() }}</p>
                <a class="rail-link rail-link-active" href="{{ route('dashboard') }}"><span class="rail-dot"></span> Overview</a>
                <a class="rail-link" href="{{ route('notifications.index') }}">Notifications @if ($unreadCount > 0)<small>{{ $unreadCount }} NEW</small>@endif</a>
                <a class="rail-link" href="{{ route('profile.show') }}">My profile</a>
                @if ($role === \App\UserRole::HrRegistryAdmin)
                    <a class="rail-link" href="{{ route('registry.profile-change-requests.index') }}">Review profile changes</a>
                @endif
                @if ($canAssignDuties)
                    <a class="rail-link" href="{{ route('ict.team-duties.index') }}">Team duties</a>
                @endif
                <div class="rail-divider"></div>
                <p class="rail-label">ROLE AREAS</p>
                @foreach ($services as $service)
                    @if (($service['route_name'] ?? null) !== null)
                        <a class="rail-link rail-link-ready" href="{{ route($service['route_name']) }}">{{ $service['title'] }} <small>OPEN</small></a>
                    @else
                        <span class="rail-link rail-link-muted">{{ $service['title'] }} <small>{{ $service['status'] }}</small></span>
                    @endif
                @endforeach
                <div class="rail-bottom"><span class="status-dot"></span> ACCOUNT ACTIVE</div>
            </aside>

            <main class="dashboard-main">
                <div class="dashboard-kicker"><span>{{ $role->dashboardTitle() }}</span><span>{{ now()->format('d M Y') }}</span></div>

                <section class="dashboard-welcome dashboard-welcome-compact">
                    <div>
                        <p class="eyebrow eyebrow-dark">{{ $role->label() }}</p>
                        <h1>Welcome, {{ $user->name }}.</h1>
                        <p>Your workspace for today, scoped to your role and assigned duties.</p>
                    </div>
                </section>

                <section class="status-bar" aria-label="Your account status">
                    <div class="status-bar-item">
                        <span class="detail-label">STAFF NUMBER</span>
                        <strong>{{ $user->staff_number }}</strong>
                    </div>
                    <div class="status-bar-item">
                        <span class="detail-label">ROLE</span>
                        <strong>{{ $role->label() }}</strong>
                    </div>
                    <div class="status-bar-item">
                        <span class="detail-label">DUTIES</span>
                        <strong>{{ count($dutyCodes) }} active</strong>
                    </div>
                    <div class="status-bar-item">
                        <span class="detail-label">NOTIFICATIONS</span>
                        <strong>{{ $unreadCount }} unread</strong>
                    </div>
                    <div class="status-bar-item">
                        <span class="detail-label">STATUS</span>
                        <strong class="active-status"><i></i> Active</strong>
                    </div>
                </section>

                <x-dashboard.action-queue :items="$metrics['actionQueue']" />

                <section class="metrics-section" aria-labelledby="metrics-heading">
                    <div class="section-heading">
                        <div>
                            <p class="eyebrow eyebrow-dark">AT A GLANCE</p>
                            <h2 id="metrics-heading">Key figures</h2>
                        </div>
                        <span>LIVE</span>
                    </div>
                    <div class="kpi-grid">
                        @foreach ($metrics['kpis'] as $kpi)
                            <x-dashboard.kpi
                                :label="$kpi['label']"
                                :value="$kpi['value']"
                                :hint="$kpi['hint']"
                                :tone="$kpi['tone']"
                                :route="$kpi['route'] ?? null"
                            />
                        @endforeach
                    </div>
                </section>

                @if (! empty($metrics['charts']))
                    <section class="metrics-section" aria-labelledby="charts-heading">
                        <div class="section-heading">
                            <div>
                                <p class="eyebrow eyebrow-dark">ANALYSIS</p>
                                <h2 id="charts-heading">Operational overview</h2>
                            </div>
                            <span>REAL DATA</span>
                        </div>
                        <div class="chart-grid">
                            @foreach ($metrics['charts'] as $panel)
                                <x-dashboard.chart :panel="$panel" />
                            @endforeach
                        </div>
                    </section>
                @endif

                <section class="dashboard-services" aria-labelledby="services-heading">
                    <div class="section-heading"><div><p class="eyebrow eyebrow-dark">{{ $role->label() }}</p><h2 id="services-heading">Role areas</h2></div><span>PREVIEW</span></div>
                    <div class="dashboard-service-grid">
                        @foreach ($services as $index => $service)
                            <article class="dashboard-service">
                                <span class="service-index">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }} / {{ $service['category'] }}</span>
                                <h3>{{ $service['title'] }}</h3>
                                <p>{{ $service['description'] }}</p>
                                @if (($service['route_name'] ?? null) !== null)
                                    <a class="service-action" href="{{ route($service['route_name']) }}">Open area <span aria-hidden="true">&#8594;</span></a>
                                @else
                                    <span class="availability {{ $service['status'] === 'POLICY CONFIGURATION PENDING' ? 'availability-pending' : '' }}">{{ $service['status'] }}</span>
                                @endif
                            </article>
                        @endforeach
                    </div>
                </section>
                <footer class="dashboard-footer">NfLV STAFF PORTAL <span>·</span> Role preview only. Unbuilt areas are not accessible.</footer>
            </main>
        </div>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/charts.js')
@endpush
