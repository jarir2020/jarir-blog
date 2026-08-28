@php
    $isAdmin = $isAdmin ?? false;
@endphp

<x-site.site-layout
    :isAdmin="$isAdmin"
    :title="$isAdmin ? 'Admin sign-in' : 'Sign in'"
    :description="$isAdmin ? 'Sign in to manage the blog.' : 'Sign in to your account.'"
>
    <div class="flex items-center justify-center px-4 py-12 sm:py-16">
        <div class="w-full max-w-md">
            <div class="bg-white rounded-lg shadow-md p-8">
                <h1 class="text-2xl font-bold text-gray-900 mb-1">
                    {{ $isAdmin ? 'Admin sign-in' : 'Welcome back' }}
                </h1>
                <p class="text-sm text-gray-600 mb-6">
                    {{ $isAdmin ? 'Sign in to manage posts and moderate comments.' : 'Sign in to your account.' }}
                </p>

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

                    @if (is_string($intended ?? null) && str_starts_with($intended, '/') && ! str_starts_with($intended, '//'))
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
                        <a href="{{ route('register') }}" class="text-blue-600 hover:underline">Create one</a>
                    </p>
                @endif
            </div>

            <p class="mt-6 text-center text-xs text-gray-500">
                Demo admin: <code class="text-gray-700">demo@jarir.test</code> / <code class="text-gray-700">password</code>
            </p>
        </div>
    </div>
</x-site.site-layout>
