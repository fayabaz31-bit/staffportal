@extends('layouts.public-site')

@section('title', 'About NFLV | Nigeria French Language Village')

@section('content')
    <div class="nfv-site nfv-about-page">
        <div class="nfv-utility-bar">
            <div class="nfv-container nfv-utility-content">
                <p>Inter-University Centre for French Studies · Badagry, Nigeria</p>
                <nav aria-label="Utility navigation">
                    <a href="{{ route('website.contact') }}">Contact</a>
                    <a href="{{ route('website.news') }}">News</a>
                    <a href="{{ route('student.login') }}">Student portal</a>
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
                    <a href="{{ route('website.programmes') }}">Study at NFLV</a>
                    <a href="{{ route('website.admissions') }}">Admissions</a>
                    <a href="{{ route('website.research') }}">Research &amp; resources</a>
                    <a href="{{ route('website.news') }}">News &amp; events</a>
                    @include('partials.public-tetfund-navigation')
                </nav>
                <a class="nfv-header-action" href="{{ route('website.admissions') }}">Apply now</a>
            </div>
        </header>

        <main>
            <section class="nfv-about-hero" aria-labelledby="about-page-heading">
                <img class="nfv-about-hero-photo" src="{{ asset('media/campus-palms.jpg') }}" alt="" fetchpriority="high">
                <div class="nfv-container nfv-about-hero-copy">
                    <h1 id="about-page-heading">About NFLV</h1>
                    <p>The Nigeria French Language Village is an Inter-University Centre for French Studies in Badagry, Lagos State.</p>
                </div>
            </section>

            <nav class="nfv-about-jump" aria-label="About page sections">
                <div class="nfv-container">
                    <ul>
                        <li><a href="#overview">Overview</a></li>
                        <li><a href="#vision">Vision</a></li>
                        <li><a href="#mission">Mission</a></li>
                        <li><a href="#leadership">Leadership</a></li>
                        <li><a href="#history">History</a></li>
                        <li><a href="#mandate">Mandate</a></li>
                    </ul>
                </div>
            </nav>

            <section class="nfv-about-overview" id="overview" aria-labelledby="overview-heading">
                <div class="nfv-container">
                    <div class="nfv-about-split nfv-about-overview-intro">
                        <h2 id="overview-heading">French immersion in Badagry</h2>
                        <div class="nfv-about-prose">
                            <p>At the Village, students practise French in class and in everyday life. Debates, drama and other campus activities give them opportunities to use what they learn.</p>
                            <p>NFLV runs immersion for university students and an acculturation programme for Colleges of Education. It also offers specialist training for professionals and other learners.</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="nfv-about-purpose" aria-label="Vision and mission">
                <div class="nfv-container nfv-about-purpose-grid">
                    <article class="nfv-about-prose" id="vision" aria-labelledby="vision-heading">
                        <h2 id="vision-heading">Our vision</h2>
                        <p>To give people of every age, culture, belief and sex the skills to use French effectively in their work and everyday relationships.</p>
                        <p lang="fr">Donner à chacun, quels que soient son âge, sa culture, ses croyances ou son sexe, les moyens de communiquer efficacement en français dans la vie professionnelle et les relations personnelles.</p>
                    </article>
                    <article class="nfv-about-prose" id="mission" aria-labelledby="mission-heading">
                        <h2 id="mission-heading">Our mission</h2>
                        <p>To develop a centre of excellence for French studies, research and language development in Nigeria. Through skilled staff, modern technology and teaching methods, NFLV promotes bilingualism across borders to support nation-building, regional integration and international cooperation.</p>
                        <p lang="fr">Faire du Village un centre d’excellence pour l’enseignement, la recherche et la diffusion du français au Nigeria. Le Village s’appuie sur un personnel qualifié et sur les technologies éducatives pour développer le bilinguisme au-delà des frontières et favoriser l’intégration régionale, la coopération internationale et la compréhension mutuelle.</p>
                    </article>
                </div>
            </section>

            <section class="nfv-about-leadership" id="leadership" aria-labelledby="leadership-heading">
                <div class="nfv-container">
                    <h2 id="leadership-heading">Our leadership</h2>
                    <a class="nfv-about-link" href="{{ route('website.leadership') }}">See Management, Governing Council Members and Deans</a>
                </div>
            </section>

            <section class="nfv-about-story" id="history" aria-labelledby="history-heading">
                <div class="nfv-container nfv-about-split">
                    <div class="nfv-about-prose">
                        <h2 id="history-heading">Our history</h2>
                        <p>The Village was established in December 1991 under the National Universities Commission. It gave students on BA and BEd French programmes in Nigerian universities a local alternative to their compulsory year abroad.</p>
                        <p>It also gave Colleges of Education a place to run French acculturation programmes. The Village has since served federal, state and private universities, as well as students of bilingual secretarial studies at some polytechnics.</p>
                    </div>
                    <figure>
                        <img src="{{ asset('media/campus-brick-building.jpg') }}" alt="A brick campus building behind a traveller palm" loading="lazy">
                        <figcaption>The Nigeria French Language Village campus in Badagry.</figcaption>
                    </figure>
                </div>
            </section>

            <section class="nfv-about-mandate" id="mandate" aria-labelledby="mandate-heading">
                <div class="nfv-container">
                    <h2 id="mandate-heading">Our mandate</h2>
                    <div class="nfv-about-mandate-grid">
                        <section aria-labelledby="mandate-learners">
                            <h3 id="mandate-learners">Learners and teachers</h3>
                            <ul>
                                <li>Provide French immersion in Nigeria for students in universities and Colleges of Education.</li>
                                <li>Retrain secondary school French teachers to support national language policy.</li>
                                <li>Offer tailored French programmes for interested Nigerians.</li>
                            </ul>
                        </section>
                        <section aria-labelledby="mandate-institutions">
                            <h3 id="mandate-institutions">Academic institutions</h3>
                            <ul>
                                <li>Help tertiary institutions access staff, materials and facilities for teaching, learning and research.</li>
                                <li>Coordinate French teaching and learning activities across Nigeria.</li>
                                <li>Work with relevant agencies on examinations, curricula and French-language documentation.</li>
                            </ul>
                        </section>
                        <section aria-labelledby="mandate-cooperation">
                            <h3 id="mandate-cooperation">Policy and cooperation</h3>
                            <ul>
                                <li>Explore practical uses of French in Nigeria.</li>
                                <li>Promote economic, technical and social integration across Africa.</li>
                                <li>Provide information and expertise to government and businesses for policy and decision-making.</li>
                            </ul>
                        </section>
                    </div>
                </div>
            </section>

            <section class="nfv-about-visit" aria-labelledby="about-visit-heading">
                <div class="nfv-container nfv-about-visit-grid">
                    <div>
                        <h2 id="about-visit-heading">Visit or study at NFLV</h2>
                        <p>Find the campus in Badagry or see the French programmes available at the Village.</p>
                    </div>
                    <div class="nfv-about-actions">
                        <a class="nfv-button" href="{{ route('website.programmes') }}">View programmes</a>
                        <a class="nfv-about-link" href="{{ route('website.contact') }}">Visit &amp; contact us <span aria-hidden="true">→</span></a>
                    </div>
                </div>
            </section>
        </main>

        <footer class="nfv-footer" id="contact">
            <div class="nfv-container nfv-footer-grid">
                <div><a class="nfv-brand nfv-brand-footer" href="{{ route('website.preview') }}"><span class="nfv-brand-mark" aria-hidden="true"></span><span><strong>Nigeria French<br>Language Village</strong><small>Badagry, Nigeria</small></span></a>@include('partials.public-footer-intro')</div>
                <div><h2>Explore</h2><a href="{{ route('website.about') }}">About NFLV</a><a href="{{ route('website.programmes') }}">Programmes</a><a href="{{ route('website.admissions') }}">Admissions</a><a href="{{ route('website.news') }}">News &amp; events</a></div>
                <div><h2>Portals</h2><a href="{{ route('student.login') }}">Student portal</a><a href="{{ route('home') }}">Staff portal</a><a href="{{ route('website.fees') }}">Fees &amp; payments</a><a href="{{ route('website.contact') }}">Contact us</a></div>
                @include('partials.public-footer-contact')
            </div>
            <div class="nfv-container nfv-footer-bottom"><span>© {{ now()->year }} Nigeria French Language Village</span></div>
        </footer>
    </div>
@endsection
