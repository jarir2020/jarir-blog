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

            // Phase 9 — pages that appear in the chrome nav
            // (masthead + footer). We include:
            //   - top-level pages (parent_slug IS NULL), e.g.
            //     "about", "contact"
            //   - sub-pages of the "about" parent, e.g.
            //     "about/our-mission", "about/our-team"
            // Other sub-pages (a future "docs/something") stay
            // reachable only by direct URL.
            $view->with('navPages', Schema::hasTable('pages')
                ? Page::query()
                    ->enabled()
                    ->where(function ($q) {
                        $q->whereNull('parent_slug')
                            ->orWhere('parent_slug', 'about');
                    })
                    ->ordered()
                    ->get()
                : collect());
        });
    }
}
