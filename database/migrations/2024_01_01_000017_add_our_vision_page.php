<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 9b — adds the "Our Vision" sub-page to the `pages` table
 * (the original seed didn't include it) and the `hero_image` column
 * to support a per-page hero image.
 *
 * The page is inserted conditionally so re-running on a DB that
 * already has the row is a no-op.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Add the hero_image column (nullable; existing rows get null).
        if (Schema::hasTable('pages') && ! Schema::hasColumn('pages', 'hero_image')) {
            Schema::table('pages', function ($table) {
                $table->string('hero_image', 500)->nullable()->after('excerpt');
            });
        }

        if (Schema::hasTable('pages') && ! DB::table('pages')->where('slug', 'about/our-vision')->exists()) {
            DB::table('pages')->insert([
                'slug' => 'about/our-vision',
                'title' => 'Our Vision',
                'excerpt' => 'Where we want to go.',
                'body' => "We're building a long-running, independent publication that values clarity, depth, and reader trust over short-term traffic. Our vision is a blog that a writer in five years will be proud to have on their portfolio, and a reader in five years will be glad they bookmarked.",
                'order' => 2,
                'enabled' => true,
                'parent_slug' => 'about',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('pages')) {
            DB::table('pages')->where('slug', 'about/our-vision')->delete();
        }
        if (Schema::hasTable('pages') && Schema::hasColumn('pages', 'hero_image')) {
            Schema::table('pages', function ($table) {
                $table->dropColumn('hero_image');
            });
        }
    }
};
