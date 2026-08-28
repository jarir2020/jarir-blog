@props(['title' => null])

@php
    $siteName = config('app.name', 'Jarir Blog');
    // Pending-comment count for the sidebar badge. Only show the
    // badge when the user is an admin; otherwise the count is 0 and
    // the badge would never appear anyway.
    $pendingComments = auth()->user()?->isAdmin()
        ? \App\Models\Comment::where('approved', false)->count()
        : 0;
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#111827">

    <title>{{ $title ? $title . ' — ' . $siteName . ' Admin' : $siteName . ' Admin' }}</title>

    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:title" content="{{ $title ?? $siteName . ' Admin' }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <link rel="canonical" href="{{ url()->current() }}">

    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-gray-50 text-gray-900 antialiased">
    <div class="flex min-h-screen">
        {{-- Sidebar (server-side mirror of the Vue admin sidebar) --}}
        <aside class="w-60 shrink-0 bg-gray-900 text-gray-300 flex flex-col">
            <div class="px-5 py-5 border-b border-gray-800">
                <a href="/admin" class="block text-lg font-bold text-white">
                    {{ $siteName }}
                </a>
                <p class="text-xs text-gray-500 uppercase tracking-wider mt-0.5">Admin</p>
            </div>
            <nav class="flex-1 px-3 py-4 space-y-0.5 text-sm">
                <a href="/admin" class="flex items-center px-3 py-2 rounded-md hover:bg-gray-800 hover:text-white {{ request()->is('admin') ? 'bg-gray-800 text-white' : '' }}">Dashboard</a>
                <a href="/admin/posts" class="flex items-center px-3 py-2 rounded-md hover:bg-gray-800 hover:text-white {{ request()->is('admin/posts*') ? 'bg-gray-800 text-white' : '' }}">Posts</a>
                <a href="/admin/posts/new" class="flex items-center px-3 py-2 rounded-md hover:bg-gray-800 hover:text-white {{ request()->is('admin/posts/new') ? 'bg-gray-800 text-white' : '' }}">New post</a>
                <a href="/admin/comments" class="flex items-center justify-between px-3 py-2 rounded-md hover:bg-gray-800 hover:text-white {{ request()->is('admin/comments*') ? 'bg-gray-800 text-white' : '' }}">
                    <span>Comments</span>
                    @if ($pendingComments > 0)
                        <span class="px-2 py-0.5 text-xs rounded-full bg-red-500 text-white">
                            {{ $pendingComments }}
                        </span>
                    @endif
                </a>
            </nav>
            <div class="px-3 py-4 border-t border-gray-800 text-sm">
                <a href="/" class="block px-3 py-2 text-gray-400 hover:text-white">← Back to blog</a>
            </div>
        </aside>

        {{-- Main --}}
        <div class="flex-1 flex flex-col min-w-0">
            <header class="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-end gap-4 text-sm">
                @auth
                    <span class="text-gray-700">
                        {{ auth()->user()->name }}
                        <span class="text-xs text-gray-500 ml-1">({{ auth()->user()->role }})</span>
                    </span>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="px-3 py-1.5 text-sm bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200">
                            Log out
                        </button>
                    </form>
                @endauth
            </header>
            <main class="flex-1 px-6 py-8 max-w-7xl w-full">
                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>
