@extends('layouts.public-site')

@section('title', 'Nigeria French Language Village | French immersion and studies')

@section('content')
    <div class="nfv-site">
        <div class="nfv-utility-bar">
            <div class="nfv-container nfv-utility-content">
                <p>Inter-University Centre for French Studies · Badagry, Nigeria</p>
                <nav aria-label="Utility navigation">
                    <a href="{{ route('website.preview') }}">Home</a>
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
                    <span>
                        <strong>Nigeria French<br>Language Village</strong>
                        <small>Inter-University Centre for French Studies</small>
                    </span>
                </a>
                <nav class="nfv-main-nav" aria-label="Main navigation">
                    <a href="{{ route('website.about') }}">About NfLV</a>
                    <a href="{{ route('website.programmes') }}">Study at NfLV</a>
                    <a href="{{ route('website.admissions') }}">Admissions</a>
                    <a href="{{ route('website.research') }}">Research &amp; resources</a>
                    <a href="{{ route('website.news') }}">News &amp; events</a>
                </nav>
                <a class="nfv-header-action" href="https://portal.frenchvillage.edu.ng/register">Apply now <span aria-hidden="true">↗</span></a>
            </div>
        </header>

        <main id="top">
            <section class="nfv-carousel" aria-label="Discover Nigeria French Language Village" aria-roledescription="carousel" data-carousel>
                <article class="nfv-slide is-active nfv-slide-banner" data-slide role="group" aria-roledescription="slide" aria-label="1 of 4" >
                    <img src="{{ asset('media/hero-excellence.png') }}" alt="Nigeria French Language Village: a centre of excellence in French" fetchpriority="high">
                </article>
                <article class="nfv-slide" data-slide role="group" aria-roledescription="slide" aria-label="2 of 4" inert aria-hidden="true">
                    <img src="{{ asset('media/hero-visit.jpg') }}" alt="Institutional visitors at the administrative block" loading="lazy">
                    <div class="nfv-slide-shade"></div>
                    <div class="nfv-container nfv-slide-copy"><p>CONNECTIONS THAT MATTER</p><h2>Opening doors.<br>Connecting cultures.</h2><div class="nfv-slide-description">Developing relationships through French language, education and exchange.</div><a class="nfv-button" href="{{ route('website.programmes') }}">Explore programmes <span aria-hidden="true">→</span></a></div>
                </article>
                <article class="nfv-slide" data-slide role="group" aria-roledescription="slide" aria-label="3 of 4" inert aria-hidden="true">
                    <img src="{{ asset('media/hero-community.jpg') }}" alt="Students taking part in a campus activity" loading="lazy">
                    <div class="nfv-slide-shade"></div>
                    <div class="nfv-container nfv-slide-copy"><p>A LIVING LANGUAGE. A VIBRANT COMMUNITY.</p><h2>Learn together.<br>Go further.</h2><div class="nfv-slide-description">Experience French through active learning and a rich campus community.</div><a class="nfv-button" href="{{ route('website.programmes') }}">Explore programmes <span aria-hidden="true">→</span></a></div>
                </article>
                <article class="nfv-slide" data-slide role="group" aria-roledescription="slide" aria-label="4 of 4" inert aria-hidden="true">
                    <img src="{{ asset('media/hero-administration.png') }}" alt="Staff and guests outside the administrative block" loading="lazy">
                    <div class="nfv-slide-shade"></div>
                    <div class="nfv-container nfv-slide-copy"><p>WELCOME TO BADAGRY</p><h2>Your journey to<br>French starts here.</h2><div class="nfv-slide-description">Discover immersion programmes and flexible pathways for your next chapter.</div><a class="nfv-button" href="{{ route('website.programmes') }}">Explore programmes <span aria-hidden="true">→</span></a></div>
                </article>
                <h1 class="nfv-carousel-sr">Nigeria French Language Village</h1>
            </section>

            <section class="nfv-quick-links" aria-label="Popular services">
                <div class="nfv-container nfv-quick-links-grid">
                    <a href="{{ route('website.admissions') }}"><strong>Admissions</strong><small>Requirements, intakes and how to apply</small><b aria-hidden="true">→</b></a>
                    <a href="https://portal.frenchvillage.edu.ng/paynow"><strong>Fees &amp; payments</strong><small>Pay tuition or confirm a payment</small><b aria-hidden="true">→</b></a>
                    <a href="https://portal.frenchvillage.edu.ng/"><strong>Student portal</strong><small>Registration and student services</small><b aria-hidden="true">→</b></a>
                    <a href="{{ route('home') }}"><strong>Staff portal</strong><small>Secure staff self-service</small><b aria-hidden="true">→</b></a>
                </div>
            </section>

            <section class="nfv-discover" aria-labelledby="discover-heading">
                <div class="nfv-container">
                    <div class="nfv-discover-heading">
                        <div><p class="nfv-kicker nfv-kicker-dark"><span></span> DISCOVER YOUR NEXT CHAPTER</p><h2 id="discover-heading">Study, connect and thrive at NfLV.</h2></div>
                        <a class="nfv-text-action" href="{{ route('website.programmes') }}">All programmes <span aria-hidden="true">→</span></a>
                    </div>
                    <div class="nfv-discover-grid">
                        <div class="nfv-study-options" aria-label="Study at NfLV">
                            <a class="nfv-study-option" href="{{ route('website.programmes') }}">
                                <span class="nfv-study-symbol" aria-hidden="true">Aa</span>
                                <span class="nfv-study-category">IMMERSIVE LEARNING</span>
                                <h3>Language immersion</h3>
                                <p>French studies for universities and Colleges of Education.</p>
                                <span class="nfv-study-action">Explore programme <b aria-hidden="true">↗</b></span>
                            </a>
                            <a class="nfv-study-option" href="{{ route('website.programmes') }}">
                                <span class="nfv-study-symbol" aria-hidden="true">D</span>
                                <span class="nfv-study-category">DEVELOP YOUR POTENTIAL</span>
                                <h3>Diploma programmes</h3>
                                <p>Build your French language skills through structured study.</p>
                                <span class="nfv-study-action">Find your pathway <b aria-hidden="true">↗</b></span>
                            </a>
                            <a class="nfv-study-option" href="{{ route('website.programmes') }}">
                                <span class="nfv-study-symbol" aria-hidden="true">C</span>
                                <span class="nfv-study-category">TAKE THE NEXT STEP</span>
                                <h3>Certificate programmes</h3>
                                <p>Develop practical French for personal and professional goals.</p>
                                <span class="nfv-study-action">Discover options <b aria-hidden="true">↗</b></span>
                            </a>
                            <a class="nfv-study-option" href="{{ route('website.programmes') }}">
                                <span class="nfv-study-symbol" aria-hidden="true">↗</span>
                                <span class="nfv-study-category">LEARNING THAT FITS</span>
                                <h3>Flexible &amp; short courses</h3>
                                <p>Evening, weekend and customised learning experiences.</p>
                                <span class="nfv-study-action">Explore courses <b aria-hidden="true">↗</b></span>
                            </a>
                        </div>
                        <article class="nfv-welcome-panel" aria-labelledby="director-welcome-heading">
                            <div class="nfv-welcome-copy">
                                <p class="nfv-kicker"><span></span> A MESSAGE FROM OUR DIRECTOR</p>
                                <h2 id="director-welcome-heading">Bienvenue.<br>Welcome to the Village.</h2>
                                <p class="nfv-welcome-greeting">Ici on parle Français!</p>
                                <div class="nfv-welcome-summary">
                                    <p>The Director welcomes students, alumni, researchers and partners to a community connecting Nigeria with the Francophone world.</p>
                                    <p>His address celebrates learning through immersion, cultural understanding and practical communication, and invites you to discover NfLV’s programmes, collaborations and campus life in Badagry.</p>
                                </div>
                                <div class="nfv-welcome-signoff"><strong>Prof. Samuel Oladiipo Kolawole</strong><span>FNITI, MNAL</span><small>Director &amp; Chief Executive Officer</small></div>
                                <a href="https://www.frenchvillage.edu.ng/" class="nfv-welcome-link">Read the full welcome address <span aria-hidden="true">→</span></a>
                            </div>
                            <div class="nfv-welcome-portrait">
                                <img src="{{ asset('media/director-portrait.webp') }}" alt="Prof. Samuel Oladiipo Kolawole, Director and Chief Executive Officer" width="715" height="1000" loading="lazy">
                                <span>LEADERSHIP WITH PURPOSE</span>
                            </div>
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
                        <a class="nfv-text-action" href="{{ route('website.news') }}">View all news <span aria-hidden="true">→</span></a>
                    </div>
                    <div class="nfv-news-grid">
                        <article class="nfv-news-feature"><div class="nfv-news-image nfv-news-image-main"><span>Campus &amp; community</span></div><p>FEATURED</p><h3>Discover the people, culture and opportunities that make up life at NfLV.</h3><a href="{{ route('website.about') }}">Explore campus life <b aria-hidden="true">→</b></a></article>
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
                <div><a class="nfv-brand nfv-brand-footer" href="{{ route('website.preview') }}"><span class="nfv-brand-mark" aria-hidden="true"></span><span><strong>Nigeria French<br>Language Village</strong><small>Badagry, Nigeria</small></span></a><p>French immersion, education, research and cultural exchange.</p></div>
                <div><h2>Explore</h2><a href="{{ route('website.about') }}">About NfLV</a><a href="{{ route('website.programmes') }}">Programmes</a><a href="{{ route('website.admissions') }}">Admissions</a><a href="{{ route('website.news') }}">News &amp; events</a></div>
                <div><h2>Portals</h2><a href="https://portal.frenchvillage.edu.ng/">Student portal</a><a href="{{ route('home') }}">Staff portal</a><a href="https://portal.frenchvillage.edu.ng/paynow">Fees &amp; payments</a><a href="https://www.frenchvillage.edu.ng/contact-us">Contact us</a></div>
                <div><h2>Visit or contact us</h2><p>Badagry, Lagos State, Nigeria</p><a href="https://www.frenchvillage.edu.ng/contact-us">Get in touch <span aria-hidden="true">→</span></a></div>
            </div>
            <div class="nfv-container nfv-footer-bottom"><span>© {{ now()->year }} Nigeria French Language Village</span><span>Privacy · Accessibility · Terms</span></div>
        </footer>
    </div>
@endsection
