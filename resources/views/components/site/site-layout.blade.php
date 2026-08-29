@props(['isAdmin' => false, 'title' => null, 'description' => null])

@php
    // Phase 10 — site name + tagline come from the site_settings
    // table (admin-editable). Falls back to APP_NAME if the table
    // is missing (e.g. pre-migration test envs).
    $siteName = $siteSettings->siteName();
    $siteTagline = $siteSettings->siteTagline();
    $siteUrl = rtrim(config('app.url'), '/');
    // Categories for the horizontal nav. We pull them here (cheap
    // query) so the chrome renders before the SPA mounts. The
    // Vue components also fetch /api/categories, but the Blade-rendered
    // version is the source of truth for the no-JS fallback.
    //
    // Guarded with Schema::hasTable so the layout still renders in
    // test environments where migrations haven't run yet (e.g.
    // Phase0RoutesTest asserts the welcome view without seeding
    // any data).
    $navCategories = \Illuminate\Support\Facades\Schema::hasTable('categories')
        ? \App\Models\Category::orderBy('name')->get(['id', 'name', 'slug'])
        : collect();
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#ffffff">

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
    <meta property="og:description" content="{{ $description ?? $siteTagline }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <link rel="canonical" href="{{ url()->current() }}">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $title ?? $siteName }}">
    <meta name="twitter:description" content="{{ $description ?? $siteTagline }}">

    <link rel="alternate" type="application/atom+xml" title="{{ $siteName }} feed" href="{{ $siteUrl }}/feed.xml">

    {{-- FOUC prevention: read the user's stored theme preference and
         apply the `dark` class on <html> *before* the body paints.
         Without this, dark-mode users would see a white flash on
         every page load. Kept tiny so it runs synchronously. --}}
    <script>
        (function () {
            try {
                var saved = localStorage.getItem('theme');
                var dark = saved === 'dark' || (saved !== 'light' && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches);
                if (dark) document.documentElement.classList.add('dark');
            } catch (e) { /* privacy mode etc. — fall through to light */ }
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50 text-gray-900 dark:bg-gray-900 dark:text-gray-100 antialiased flex flex-col">
    {{-- Top utility bar (slim, dark). Mirrors the reference site's
         search / theme / social row. The "Random" link is a real
         endpoint; the theme + sidebar toggles are placeholders for
         future work. --}}
    <div class="bg-gray-900 text-gray-300 text-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2 flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <a href="/feed.xml" class="hover:text-white">RSS</a>
                <span class="text-gray-600">|</span>
                <button
                    type="button"
                    id="theme-toggle"
                    class="hover:text-white inline-flex items-center"
                    title="Toggle theme"
                    aria-label="Toggle theme"
                >
                    {{-- Sun icon — shown in dark mode (i.e. click to go light).
                         Starts hidden so the moon shows on light first paint; the
                         inline script below flips display on load + click. --}}
                    <svg class="theme-icon-sun w-4 h-4" style="display: none" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    {{-- Moon icon — shown in light mode (i.e. click to go dark). --}}
                    <svg class="theme-icon-moon w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                    </svg>
                </button>
                <span class="text-gray-600">|</span>
                <a href="/api/posts/random" id="random-post-link" class="hover:text-white" data-random>Random</a>
            </div>
            <div class="flex items-center gap-3">
                {{-- Brand social links (Phase 8). The view composer in
                     AppServiceProvider injects $socialLinks as the
                     enabled list ordered by `order, id`. --}}
                @foreach ($socialLinks ?? [] as $link)
                    <a
                        href="{{ $link->url }}"
                        class="hover:text-white inline-flex items-center"
                        aria-label="{{ $link->label }}"
                        @if (! str_starts_with($link->url, url('/'))) target="_blank" rel="noopener"@endif
                    >
                        <x-site._social-icon :platform="$link->icon" class="w-4 h-4" />
                        <span class="sr-only">{{ $link->label }}</span>
                    </a>
                @endforeach
                @auth
                    <span class="text-gray-600">|</span>
                    <a href="{{ route('dashboard') }}" class="hover:text-white">Dashboard</a>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="hover:text-white">Log out</button>
                    </form>
                @else
                    <span class="text-gray-600">|</span>
                    <a href="{{ route('login') }}" class="hover:text-white">Log in</a>
                @endauth
            </div>
        </div>
    </div>

    {{-- Logo + search + category nav. This is the "masthead" of the
         reference site. The Vue SPA replaces only the slot below; the
         masthead is purely Blade so it renders instantly and stays
         accessible. --}}
    <header class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex flex-wrap items-center justify-between gap-4">
            <a href="/" class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-full bg-blue-600 text-white flex items-center justify-center text-xl font-bold">
                    {{ strtoupper(substr($siteName, 0, 1)) }}
                </div>
                <span class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $siteName }}</span>
            </a>

            <form action="/search" method="get" class="flex-1 max-w-md">
                <div class="relative">
                    <input
                        type="search"
                        name="q"
                        value="{{ request('q') }}"
                        placeholder="Search…"
                        class="w-full px-4 py-2 pr-10 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 dark:placeholder-gray-400 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                    <button type="submit" class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300" aria-label="Search">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z"/>
                        </svg>
                    </button>
                </div>
            </form>
        </div>

        <nav class="bg-gray-100 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-1 overflow-x-auto py-1 text-sm">
                    <a href="/" class="px-3 py-2 rounded-md text-gray-700 dark:text-gray-200 hover:bg-white dark:hover:bg-gray-800 hover:text-blue-600 font-medium {{ request()->is('/') ? 'text-blue-600 dark:text-blue-400' : '' }}">Home</a>
                    @foreach ($navCategories as $category)
                        <a
                            href="/category/{{ $category->slug }}"
                            class="px-3 py-2 rounded-md text-gray-700 dark:text-gray-200 hover:bg-white dark:hover:bg-gray-800 hover:text-blue-600 font-medium whitespace-nowrap {{ request()->is('category/'.$category->slug) ? 'text-blue-600 dark:text-blue-400' : '' }}"
                        >{{ $category->name }}</a>
                    @endforeach
                </div>
                {{-- Phase 9 — admin-managed page nav (About, Our
                     Mission, Our Team, Our Vision, What We Offer,
                     Contact, …). The list comes from the view
                     composer in AppServiceProvider; sub-pages of
                     any non-"about" parent are excluded. --}}
                @if (! empty($navPages) && $navPages->count() > 0)
                    <div class="flex items-center gap-1 overflow-x-auto py-1 text-sm border-t border-gray-200 dark:border-gray-700 mt-1 pt-1">
                        @foreach ($navPages as $page)
                            <a
                                href="/{{ $page->slug }}"
                                class="px-3 py-2 rounded-md text-gray-700 dark:text-gray-200 hover:bg-white dark:hover:bg-gray-800 hover:text-blue-600 font-medium whitespace-nowrap {{ request()->is($page->slug) || request()->is($page->slug.'/*') || ($page->slug === 'about' && request()->is('about*')) ? 'text-blue-600 dark:text-blue-400' : '' }}"
                            >{{ $page->title }}</a>
                        @endforeach
                    </div>
                @endif
            </div>
        </nav>
    </header>

    {{-- Page content. The Vue SPA mounts into this slot via
         <div id="app">. The slot is empty by default; the
         public Blade fallback (welcome.blade.php) renders its own
         placeholder inside it. --}}
    <main class="flex-1 w-full">
        {{ $slot }}
    </main>

    {{-- Dark footer. --}}
    <footer class="bg-gray-900 text-gray-300 mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-6 text-sm">
                <div>
                    <h3 class="text-white font-bold mb-3">{{ $siteName }}</h3>
                    <p class="text-gray-400">{{ $siteTagline }}</p>
                </div>
                <div>
                    <h3 class="text-white font-bold mb-3">Explore</h3>
                    <ul class="space-y-1">
                        <li><a href="/" class="hover:text-white">Home</a></li>
                        {{-- Phase 9 — admin-managed page nav, same
                             list the masthead uses. --}}
                        @foreach ($navPages ?? [] as $page)
                            <li>
                                <a href="/{{ $page->slug }}" class="hover:text-white">{{ $page->title }}</a>
                            </li>
                        @endforeach
                        <li><a href="/feed.xml" class="hover:text-white">RSS</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-white font-bold mb-3">Follow</h3>
                    <div class="flex flex-wrap gap-3">
                        {{-- Same social links as the topbar (Phase 8).
                             Footer block shows icon + label for the
                             same social-list the topbar uses. --}}
                        @foreach ($socialLinks ?? [] as $link)
                            <a
                                href="{{ $link->url }}"
                                class="hover:text-white inline-flex items-center gap-1.5"
                                aria-label="{{ $link->label }}"
                                @if (! str_starts_with($link->url, url('/'))) target="_blank" rel="noopener"@endif
                            >
                                <x-site._social-icon :platform="$link->icon" class="w-4 h-4" />
                                <span>{{ $link->label }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="pt-4 border-t border-gray-800 text-center text-gray-500 text-xs">
                &copy; {{ date('Y') }} {{ $siteName }}. All rights reserved.
            </div>
        </div>
    </footer>

    {{-- Floating Back-to-Top. The Vue app mounts <back-to-top> into
         the same #app node, but having a no-JS fallback keeps the
         public Blade preview usable. --}}
    <a
        href="#top"
        class="fixed bottom-6 right-6 z-50 hidden md:flex w-12 h-12 rounded-full bg-blue-600 text-white items-center justify-center shadow-lg hover:bg-blue-700"
        aria-label="Back to top"
    >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
        </svg>
    </a>

    {{-- Random-post link: hit /api/posts/random and navigate. Wired
         in the global app.js so it works across all pages. --}}
    <script>
        document.addEventListener('click', async (e) => {
            const link = e.target.closest('[data-random]');
            if (!link) return;
            e.preventDefault();
            try {
                const res = await fetch('/api/posts/random', { headers: { Accept: 'application/json' } });
                if (!res.ok) return;
                const data = await res.json();
                if (data.slug) window.location.href = '/blog/' + data.slug;
            } catch (_) {
                // Silent: the link's href is /api/posts/random as a
                // graceful no-JS fallback (browsers will JSON-render it).
            }
        });

        // Theme toggle. The toggle button is rendered in the dark
        // utility bar, so the icons inherit `text-gray-300`; we
        // just flip the click handler and let the body's `dark`
        // class + Tailwind variants re-style everything else.
        (function () {
            var btn = document.getElementById('theme-toggle');
            if (!btn) return;
            var sun = btn.querySelector('.theme-icon-sun');
            var moon = btn.querySelector('.theme-icon-moon');

            function syncIcon() {
                var dark = document.documentElement.classList.contains('dark');
                if (sun) sun.style.display = dark ? '' : 'none';
                if (moon) moon.style.display = dark ? 'none' : '';
            }
            syncIcon();

            btn.addEventListener('click', function () {
                if (window.__theme && typeof window.__theme.toggle === 'function') {
                    window.__theme.toggle();
                } else {
                    // Vue hasn't loaded yet — toggle manually so the
                    // button still feels responsive on first paint.
                    var isDark = document.documentElement.classList.toggle('dark');
                    try { localStorage.setItem('theme', isDark ? 'dark' : 'light'); } catch (e) {}
                }
                syncIcon();
            });
        })();
    </script>
</body>
</html>
