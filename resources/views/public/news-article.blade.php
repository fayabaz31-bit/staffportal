@extends('layouts.public-site')


@section('content')
    <div class="nfv-site">
        @include('partials.public-header')

        <main id="main-content" tabindex="-1">
            <section class="nfv-page-hero" aria-labelledby="article-heading">
                <div class="nfv-container nfv-page-hero-content">
                    <a class="nfv-text-action" href="{{ route('website.news') }}">← Back to news &amp; events</a>
                    <h1 id="article-heading">@yield('article-title')</h1>
                    <p>@yield('article-date') · @yield('article-category')</p>
                </div>
            </section>
            <article class="nfv-article-body nfv-container">
                @yield('article-content')
                <div class="nfv-article-back"><a class="nfv-text-action" href="{{ route('website.news') }}">Browse more news <span aria-hidden="true">→</span></a></div>
            </article>
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
