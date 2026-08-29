<?php

namespace App\Providers;

use App\Models\Page;
use App\Models\SocialLink;
use App\Support\MarkdownRenderer;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Phase 9 — MarkdownRenderer is built once per request
        // (the underlying CommonMarkConverter is expensive). The
        // controller's type-hint resolves the same singleton.
        $this->app->singleton(MarkdownRenderer::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Schema::hasTable guards keep pre-migration test
        // environments (e.g. Phase0RoutesTest, which exercises the
        // route file before any data is set up) from blowing up.
        View::composer('components.site.site-layout', function ($view) {
            $view->with('socialLinks', Schema::hasTable('social_links')
                ? SocialLink::query()->enabled()->ordered()->get()
                : collect());

            // Phase 9c — every enabled top-level page shows up in
            // the chrome nav. After the slug-flattening migration,
            // that's "about" + the 4 about-sub-pages (now top-level
            // slugs: our-mission, our-vision, what-we-offer, our-team)
            // + "contact". We exclude the slug "about" from the loop
            // (it's already in the masthead's first row as the "Home"
            // link) and we re-order so "About Us" is first.
            $view->with('navPages', Schema::hasTable('pages')
                ? Page::query()
                    ->enabled()
                    ->whereNull('parent_slug')
                    ->ordered()
                    ->get()
                : collect());
        });
    }
}
