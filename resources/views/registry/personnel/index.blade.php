@extends('layouts.app')

@section('title', 'Staff directory | NfLV Staff Portal')

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
                <div class="rail-bottom"><span class="status-dot"></span> REGISTRY SCOPE</div>
            </aside>

            <main class="dashboard-main">
                <div class="dashboard-kicker"><span>PERSONNEL</span><span>{{ $staffProfiles->total() }} records</span></div>
                <section class="dashboard-welcome">
                    <p class="eyebrow eyebrow-dark">HR / REGISTRY</p>
                    <h1>Staff directory</h1>
                    <p>{{ auth()->user()->can('manage-personnel-records') ? 'Personnel records within Registry scope.' : 'Read-only access to full personnel records for authorised Registry leadership.' }}</p>
                </section>

                @if (session('status'))
                    <p class="profile-notice" role="status">{{ session('status') }}</p>
                @endif

                <div class="directory-toolbar">
                    <form class="directory-search" method="GET" action="{{ route('registry.personnel.index') }}">
                        <label class="sr-only" for="directory-search">Search staff</label>
                        <input id="directory-search" name="search" type="search" value="{{ $search }}" placeholder="Search by staff number, name, or department">
                        <button class="button directory-search-button" type="submit">Search</button>
                    </form>
                    @can('manage-personnel-records')
                        <a class="button directory-add-button" href="{{ route('registry.personnel.create') }}">Add staff member</a>
                        <a class="button button-primary directory-import-button" href="{{ route('registry.personnel-imports.create') }}">Import personnel <span aria-hidden="true">&#8594;</span></a>
                    @endcan
                </div>

                <div class="scope-tabs" aria-label="Filter by department">
                    <a class="scope-tab {{ $departmentCode === '' ? 'scope-tab-active' : '' }}" href="{{ route('registry.personnel.index', array_filter(['search' => $search])) }}">All departments</a>
                    @foreach ($departments as $department)
                        <a class="scope-tab {{ $departmentCode === $department->code ? 'scope-tab-active' : '' }}" href="{{ route('registry.personnel.index', array_filter(['search' => $search, 'department' => $department->code])) }}">
                            {{ $department->name }} <span>{{ $departmentCounts[$department->id] ?? 0 }}</span>
                        </a>
                    @endforeach
                </div>

                <div class="directory-table-wrap">
                    <table class="directory-table">
                        <thead>
                            <tr>
                                <th scope="col">Staff member</th>
                                <th scope="col">Designation</th>
                                <th scope="col">Grade</th>
                                <th scope="col">Employment</th>
                                <th scope="col">Account</th>
                                <th scope="col">Profile</th>
                            </tr>
                        </thead>
                        @forelse ($groupedProfiles as $code => $profiles)
                            <tbody>
                                <tr class="directory-group-row">
                                    <th colspan="6" scope="colgroup">
                                        @php($groupDepartment = $profiles->first()->department)
                                        @if ($groupDepartment === null)
                                            Not assigned
                                        @else
                                            {{ $groupDepartment->name }}
                                            @unless (in_array($groupDepartment->code, \App\DepartmentCatalog::codes(), true))
                                                <em class="directory-group-warning">unmapped department</em>
                                            @endunless
                                        @endif
                                        <span>{{ $profiles->count() }} on this page</span>
                                    </th>
                                </tr>
                                @foreach ($profiles as $profile)
                                    <tr>
                                        <td>
                                            <a class="directory-person-link" href="{{ route('registry.personnel.show', $profile) }}" aria-label="View full profile for {{ $profile->user->name }}"><strong>{{ $profile->user->name }}</strong></a>
                                            <span>{{ $profile->user->staff_number }} · {{ $profile->user->email ?? 'No institutional email' }}</span>
                                        </td>
                                        <td>{{ $profile->designation ?? 'Not recorded' }}@if ($profile->unit)<span>{{ $profile->unit }}</span>@endif</td>
                                        <td>{{ $profile->grade_level ?? '—' }}</td>
                                        <td>{{ str($profile->employment_status)->replace('_', ' ')->title() }}</td>
                                        <td><span class="directory-account-status directory-account-{{ $profile->user->status }}">{{ str($profile->user->status)->title() }}</span></td>
                                        <td><a class="directory-profile-link" href="{{ route('registry.personnel.show', $profile) }}" aria-label="View full profile for {{ $profile->user->name }}">View profile</a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        @empty
                            <tbody>
                                <tr><td class="directory-empty" colspan="6">No personnel records match this search.</td></tr>
                            </tbody>
                        @endforelse
                    </table>
                </div>
                {{ $staffProfiles->links() }}
            </main>
        </div>
    </div>
@endsection