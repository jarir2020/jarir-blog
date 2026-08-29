<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 9c — flatten the page slugs.
 *
 * Per the user's design feedback, the about sub-pages should be
 * top-level URLs (no /about/ prefix). After this migration:
 *
 *   - "about"           stays
 *   - "contact"         stays
 *   - "about/our-mission"     -> "our-mission"
 *   - "about/our-vision"      -> "our-vision"
 *   - "about/what-we-offer"   -> "what-we-offer"
 *   - "about/our-team"        -> "our-team"
 *   - "about/contact-us"      -> DELETED (duplicate of /contact)
 *
 * The `parent_slug` column loses its meaning (everything is
 * top-level now), so we set all rows to NULL.
 *
 * Idempotent: re-running on a DB that's already flat is a no-op.
 */
return new class extends Migration
{
    private const RENAMES = [
        'about/our-mission'   => 'our-mission',
        'about/our-vision'    => 'our-vision',
        'about/what-we-offer' => 'what-we-offer',
        'about/our-team'      => 'our-team',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('pages')) {
            return;
        }

        foreach (self::RENAMES as $from => $to) {
            if (! DB::table('pages')->where('slug', $from)->exists()) {
                continue;
            }
            if (DB::table('pages')->where('slug', $to)->exists()) {
                // Target already exists (e.g. a manually-created page
                // with the new slug). Skip to avoid a unique-key
                // collision; the operator can resolve manually.
                continue;
            }
            DB::table('pages')->where('slug', $from)->update(['slug' => $to]);
        }

        // Delete the duplicate contact-us sub-page (the canonical
        // /contact page already exists).
        DB::table('pages')->where('slug', 'about/contact-us')->delete();

        // All rows are top-level now; the parent_slug column is no
        // longer useful but we keep it (nullable) for future
        // groupings (e.g. a "docs" parent in the future).
        DB::table('pages')->update(['parent_slug' => null]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('pages')) {
            return;
        }
        // Reverse the renames (best-effort; may fail if the target
        // exists for any reason).
        foreach (array_reverse(self::RENAMES) as $to => $from) {
            if (DB::table('pages')->where('slug', $to)->exists()
                && ! DB::table('pages')->where('slug', $from)->exists()) {
                DB::table('pages')->where('slug', $to)->update(['slug' => $from]);
            }
        }
        // No way to re-insert the deleted contact-us row with the
        // original slug + parent_slug; the down() is lossy.
    }
};
