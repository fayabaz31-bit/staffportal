@extends('layouts.public-site')

@section('title', 'Admissions | Nigeria French Language Village')

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
                    <a href="{{ route('website.programmes') }}">Study at NfLV</a>
                    <a class="nfv-nav-active" href="{{ route('website.admissions') }}">Admissions</a>
                    <a href="{{ route('website.research') }}">Research &amp; resources</a>
                    <a href="{{ route('website.news') }}">News &amp; events</a>
                </nav>
                <a class="nfv-header-action" href="https://portal.frenchvillage.edu.ng/register">Apply now <span aria-hidden="true">↗</span></a>
            </div>
        </header>

        <main>
            <section class="nfv-page-hero" aria-labelledby="admissions-page-heading">
                <div class="nfv-container nfv-page-hero-content">
                    <p class="nfv-kicker nfv-kicker-dark"><span></span> ADMISSIONS</p>
                    <h1 id="admissions-page-heading">Choose a route, complete your application and begin your French learning journey.</h1>
                    <p>Applications are managed through the official student portal, and the right programme depends on your academic and professional goals.</p>
                </div>
            </section>

            <section class="nfv-about-history" aria-labelledby="admissions-process-heading">
                <div class="nfv-container nfv-about-history-grid">
                    <p class="nfv-section-index">01</p>
                    <div>
                        <p class="nfv-kicker nfv-kicker-dark">HOW TO APPLY</p>
                        <h2 id="admissions-process-heading">A simple three-step application process.</h2>
                    </div>
                    <div class="nfv-rich-copy">
                        <ol class="nfv-mandate-list">
                            <li>Choose the study path that matches your academic level or professional goal.</li>
                            <li>Review the programme requirements and application details.</li>
                            <li>Submit your application through the official student portal and follow up on your admission status.</li>
                        </ol>
                    </div>
                </div>
            </section>

            <section class="nfv-purpose-section" aria-label="Admissions categories">
                <div class="nfv-container nfv-purpose-grid">
                    <article>
                        <p class="nfv-section-index">02</p>
                        <p class="nfv-kicker"><span></span> UNDERGRADUATE</p>
                        <h2>Language immersion and study support.</h2>
                        <p>Designed for students who need to strengthen speaking, comprehension and confidence in French as part of their academic programme.</p>
                    </article>
                    <article>
                        <p class="nfv-section-index">03</p>
                        <p class="nfv-kicker"><span></span> PROFESSIONAL</p>
                        <h2>Flexible learning for career and institutional goals.</h2>
                        <p>Short courses and tailored programmes support professionals, institutions and organisations that want applied French language skills.</p>
                    </article>
                </div>
            </section>

            <section class="nfv-about-callout">
                <div class="nfv-container nfv-about-callout-content">
                    <p class="nfv-kicker"><span></span> READY TO START</p>
                    <h2>Begin your application through the official portal.</h2>
                    <a class="nfv-button nfv-button-primary" href="https://portal.frenchvillage.edu.ng/register">Apply now <span aria-hidden="true">→</span></a>
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
