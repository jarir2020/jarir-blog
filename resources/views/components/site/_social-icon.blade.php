@props(['platform', 'class' => 'w-4 h-4'])

{{-- Phase 8 — per-platform SVG icon for the brand social links.
     Rendered as <svg class="..."> so it inherits text colour from
     its parent (the top utility bar uses text-gray-300 → hover text-white;
     the footer block does the same). All paths use currentColor so
     the icon's tint follows the surrounding text.

     Adding a new platform is a one-line @case — keep the SVGs inline
     so the chrome is self-contained and works without an HTTP request. --}}

@switch($platform)
    @case('facebook')
        <svg class="{{ $class }}" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M22 12a10 10 0 10-11.6 9.87v-6.98H7.9V12h2.5V9.8c0-2.47 1.48-3.84 3.74-3.84 1.08 0 2.21.2 2.21.2v2.43h-1.25c-1.23 0-1.61.76-1.61 1.55V12h2.74l-.44 2.89h-2.3v6.98A10 10 0 0022 12z"/>
        </svg>
        @break

    @case('x')
        <svg class="{{ $class }}" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M18.244 2H21l-6.51 7.44L22 22h-6.86l-4.79-6.27L4.7 22H2l6.96-7.96L2 2h6.95l4.33 5.72L18.244 2zm-2.41 18.2h1.83L7.27 3.7H5.31l10.523 16.5z"/>
        </svg>
        @break

    @case('youtube')
        <svg class="{{ $class }}" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M23.5 6.2a3 3 0 00-2.1-2.1C19.5 3.5 12 3.5 12 3.5s-7.5 0-9.4.6A3 3 0 00.5 6.2C0 8.1 0 12 0 12s0 3.9.5 5.8a3 3 0 002.1 2.1c1.9.6 9.4.6 9.4.6s7.5 0 9.4-.6a3 3 0 002.1-2.1c.5-1.9.5-5.8.5-5.8s0-3.9-.5-5.8zM9.6 15.6V8.4l6.2 3.6-6.2 3.6z"/>
        </svg>
        @break

    @case('instagram')
        <svg class="{{ $class }}" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M12 2.16c3.2 0 3.58 0 4.85.07 3.25.15 4.77 1.69 4.92 4.92.07 1.27.07 1.65.07 4.85s0 3.58-.07 4.85c-.15 3.23-1.66 4.77-4.92 4.92-1.27.07-1.65.07-4.85.07s-3.58 0-4.85-.07c-3.26-.15-4.77-1.7-4.92-4.92-.07-1.27-.07-1.65-.07-4.85s0-3.58.07-4.85c.15-3.23 1.67-4.77 4.92-4.92C8.42 2.16 8.8 2.16 12 2.16zM12 0C8.74 0 8.33 0 7.05.07 2.7.27.27 2.7.07 7.05.01 8.33 0 8.74 0 12s0 3.67.07 4.95c.2 4.36 2.62 6.78 6.98 6.98C8.33 24 8.74 24 12 24s3.67 0 4.95-.07c4.35-.2 6.78-2.62 6.98-6.98.07-1.28.07-1.69.07-4.95s0-3.67-.07-4.95c-.2-4.35-2.62-6.78-6.98-6.98C15.67 0 15.26 0 12 0zm0 5.84A6.16 6.16 0 1018.16 12 6.16 6.16 0 0012 5.84zM12 16a4 4 0 114-4 4 4 0 01-4 4zm6.41-11.85a1.44 1.44 0 11-1.44 1.44 1.44 1.44 0 011.44-1.44z"/>
        </svg>
        @break

    @case('linkedin')
        <svg class="{{ $class }}" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M20.45 20.45h-3.55v-5.57c0-1.33-.03-3.04-1.85-3.04-1.85 0-2.14 1.45-2.14 2.94v5.67H9.36V9h3.41v1.56h.05c.47-.9 1.63-1.85 3.36-1.85 3.6 0 4.26 2.37 4.26 5.45v6.29zM5.34 7.43a2.06 2.06 0 110-4.13 2.06 2.06 0 010 4.13zM7.12 20.45H3.56V9h3.56v11.45z"/>
        </svg>
        @break

    @case('github')
        <svg class="{{ $class }}" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.44 9.8 8.21 11.39.6.11.82-.26.82-.58 0-.29-.01-1.04-.02-2.05-3.34.73-4.04-1.61-4.04-1.61-.55-1.39-1.34-1.76-1.34-1.76-1.09-.74.08-.73.08-.73 1.21.08 1.84 1.24 1.84 1.24 1.07 1.84 2.81 1.31 3.5 1 .11-.78.42-1.31.76-1.61-2.66-.3-5.47-1.33-5.47-5.93 0-1.31.47-2.38 1.24-3.22-.13-.3-.54-1.52.11-3.18 0 0 1.01-.32 3.3 1.23a11.5 11.5 0 016 0c2.29-1.55 3.3-1.23 3.3-1.23.65 1.66.24 2.88.12 3.18.77.84 1.24 1.91 1.24 3.22 0 4.61-2.81 5.62-5.49 5.92.43.37.81 1.1.81 2.22 0 1.6-.01 2.89-.01 3.29 0 .32.22.7.83.58A12 12 0 0024 12c0-6.63-5.37-12-12-12z"/>
        </svg>
        @break

    @case('rss')
        <svg class="{{ $class }}" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M6.18 17.82a2.18 2.18 0 100-4.36 2.18 2.18 0 000 4.36zM4 4.44V7.5c7.18 0 13 5.82 13 13h3.06C20.06 11.49 12.95 4.44 4 4.44zm0-4.44V3C14.18 3 22 10.82 22 20.94h-2.99C19.01 12.62 12.39 4.44 4 0zM2 0v3.06c10.39 0 18.94 8.55 18.94 18.94H24C24 9.57 14.43 0 2 0z" transform="translate(0, 0) scale(0.875)"/>
        </svg>
        @break

    @default
        {{-- Generic link icon for `custom` or unknown platforms. --}}
        <svg class="{{ $class }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 015.656 5.656l-3 3a4 4 0 01-5.656-5.656m-3.656 3.656a4 4 0 01-5.656-5.656l3-3a4 4 0 015.656 5.656"/>
        </svg>
@endswitch
