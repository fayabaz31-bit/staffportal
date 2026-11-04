@extends('layouts.public-site')

@section('title', 'News & Events | Nigeria French Language Village')

@section('content')
    <div class="nfv-site">
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
                    <a href="{{ route('website.about') }}">About NfLV</a>
                    <a href="{{ route('website.programmes') }}">Study at NfLV</a>
                    <a href="{{ route('website.admissions') }}">Admissions</a>
                    <a href="{{ route('website.research') }}">Research &amp; resources</a>
                    <a class="nfv-nav-active" href="{{ route('website.news') }}">News &amp; events</a>
                </nav>
                <a class="nfv-header-action" href="https://www.frenchvillage.edu.ng/event">Browse events <span aria-hidden="true">↗</span></a>
            </div>
        </header>

        <main>
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
                        <article class="nfv-news-item"><p>PROGRAMMES · JUNE 2026</p><h3>Diploma in French: Batch A 2026</h3><a href="https://www.frenchvillage.edu.ng/newsletter/78/news">Read story <b aria-hidden="true">→</b></a></article>
                        <article class="nfv-news-item"><p>PROGRAMMES · JUNE 2026</p><h3>Certificate in French: Third Batch 2026</h3><a href="https://www.frenchvillage.edu.ng/newsletter/77/news">Read story <b aria-hidden="true">→</b></a></article>
                        <article class="nfv-news-item"><p>INSTITUTION · APRIL 2026</p><h3>French Embassy officials visit NfLV for a technical meeting</h3><a href="https://www.frenchvillage.edu.ng/newsletter/73/news">Read story <b aria-hidden="true">→</b></a></article>
                    </div>
                </div>
            </section>
        </main>

        <footer class="nfv-footer" id="contact">
            <div class="nfv-container nfv-footer-grid">
                <div><a class="nfv-brand nfv-brand-footer" href="{{ route('website.preview') }}"><span class="nfv-brand-mark" aria-hidden="true"></span><span><strong>Nigeria French<br>Language Village</strong><small>Badagry, Nigeria</small></span></a><p>French immersion, education, research and cultural exchange.</p></div>
                <div><h2>Explore</h2><a href="{{ route('website.about') }}">About NfLV</a><a href="{{ route('website.programmes') }}">Programmes</a><a href="{{ route('website.admissions') }}">Admissions</a><a href="{{ route('website.news') }}">News &amp; events</a></div>
                <div><h2>Portals</h2><a href="https://portal.frenchvillage.edu.ng/">Student portal</a><a href="{{ route('home') }}">Staff portal</a><a href="https://portal.frenchvillage.edu.ng/paynow">Fees &amp; payments</a><a href="https://www.frenchvillage.edu.ng/contact-us">Contact us</a></div>
                <div><h2>Visit or contact us</h2><p>Badagry, Lagos State, Nigeria</p><a href="https://www.frenchvillage.edu.ng/contact-us">Get in touch <span aria-hidden="true">→</span></a></div>
            </div>
            <div class="nfv-container nfv-footer-bottom"><span>© {{ now()->year }} Nigeria French Language Village</span><span>Privacy · Accessibility · Terms</span></div>
        </footer>
    </div>
@endsection
