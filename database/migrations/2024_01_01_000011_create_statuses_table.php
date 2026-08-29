<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 5 — admin-editable post statuses.
 *
 * Replaces the hardcoded `enum('draft','published','archived')` on the
 * `posts` table (see migration 2024_01_01_000003) with a real table
 * admins can CRUD. The seeded slugs match the enum's values so the
 * public site (Post::scopePublished, SidebarController, etc.) keeps
 * working through the cutover.
 *
 * The seeder lives in this migration rather than a separate
 * DatabaseSeeder row because the table must never be empty — every
 * post needs a valid `status_id` once the FK lands.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('label');
            $table->string('color', 7)->default('#6b7280'); // hex; default to Tailwind gray-500
            $table->text('description')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
        });

        // Seed the three existing values so the public site's "published
        // filter" continues to find rows. `label` defaults to `name` so
        // a future rename doesn't need to touch every row.
        DB::table('statuses')->insert([
            [
                'name' => 'Draft',
                'slug' => 'draft',
                'label' => 'Draft',
                'color' => '#facc15', // Tailwind yellow-400
                'description' => 'Not yet visible to readers.',
                'order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Published',
                'slug' => 'published',
                'label' => 'Published',
                'color' => '#22c55e', // Tailwind green-500
                'description' => 'Live on the public site.',
                'order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Archived',
                'slug' => 'archived',
                'label' => 'Archived',
                'color' => '#6b7280', // Tailwind gray-500
                'description' => 'Hidden but kept for reference.',
                'order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('statuses');
    }
};
