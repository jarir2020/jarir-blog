@php
    $isAdmin = auth()->user()?->isAdmin() ?? false;
@endphp

<x-site.site-layout
    :title="'Dashboard'"
    :description="$isAdmin ? 'Manage the blog.' : 'Your account.'"
>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">
            Welcome back, {{ auth()->user()?->name ?? 'there' }}
        </h1>
        <p class="text-gray-600 mb-8">
            @if ($isAdmin)
                You're signed in as an admin. Manage the blog below.
            @else
                You're signed in to your account.
            @endif
        </p>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @if ($isAdmin)
                <a href="{{ url('/admin') }}"
                   class="block bg-white rounded-lg shadow-sm p-6 hover:shadow-md transition-shadow">
                    <h2 class="text-lg font-semibold text-gray-900 mb-1">Admin dashboard</h2>
                    <p class="text-sm text-gray-600">Manage posts, moderate comments, view subscribers.</p>
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
    </div>
</x-site.site-layout>
