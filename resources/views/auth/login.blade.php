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
        $isAdmin = request()->is('admin*') || request()->routeIs('admin.login');
        $intended = request()->query('intended', request()->route('intended'));
        $cardTitle = $isAdmin ? 'Admin sign-in' : 'Welcome back';
        $cardSubtitle = $isAdmin
            ? 'Sign in to manage posts and moderate comments.'
            : 'Sign in to your account.';
    @endphp

    <title>{{ $cardTitle }} — {{ $siteName }}</title>

    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:title" content="{{ $cardTitle }} — {{ $siteName }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <link rel="canonical" href="{{ url()->current() }}">

    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-gray-50 text-gray-900 antialiased">
    {{-- Same header as the public SPA so the login page feels like part
         of the blog. The admin variant uses the dark header. --}}
    <header class="{{ $isAdmin ? 'bg-gray-900 text-white' : 'bg-white shadow-sm' }}">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
            <a href="/" class="text-2xl font-bold {{ $isAdmin ? 'text-white' : 'text-blue-600' }}">
                {{ $siteName }}
            </a>
            <nav class="flex items-center space-x-6 text-sm">
                <a href="/" class="{{ $isAdmin ? 'text-gray-300 hover:text-white' : 'text-gray-700 hover:text-blue-600' }}">Home</a>
                @if ($isAdmin)
                    <span class="text-gray-400 text-xs uppercase tracking-wider">Admin</span>
                @else
                    <a href="/register" class="text-gray-700 hover:text-blue-600">Register</a>
                @endif
            </nav>
        </div>
    </header>

    <main class="flex items-center justify-center px-4 py-12 sm:py-16">
        <div class="w-full max-w-md">
            <div class="bg-white rounded-lg shadow-md p-8">
                <h1 class="text-2xl font-bold text-gray-900 mb-1">{{ $cardTitle }}</h1>
                <p class="text-sm text-gray-600 mb-6">{{ $cardSubtitle }}</p>

                {{-- Session status (e.g. password-reset confirmation) --}}
                @if (session('status'))
                    <div class="mb-4 rounded-md bg-green-50 p-3 text-sm text-green-700">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-4 rounded-md bg-red-50 p-3 text-sm text-red-700">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="space-y-4">
                    @csrf

                    {{-- Preserve the ?intended=… query through the POST so
                         AuthenticatedSessionController can use it. --}}
                    @if (is_string($intended) && str_starts_with($intended, '/') && ! str_starts_with($intended, '//'))
                        <input type="hidden" name="intended" value="{{ $intended }}">
                    @endif

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input
                            id="email"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            required
                            autofocus
                            autocomplete="username"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('email') border-red-500 @enderror"
                        >
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <input
                            id="password"
                            type="password"
                            name="password"
                            required
                            autocomplete="current-password"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('password') border-red-500 @enderror"
                        >
                    </div>

                    <div class="flex items-center justify-between">
                        <label for="remember_me" class="inline-flex items-center">
                            <input
                                id="remember_me"
                                type="checkbox"
                                name="remember"
                                class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                            >
                            <span class="ml-2 text-sm text-gray-600">Remember me</span>
                        </label>

                        <a
                            href="{{ route('password.request') }}"
                            class="text-sm text-blue-600 hover:underline"
                        >Forgot your password?</a>
                    </div>

                    <button
                        type="submit"
                        class="w-full px-4 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors"
                    >
                        Sign in
                    </button>
                </form>

                @if (! $isAdmin)
                    <p class="mt-6 text-center text-sm text-gray-600">
                        Don't have an account?
                        <a href="/register" class="text-blue-600 hover:underline">Create one</a>
                    </p>
                @endif
            </div>

            <p class="mt-6 text-center text-xs text-gray-500">
                Demo admin: <code class="text-gray-700">demo@jarir.test</code> / <code class="text-gray-700">password</code>
            </p>
        </div>
    </main>

    {{-- Same footer as the public SPA --}}
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
