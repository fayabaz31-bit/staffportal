@extends('layouts.public-site')

@section('title', 'About NfLV | Nigeria French Language Village')

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
                    <a class="nfv-nav-active" href="{{ route('website.about') }}">About NfLV</a>
                    <a href="{{ route('website.programmes') }}">Study at NfLV</a>
                    <a href="{{ route('website.admissions') }}">Admissions</a>
                    <a href="{{ route('website.research') }}">Research &amp; resources</a>
                    <a href="{{ route('website.news') }}">News &amp; events</a>
                </nav>
                <a class="nfv-header-action" href="https://portal.frenchvillage.edu.ng/register">Apply now <span aria-hidden="true">↗</span></a>
            </div>
        </header>

        <main>
            <section class="nfv-page-hero" aria-labelledby="about-page-heading">
                <div class="nfv-container nfv-page-hero-content">
                    <p class="nfv-kicker nfv-kicker-dark"><span></span> ABOUT NFLV</p>
                    <h1 id="about-page-heading">A national centre for<br><em>French studies and exchange.</em></h1>
                    <p>The Nigeria French Language Village brings language, culture and learning together to strengthen communication across Nigeria, West Africa and the Francophone world.</p>
                </div>
            </section>

            <section class="nfv-about-history" aria-labelledby="history-heading">
                <div class="nfv-container nfv-about-history-grid">
                    <p class="nfv-section-index">01</p>
                    <div>
                        <p class="nfv-kicker nfv-kicker-dark">OUR HISTORY</p>
                        <h2 id="history-heading">Established to make French immersion accessible in Nigeria.</h2>
                    </div>
                    <div class="nfv-rich-copy">
                        <p>The Nigeria French Language Village was established in December 1991 as an Inter-University Centre for French Studies operating within the ambit of the National Universities Commission.</p>
                        <p>It was created to provide the Language Immersion Programme as a domestic alternative to the former year-abroad component of French degree programmes in Nigerian universities, and to support French students in Colleges of Education.</p>
                        <p>Today, NfLV serves universities, Colleges of Education and other institutions, helping learners develop practical French language competence in a non-Francophone setting.</p>
                    </div>
                </div>
            </section>

            <section class="nfv-purpose-section" aria-label="Vision and mission">
                <div class="nfv-container nfv-purpose-grid">
                    <article>
                        <p class="nfv-section-index">02</p>
                        <p class="nfv-kicker"><span></span> OUR VISION</p>
                        <h2>French communication skills for everyone.</h2>
                        <p>To empower all persons, irrespective of age, culture, creed or sex, with appropriate communication skills in the effective use of French language at both professional and interpersonal levels.</p>
                    </article>
                    <article>
                        <p class="nfv-section-index">03</p>
                        <p class="nfv-kicker"><span></span> OUR MISSION</p>
                        <h2>A centre of excellence for French in Nigeria.</h2>
                        <p>To develop an outstanding centre for the study, research and development of French in Nigeria through skilled personnel, appropriate technology and modern teaching methods that support bilingualism, regional integration and international cooperation.</p>
                    </article>
                </div>
            </section>

            <section class="nfv-mandate-section" aria-labelledby="mandate-heading">
                <div class="nfv-container">
                    <div class="nfv-mandate-intro">
                        <p class="nfv-section-index">04</p>
                        <div><p class="nfv-kicker nfv-kicker-dark">OUR MANDATE</p><h2 id="mandate-heading">Building French capacity for Nigeria and the region.</h2></div>
                        <p>NfLV’s work extends beyond programmes for individual learners. The institution supports teaching, research, policy and the wider use of French across Nigeria.</p>
                    </div>
                    <ol class="nfv-mandate-list">
                        <li>Provide a domestic alternative to the mandatory language immersion programme for French undergraduates in Nigerian universities and Colleges of Education.</li>
                        <li>Support tertiary institutions with the human, material and infrastructural resources required for French teaching, learning and research.</li>
                        <li>Explore practical applications of the French language within the Nigerian context.</li>
                        <li>Promote economic, technical and social integration across Africa.</li>
                        <li>Provide relevant information and expertise to government and corporate institutions for policy and decision-making.</li>
                        <li>Coordinate activities relating to French language teaching and learning in Nigeria.</li>
                        <li>Collaborate with relevant agencies on examinations, curriculum development and essential French-language documentation.</li>
                        <li>Retrain French teachers to support the implementation of language policy in Nigerian secondary schools.</li>
                        <li>Organise customised programmes that make French learning accessible to interested Nigerians.</li>
                    </ol>
                </div>
            </section>

            <section class="nfv-about-callout">
                <div class="nfv-container nfv-about-callout-content">
                    <p class="nfv-kicker"><span></span> STUDY AT NFLV</p>
                    <h2>Ready to begin your French learning journey?</h2>
                    <a class="nfv-button nfv-button-primary" href="{{ route('website.programmes') }}">Explore programmes <span aria-hidden="true">→</span></a>
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
