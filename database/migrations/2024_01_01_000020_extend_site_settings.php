<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 10b — extend site_settings with three more admin-editable
 * chrome strings:
 *
 *   site.no_js_message  - text shown to visitors with JS disabled
 *                         (the <noscript> fallback in welcome.blade.php)
 *   site.loading_message - text shown while the Vue SPA bundle loads
 *   site.theme_color     - hex color for the <meta name="theme-color">
 *                         browser chrome; was hardcoded #ffffff
 *
 * The new keys are inserted only if the table exists and the row
 * doesn't already have the key (idempotent).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('site_settings')) {
            return;
        }

        $rows = [
            [
                'key' => 'site.no_js_message',
                'value' => "The blog needs JavaScript to render. Please enable it to browse posts. You can still subscribe to the RSS feed.",
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'site.loading_message',
                'value' => 'Loading the blog…',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'site.theme_color',
                'value' => '#ffffff',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($rows as $row) {
            if (! DB::table('site_settings')->where('key', $row['key'])->exists()) {
                DB::table('site_settings')->insert($row);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('site_settings')) {
            return;
        }
        DB::table('site_settings')->whereIn('key', [
            'site.no_js_message',
            'site.loading_message',
            'site.theme_color',
        ])->delete();
    }
};
