<?php

namespace App\Providers;

use App\Models\SocialLink;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Phase 8 — inject the admin-managed social links into the
        // public site layout. Both the top utility bar and the
        // footer iterate `$socialLinks`, so we run the query once
        // per request (Eloquent caches it within the same request)
        // and the chrome reads from a single source of truth.
        //
        // The Schema::hasTable guard keeps pre-migration test
        // environments (e.g. Phase0RoutesTest, which exercises the
        // route file before any data is set up) from blowing up.
        View::composer('components.site.site-layout', function ($view) {
            $view->with('socialLinks', \Illuminate\Support\Facades\Schema::hasTable('social_links')
                ? SocialLink::query()->enabled()->ordered()->get()
                : collect());
        });
    }
}
