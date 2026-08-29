<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 5 — cut `posts.status` over to a `statuses` FK.
 *
 * Single multi-step migration:
 *   1. Add nullable `status_id` so we can backfill without violating
 *      the (yet-to-exist) NOT NULL constraint.
 *   2. Backfill from the old `status` enum to the new table via slug.
 *   3. Drop the index + the enum column.
 *   4. Make `status_id` NOT NULL (raw SQL — we don't pull in Doctrine
 *      just for this).
 *   5. Add an index for the `whereHas('status', ...)` queries.
 *
 * `down()` reverses: re-add the enum, copy `status_id` back to `status`
 * by joining on `statuses.slug`, drop the FK and column.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Add the nullable FK. We start nullable so the backfill
        // doesn't violate a NOT NULL constraint, then tighten it
        // below. The FK uses `restrictOnDelete` so an admin can't
        // delete a status that's still referenced by a post (the
        // controller checks for that too, but the DB is the last
        // line of defence).
        Schema::table('posts', function (Blueprint $table) {
            $table->foreignId('status_id')
                ->nullable()
                ->after('status')
                ->constrained('statuses')
                ->restrictOnDelete();
        });

        // 2. Backfill from the enum value to the new FK via slug.
        DB::statement(
            'UPDATE posts SET status_id = ('
            . 'SELECT id FROM statuses WHERE statuses.slug = posts.status'
            . ') WHERE status_id IS NULL'
        );

        // 3. Drop the old enum + its index.
        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        // 4. Tighten to NOT NULL. We can't use `->change()` without
        // pulling in doctrine/dbal, so we hand-roll a driver-aware
        // ALTER. MySQL uses `MODIFY`, SQLite has no equivalent (we
        // can only enforce NOT NULL on table create there) and the
        // tests rely on SQLite. The migration succeeds either way;
        // SQLite just keeps the column nullable.
        $driver = DB::connection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE posts MODIFY status_id BIGINT UNSIGNED NOT NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE posts ALTER COLUMN status_id SET NOT NULL');
        }
        // SQLite: skip — it has no `MODIFY` and the column was created
        // nullable. The application-layer validations on `status_id`
        // (required + exists) keep it functionally NOT NULL.

        // 5. Index for the new query path.
        Schema::table('posts', function (Blueprint $table) {
            $table->index('status_id');
        });
    }

    public function down(): void
    {
        // Re-introduce the enum.
        Schema::table('posts', function (Blueprint $table) {
            $table->enum('status', ['draft', 'published', 'archived'])
                ->default('draft')
                ->after('featured_image');
        });

        // Copy FK back to enum string.
        DB::statement(
            'UPDATE posts p '
            . 'JOIN statuses s ON s.id = p.status_id '
            . 'SET p.status = s.slug'
        );

        // Re-add the original index.
        Schema::table('posts', function (Blueprint $table) {
            $table->index('status');
        });

        // Drop the new FK + column.
        Schema::table('posts', function (Blueprint $table) {
            $table->dropForeign(['status_id']);
        });
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('status_id');
        });
    }
};
