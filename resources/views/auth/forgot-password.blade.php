@extends('layouts.app')

@section('title', 'Reset your password | NfLV Staff Portal')

@section('content')
    <main class="login-page">
        <a class="wordmark login-wordmark" href="{{ route('home') }}">
            <span class="wordmark-name">NfLV</span>
            <span class="wordmark-divider" aria-hidden="true"></span>
            <span class="wordmark-caption">Staff Portal</span>
        </a>
        <section class="login-card" aria-labelledby="forgot-heading">
            <p class="eyebrow eyebrow-dark">PASSWORD RECOVERY</p>
            <h1 id="forgot-heading">Reset your<br>password.</h1>
            <p class="login-intro">Enter your institutional email address and we will send a secure reset link.</p>

            @if (session('status'))
                <p class="profile-notice" role="status">{{ session('status') }}</p>
            @endif

            <form class="signin-form" method="POST" action="{{ route('password.email') }}">
                @csrf
                <div class="field-group">
                    <label for="email">Institutional email</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        value="{{ old('email') }}"
                        autocomplete="email"
                        aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}"
                        required
                    >
                    @error('email')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                </div>

                <button class="button button-primary" type="submit">Send reset link <span aria-hidden="true">&#8594;</span></button>
            </form>

            <a class="back-link" href="{{ route('login') }}"><span aria-hidden="true">&#8592;</span> Back to sign in</a>
        </section>
        <p class="login-footer">NIGERIA FRENCH LANGUAGE VILLAGE · BADAGRY</p>
    </main>
@endsection
