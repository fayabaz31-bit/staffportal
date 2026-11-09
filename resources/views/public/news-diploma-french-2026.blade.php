@extends('public.news-article')
@section('title', 'Diploma in French: Batch A 2026 | Nigeria French Language Village')

@section('article-title', 'Diploma in French: Batch A 2026')
@section('article-date', '24 June 2026')
@section('article-category', 'Programmes')

@section('article-content')
    <p class="nfv-article-lead">Explore the Batch A 2026 Diploma in French announcement from the Nigeria French Language Village.</p>
    <p>The programme announcement below contains the published application information. Select the flyer to read it at full size.</p>
    <a class="nfv-article-flyer" href="{{ asset('media/news-78-announcement.jpg') }}" target="_blank" rel="noopener"><img src="{{ asset('media/news-78-announcement.jpg') }}" alt="Official Batch A 2026 Diploma in French announcement" loading="lazy"><span>View full-size announcement ↗</span></a>
    <h2>Applications and enquiries</h2>
    <p>This is an archived 2026 announcement. Confirm current availability and intake information before applying.</p>
    <a class="nfv-button" href="{{ route('website.admissions') }}">Visit admissions <span aria-hidden="true">→</span></a>
@endsection
