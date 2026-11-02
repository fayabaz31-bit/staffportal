@extends('layouts.public-site')

@section('title', 'Nigeria French Language Village | French immersion and studies')

@section('content')
    <div class="nfv-site">
        <div class="nfv-utility-bar">
            <div class="nfv-container nfv-utility-content">
                <p>Inter-University Centre for French Studies · Badagry, Nigeria</p>
                <nav aria-label="Utility navigation">
                    <a href="#contact">Contact</a>
                    <a href="#news">News</a>
                    <a href="https://portal.frenchvillage.edu.ng/">Student portal</a>
                    <a href="{{ route('home') }}">Staff portal</a>
                </nav>
            </div>
        </div>

        <header class="nfv-header">
            <div class="nfv-container nfv-header-content">
                <a class="nfv-brand" href="#top" aria-label="Nigeria French Language Village home">
                    <span class="nfv-brand-mark" aria-hidden="true"></span>
                    <span>
                        <strong>Nigeria French<br>Language Village</strong>
                        <small>Inter-University Centre for French Studies</small>
                    </span>
                </a>
                <nav class="nfv-main-nav" aria-label="Main navigation">
                    <a href="{{ route('website.about') }}">About NfLV</a>
                    <a href="#programmes">Study at NfLV</a>
                    <a href="#admissions">Admissions</a>
                    <a href="#research">Research &amp; resources</a>
                    <a href="#news">News &amp; events</a>
                </nav>
                <a class="nfv-header-action" href="https://portal.frenchvillage.edu.ng/register">Apply now <span aria-hidden="true">↗</span></a>
            </div>
        </header>

        <main id="top">
            <section class="nfv-hero" aria-labelledby="hero-heading">
                <div class="nfv-container nfv-hero-grid">
                    <div class="nfv-hero-copy">
                        <p class="nfv-kicker"><span></span> A CENTRE FOR LANGUAGE, CULTURE AND CONNECTION</p>
                        <h1 id="hero-heading">Learn French.<br><em>Connect Africa.</em><br>Build your future.</h1>
                        <p class="nfv-hero-intro">Build practical French fluency through immersive learning, specialist teaching and a vibrant Francophone community in Badagry.</p>
                        <div class="nfv-hero-actions">
                            <a class="nfv-button nfv-button-primary" href="#programmes">Explore programmes <span aria-hidden="true">→</span></a>
                            <a class="nfv-text-action" href="https://portal.frenchvillage.edu.ng/register">Start an application <span aria-hidden="true">↗</span></a>
                        </div>
                    </div>
                    <div class="nfv-hero-visual" aria-label="Nigeria French Language Village campus experience">
                        <div class="nfv-hero-visual-copy">
                            <span>French immersion in Nigeria</span>
                            <strong>Study. Speak.<br>Belong.</strong>
                        </div>
                        <div class="nfv-hero-sun" aria-hidden="true"></div>
                        <div class="nfv-hero-arch nfv-hero-arch-back" aria-hidden="true"></div>
                        <div class="nfv-hero-arch nfv-hero-arch-front" aria-hidden="true"></div>
                        <p class="nfv-hero-caption">BADAGRY · NIGERIA <span>01 / 04</span></p>
                    </div>
                </div>
            </section>

            <section class="nfv-quick-links" aria-label="Popular services">
                <div class="nfv-container nfv-quick-links-grid">
                    <a href="#admissions"><span>01</span><strong>Admissions</strong><small>Requirements, intakes and how to apply</small><b aria-hidden="true">→</b></a>
                    <a href="https://portal.frenchvillage.edu.ng/paynow"><span>02</span><strong>Fees &amp; payments</strong><small>Pay tuition or confirm a payment</small><b aria-hidden="true">→</b></a>
                    <a href="https://portal.frenchvillage.edu.ng/"><span>03</span><strong>Student portal</strong><small>Registration and student services</small><b aria-hidden="true">→</b></a>
                    <a href="{{ route('home') }}"><span>04</span><strong>Staff portal</strong><small>Secure staff self-service</small><b aria-hidden="true">→</b></a>
                </div>
            </section>

            <section class="nfv-section nfv-programmes" id="programmes" aria-labelledby="programmes-heading">
                <div class="nfv-container">
                    <div class="nfv-section-heading">
                        <div>
                            <p class="nfv-kicker nfv-kicker-dark"><span></span> STUDY AT NFLV</p>
                            <h2 id="programmes-heading">Find the programme<br>that fits your path.</h2>
                        </div>
                        <p>From university immersion to professional and flexible learning, NfLV offers French studies for every stage of life.</p>
                    </div>
                    <div class="nfv-programme-grid">
                        <article class="nfv-programme-card nfv-programme-card-featured">
                            <p>FOR UNDERGRADUATES</p>
                            <h3>French Language<br>Immersion Programme</h3>
                            <span>Universities &amp; Colleges of Education</span>
                            <a href="#admissions">Explore the immersion programme <b aria-hidden="true">→</b></a>
                        </article>
                        <article class="nfv-programme-card">
                            <p>CAREER &amp; PROFESSIONAL</p>
                            <h3>Diploma and<br>Certificate programmes</h3>
                            <span>Structured French study with recognised qualifications</span>
                            <a href="#admissions">View programme options <b aria-hidden="true">→</b></a>
                        </article>
                        <article class="nfv-programme-card">
                            <p>FLEXIBLE LEARNING</p>
                            <h3>Short courses and<br>custom programmes</h3>
                            <span>Weekend, evening, holiday and tailored learning</span>
                            <a href="#admissions">Discover flexible learning <b aria-hidden="true">→</b></a>
                        </article>
                    </div>
                </div>
            </section>

            <section class="nfv-section nfv-about" id="about" aria-labelledby="about-heading">
                <div class="nfv-container nfv-about-grid">
                    <div class="nfv-statements">
                        <p class="nfv-kicker"><span></span> WHY NFLV</p>
                        <h2 id="about-heading">A place where French becomes part of everyday life.</h2>
                        <p>NfLV brings together language learning, culture, research and regional exchange. Our programmes build confident communicators ready for academic, professional and cross-border opportunities.</p>
                        <a class="nfv-text-action nfv-text-action-light" href="{{ route('website.about') }}">Discover NfLV <span aria-hidden="true">→</span></a>
                    </div>
                    <div class="nfv-benefits">
                        <article><span>01</span><h3>Immersive learning</h3><p>Practice French in a learning environment designed for active communication.</p></article>
                        <article><span>02</span><h3>Specialist teaching</h3><p>Learn with educators focused on French language, culture and pedagogy.</p></article>
                        <article><span>03</span><h3>Regional connection</h3><p>Develop skills that deepen Nigeria’s ties with the wider Francophone world.</p></article>
                    </div>
                </div>
            </section>

            <section class="nfv-section nfv-admissions" id="admissions" aria-labelledby="admissions-heading">
                <div class="nfv-container nfv-admissions-grid">
                    <div>
                        <p class="nfv-kicker nfv-kicker-dark"><span></span> ADMISSIONS</p>
                        <h2 id="admissions-heading">Your next step<br>starts here.</h2>
                    </div>
                    <div class="nfv-admission-steps">
                        <article><span>01</span><h3>Choose a programme</h3><p>Compare programmes and find the option that suits your goals.</p></article>
                        <article><span>02</span><h3>Check requirements</h3><p>Understand eligibility, documents, tuition and upcoming intakes.</p></article>
                        <article><span>03</span><h3>Apply online</h3><p>Submit your application and manage your progress in the student portal.</p></article>
                    </div>
                    <a class="nfv-button nfv-button-primary" href="https://portal.frenchvillage.edu.ng/register">Begin your application <span aria-hidden="true">→</span></a>
                </div>
            </section>

            <section class="nfv-section nfv-news" id="news" aria-labelledby="news-heading">
                <div class="nfv-container">
                    <div class="nfv-section-heading nfv-news-heading">
                        <div><p class="nfv-kicker nfv-kicker-dark"><span></span> FROM NFLV</p><h2 id="news-heading">News &amp; events</h2></div>
                        <a class="nfv-text-action" href="https://www.frenchvillage.edu.ng/event">View all news <span aria-hidden="true">→</span></a>
                    </div>
                    <div class="nfv-news-grid">
                        <article class="nfv-news-feature"><div class="nfv-news-image nfv-news-image-main"><span>Campus &amp; community</span></div><p>FEATURED</p><h3>Discover the people, culture and opportunities that make up life at NfLV.</h3><a href="#contact">Explore campus life <b aria-hidden="true">→</b></a></article>
                        <article class="nfv-news-item"><p>PROGRAMMES · JUNE 2026</p><h3>Diploma in French: Batch A 2026</h3><a href="https://www.frenchvillage.edu.ng/newsletter/78/news">Read story <b aria-hidden="true">→</b></a></article>
                        <article class="nfv-news-item"><p>PROGRAMMES · JUNE 2026</p><h3>Certificate in French: Third Batch 2026</h3><a href="https://www.frenchvillage.edu.ng/newsletter/77/news">Read story <b aria-hidden="true">→</b></a></article>
                        <article class="nfv-news-item"><p>INSTITUTION · APRIL 2026</p><h3>French Embassy officials visit NfLV for a technical meeting</h3><a href="https://www.frenchvillage.edu.ng/newsletter/73/news">Read story <b aria-hidden="true">→</b></a></article>
                    </div>
                </div>
            </section>

            <section class="nfv-section nfv-research" id="research" aria-labelledby="research-heading">
                <div class="nfv-container nfv-research-grid">
                    <p class="nfv-kicker"><span></span> RESEARCH &amp; RESOURCES</p>
                    <h2 id="research-heading">Advancing French studies<br>in Nigeria and beyond.</h2>
                    <p>Explore teaching resources, journals, specialist programmes and opportunities for institutional collaboration.</p>
                    <a class="nfv-text-action nfv-text-action-light" href="https://www.frenchvillage.edu.ng/journals">Explore resources <span aria-hidden="true">→</span></a>
                </div>
            </section>
        </main>

        <footer class="nfv-footer" id="contact">
            <div class="nfv-container nfv-footer-grid">
                <div><a class="nfv-brand nfv-brand-footer" href="#top"><span class="nfv-brand-mark" aria-hidden="true"></span><span><strong>Nigeria French<br>Language Village</strong><small>Badagry, Nigeria</small></span></a><p>French immersion, education, research and cultural exchange.</p></div>
                <div><h2>Explore</h2><a href="{{ route('website.about') }}">About NfLV</a><a href="#programmes">Programmes</a><a href="#admissions">Admissions</a><a href="#news">News &amp; events</a></div>
                <div><h2>Portals</h2><a href="https://portal.frenchvillage.edu.ng/">Student portal</a><a href="{{ route('home') }}">Staff portal</a><a href="https://portal.frenchvillage.edu.ng/paynow">Fees &amp; payments</a><a href="https://www.frenchvillage.edu.ng/contact-us">Contact us</a></div>
                <div><h2>Visit or contact us</h2><p>Badagry, Lagos State, Nigeria</p><a href="https://www.frenchvillage.edu.ng/contact-us">Get in touch <span aria-hidden="true">→</span></a></div>
            </div>
            <div class="nfv-container nfv-footer-bottom"><span>© {{ now()->year }} Nigeria French Language Village</span><span>Privacy · Accessibility · Terms</span></div>
        </footer>
    </div>
@endsection
