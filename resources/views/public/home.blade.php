@extends('layouts.public-site')

@section('title', 'Nigeria French Language Village | French immersion and studies')

@section('content')
    <div class="nfv-site nfv-home-editorial">
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
                    @include('partials.public-about-navigation')
                    <a href="{{ route('website.programmes') }}">Study at NfLV</a>
                    <a href="{{ route('website.admissions') }}">Admissions</a>
                    <a href="{{ route('website.research') }}">Research &amp; resources</a>
                    <a href="{{ route('website.news') }}">News &amp; events</a>
                </nav>
                <a class="nfv-header-action" href="https://portal.frenchvillage.edu.ng/register">Apply now</a>
            </div>
        </header>

        <main id="top">
            <section class="nfv-carousel" aria-label="Discover Nigeria French Language Village" aria-roledescription="carousel" data-carousel>
                <article class="nfv-slide is-active nfv-slide-banner" data-slide role="group" aria-roledescription="slide" aria-label="1 of 4" >
                    <img src="{{ asset('media/hero-excellence-navy-blend.png') }}" alt="Nigeria French Language Village: a centre of excellence in French, with the portrait on the left, the original green and black logo blended into the navy design, and French tricolour accents" fetchpriority="high">
                </article>
                <article class="nfv-slide" data-slide role="group" aria-roledescription="slide" aria-label="2 of 4" inert aria-hidden="true">
                    <img src="{{ asset('media/hero-visit.jpg') }}" alt="Institutional visitors at the administrative block" loading="lazy">
                    <div class="nfv-slide-shade"></div>
                    <div class="nfv-container nfv-slide-copy"><p>INSTITUTIONAL COOPERATION</p><h2>French language<br>and educational exchange.</h2><div class="nfv-slide-description">Developing relationships through French language, education and exchange.</div><a class="nfv-hero-link" href="{{ route('website.programmes') }}">View programmes</a></div>
                </article>
                <article class="nfv-slide" data-slide role="group" aria-roledescription="slide" aria-label="3 of 4" inert aria-hidden="true">
                    <img src="{{ asset('media/hero-community.jpg') }}" alt="Students taking part in a campus activity" loading="lazy">
                    <div class="nfv-slide-shade"></div>
                    <div class="nfv-container nfv-slide-copy"><p>STUDENT LIFE</p><h2>Learning French<br>at the Village.</h2><div class="nfv-slide-description">Experience French through active learning and a rich campus community.</div><a class="nfv-hero-link" href="{{ route('website.programmes') }}">View programmes</a></div>
                </article>
                <article class="nfv-slide" data-slide role="group" aria-roledescription="slide" aria-label="4 of 4" inert aria-hidden="true">
                    <img src="{{ asset('media/hero-administration.png') }}" alt="Staff and guests outside the administrative block" loading="lazy">
                    <div class="nfv-slide-shade"></div>
                    <div class="nfv-container nfv-slide-copy"><p>BADAGRY, NIGERIA</p><h2>A centre for<br>French studies.</h2><div class="nfv-slide-description">French immersion, diploma, certificate and short-course programmes.</div><a class="nfv-hero-link" href="{{ route('website.programmes') }}">View programmes</a></div>
                </article>
                <h1 class="nfv-carousel-sr">Nigeria French Language Village</h1>
                <button class="nfv-hero-arrow nfv-hero-prev" type="button" data-hero-prev aria-label="Previous hero slide">‹</button>
                <button class="nfv-hero-arrow nfv-hero-next" type="button" data-hero-next aria-label="Next hero slide">›</button>
                <div class="nfv-hero-pagination" aria-label="Hero slideshow controls">
                    <button type="button" data-hero-go="0" aria-label="Show slide 1" aria-pressed="true"></button>
                    <button type="button" data-hero-go="1" aria-label="Show slide 2" aria-pressed="false"></button>
                    <button type="button" data-hero-go="2" aria-label="Show slide 3" aria-pressed="false"></button>
                    <button type="button" data-hero-go="3" aria-label="Show slide 4" aria-pressed="false"></button>
                </div>
            </section>

            <section class="nfv-quick-links" aria-label="Popular services">
                <div class="nfv-container nfv-quick-links-grid">
                    <a href="{{ route('website.admissions') }}"><strong>Admissions</strong><small>Requirements, intakes and how to apply</small></a>
                    <a href="https://portal.frenchvillage.edu.ng/paynow"><strong>Fees &amp; payments</strong><small>Pay tuition or confirm a payment</small></a>
                    <a href="https://portal.frenchvillage.edu.ng/"><strong>Student portal</strong><small>Registration and student services</small></a>
                    <a href="{{ route('home') }}"><strong>Staff portal</strong><small>Secure staff self-service</small></a>
                </div>
            </section>

            <section class="nfv-discover" aria-labelledby="discover-heading">
                <div class="nfv-container">
                    <div class="nfv-discover-grid">
                        <div class="nfv-programme-directory">
                            <header class="nfv-programme-heading">
                                <h2 id="discover-heading">Study at NFLV</h2>
                                <p>French language programmes for students, teachers and professionals.</p>
                            </header>
                            <div class="nfv-programme-list">
                                <a class="nfv-programme-row" href="{{ route('website.programmes') }}">
                                    <h3>Language immersion</h3>
                                    <p>French studies for universities and Colleges of Education.</p>
                                </a>
                                <a class="nfv-programme-row" href="{{ route('website.programmes') }}">
                                    <h3>Diploma programmes</h3>
                                    <p>Structured study in French language.</p>
                                </a>
                                <a class="nfv-programme-row" href="{{ route('website.programmes') }}">
                                    <h3>Certificate programmes</h3>
                                    <p>Practical French for personal and professional use.</p>
                                </a>
                                <a class="nfv-programme-row" href="{{ route('website.programmes') }}">
                                    <h3>Flexible &amp; short courses</h3>
                                    <p>Evening, weekend and customised programmes.</p>
                                </a>
                            </div>
                            <a class="nfv-editorial-link" href="{{ route('website.programmes') }}">View all programmes</a>
                        </div>
                        <article class="nfv-welcome-panel" aria-labelledby="director-welcome-heading">
                            <div class="nfv-welcome-copy">
                                <h2 id="director-welcome-heading">Director’s welcome</h2>
                                <p class="nfv-welcome-greeting">Ici on parle Francais!</p>
                                <div class="nfv-welcome-summary">
                                    <p>Dear Students and Distinguished Partners,</p>
                                    <p>It is with great pleasure and a profound sense of responsibility that I welcome you to the official digital gateway of the Nigeria French Language Village (NFLV), Badagry,Nigeria’s premier Inter-University Centre for French Studies.</p>
                                    <p>Whether you are a prospective student looking to immerse yourself in the French language, an alumnus reconnecting with your alma mater, a scholar seeking collaborative research opportunities, or a visitor curious about our unique mandate, we are delighted to have you here.</p>
                                </div>
                                <a href="{{ route('website.director-welcome') }}" class="nfv-welcome-link">Read the full message</a>
                            </div>
                            <div class="nfv-welcome-portrait">
                                <img src="{{ asset('media/director-portrait.webp') }}" alt="Prof. Samuel Oladiipo Kolawole, Director and Chief Executive Officer" width="715" height="1000" loading="lazy">
                                <div class="nfv-portrait-caption"><strong>Prof. Samuel Oladiipo Kolawole</strong><span>FNITI, MNAL</span><small>Director &amp; Chief Executive Officer</small></div>
                            </div>
                        </article>
                    </div>
                </div>
            </section>

            <section class="nfv-campus-showcase" aria-labelledby="about-heading" data-campus-showcase>
                <div class="nfv-container nfv-campus-heading">
                    <div><h2 id="about-heading">Campus life</h2><p>Photographs and moments from our campus in Badagry.</p></div>
                    <a class="nfv-campus-explore" href="{{ route('website.about') }}">About the Village</a>
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

            <section class="nfv-updates" id="news" aria-labelledby="news-heading">
                <div class="nfv-container nfv-updates-layout">
                    <div class="nfv-updates-news">
                        <header class="nfv-updates-heading"><h2 id="news-heading">News &amp; events</h2><p>Institutional news and programme announcements.</p></header>
                        <div class="nfv-updates-cards">
                            <article class="nfv-update-card nfv-news-feature">
                                <a class="nfv-update-image" href="{{ route('website.news.french-embassy-technical-meeting') }}" tabindex="-1" aria-hidden="true"><img src="{{ asset('media/hero-visit.jpg') }}" alt="" loading="lazy" width="2048" height="1256"></a>
                                <time datetime="2026-04-15">15 April 2026</time>
                                <h3><a href="{{ route('website.news.french-embassy-technical-meeting') }}">French Embassy officials visit NFLV for a technical meeting</a></h3>
                                <a class="nfv-update-read" href="{{ route('website.news.french-embassy-technical-meeting') }}">Read the report</a>
                            </article>
                            <div class="nfv-news-notices">
                                <article class="nfv-update-card nfv-news-brief">
                                    <time datetime="2026-06-24">24 June 2026 · Admissions</time>
                                    <h3><a href="{{ route('website.news.diploma-french-2026') }}">Diploma in French: Batch A 2026</a></h3>
                                    <a class="nfv-update-read" href="{{ route('website.news.diploma-french-2026') }}">View admission notice</a>
                                </article>
                                <article class="nfv-update-card nfv-news-brief">
                                    <time datetime="2026-06-24">24 June 2026 · Admissions</time>
                                    <h3><a href="{{ route('website.news.certificate-french-2026') }}">Certificate in French: Third Batch 2026</a></h3>
                                    <a class="nfv-update-read" href="{{ route('website.news.certificate-french-2026') }}">View admission notice</a>
                                </article>
                            </div>
                        </div>
                        <a class="nfv-updates-all" href="{{ route('website.news') }}">View all news</a>
                    </div>
                    <aside class="nfv-updates-events" aria-labelledby="events-heading">
                        <h2 id="events-heading">Events</h2>
                        <div class="nfv-events-notice"><p>New event dates will be published here when announced.</p></div>
                        <a class="nfv-updates-all" href="{{ route('website.news') }}">View campus updates</a>
                    </aside>
                </div>
            </section>

            <section class="nfv-section nfv-admissions" id="admissions" aria-labelledby="admissions-heading">
                <div class="nfv-container nfv-admissions-grid">
                    <div>
                        <h2 id="admissions-heading">Admissions</h2>
                    </div>
                    <div class="nfv-admission-steps">
                        <article><h3>Choose a programme</h3><p>Compare programmes and find the option that suits your goals.</p></article>
                        <article><h3>Check requirements</h3><p>Understand eligibility, documents, tuition and upcoming intakes.</p></article>
                        <article><h3>Apply online</h3><p>Submit your application and manage your progress in the student portal.</p></article>
                    </div>
                    <a class="nfv-button nfv-button-primary" href="https://portal.frenchvillage.edu.ng/register">Apply now</a>
                </div>
            </section>

            <section class="nfv-section nfv-research" id="research" aria-labelledby="research-heading">
                <div class="nfv-container nfv-research-grid">
                    <h2 id="research-heading">Library &amp; publications</h2>
                    <p>Explore teaching resources, journals, specialist programmes and opportunities for institutional collaboration.</p>
                    <a class="nfv-text-action nfv-text-action-light" href="{{ route('website.research') }}">Browse teaching resources and journals</a>
                </div>
            </section>
            <section class="nfv-campus-map" aria-label="Find Nigeria French Language Village in Badagry">
                <iframe
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3964.6980390253807!2d2.892601514273933!3d6.432820695345869!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x103b6257735a5093:0x709631afff924fc9!2sNIGERIA+FRENCH+LANGUAGE+VILLAGE!5e0!3m2!1sen!2sng!4v1575147388419!5m2!1sen!2sng"
                    title="Nigeria French Language Village campus location on Google Maps"
                    width="1600"
                    height="360"
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                    allowfullscreen
                ></iframe>
            </section>
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
