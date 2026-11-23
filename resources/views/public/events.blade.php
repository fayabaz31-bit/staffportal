@extends('layouts.public-information')

@section('title', 'Campus Events | Nigeria French Language Village')
@section('information-heading', 'Campus events')
@section('information-intro', 'See highlights from events at the Nigeria French Language Village.')

@section('information-content')
    <h2>Hall Week</h2>
    <p>Watch highlights from Hall Week at the Village.</p>
    <video controls playsinline preload="none" poster="{{ asset('media/hall-week-poster.jpg') }}" aria-label="NFLV Hall Week highlights">
        <source src="{{ asset('media/hall-week.mp4') }}" type="video/mp4">
        <a href="{{ asset('media/hall-week.mp4') }}">Download Hall Week highlights</a>
    </video>

    <p>Check <a href="{{ route('website.news') }}">NFLV news</a> for new event announcements.</p>
@endsection
