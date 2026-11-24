@extends('layouts.app')
@section('title', ($heading ?? 'Student portal').' | NFLV')
@section('description', 'Secure student services for the Nigeria French Language Village.')
@section('content')
<div class="sp-page">
    <a class="sp-skip" href="#student-main">Skip to content</a>
    <header class="sp-header">
        <a class="sp-brand" href="{{ route('student.login') }}">
            <img src="{{ Vite::asset('resources/images/nfvl-logo.jpg') }}" alt="NFLV" width="58" height="48">
            <span>Nigeria French Language Village<small>{{ ($administration ?? false) ? 'Student administration' : 'Student portal' }}</small>
            </span>
        </a>
        <div class="sp-account">
            @if ($administration ?? false)
                <a href="{{ route('dashboard') }}">Staff dashboard</a>
            @elseif (auth('student')->check())
                <span>{{ auth('student')->user()->student_number }}</span>
                <form method="POST" action="{{ route('student.logout') }}">@csrf
                    <button type="submit" class="sp-text-button">Sign out</button>
                </form>
            @else
                <a href="{{ route('website.preview') }}">Home</a>
            @endif
        </div>
    </header>
    <div class="sp-shell {{ auth('student')->check() && !($administration ?? false) ? '' : 'sp-shell-wide' }}">
        @if (auth('student')->check() && !($administration ?? false))
            <nav class="sp-nav" aria-label="Student services">
                @foreach (['dashboard' => 'Overview', 'profile' => 'My profile', 'registration' => 'Course registration', 'timetable' => 'Timetable & calendar', 'results' => 'Results & placement', 'fees' => 'Fees & receipts', 'documents' => 'My documents', 'accommodation' => 'Accommodation', 'notices' => 'Notices', 'support' => 'Help & requests', 'settings' => 'Security & preferences'] as $key => $label)
                    <a href="{{ $key === 'dashboard' ? route('student.dashboard') : route('student.section', $key) }}"
                    @if (($section ?? '') === $key)
                        aria-current="page"
                    @endif
                    >{{ $label }}</a>
            @endforeach
        </nav>
    @endif
    <main class="sp-main" id="student-main">
        @if (session('status'))
            <div class="sp-feedback" role="status">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="sp-errors" role="alert">
                <strong>Please check the following</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @yield('student-content')
        <footer class="sp-footer">Nigeria French Language Village · Badagry, Lagos State</footer>
    </main>
</div>
</div>
@endsection
