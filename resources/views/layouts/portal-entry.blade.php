@extends('layouts.app')

@php
    $isStudent = $audience === 'student';
    $mode = $mode ?? 'login';
    $portalName = $isStudent ? 'Student portal' : 'Staff portal';
    $entryRoute = $isStudent ? route('student.login') : route('home');
@endphp

@section('description', 'Access '.strtolower($portalName).' services at the Nigeria French Language Village, Badagry.')

@section('content')
    <div class="entry-page {{ $isStudent ? 'entry-student' : 'entry-staff' }}">
        <a class="entry-skip" href="#portal-signin">Skip to sign-in</a>
        <div class="entry-topline">
            <div class="entry-container">
                <span>Inter-University Centre for French Studies</span>
                <span>Badagry, Nigeria</span>
            </div>
        </div>
        <header class="entry-header">
            <div class="entry-container">
                <a class="entry-brand" href="{{ $entryRoute }}" aria-label="{{ $portalName }} home">
                    <img src="{{ Vite::asset('resources/images/nfvl-logo.jpg') }}" alt="NFLV" width="76" height="64">
                    <span>Nigeria French<br>Language Village</span>
                </a>
                <nav aria-label="Portal navigation">
                    <a href="{{ route('website.preview') }}">Home</a>
                    <a href="{{ route('website.contact') }}">Contact &amp; help</a>
                </nav>
            </div>
        </header>
        <main class="entry-stage">
            <div class="entry-photograph" aria-hidden="true">
                <img src="{{ asset($isStudent ? 'media/hero-community.jpg' : 'media/campus-brick-building.jpg') }}" alt="" fetchpriority="high">
            </div>
            <div class="entry-container entry-grid">
                <div class="entry-left">
                    <section class="entry-introduction" aria-labelledby="portal-heading">
                        <p>NIGERIA FRENCH LANGUAGE VILLAGE</p>
                        <h1 id="portal-heading">{{ $portalName }}</h1>
                        <div class="entry-intro-copy">
                            @if ($isStudent)
                                <p>Registration, academic records and student services.</p>
                            @else
                                <p>Staff records, correspondence and administrative services.</p>
                            @endif
                        </div>
                    </section>
                    <section class="entry-services" aria-labelledby="portal-services-heading">
                        <h2 id="portal-services-heading">{{ $isStudent ? 'Before you sign in' : 'Staff services' }}</h2>
                        <div class="entry-service-links">
                            @if ($isStudent)
                                <a href="{{ route('website.programmes') }}"><span>Explore programmes</span><small>French courses and study options</small></a>
                                <a href="{{ route('website.admissions') }}"><span>Admissions information</span><small>Applications and registration guidance</small></a>
                                <a href="{{ route('website.fees') }}"><span>Fees &amp; payments</span><small>Payment information and assistance</small></a>
                                <a href="{{ route('website.contact') }}"><span>Student support</span><small>Contact the Village for help</small></a>
                            @else
                                <a href="{{ route('profile.show') }}"><span>Personal records</span><small>Profile details and corrections</small></a>
                                <a href="{{ route('memos.index') }}"><span>Correspondence</span><small>Official memos and circulars</small></a>
                                <a href="{{ route('leave.index') }}"><span>Leave applications</span><small>Requests and approval progress</small></a>
                                <a href="{{ route('payslips.index') }}"><span>Payslips &amp; documents</span><small>Your published staff documents</small></a>
                            @endif
                        </div>
                    </section>
                </div>
                <section class="entry-access" id="portal-signin" aria-labelledby="entry-form-heading">
                    <div class="entry-access-heading">
                        <p>{{ $mode === 'login' ? 'ACCOUNT ACCESS' : 'ACCOUNT RECOVERY' }}</p>
                        <h2 id="entry-form-heading">{{ match ($mode) { 'forgot' => 'Forgot your password?', 'reset' => 'Set a new password', default => $isStudent ? 'Student sign-in' : 'Staff sign-in' } }}</h2>
                        <p>{{ $mode === 'login' ? ($isStudent ? 'Use your student number or registered email.' : 'Use your Staff ID or institutional email.') : ($isStudent ? 'Recover access to your student account.' : 'Recover access to your staff account.') }}</p>
                    </div>
                    @if (session('status'))
                        <p class="entry-status" role="status">{{ session('status') }}</p>
                    @endif
                    @if ($errors->any())
                        <div class="entry-errors" role="alert">
                            <p>Please check your details.</p>
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    @yield('portal-form')
                    <div class="entry-assistance">
                        <h3>{{ $isStudent ? 'Need your account details?' : 'Need access to your account?' }}</h3>
                        <p>{{ $isStudent ? 'Registry issues student accounts. Your student number is shown on your registration documents.' : 'Contact the ICT Unit if you need a staff account or help with your sign-in details.' }}</p>
                        <a href="{{ route('website.contact') }}">Get account assistance</a>
                    </div>
                    <p class="entry-switch">
                        {{ $isStudent ? 'For members of staff' : 'For students' }}
                        <a href="{{ $isStudent ? route('login') : route('student.login') }}">{{ $isStudent ? 'Staff sign-in' : 'Student sign-in' }}</a>
                    </p>
                </section>
            </div>
        </main>
        <footer class="entry-footer">
            <div class="entry-container">
                <span>© {{ now()->year }} Nigeria French Language Village</span>
                <a href="{{ route('website.about') }}">About NFLV</a>
            </div>
        </footer>
    </div>
@endsection
