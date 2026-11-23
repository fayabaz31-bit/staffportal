@extends('student.layout')
@section('student-content')
    <div class="sp-auth">
        <section class="sp-auth-intro">
            <p class="sp-eyebrow">NFLV STUDENT SERVICES</p>
            <h1>{{ $mode === 'login' ? 'Your studies, in one place.' : 'Account recovery' }}</h1>
            <p>Manage registration, view academic records, track your fees and get help from the Village.</p>
            <p>Use the student account issued by Registry. Your student number is shown on your registration documents.</p>
            <a href="{{ route('website.contact') }}">Contact NFLV for account assistance</a>
        </section>
        <section class="sp-panel">
            <h2>{{ match ($mode) { 'forgot' => 'Forgot your password?', 'reset' => 'Choose a new password', default => 'Student sign-in' } }}</h2>
            <form method="POST" action="{{ match ($mode) { 'forgot' => route('student.password.email'), 'reset' => route('student.password.save'), default => route('student.authenticate') } }}" class="sp-form">
                @csrf
                @if ($mode === 'login')
                    <label>Student number or email<input name="identifier" value="{{ old('identifier') }}" autocomplete="username" required autofocus>
                    </label>
                    <label>Password<input type="password" name="password" autocomplete="current-password" required>
                    </label>
                @else
                    <label>Email<input type="email" name="email" value="{{ old('email', $email ?? '') }}" autocomplete="email" required>
                    </label>
                @endif
                @if ($mode === 'reset')
                    <input type="hidden" name="token" value="{{ $token }}">
                    <label>New password (at least 12 characters)<input type="password" name="password" minlength="12" autocomplete="new-password" required>
                    </label>
                    <label>Confirm password<input type="password" name="password_confirmation" minlength="12" autocomplete="new-password" required>
                    </label>
                @endif
                <button type="submit" class="sp-button">{{ match ($mode) { 'forgot' => 'Send reset link', 'reset' => 'Reset password', default => 'Sign in' } }}</button>
                <a href="{{ $mode === 'login' ? route('student.password.request') : route('student.login') }}">{{ $mode === 'login' ? 'Forgot password?' : 'Back to sign-in' }}</a>
            </form>
        </section>
    </div>
@endsection
