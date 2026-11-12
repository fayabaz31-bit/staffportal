@extends('layouts.public-site')

@section('title', 'Director’s Welcome | Nigeria French Language Village')

@section('content')
    <div class="nfv-site">
        <div class="nfv-utility-bar">
            <div class="nfv-container nfv-utility-content">
                <p>Inter-University Centre for French Studies · Badagry, Nigeria</p>
                <nav aria-label="Utility navigation">
                    <a href="{{ route('website.preview') }}">Contact</a>
                    <a href="{{ route('website.news') }}">News</a>
                    <a href="https://portal.frenchvillage.edu.ng/">Student portal</a>
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
                    <a href="{{ route('website.programmes') }}">Study at NfLV</a>
                    <a href="{{ route('website.admissions') }}">Admissions</a>
                    <a href="{{ route('website.research') }}">Research &amp; resources</a>
                    <a href="{{ route('website.news') }}">News &amp; events</a>
                </nav>
                <a class="nfv-header-action" href="https://portal.frenchvillage.edu.ng/register">Apply now <span aria-hidden="true">↗</span></a>
            </div>
        </header>

        <main>
            <section class="nfv-page-hero" aria-labelledby="welcome-page-heading">
                <div class="nfv-container nfv-page-hero-content">
                    <p class="nfv-kicker nfv-kicker-dark nfv-section-label">From the Director</p>
                    <h1 id="welcome-page-heading">Bienvenue.<br><em>Welcome to NfLV.</em></h1>
                    <p>A message from Prof. Samuel Oladiipo Kolawole, Director &amp; Chief Executive Officer.</p>
                </div>
            </section>
            <section class="nfv-welcome-letter">
                <div class="nfv-container nfv-welcome-letter-grid">
                    <figure><img src="{{ asset('media/director-portrait.webp') }}" alt="Prof. Samuel Oladiipo Kolawole" width="715" height="1000"><figcaption>Prof. Samuel Oladiipo Kolawole<br><small>FNITI, MNAL · Director &amp; Chief Executive Officer</small></figcaption></figure>
                    <article aria-label="Director’s welcome message">
                        @include('partials.director-address')
                        <div class="nfv-director-signoff"><strong>Prof. Samuel Oladiipo Kolawole</strong><span>FNITI, MNAL</span><small>Director &amp; Chief Executive Officer</small></div>
                        <a class="nfv-text-action" href="{{ route('website.programmes') }}">Explore our programmes <span aria-hidden="true">→</span></a>
                    </article>
                </div>
            </section>
        </main>

        <footer class="nfv-footer" id="contact">
            <div class="nfv-container nfv-footer-grid">
                <div><a class="nfv-brand nfv-brand-footer" href="{{ route('website.preview') }}"><span class="nfv-brand-mark" aria-hidden="true"></span><span><strong>Nigeria French<br>Language Village</strong><small>Badagry, Nigeria</small></span></a>@include('partials.public-footer-intro')</div>
                <div><h2>Explore</h2><a href="{{ route('website.about') }}">About NfLV</a><a href="{{ route('website.programmes') }}">Programmes</a><a href="{{ route('website.admissions') }}">Admissions</a><a href="{{ route('website.news') }}">News &amp; events</a></div>
                <div><h2>Portals</h2><a href="https://portal.frenchvillage.edu.ng/">Student portal</a><a href="{{ route('home') }}">Staff portal</a><a href="https://portal.frenchvillage.edu.ng/paynow">Fees &amp; payments</a><a href="https://www.frenchvillage.edu.ng/contact-us">Contact us</a></div>
                @include('partials.public-footer-contact')
            </div>
            <div class="nfv-container nfv-footer-bottom"><span>© {{ now()->year }} Nigeria French Language Village</span></div>
        </footer>
    </div>
@endsection
