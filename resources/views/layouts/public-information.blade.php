@extends('layouts.public-site')

@section('content')
    <div class="nfv-site @yield('information-page-class')">
        <div class="nfv-utility-bar">
            <div class="nfv-container nfv-utility-content">
                <p>Inter-University Centre for French Studies · Badagry, Nigeria</p>
                <nav aria-label="Utility navigation">
                    <a href="{{ route('website.preview') }}">Home</a>
                    <a href="{{ route('website.about') }}">About</a>
                    <a href="{{ route('home') }}">Staff portal</a>
                </nav>
            </div>
        </div>

        <header class="nfv-header">
            <div class="nfv-container nfv-header-content">
                <a class="nfv-brand" href="{{ route('website.preview') }}" aria-label="Nigeria French Language Village home">
                    <span class="nfv-brand-mark" aria-hidden="true"></span>
                    <span><strong>Nigeria French<br>Language Village</strong><small>Inter-University Centre for French Studies</small></span>
                </a>
                <nav class="nfv-main-nav" aria-label="Main navigation">
                    @include('partials.public-about-navigation')
                    <a href="{{ route('website.programmes') }}">Study at NFLV</a>
                    <a href="{{ route('website.admissions') }}">Admissions</a>
                    <a href="{{ route('website.research') }}">Research &amp; resources</a>
                    <a href="{{ route('website.news') }}">News &amp; events</a>
                    @include('partials.public-tetfund-navigation')
                </nav>
                <a class="nfv-header-action" href="{{ route('website.admissions') }}">Apply now</a>
            </div>
        </header>

        <main>
            <section class="nfv-page-hero" aria-labelledby="information-heading">
                <div class="nfv-container nfv-page-hero-content">
                    <h1 id="information-heading">@yield('information-heading')</h1>
                    <p>@yield('information-intro')</p>
                </div>
            </section>

            <div class="nfv-article-body nfv-container">
                @yield('information-content')
            </div>

            @yield('information-media')
        </main>

        <footer class="nfv-footer" id="contact">
            <div class="nfv-container nfv-footer-grid">
                <div><a class="nfv-brand nfv-brand-footer" href="{{ route('website.preview') }}"><span class="nfv-brand-mark" aria-hidden="true"></span><span><strong>Nigeria French<br>Language Village</strong><small>Badagry, Nigeria</small></span></a>@include('partials.public-footer-intro')</div>
                <div><h2>Explore</h2><a href="{{ route('website.about') }}">About NFLV</a><a href="{{ route('website.programmes') }}">Programmes</a><a href="{{ route('website.admissions') }}">Admissions</a><a href="{{ route('website.news') }}">News &amp; events</a></div>
                <div><h2>Portals</h2><a href="{{ route('student.login') }}">Student portal</a><a href="{{ route('home') }}">Staff portal</a><a href="{{ route('website.fees') }}">Fees &amp; payments</a><a href="{{ route('website.contact') }}">Contact us</a></div>
                @include('partials.public-footer-contact')
            </div>
            <div class="nfv-container nfv-footer-bottom"><span>© {{ now()->year }} Nigeria French Language Village</span></div>
        </footer>
    </div>
@endsection
