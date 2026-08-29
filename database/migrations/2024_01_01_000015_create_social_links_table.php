<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 8 — admin-editable social links.
 *
 * The brand's profile links (Facebook, X, YouTube, …) used to be
 * hardcoded in `resources/views/components/site/site-layout.blade.php`
 * in two places (top utility bar + footer). They now live in this
 * table and are read by a view composer registered in
 * `AppServiceProvider::boot()`.
 *
 * `platform` is the friendly key (e.g. "facebook", "x") that
 * selects the SVG icon in the `_social-icon` Blade partial. `icon`
 * is auto-set from `platform` in the controller so admins can't
 * accidentally mis-key the two fields.
 *
 * The default seed keeps the three current links so the chrome
 * isn't empty on a fresh DB.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_links', function (Blueprint $table) {
            $table->id();
            $table->string('platform', 32);
            $table->string('label', 80);
            $table->string('url', 500);
            $table->string('icon', 32);
            $table->unsignedInteger('order')->default(0);
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->index(['enabled', 'order']);
        });

        DB::table('social_links')->insert([
            [
                'platform' => 'facebook',
                'label' => 'Facebook',
                'url' => 'https://facebook.com',
                'icon' => 'facebook',
                'order' => 1,
                'enabled' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'platform' => 'x',
                'label' => 'X',
                'url' => 'https://twitter.com',
                'icon' => 'x',
                'order' => 2,
                'enabled' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'platform' => 'youtube',
                'label' => 'YouTube',
                'url' => 'https://youtube.com',
                'icon' => 'youtube',
                'order' => 3,
                'enabled' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('social_links');
    }
};
