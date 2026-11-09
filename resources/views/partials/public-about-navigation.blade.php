<div class="nfv-nav-about">
    <a href="{{ route('website.about') }}" @if (request()->routeIs('website.about', 'website.director-welcome')) class="nfv-nav-active" @endif @if (request()->routeIs('website.about')) aria-current="page" @endif>About NFLV</a>
    <details class="nfv-about-dropdown">
        <summary aria-label="Open About NfLV submenu"><span aria-hidden="true">⌄</span></summary>
        <div class="nfv-about-submenu"><a href="{{ route('website.director-welcome') }}" @if (request()->routeIs('website.director-welcome')) aria-current="page" @endif>Director’s welcome message</a></div>
    </details>
</div>
