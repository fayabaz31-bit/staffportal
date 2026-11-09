<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#123f35">
        <meta name="description" content="Nigeria French Language Village — French immersion, professional programmes, research and cultural exchange.">
        <title>@yield('title', 'Nigeria French Language Village')</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700&family=Newsreader:opsz,wght@6..72,400;6..72,500;6..72,600&display=swap" rel="stylesheet">
        @vite(['resources/css/public-site.css', 'resources/js/public-site.js'])
    </head>
    <body>
        <a class="nfv-skip-link" href="#main-content">Skip to main content</a>
        @yield('content')
    </body>
</html>
