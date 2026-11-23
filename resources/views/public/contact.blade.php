@extends('layouts.public-information')

@section('title', 'Visit & Contact | Nigeria French Language Village')
@section('information-heading', 'Visit & contact us')
@section('information-intro', 'Find the Nigeria French Language Village campus in Ajara, Badagry.')

@section('information-content')
    <h2>Campus address</h2>
    <address>
        Nigeria French Language Village<br>
        Badagry–Lagos Expressway, P.M.B. 1011<br>
        Ajara, Badagry, Lagos State, Nigeria
    </address>

    <h2>Enquiries</h2>
    <p>See <a href="{{ route('website.admissions') }}">Admissions</a> for programme and application enquiries. Use the campus address for visits and written correspondence.</p>
@endsection

@section('information-media')
    @include('partials.public-campus-map')
@endsection
