@extends('layouts.public-information')

@section('title', 'Student portal | Nigeria French Language Village')
@section('information-heading', 'Student portal')
@section('information-intro', 'Access your application, registration and student services.')

@section('information-content')
    <h2>Sign in to your account</h2>
    <p>Use the <a href="{{ route('student.login') }}">NFLV student portal</a> to sign in and access your student services.</p>

    <h2>Applying for the first time?</h2>
    <p>Read the <a href="{{ route('website.admissions') }}">admissions guide</a> before creating your account. For tuition payments, see <a href="{{ route('website.fees') }}">Fees &amp; payments</a>.</p>
@endsection
