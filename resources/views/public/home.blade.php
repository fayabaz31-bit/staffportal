@extends('layouts.public-site')

@section('title', 'Nigeria French Language Village | French immersion and studies')

@section('content')
    <div class="nfv-site nfv-home">
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
                    <a href="{{ route('website.programmes') }}">Study at NFLV</a>
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
                    <p class="nfv-slide-caption">Institutional visitors at the Village</p>
                </article>
                <article class="nfv-slide" data-slide role="group" aria-roledescription="slide" aria-label="3 of 4" inert aria-hidden="true">
                    <img src="{{ asset('media/hero-community.jpg') }}" alt="Students taking part in a campus activity" loading="lazy">
                    <div class="nfv-slide-shade"></div>
                    <p class="nfv-slide-caption">Students gather on campus</p>
                </article>
                <article class="nfv-slide" data-slide role="group" aria-roledescription="slide" aria-label="4 of 4" inert aria-hidden="true">
                    <img src="{{ asset('media/hero-administration.png') }}" alt="Staff and guests outside the administrative block" loading="lazy">
                    <div class="nfv-slide-shade"></div>
                    <p class="nfv-slide-caption">NFLV staff and guests in Badagry</p>
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
                    <a href="{{ route('website.admissions') }}"><strong>Admissions</strong><small>Requirements, intakes and how to apply</small><b aria-hidden="true">→</b></a>
                    <a href="https://portal.frenchvillage.edu.ng/paynow"><strong>Fees &amp; payments</strong><small>Pay tuition or confirm a payment</small><b aria-hidden="true">→</b></a>
                    <a href="https://portal.frenchvillage.edu.ng/"><strong>Student portal</strong><small>Registration and student services</small><b aria-hidden="true">→</b></a>
                    <a href="{{ route('home') }}"><strong>Staff portal</strong><small>Secure staff self-service</small><b aria-hidden="true">→</b></a>
                </div>
            </section>

            <section class="nfv-discover" aria-labelledby="discover-heading">
                <div class="nfv-container">
                    <div class="nfv-discover-heading">
                        <h2 id="discover-heading">Study at NFLV</h2>
                        <a href="{{ route('website.programmes') }}">View all programmes</a>
                    </div>
                    <div class="nfv-discover-grid">
                        <div class="nfv-study-options" role="group" aria-label="Study at NFLV" data-programme-cards>
                            <a class="nfv-study-option" href="{{ route('website.programmes') }}">
                                <svg class="nfv-study-icon" viewBox="0 0 48 48" aria-hidden="true" focusable="false"><path d="M5 8h26v17H17l-8 6v-6H5z"/><path d="M32 17h11v17h-4v6l-8-6H19v-6M11 14h14M11 19h9"/></svg>
                                <h3>Language immersion</h3>
                                <p>French studies for universities and Colleges of Education.</p>
                            </a>
                            <a class="nfv-study-option" href="{{ route('website.programmes') }}">
                                <svg class="nfv-study-icon" viewBox="0 0 48 48" aria-hidden="true" focusable="false"><path d="m3 17 21-9 21 9-21 9-21-9Zm9 4v12c7 6 17 6 24 0V21m6-3v11"/><circle cx="42" cy="32" r="3"/></svg>
                                <h3>Diploma programmes</h3>
                                <p>Build your French language skills through structured study.</p>
                            </a>
                            <a class="nfv-study-option" href="{{ route('website.programmes') }}">
                                <svg class="nfv-study-icon" viewBox="0 0 48 48" aria-hidden="true" focusable="false"><path d="M31 35H6V8h36v19M12 15h24m-24 6h24m-24 6h12"/><circle cx="35" cy="32" r="6"/><path d="m31 37-2 7 6-3 6 3-2-7"/></svg>
                                <h3>Certificate programmes</h3>
                                <p>Develop practical French for personal and professional goals.</p>
                            </a>
                            <a class="nfv-study-option" href="{{ route('website.programmes') }}">
                                <svg class="nfv-study-icon" viewBox="0 0 48 48" aria-hidden="true" focusable="false"><path d="M6 12h36v27H6zm0 8h36M14 7v10M34 7v10"/><circle cx="25" cy="30" r="6"/><path d="M25 27v4l3 1"/></svg>
                                <h3>Flexible &amp; short courses</h3>
                                <p>Evening, weekend and customised learning experiences.</p>
                            </a>
                        </div>
                        <article class="nfv-welcome-panel" aria-labelledby="director-welcome-heading">
                            <div class="nfv-welcome-copy">
                                <h2 id="director-welcome-heading">Bienvenue.<br>Welcome to the Village.</h2>
                                <p class="nfv-welcome-greeting">Ici on parle Francais!</p>
                                <div class="nfv-welcome-summary">
                                    <p>Dear Students and Distinguished Partners,</p>
                                    <p>It is with great pleasure and a profound sense of responsibility that I welcome you to the official digital gateway of the Nigeria French Language Village (NFLV), Badagry,Nigeria’s premier Inter-University Centre for French Studies.</p>
                                    <p>Whether you are a prospective student looking to immerse yourself in the French language, an alumnus reconnecting with your alma mater, a scholar seeking collaborative research opportunities, or a visitor curious about our unique mandate, we are delighted to have you here.</p>
                                </div>
                                <a href="{{ route('website.director-welcome') }}" class="nfv-welcome-link" aria-label="Read more of the Director’s welcome message">Read more</a>
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
                    <h2 id="about-heading">Discover our campus</h2>
                    <p>Green spaces, cultural experiences and a community where learning continues beyond the classroom.</p>
                    <a class="nfv-campus-explore" href="{{ route('website.about') }}">Explore NFLV <span aria-hidden="true">→</span></a>
                </div>
                <div class="nfv-campus-reel-wrap">
                    <div class="nfv-campus-reel" id="campus-gallery" data-campus-reel tabindex="0" role="region" aria-label="NFLV campus gallery">
                        <a class="nfv-campus-tile" href="{{ asset('media/campus-brick-building.jpg') }}" target="_blank" rel="noopener" aria-label="Spaces to discover: open full photograph in a new tab"><img class="nfv-campus-photograph" src="{{ asset('media/campus-brick-building.jpg') }}" alt="Brick campus building framed by a traveller palm" loading="lazy"></a>
                        <a class="nfv-campus-tile" href="{{ asset('media/campus-palms.jpg') }}" target="_blank" rel="noopener" aria-label="Under the palms: open full photograph in a new tab"><img class="nfv-campus-photograph" src="{{ asset('media/campus-palms.jpg') }}" alt="Palm trees beside NFLV campus buildings" loading="lazy"></a>
                        <a class="nfv-campus-tile" href="{{ asset('media/campus-balconies.jpg') }}" target="_blank" rel="noopener" aria-label="Around the Village: open full photograph in a new tab"><img class="nfv-campus-photograph" src="{{ asset('media/campus-balconies.jpg') }}" alt="NFLV building with open balconies and covered walkways" loading="lazy"></a>
                        <a class="nfv-campus-tile" href="{{ asset('media/campus-green-courtyard.jpg') }}" target="_blank" rel="noopener" aria-label="A greener outlook: open full photograph in a new tab"><img class="nfv-campus-photograph" src="{{ asset('media/campus-green-courtyard.jpg') }}" alt="Landscaped courtyard beside a two-storey NFLV building" loading="lazy"></a>
                        <a class="nfv-campus-tile" href="{{ asset('media/campus-photo-collection.jpg') }}" target="_blank" rel="noopener" aria-label="Campus building: open full campus photo collection in a new tab"><span class="nfv-campus-crop" style="--campus-x: 0.0000%; --campus-y: 0%;" role="img" aria-label="Campus building"></span></a>
                        <a class="nfv-campus-tile" href="{{ asset('media/campus-photo-collection.jpg') }}" target="_blank" rel="noopener" aria-label="Landscaped campus: open full campus photo collection in a new tab"><span class="nfv-campus-crop" style="--campus-x: 39.5170%; --campus-y: 0%;" role="img" aria-label="Landscaped campus"></span></a>
                        <a class="nfv-campus-tile" href="{{ asset('media/campus-photo-collection.jpg') }}" target="_blank" rel="noopener" aria-label="Campus road: open full campus photo collection in a new tab"><span class="nfv-campus-crop" style="--campus-x: 19.7585%; --campus-y: 91.6667%;" role="img" aria-label="Campus road"></span></a>
                        <a class="nfv-campus-tile" href="{{ asset('media/campus-photo-collection.jpg') }}" target="_blank" rel="noopener" aria-label="Campus architecture: open full campus photo collection in a new tab"><span class="nfv-campus-crop" style="--campus-x: 39.5170%; --campus-y: 91.6667%;" role="img" aria-label="Campus architecture"></span></a>
                        <a class="nfv-campus-tile" href="{{ asset('media/campus-photo-collection.jpg') }}" target="_blank" rel="noopener" aria-label="Administrative block: open full campus photo collection in a new tab"><span class="nfv-campus-crop" style="--campus-x: 79.0340%; --campus-y: 91.6667%;" role="img" aria-label="Administrative block"></span></a>
                        <figure class="nfv-campus-tile nfv-campus-video-tile"><video controls playsinline preload="none" poster="{{ asset('media/hall-week-poster.jpg') }}" aria-label="NFLV Hall Week video"><source src="{{ asset('media/hall-week.mp4') }}" type="video/mp4"><a href="{{ asset('media/hall-week.mp4') }}">Download Hall Week video</a></video><figcaption>Hall Week · Culture &amp; community</figcaption></figure>
                    </div>
                    <div class="nfv-campus-reel-controls" role="group" aria-label="Campus gallery controls">
                        <button type="button" data-campus-prev aria-label="Previous campus photos" aria-controls="campus-gallery"><svg viewBox="0 0 48 48" aria-hidden="true" focusable="false"><circle cx="24" cy="24" r="21"/><path d="m27 15-9 9 9 9"/></svg></button>
                        <button type="button" data-campus-next aria-label="Next campus photos" aria-controls="campus-gallery"><svg viewBox="0 0 48 48" aria-hidden="true" focusable="false"><circle cx="24" cy="24" r="21"/><path d="m21 15 9 9-9 9"/></svg></button>
                    </div>
                </div>
            </section>

            <section class="nfv-newsroom" id="news" aria-labelledby="news-heading">
                <div class="nfv-container">
                    <header class="nfv-newsroom-header">
                        <h2 id="news-heading">News &amp; events</h2>
                        <a class="nfv-newsroom-more" href="{{ route('website.news') }}">See more news</a>
                    </header>
                    @include('partials.public-news-list')
                </div>
            </section>

            <section class="nfv-section nfv-research" id="research" aria-labelledby="research-heading">
                <div class="nfv-container nfv-research-grid">
                    <h2 id="research-heading">French studies resources</h2>
                    <p>Explore teaching resources, journals, specialist programmes and opportunities for institutional collaboration.</p>
                    <a class="nfv-text-action" href="{{ route('website.research') }}">Explore resources <span aria-hidden="true">→</span></a>
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
                <div><h2>Explore</h2><a href="{{ route('website.about') }}">About NFLV</a><a href="{{ route('website.programmes') }}">Programmes</a><a href="{{ route('website.admissions') }}">Admissions</a><a href="{{ route('website.news') }}">News &amp; events</a></div>
                <div><h2>Portals</h2><a href="https://portal.frenchvillage.edu.ng/">Student portal</a><a href="{{ route('home') }}">Staff portal</a><a href="https://portal.frenchvillage.edu.ng/paynow">Fees &amp; payments</a><a href="https://www.frenchvillage.edu.ng/contact-us">Contact us</a></div>
                @include('partials.public-footer-contact')
            </div>
            <div class="nfv-container nfv-footer-bottom"><span>© {{ now()->year }} Nigeria French Language Village</span></div>
        </footer>
    </div>
@endsection
