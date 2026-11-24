@extends('layouts.portal-entry', ['audience' => 'staff', 'mode' => 'forgot'])

@section('title', 'Reset your password | NfLV Staff Portal')

@section('portal-form')
    <form class="signin-form" method="POST" action="{{ route('password.email') }}">
        @csrf
        <div class="field-group">
            <label for="email">Institutional email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}" required>
        </div>
        <button class="button button-primary" type="submit">Send reset link</button>
        <p class="entry-recovery"><a href="{{ route('login') }}">Back to staff sign-in</a></p>
    </form>
@endsection
