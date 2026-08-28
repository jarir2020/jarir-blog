<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#2563eb">

    @php
        $siteName = config('app.name', 'Jarir Blog');
        $siteUrl = rtrim(config('app.url'), '/');
    @endphp

    <title>Dashboard — {{ $siteName }}</title>
    <meta name="description" content="Your {{ $siteName }} dashboard.">
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:title" content="Dashboard — {{ $siteName }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <link rel="canonical" href="{{ url()->current() }}">

    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-gray-50 text-gray-900 antialiased">
    <header class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
            <a href="/" class="text-2xl font-bold text-blue-600">{{ $siteName }}</a>
            <nav class="flex items-center space-x-6 text-sm">
                <a href="/" class="text-gray-700 hover:text-blue-600">Home</a>
                <a href="{{ route('profile.edit') }}" class="text-gray-700 hover:text-blue-600">Profile</a>
                <span class="text-gray-700">{{ auth()->user()?->name ?? 'Guest' }}</span>
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="px-3 py-1.5 text-sm bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200">
                        Log out
                    </button>
                </form>
            </nav>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">
            Welcome back, {{ auth()->user()?->name ?? 'there' }}
        </h1>
        <p class="text-gray-600 mb-8">
            @if ($isAdmin)
                You're signed in as an admin. Manage the blog below.
            @else
                You're signed in to your {{ $siteName }} account.
            @endif
        </p>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @if ($isAdmin)
                <a href="{{ url('/admin') }}"
                   class="block bg-white rounded-lg shadow-sm p-6 hover:shadow-md transition-shadow">
                    <h2 class="text-lg font-semibold text-gray-900 mb-1">Admin dashboard</h2>
                    <p class="text-sm text-gray-600">
                        Manage posts, moderate comments, view subscribers.
                    </p>
                </a>
                <a href="{{ url('/admin/posts') }}"
                   class="block bg-white rounded-lg shadow-sm p-6 hover:shadow-md transition-shadow">
                    <h2 class="text-lg font-semibold text-gray-900 mb-1">All posts</h2>
                    <p class="text-sm text-gray-600">Create, edit, and delete posts.</p>
                </a>
                <a href="{{ url('/admin/comments') }}"
                   class="block bg-white rounded-lg shadow-sm p-6 hover:shadow-md transition-shadow">
                    <h2 class="text-lg font-semibold text-gray-900 mb-1">Comments</h2>
                    <p class="text-sm text-gray-600">Approve or reject visitor comments.</p>
                </a>
            @endif

            <a href="{{ route('profile.edit') }}"
               class="block bg-white rounded-lg shadow-sm p-6 hover:shadow-md transition-shadow">
                <h2 class="text-lg font-semibold text-gray-900 mb-1">Edit profile</h2>
                <p class="text-sm text-gray-600">Update your name, email, and password.</p>
            </a>

            <a href="{{ url('/') }}"
               class="block bg-white rounded-lg shadow-sm p-6 hover:shadow-md transition-shadow">
                <h2 class="text-lg font-semibold text-gray-900 mb-1">Read the blog</h2>
                <p class="text-sm text-gray-600">Browse the latest published posts.</p>
            </a>

            <a href="{{ url('/feed.xml') }}"
               class="block bg-white rounded-lg shadow-sm p-6 hover:shadow-md transition-shadow">
                <h2 class="text-lg font-semibold text-gray-900 mb-1">RSS feed</h2>
                <p class="text-sm text-gray-600">Subscribe in your feed reader.</p>
            </a>
        </div>
    </main>

    <footer class="bg-gray-800 text-white mt-12">
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
