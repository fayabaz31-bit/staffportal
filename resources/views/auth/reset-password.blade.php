@extends('layouts.portal-entry', ['audience' => 'staff', 'mode' => 'reset'])

@section('title', 'Choose a new password | NfLV Staff Portal')

@section('portal-form')
    <form class="signin-form" method="POST" action="{{ route('password.store') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div class="field-group">
            <label for="email">Institutional email</label>
            <input id="email" name="email" type="email" value="{{ old('email', $email) }}" autocomplete="email" aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}" required>
        </div>
        <div class="field-group">
            <label for="password">New password</label>
            <input id="password" name="password" type="password" minlength="8" autocomplete="new-password" aria-describedby="staff-password-help" required>
            <p class="entry-field-help" id="staff-password-help">Use at least 8 characters.</p>
        </div>
        <div class="field-group">
            <label for="password_confirmation">Confirm new password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" minlength="8" autocomplete="new-password" required>
        </div>
        <button class="button button-primary" type="submit">Reset password</button>
        <p class="entry-recovery"><a href="{{ route('login') }}">Back to staff sign-in</a></p>
    </form>
@endsection
