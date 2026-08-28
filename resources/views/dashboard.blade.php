<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>
    <link rel="stylesheet" href="{{ asset('css/btn.css') }}">
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex justify-center space-x-4">

                <a href="{{ route('todos.index') }}" class="btn">
                    View All Todos
                </a>

                <span style="margin-right: 19px;"></span>

                <a href="{{ route('todos.create') }}" class="btn">
                    Add Todo
                </a>
            </div>
        </div>
    </div>
</x-app-layout>

