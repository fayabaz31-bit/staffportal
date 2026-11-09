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

<header class="nfv-header" data-public-header>
    <div class="nfv-container nfv-header-content">
        <a class="nfv-brand" href="{{ route('website.preview') }}" aria-label="Nigeria French Language Village home">
            <span class="nfv-brand-mark" aria-hidden="true"></span>
            <span><strong>Nigeria French<br>Language Village</strong><small>Inter-University Centre for French Studies</small></span>
        </a>
        <button class="nfv-menu-toggle" type="button" aria-controls="public-main-navigation" aria-expanded="false" data-public-menu-toggle hidden>
            Menu <span aria-hidden="true">+</span>
        </button>
        <nav class="nfv-main-nav" id="public-main-navigation" aria-label="Main navigation">
            @include('partials.public-about-navigation')
            <a href="{{ route('website.programmes') }}" @if (request()->routeIs('website.programmes')) class="nfv-nav-active" aria-current="page" @endif>Study at NFLV</a>
            <a href="{{ route('website.admissions') }}" @if (request()->routeIs('website.admissions')) class="nfv-nav-active" aria-current="page" @endif>Admissions</a>
            <a href="{{ route('website.research') }}" @if (request()->routeIs('website.research')) class="nfv-nav-active" aria-current="page" @endif>Research &amp; resources</a>
            <a href="{{ route('website.news') }}" @if (request()->routeIs('website.news', 'website.news.*')) class="nfv-nav-active" @endif @if (request()->routeIs('website.news')) aria-current="page" @endif>News &amp; events</a>
        </nav>
        <a class="nfv-header-action" href="https://portal.frenchvillage.edu.ng/register">Apply now</a>
    </div>
</header>
