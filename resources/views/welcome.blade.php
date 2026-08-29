@php $siteSettings = app(\App\Support\Settings::class); @endphp
<x-site.site-layout
    title="Home"
    :description="$siteSettings->siteTagline()"
>
    <div id="app" class="flex-1 w-full">
        {{-- The Vue SPA mounts here. If the JS bundle fails to load,
             a static fallback is shown below so the page is never
             completely blank. --}}
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 text-center text-gray-500">
            <p class="text-sm">{{ $siteSettings->loadingMessage() }}</p>
        </div>
    </div>

    <noscript>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 text-center">
            <p class="text-gray-700">{{ $siteSettings->noJsMessage() }}</p>
            <p class="text-gray-500 text-sm mt-2">
                You can still subscribe to the <a href="/feed.xml" class="text-blue-600 hover:underline">RSS feed</a>.
            </p>
        </div>
    </noscript>
</x-site.site-layout>
