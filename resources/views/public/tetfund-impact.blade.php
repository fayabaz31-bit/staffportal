@extends('layouts.public-information')

@section('title', 'TETFund Impact | Nigeria French Language Village')
@section('information-heading', 'TETFund Impact')
@section('information-intro', 'Intervention areas and funding resources from the Tertiary Education Trust Fund.')

@section('information-content')
    <h2>Intervention areas</h2>
    <p>TETFund supports eligible public tertiary institutions through programmes for teaching and research facilities, library development, academic research, staff training and conference attendance.</p>
    <p><a href="https://www.tetfund.gov.ng/interventions">Explore TETFund intervention programmes</a>.</p>

    <h2>NFLV project records</h2>
    <p>Verified NFLV project and funding records are currently unavailable on this page. For institutional enquiries, <a href="{{ route('website.contact') }}">contact NFLV</a>.</p>
@endsection
