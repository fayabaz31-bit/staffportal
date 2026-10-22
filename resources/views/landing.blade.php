@extends('layouts.app')

@section('title', 'NfLV Staff Portal | Staff services')

@section('content')
    <div class="public-page">
        <header class="site-header">
            <a class="wordmark" href="{{ route('home') }}" aria-label="NfLV Staff Portal home">
                <span class="wordmark-name">NfLV</span>
                <span class="wordmark-divider" aria-hidden="true"></span>
                <span class="wordmark-caption">Staff Portal</span>
            </a>
            <nav class="site-nav" aria-label="Main navigation">
                <a href="#services">Services</a>
                <a href="#about">About</a>
                <a class="nav-signin" href="{{ route('login') }}">Staff sign-in <span aria-hidden="true">&#8594;</span></a>
            </nav>
        </header>

        <main>
            <section class="landing-grid" aria-labelledby="welcome-heading">
                <div class="welcome-panel">
                    <div class="welcome-copy">
                        <p class="eyebrow"><span class="eyebrow-mark"></span> NIGERIA FRENCH LANGUAGE VILLAGE</p>
                        <h1 id="welcome-heading">Your work,<br>in <em>one place.</em></h1>
                        <p class="welcome-intro">A secure place for staff correspondence, personal records and everyday administrative services.</p>
                    </div>

                    <div class="paperwork-visual" aria-hidden="true">
                        <div class="paper sheet-back"><span></span><span></span><span></span></div>
                        <div class="paper sheet-front">
                            <div class="paper-heading"><span class="paper-seal">N</span><span class="paper-label">STAFF SERVICES</span></div>
                            <div class="paper-rule"></div>
                            <div class="paper-line long"></div>
                            <div class="paper-line medium"></div>
                            <div class="paper-line short"></div>
                            <div class="paper-stamp">NfLV</div>
                        </div>
                        <div class="visual-note"><span>01</span> DIGITAL SELF-SERVICE</div>
                    </div>

                    <div class="welcome-footnote"><span>BADAGRY, NIGERIA</span><span>STAFF ACCESS PORTAL</span></div>
                </div>

                <aside class="access-panel" aria-labelledby="signin-heading">
                    <div class="panel-topline"><span>STAFF ACCESS</span><span class="secure-indicator"><i></i> SECURE SIGN-IN</span></div>
                    <div class="panel-heading">
                        <p class="eyebrow eyebrow-dark">WELCOME BACK</p>
                        <h2 id="signin-heading">Sign in to your<br>staff account.</h2>
                        <p>Use your staff number or institutional email.</p>
                    </div>
                    @include('partials.login-form')
                    <p class="access-help">Need help accessing your account? Contact the ICT Unit through your usual institutional channel.</p>
                </aside>
            </section>

            <section class="service-band" id="services" aria-label="Portal services">
                <div class="service-heading"><span class="eyebrow eyebrow-dark">STAFF SERVICES</span><span>01 — 04</span></div>
                <div class="service-list">
                    <article class="service-item"><span class="service-index">01</span><h3>Correspondence</h3><p>Official memos and circulars</p></article>
                    <article class="service-item"><span class="service-index">02</span><h3>Staff records</h3><p>Your profile and personnel details</p></article>
                    <article class="service-item"><span class="service-index">03</span><h3>Leave</h3><p>Applications and approval status</p></article>
                    <article class="service-item"><span class="service-index">04</span><h3>Documents</h3><p>Payslips and official letters</p></article>
                </div>
            </section>
        </main>

        <footer class="site-footer" id="about">
            <span>NIGERIA FRENCH LANGUAGE VILLAGE · BADAGRY</span>
            <span>Privacy notice and terms pending institutional approval</span>
        </footer>
    </div>
@endsection