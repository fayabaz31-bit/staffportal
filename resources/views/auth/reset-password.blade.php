@extends('layouts.app')

@section('title', 'Choose a new password | NfLV Staff Portal')

@section('content')
    <main class="login-page">
        <a class="wordmark login-wordmark" href="{{ route('home') }}">
            <span class="wordmark-name">NfLV</span>
            <span class="wordmark-divider" aria-hidden="true"></span>
            <span class="wordmark-caption">Staff Portal</span>
        </a>
        <section class="login-card" aria-labelledby="reset-heading">
            <p class="eyebrow eyebrow-dark">PASSWORD RECOVERY</p>
            <h1 id="reset-heading">Choose a new<br>password.</h1>
            <p class="login-intro">Use at least 8 characters. A strong, unique password is recommended.</p>

            <form class="signin-form" method="POST" action="{{ route('password.store') }}">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div class="field-group">
                    <label for="email">Institutional email</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $email) }}" autocomplete="email" required>
                    @error('email')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="field-group">
                    <label for="password">New password</label>
                    <input id="password" name="password" type="password" autocomplete="new-password" required>
                    @error('password')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="field-group">
                    <label for="password_confirmation">Confirm new password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
                </div>

                <button class="button button-primary" type="submit">Reset password <span aria-hidden="true">&#8594;</span></button>
            </form>

            <a class="back-link" href="{{ route('login') }}"><span aria-hidden="true">&#8592;</span> Back to sign in</a>
        </section>
        <p class="login-footer">NIGERIA FRENCH LANGUAGE VILLAGE · BADAGRY</p>
    </main>
@endsection
