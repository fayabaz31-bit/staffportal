@extends('layouts.portal-entry', ['audience' => 'student'])

@section('title', match ($mode) {
    'forgot' => 'Password recovery | NFLV Student Portal',
    'reset' => 'Reset password | NFLV Student Portal',
    default => 'NFLV Student Portal',
})

@section('portal-form')
    <form method="POST" action="{{ match ($mode) { 'forgot' => route('student.password.email'), 'reset' => route('student.password.save'), default => route('student.authenticate') } }}" class="signin-form">
        @csrf
        @if ($mode === 'login')
            <div class="field-group">
                <label for="student-identifier">Student number or email</label>
                <input id="student-identifier" name="identifier" value="{{ old('identifier') }}" autocomplete="username" autocapitalize="none" spellcheck="false" aria-invalid="{{ $errors->has('identifier') ? 'true' : 'false' }}" required>
            </div>
            <div class="field-group">
                <label for="student-password">Password</label>
                <input id="student-password" type="password" name="password" autocomplete="current-password" aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}" required>
            </div>
        @else
            <div class="field-group">
                <label for="student-email">Registered email</label>
                <input id="student-email" type="email" name="email" value="{{ old('email', $email ?? '') }}" autocomplete="email" aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}" required>
            </div>
        @endif
        @if ($mode === 'reset')
            <input type="hidden" name="token" value="{{ $token }}">
            <div class="field-group">
                <label for="student-new-password">New password</label>
                <input id="student-new-password" type="password" name="password" minlength="12" autocomplete="new-password" required aria-describedby="student-password-help">
                <p class="entry-field-help" id="student-password-help">Use at least 12 characters.</p>
            </div>
            <div class="field-group">
                <label for="student-password-confirmation">Confirm password</label>
                <input id="student-password-confirmation" type="password" name="password_confirmation" minlength="12" autocomplete="new-password" required>
            </div>
        @endif
        <button type="submit" class="button button-primary">{{ match ($mode) { 'forgot' => 'Send reset link', 'reset' => 'Reset password', default => 'Sign in' } }}</button>
        <p class="entry-recovery">
            <a href="{{ $mode === 'login' ? route('student.password.request') : route('student.login') }}">{{ $mode === 'login' ? 'Forgot your password?' : 'Back to student sign-in' }}</a>
        </p>
    </form>
@endsection
