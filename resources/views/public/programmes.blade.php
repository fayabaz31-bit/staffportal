@extends('layouts.public-site')

@section('title', 'Study at NfLV | Nigeria French Language Village')

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
                    @include('partials.public-about-navigation')
                    <a class="nfv-nav-active" href="{{ route('website.programmes') }}">Study at NfLV</a>
                    <a href="{{ route('website.admissions') }}">Admissions</a>
                    <a href="{{ route('website.research') }}">Research &amp; resources</a>
                    <a href="{{ route('website.news') }}">News &amp; events</a>
                </nav>
                <a class="nfv-header-action" href="https://portal.frenchvillage.edu.ng/register">Apply now <span aria-hidden="true">↗</span></a>
            </div>
        </header>

        <main>
            <section class="nfv-page-hero" aria-labelledby="programmes-page-heading">
                <div class="nfv-container nfv-page-hero-content">
                    <p class="nfv-kicker nfv-kicker-dark"><span></span> STUDY AT NFLV</p>
                    <h1 id="programmes-page-heading">French immersion and study programmes built for real progress.</h1>
                    <p>From university immersion to professional and flexible French learning, NfLV creates meaningful pathways for learners at every stage.</p>
                </div>
            </section>

            <section class="nfv-section" aria-labelledby="programme-list-heading">
                <div class="nfv-container">
                    <div class="nfv-section-heading">
                        <div>
                            <p class="nfv-kicker nfv-kicker-dark"><span></span> PROGRAMME TYPES</p>
                            <h2 id="programme-list-heading">Choose the learning path that matches your goals.</h2>
                        </div>
                    </div>

                    <div class="nfv-programme-grid">
                        <article class="nfv-programme-card nfv-programme-card-featured">
                            <p>FOR UNDERGRADUATES</p>
                            <h3>French Language Immersion Programme</h3>
                            <span>Designed for students in universities and colleges of education.</span>
                            <a href="{{ route('website.admissions') }}">Explore admissions <b aria-hidden="true">→</b></a>
                        </article>
                        <article class="nfv-programme-card">
                            <p>CAREER &amp; PROFESSIONAL</p>
                            <h3>Diploma and certificate study</h3>
                            <span>Structured pathways for practical French communication and international engagement.</span>
                            <a href="{{ route('website.admissions') }}">See requirements <b aria-hidden="true">→</b></a>
                        </article>
                        <article class="nfv-programme-card">
                            <p>FLEXIBLE LEARNING</p>
                            <h3>Short courses and customised programmes</h3>
                            <span>Weekend, holiday and targeted learning experiences for institutions and professionals.</span>
                            <a href="{{ route('website.admissions') }}">Learn more <b aria-hidden="true">→</b></a>
                        </article>
                    </div>
                </div>
            </section>

            <section class="nfv-purpose-section" aria-label="Learning approach">
                <div class="nfv-container nfv-purpose-grid">
                    <article>
                        <p class="nfv-section-index">01</p>
                        <p class="nfv-kicker"><span></span> IMMERSION</p>
                        <h2>Practical speaking and confidence.</h2>
                        <p>Students learn in settings that encourage daily use of French, helping them become more fluent and confident in real communication.</p>
                    </article>
                    <article>
                        <p class="nfv-section-index">02</p>
                        <p class="nfv-kicker"><span></span> ASSISTANCE</p>
                        <h2>Supportive teaching and structured pathways.</h2>
                        <p>From orientation to assessments, learners receive guidance that helps them stay focused, build momentum and excel.</p>
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
