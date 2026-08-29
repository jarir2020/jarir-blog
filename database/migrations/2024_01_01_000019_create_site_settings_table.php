<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 10 — site settings (key/value).
 *
 * One table, one row per setting. The columns are intentionally
 * minimal (key + value + timestamps) so we never need a
 * migration to add a new setting — just a new row.
 *
 * Seeded with the values that were previously hardcoded in
 * Blade / Vue so the public site looks identical after the
 * migration runs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 80)->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // Seed with the values that were hardcoded in:
        //   - config/app.php ('name' => 'Jarir Blog')
        //   - site-layout.blade.php (default meta description, footer)
        //   - FeedController (title + subtitle)
        //   - Contact.vue (email, address, phone)
        $now = now();
        DB::table('site_settings')->insert([
            ['key' => 'site.name',              'value' => 'Jarir Blog',                                  'created_at' => $now, 'updated_at' => $now],
            ['key' => 'site.tagline',           'value' => 'Insightful articles, news, and stories from around the world.', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'contact.email',          'value' => 'contact@jarirblog.com',                     'created_at' => $now, 'updated_at' => $now],
            ['key' => 'contact.address',        'value' => '123 Blog Street, Dhaka, Bangladesh',        'created_at' => $now, 'updated_at' => $now],
            ['key' => 'contact.phone',          'value' => '+880 1234 567890',                            'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
