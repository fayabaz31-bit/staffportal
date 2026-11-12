@extends('layouts.public-site')

@section('title', 'Research & Resources | Nigeria French Language Village')

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
                    <a href="{{ route('website.admissions') }}">Admissions</a>
                    <a class="nfv-nav-active" href="{{ route('website.research') }}">Research &amp; resources</a>
                    <a href="{{ route('website.news') }}">News &amp; events</a>
                </nav>
                <a class="nfv-header-action" href="https://www.frenchvillage.edu.ng/journals">Explore resources <span aria-hidden="true">↗</span></a>
            </div>
        </header>

        <main>
            <section class="nfv-page-hero" aria-labelledby="research-page-heading">
                <div class="nfv-container nfv-page-hero-content">
                    <p class="nfv-kicker nfv-kicker-dark"><span></span> RESEARCH &amp; RESOURCES</p>
                    <h1 id="research-page-heading">Supporting French studies, scholarship and institutional growth.</h1>
                    <p>NfLV contributes to language teaching, research, policy and practical educational resources that deepen knowledge of French in Nigeria.</p>
                </div>
            </section>

            <section class="nfv-purpose-section" aria-label="Research areas">
                <div class="nfv-container nfv-purpose-grid">
                    <article>
                        <p class="nfv-section-index">01</p>
                        <p class="nfv-kicker"><span></span> TEACHING</p>
                        <h2>Language pedagogy and methodology.</h2>
                        <p>Developing effective teaching approaches and practical resources for French learning in diverse Nigerian contexts.</p>
                    </article>
                    <article>
                        <p class="nfv-section-index">02</p>
                        <p class="nfv-kicker"><span></span> CULTURE</p>
                        <h2>Experience and exchange in a Francophone context.</h2>
                        <p>Promoting language learning as a bridge to cross-cultural understanding, communication and relationship-building.</p>
                    </article>
                </div>
            </section>

            <section class="nfv-about-history" aria-labelledby="research-support-heading">
                <div class="nfv-container nfv-about-history-grid">
                    <p class="nfv-section-index">03</p>
                    <div>
                        <p class="nfv-kicker nfv-kicker-dark">WHAT WE SUPPORT</p>
                        <h2 id="research-support-heading">Teaching, development and collaborative learning.</h2>
                    </div>
                    <div class="nfv-rich-copy">
                        <p>NfLV collaborates with institutions, educators and learners to encourage the study of French and its wider applications.</p>
                        <p>Our work supports institutional capacity, advanced language learning and educational development across the region.</p>
                    </div>
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
