@extends('public.news-article')
@section('title', 'Certificate in French: Third Batch 2026 | Nigeria French Language Village')

@section('article-title', 'Certificate in French: Third Batch 2026')
@section('article-date', '24 June 2026')
@section('article-category', 'Programmes')

@section('article-content')
    <p class="nfv-article-lead">The Nigeria French Language Village’s third-batch Certificate in French announcement for 2026 is available below.</p>
    <p>Read the original programme flyer for the published course and application details. Select the image to view the complete announcement at full size.</p>
    <a class="nfv-article-flyer" href="{{ asset('media/news-77-announcement.jpg') }}" target="_blank" rel="noopener"><img src="{{ asset('media/news-77-announcement.jpg') }}" alt="Official third-batch 2026 Certificate in French announcement" loading="lazy"><span>View full-size announcement ↗</span></a>
    <h2>Plan your next step</h2>
    <p>This is an archived 2026 announcement. For current intakes and application guidance, visit our admissions page.</p>
    <a class="nfv-button" href="{{ route('website.admissions') }}">Visit admissions <span aria-hidden="true">→</span></a>
@endsection
