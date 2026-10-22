@extends('layouts.app')

@section('title', 'Staff sign-in | NfLV Staff Portal')

@section('content')
    <main class="login-page">
        <a class="wordmark login-wordmark" href="{{ route('home') }}">
            <span class="wordmark-name">NfLV</span>
            <span class="wordmark-divider" aria-hidden="true"></span>
            <span class="wordmark-caption">Staff Portal</span>
        </a>
        <section class="login-card" aria-labelledby="signin-heading">
            <p class="eyebrow eyebrow-dark">STAFF ACCESS</p>
            <h1 id="signin-heading">Sign in to your<br>staff account.</h1>
            <p class="login-intro">Use your staff number or institutional email.</p>
            @include('partials.login-form')
            <a class="back-link" href="{{ route('home') }}"><span aria-hidden="true">&#8592;</span> Back to portal home</a>
        </section>
        <p class="login-footer">NIGERIA FRENCH LANGUAGE VILLAGE · BADAGRY</p>
    </main>
@endsection