<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 6 — admin-managed sidebar widgets.
 *
 * Mirrors the WordPress-style "Appearance → Widgets" pattern: admins
 * can CRUD a list of sidebar widgets, reorder them, toggle them on /
 * off, and (for the right sidebar by default) place them in any
 * position.
 *
 * The `settings` JSON column is type-specific configuration. For
 * example, a `category` widget stores `{ "category_id": 3 }`; an
 * `html` widget stores `{ "body": "..." }`. The SidebarResolver
 * service knows how to read each type's settings.
 *
 * Seeded with the same widget shape the reference blog uses so the
 * sidebar isn't empty on a fresh install.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('widgets', function (Blueprint $table) {
            $table->id();
            $table->string('type', 32);
            $table->string('name');
            $table->string('position', 16)->default('right');
            $table->unsignedInteger('order')->default(0);
            $table->boolean('enabled')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->index(['position', 'order', 'enabled']);
        });

        // Seed: pick the first three categories we find (if any) and
        // create one "category" widget per category. The reference site
        // has Aqeedah / Review / Bangladesh; we just use whatever the
        // current site's categories are so the sidebar isn't empty on
        // a fresh install. If the seeder hasn't run yet (e.g. a test
        // env), these widget rows are still created but disabled.
        $categoryIds = DB::table('categories')
            ->orderBy('id')
            ->limit(3)
            ->pluck('id', 'name');

        $aqeedahId = $categoryIds['Aqeedah'] ?? $categoryIds->values()[0] ?? null;
        $reviewId = $categoryIds['Review'] ?? $categoryIds->values()[1] ?? null;
        $bangladeshId = $categoryIds['Bangladesh'] ?? $categoryIds->values()[2] ?? null;

        DB::table('widgets')->insert([
            [
                'type' => 'popular_recent_comments',
                'name' => 'Popular / Recent / Comments',
                'position' => 'right',
                'order' => 1,
                'enabled' => true,
                'settings' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'video',
                'name' => 'Video Gallery',
                'position' => 'right',
                'order' => 2,
                'enabled' => true,
                'settings' => json_encode(['placeholder' => 'YouTube channel id']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'category',
                'name' => 'Aqeedah',
                'position' => 'right',
                'order' => 3,
                'enabled' => (bool) $aqeedahId,
                'settings' => $aqeedahId ? json_encode(['category_id' => $aqeedahId]) : null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'category',
                'name' => 'Review',
                'position' => 'right',
                'order' => 4,
                'enabled' => (bool) $reviewId,
                'settings' => $reviewId ? json_encode(['category_id' => $reviewId]) : null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'category',
                'name' => 'Bangladesh',
                'position' => 'right',
                'order' => 5,
                'enabled' => (bool) $bangladeshId,
                'settings' => $bangladeshId ? json_encode(['category_id' => $bangladeshId]) : null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'archives',
                'name' => 'Archives',
                'position' => 'right',
                'order' => 6,
                'enabled' => true,
                'settings' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'social',
                'name' => 'Follow Us',
                'position' => 'right',
                'order' => 7,
                'enabled' => true,
                'settings' => json_encode([
                    'links' => [
                        ['platform' => 'facebook', 'url' => 'https://facebook.com'],
                        ['platform' => 'twitter', 'url' => 'https://twitter.com'],
                        ['platform' => 'youtube', 'url' => 'https://youtube.com'],
                    ],
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'newsletter',
                'name' => 'Newsletter',
                'position' => 'right',
                'order' => 8,
                'enabled' => true,
                'settings' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('widgets');
    }
};
