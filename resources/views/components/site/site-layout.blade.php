@props(['isAdmin' => false, 'title' => null, 'description' => null])

@php
    $siteName = config('app.name', 'Jarir Blog');
    $siteUrl = rtrim(config('app.url'), '/');
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="{{ $isAdmin ? '#111827' : '#2563eb' }}">

    @if ($title)
        <title>{{ $title }} — {{ $siteName }}</title>
    @else
        <title>{{ $siteName }}</title>
    @endif

    @if ($description)
        <meta name="description" content="{{ $description }}">
    @endif

    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:title" content="{{ $title ?? $siteName }}">
    <meta property="og:description" content="{{ $description ?? 'Insightful articles, news, and stories from around the world.' }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <link rel="canonical" href="{{ url()->current() }}">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $title ?? $siteName }}">
    <meta name="twitter:description" content="{{ $description ?? 'Insightful articles, news, and stories from around the world.' }}">

    <link rel="alternate" type="application/atom+xml" title="{{ $siteName }} feed" href="{{ $siteUrl }}/feed.xml">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50 text-gray-900 antialiased flex flex-col">
    {{-- Site header --}}
    <header class="{{ $isAdmin ? 'bg-gray-900 text-white' : 'bg-white border-b border-gray-200' }}">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
            <div class="flex items-center gap-6">
                <a href="/" class="text-2xl font-bold {{ $isAdmin ? 'text-white' : 'text-blue-600' }}">
                    {{ $siteName }}
                </a>
            </div>
            <div class="flex items-center gap-4 text-sm">
                @auth
                    <a href="{{ route('dashboard') }}" class="{{ $isAdmin ? 'text-gray-300 hover:text-white' : 'text-gray-700 hover:text-blue-600' }}">Dashboard</a>
                    <span class="{{ $isAdmin ? 'text-gray-300' : 'text-gray-700' }}">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="px-3 py-1.5 text-sm bg-gray-700 text-gray-200 rounded-md hover:bg-gray-600 {{ $isAdmin ? '' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                            Log out
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="{{ $isAdmin ? 'text-gray-300 hover:text-white' : 'text-gray-700 hover:text-blue-600' }}">Log in</a>
                    <a href="{{ route('register') }}" class="px-3 py-1.5 bg-blue-600 text-white rounded-md hover:bg-blue-700">Register</a>
                @endauth
            </div>
        </div>
    </header>

    {{-- Page content --}}
    <main class="flex-1 w-full">
        {{ $slot }}
    </main>

    {{-- Site footer --}}
    <footer class="bg-gray-800 text-white mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 text-center text-gray-400 text-sm">
            <p>
                &copy; {{ date('Y') }} {{ $siteName }}. All rights reserved.
                &middot;
                <a href="/feed.xml" class="hover:text-white">RSS</a>
            </p>
        </div>
    </footer>
</body>
</html>
