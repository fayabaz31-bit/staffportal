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
                        <div><p class="nfv-kicker nfv-kicker-dark nfv-section-label">Discover your next chapter</p><h2 id="discover-heading">Study, connect and <em>thrive at NfLV.</em></h2></div>
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

            <section class="nfv-campus-showcase" aria-labelledby="about-heading" data-campus-showcase>
                <div class="nfv-container nfv-campus-heading">
                    <div><p class="nfv-kicker nfv-kicker-dark nfv-section-label">Why NfLV</p><h2 id="about-heading">Discover a campus<br><em>that brings French to life.</em></h2><p>Green spaces, cultural experiences and a community where learning continues beyond the classroom.</p></div>
                    <a class="nfv-campus-explore" href="{{ route('website.about') }}">Explore NfLV <span aria-hidden="true">→</span></a>
                </div>
                <div class="nfv-campus-reel-wrap">
                    <div class="nfv-campus-reel" data-campus-reel tabindex="0" role="region" aria-label="NfLV campus gallery">
                        <a class="nfv-campus-tile" href="{{ asset('media/campus-brick-building.jpg') }}" target="_blank" rel="noopener" aria-label="Spaces to discover: open full photograph in a new tab"><img class="nfv-campus-photograph" src="{{ asset('media/campus-brick-building.jpg') }}" alt="Brick campus building framed by a traveller palm" loading="lazy"><span class="nfv-campus-tile-caption">Spaces to discover<b aria-hidden="true">↗</b></span></a>
                        <a class="nfv-campus-tile" href="{{ asset('media/campus-palms.jpg') }}" target="_blank" rel="noopener" aria-label="Under the palms: open full photograph in a new tab"><img class="nfv-campus-photograph" src="{{ asset('media/campus-palms.jpg') }}" alt="Palm trees beside NfLV campus buildings" loading="lazy"><span class="nfv-campus-tile-caption">Under the palms<b aria-hidden="true">↗</b></span></a>
                        <a class="nfv-campus-tile" href="{{ asset('media/campus-balconies.jpg') }}" target="_blank" rel="noopener" aria-label="Around the Village: open full photograph in a new tab"><img class="nfv-campus-photograph" src="{{ asset('media/campus-balconies.jpg') }}" alt="NfLV building with open balconies and covered walkways" loading="lazy"><span class="nfv-campus-tile-caption">Around the Village<b aria-hidden="true">↗</b></span></a>
                        <a class="nfv-campus-tile" href="{{ asset('media/campus-green-courtyard.jpg') }}" target="_blank" rel="noopener" aria-label="A greener outlook: open full photograph in a new tab"><img class="nfv-campus-photograph" src="{{ asset('media/campus-green-courtyard.jpg') }}" alt="Landscaped courtyard beside a two-storey NfLV building" loading="lazy"><span class="nfv-campus-tile-caption">A greener outlook<b aria-hidden="true">↗</b></span></a>
                        <a class="nfv-campus-tile" href="{{ asset('media/campus-photo-collection.jpg') }}" target="_blank" rel="noopener" aria-label="Campus building: open full campus photo collection in a new tab"><span class="nfv-campus-crop" style="--campus-x: 0.0000%; --campus-y: 0%;" role="img" aria-label="Campus building"></span><span class="nfv-campus-tile-caption">Campus building<b aria-hidden="true">↗</b></span></a>
                        <a class="nfv-campus-tile" href="{{ asset('media/campus-photo-collection.jpg') }}" target="_blank" rel="noopener" aria-label="Landscaped campus: open full campus photo collection in a new tab"><span class="nfv-campus-crop" style="--campus-x: 39.5170%; --campus-y: 0%;" role="img" aria-label="Landscaped campus"></span><span class="nfv-campus-tile-caption">Landscaped campus<b aria-hidden="true">↗</b></span></a>
                        <a class="nfv-campus-tile" href="{{ asset('media/campus-photo-collection.jpg') }}" target="_blank" rel="noopener" aria-label="Campus gardens: open full campus photo collection in a new tab"><span class="nfv-campus-crop" style="--campus-x: 98.7925%; --campus-y: 0%;" role="img" aria-label="Campus gardens"></span><span class="nfv-campus-tile-caption">Campus gardens<b aria-hidden="true">↗</b></span></a>
                        <a class="nfv-campus-tile" href="{{ asset('media/campus-photo-collection.jpg') }}" target="_blank" rel="noopener" aria-label="Campus road: open full campus photo collection in a new tab"><span class="nfv-campus-crop" style="--campus-x: 19.7585%; --campus-y: 91.6667%;" role="img" aria-label="Campus road"></span><span class="nfv-campus-tile-caption">Campus road<b aria-hidden="true">↗</b></span></a>
                        <a class="nfv-campus-tile" href="{{ asset('media/campus-photo-collection.jpg') }}" target="_blank" rel="noopener" aria-label="Campus architecture: open full campus photo collection in a new tab"><span class="nfv-campus-crop" style="--campus-x: 39.5170%; --campus-y: 91.6667%;" role="img" aria-label="Campus architecture"></span><span class="nfv-campus-tile-caption">Campus architecture<b aria-hidden="true">↗</b></span></a>
                        <a class="nfv-campus-tile" href="{{ asset('media/campus-photo-collection.jpg') }}" target="_blank" rel="noopener" aria-label="Administrative block: open full campus photo collection in a new tab"><span class="nfv-campus-crop" style="--campus-x: 79.0340%; --campus-y: 91.6667%;" role="img" aria-label="Administrative block"></span><span class="nfv-campus-tile-caption">Administrative block<b aria-hidden="true">↗</b></span></a>
                        <figure class="nfv-campus-tile nfv-campus-video-tile"><video controls playsinline preload="none" poster="{{ asset('media/hall-week-poster.jpg') }}" aria-label="NfLV Hall Week video"><source src="{{ asset('media/hall-week.mp4') }}" type="video/mp4"><a href="{{ asset('media/hall-week.mp4') }}">Download Hall Week video</a></video><figcaption>Hall Week · Culture &amp; community</figcaption></figure>
                    </div>
                    <div class="nfv-campus-reel-controls" aria-label="Campus gallery controls"><button type="button" data-campus-prev aria-label="Previous campus photos">←</button><button type="button" data-campus-next aria-label="Next campus photos">→</button></div>
                </div>
            </section>

            <section class="nfv-section nfv-admissions" id="admissions" aria-labelledby="admissions-heading">
                <div class="nfv-container nfv-admissions-grid">
                    <div>
                        <p class="nfv-kicker nfv-kicker-dark nfv-section-label">Admissions</p>
                        <h2 id="admissions-heading">Your next step<br><em>starts here.</em></h2>
                    </div>
                    <div class="nfv-admission-steps">
                        <article><span>01</span><h3>Choose a programme</h3><p>Compare programmes and find the option that suits your goals.</p></article>
                        <article><span>02</span><h3>Check requirements</h3><p>Understand eligibility, documents, tuition and upcoming intakes.</p></article>
                        <article><span>03</span><h3>Apply online</h3><p>Submit your application and manage your progress in the student portal.</p></article>
                    </div>
                    <a class="nfv-button nfv-button-primary" href="https://portal.frenchvillage.edu.ng/register">Begin your application <span aria-hidden="true">→</span></a>
                </div>
            </section>

            <section class="nfv-updates" id="news" aria-labelledby="news-heading">
                <div class="nfv-container nfv-updates-layout">
                    <div class="nfv-updates-news">
                        <header class="nfv-updates-heading"><h2 id="news-heading">News &amp; <em>happenings.</em></h2><p>Stories, announcements and perspectives from the Village.</p></header>
                        <div class="nfv-updates-cards">
                            <article class="nfv-update-card">
                                <a class="nfv-update-image" href="https://www.frenchvillage.edu.ng/newsletter/78/news" tabindex="-1" aria-hidden="true"><img src="{{ asset('media/hero-community.jpg') }}" alt="" loading="lazy" width="2048" height="1261"></a>
                                <time datetime="2026-06-24">24 June 2026</time>
                                <h3><a href="https://www.frenchvillage.edu.ng/newsletter/78/news">Diploma in French: Batch A 2026</a></h3>
                                <a class="nfv-update-read" href="https://www.frenchvillage.edu.ng/newsletter/78/news" aria-label="Read Diploma in French: Batch A 2026">Read story <span aria-hidden="true">↗</span></a>
                            </article>
                            <article class="nfv-update-card">
                                <a class="nfv-update-image" href="https://www.frenchvillage.edu.ng/newsletter/77/news" tabindex="-1" aria-hidden="true"><img src="{{ asset('media/hero-administration.png') }}" alt="" loading="lazy" width="2048" height="984"></a>
                                <time datetime="2026-06-24">24 June 2026</time>
                                <h3><a href="https://www.frenchvillage.edu.ng/newsletter/77/news">Certificate in French: Third Batch 2026</a></h3>
                                <a class="nfv-update-read" href="https://www.frenchvillage.edu.ng/newsletter/77/news" aria-label="Read Certificate in French: Third Batch 2026">Read story <span aria-hidden="true">↗</span></a>
                            </article>
                            <article class="nfv-update-card">
                                <a class="nfv-update-image" href="https://www.frenchvillage.edu.ng/newsletter/73/news" tabindex="-1" aria-hidden="true"><img src="{{ asset('media/hero-visit.jpg') }}" alt="" loading="lazy" width="2048" height="1256"></a>
                                <time datetime="2026-04-15">15 April 2026</time>
                                <h3><a href="https://www.frenchvillage.edu.ng/newsletter/73/news">French Embassy officials visit NfLV for a technical meeting</a></h3>
                                <a class="nfv-update-read" href="https://www.frenchvillage.edu.ng/newsletter/73/news" aria-label="Read about the French Embassy technical meeting">Read story <span aria-hidden="true">↗</span></a>
                            </article>
                        </div>
                        <a class="nfv-updates-all" href="{{ route('website.news') }}">View all news <span aria-hidden="true">↗</span></a>
                    </div>
                    <aside class="nfv-updates-events" aria-labelledby="events-heading">
                        <h2 id="events-heading">Events</h2><p class="nfv-events-subtitle">Upcoming events</p>
                        <div class="nfv-events-notice"><span class="nfv-events-calendar" aria-hidden="true">✦</span><h3>More moments to look forward to.</h3><p>New event dates will be announced here. Explore our news page for programme and campus updates.</p></div>
                        <a class="nfv-updates-all" href="{{ route('website.news') }}">News &amp; event updates <span aria-hidden="true">↗</span></a>
                    </aside>
                </div>
            </section>

            <section class="nfv-section nfv-research" id="research" aria-labelledby="research-heading">
                <div class="nfv-container nfv-research-grid">
                    <p class="nfv-kicker nfv-section-label">Research &amp; resources</p>
                    <h2 id="research-heading">Advancing French studies<br><em>in Nigeria and beyond.</em></h2>
                    <p>Explore teaching resources, journals, specialist programmes and opportunities for institutional collaboration.</p>
                    <a class="nfv-text-action nfv-text-action-light" href="{{ route('website.research') }}">Explore resources <span aria-hidden="true">→</span></a>
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
            @include('partials.public-social-links')
            <div class="nfv-container nfv-footer-bottom"><span>© {{ now()->year }} Nigeria French Language Village</span><span>Privacy · Accessibility · Terms</span></div>
        </footer>
    </div>
@endsection
