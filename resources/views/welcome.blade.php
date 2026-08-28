<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        @php
            $siteName = config('app.name', 'Jarir Blog');
            $siteDescription = 'Insightful articles, news, and stories from around the world.';
            $siteUrl = rtrim(config('app.url'), '/');
        @endphp

        <title>{{ $siteName }}</title>
        <meta name="description" content="{{ $siteDescription }}">

        {{-- Open Graph --}}
        <meta property="og:site_name" content="{{ $siteName }}">
        <meta property="og:title" content="{{ $siteName }}">
        <meta property="og:description" content="{{ $siteDescription }}">
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ $siteUrl }}">

        {{-- Twitter --}}
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $siteName }}">
        <meta name="twitter:description" content="{{ $siteDescription }}">

        {{-- RSS --}}
        <link rel="alternate" type="application/atom+xml" title="{{ $siteName }} feed" href="{{ $siteUrl }}/feed.xml">

        {{-- Sitemap discoverability --}}
        <link rel="canonical" href="{{ $siteUrl }}">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <div id="app"></div>
    </body>
</html>
