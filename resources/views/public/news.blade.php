@extends('layouts.public-site')

@section('title', 'News & Events | Nigeria French Language Village')

@section('content')
    <div class="nfv-site">
        @include('partials.public-header')

        <main id="main-content" tabindex="-1">
            <section class="nfv-page-hero" aria-labelledby="news-page-heading">
                <div class="nfv-container nfv-page-hero-content">
                    <p class="nfv-kicker nfv-kicker-dark"><span></span> NEWS &amp; EVENTS</p>
                    <h1 id="news-page-heading">Highlights from campus life, study and institutional engagement.</h1>
                    <p>See the latest activities, programme updates and events from across the Nigeria French Language Village.</p>
                </div>
            </section>

            <section class="nfv-section" aria-labelledby="news-grid-heading">
                <div class="nfv-container">
                    <div class="nfv-section-heading">
                        <div>
                            <p class="nfv-kicker nfv-kicker-dark"><span></span> LATEST UPDATES</p>
                            <h2 id="news-grid-heading">Featured stories and announcements.</h2>
                        </div>
                    </div>

                    <div class="nfv-news-grid">
                        <article class="nfv-news-feature"><div class="nfv-news-image nfv-news-image-main"><span>Campus &amp; community</span></div><p>FEATURED</p><h3>Discover the people, culture and opportunities that make up life at NfLV.</h3><a href="{{ route('website.about') }}">Explore campus life <b aria-hidden="true">→</b></a></article>
                        <article class="nfv-news-item"><p>PROGRAMMES · JUNE 2026</p><h3>Diploma in French: Batch A 2026</h3><a href="{{ route('website.news.diploma-french-2026') }}">Read story <b aria-hidden="true">→</b></a></article>
                        <article class="nfv-news-item"><p>PROGRAMMES · JUNE 2026</p><h3>Certificate in French: Third Batch 2026</h3><a href="{{ route('website.news.certificate-french-2026') }}">Read story <b aria-hidden="true">→</b></a></article>
                        <article class="nfv-news-item"><p>INSTITUTION · APRIL 2026</p><h3>French Embassy officials visit NfLV for a technical meeting</h3><a href="{{ route('website.news.french-embassy-technical-meeting') }}">Read story <b aria-hidden="true">→</b></a></article>
                    </div>
                </div>
            </section>
        </main>

        <footer class="nfv-footer" id="contact">
            <div class="nfv-container nfv-footer-grid">
                <div><a class="nfv-brand nfv-brand-footer" href="{{ route('website.preview') }}"><span class="nfv-brand-mark" aria-hidden="true"></span><span><strong>Nigeria French<br>Language Village</strong><small>Badagry, Nigeria</small></span></a>@include('partials.public-footer-intro')</div>
                <div><h2>Explore</h2><a href="{{ route('website.about') }}">About NfLV</a><a href="{{ route('website.programmes') }}">Programmes</a><a href="{{ route('website.admissions') }}">Admissions</a><a href="{{ route('website.news') }}">News &amp; events</a></div>
                <div><h2>Portals</h2><a href="https://portal.frenchvillage.edu.ng/">Student portal</a><a href="{{ route('home') }}">Staff portal</a><a href="https://portal.frenchvillage.edu.ng/paynow">Fees &amp; payments</a><a href="https://www.frenchvillage.edu.ng/contact-us">Contact us</a></div>
                <div><h2>Visit or contact us</h2><p>Badagry, Lagos State, Nigeria</p><a href="https://www.frenchvillage.edu.ng/contact-us">Get in touch <span aria-hidden="true">→</span></a></div>
            </div>
            @include('partials.public-social-links')
            <div class="nfv-container nfv-footer-bottom"><span>© {{ now()->year }} Nigeria French Language Village</span><span>Privacy · Accessibility · Terms</span></div>
        </footer>
    </div>
@endsection
