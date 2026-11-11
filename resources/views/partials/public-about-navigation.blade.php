<div class="nfv-nav-about">
    <a href="{{ route('website.about') }}">About NfLV</a>
    <details class="nfv-about-dropdown">
        <summary aria-label="Open About NfLV submenu"><span aria-hidden="true">⌄</span></summary>
        <div class="nfv-about-submenu"><a href="{{ route('website.director-welcome') }}" @if (request()->routeIs('website.director-welcome')) aria-current="page" @endif>Director’s welcome message</a></div>
    </details>
</div>
