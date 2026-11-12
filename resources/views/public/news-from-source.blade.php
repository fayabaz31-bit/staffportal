@extends('public.news-article')

@section('title')
    {{ $story['title'] }} | Nigeria French Language Village
@endsection

@section('article-title')
    {{ $story['title'] }}
@endsection

@section('article-date')
    {{ date('j F Y', strtotime($story['date'])) }}
@endsection

@section('article-category', 'NFLV News')

@section('article-content')
    @foreach ($paragraphs as $paragraph)
        <p>{{ $paragraph }}</p>
    @endforeach

    @if ($story['image'])
        <a class="nfv-article-flyer" href="{{ $story['image'] }}">
            <img src="{{ $story['image'] }}" alt="{{ $story['title'] }}" loading="lazy">
            <span>View full-size image</span>
        </a>
    @endif

    <p><a class="nfv-text-action" href="{{ $source }}">Original announcement on the official NFLV website</a></p>
@endsection
