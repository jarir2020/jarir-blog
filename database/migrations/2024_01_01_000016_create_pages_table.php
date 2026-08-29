<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 9 — admin-editable pages.
 *
 * Replaces the hardcoded text in `About.vue` and `Contact.vue` with
 * a single `pages` table. The public Vue views fetch these via
 * `/api/pages/{slug}` and render the markdown body to HTML.
 *
 * Slugs are full paths: "about" for the index, "about/our-mission"
 * for a sub-page. `parent_slug` is denormalized so the
 * `/api/pages?parent=about` query is a simple `where`, not a `LIKE`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 80)->unique();
            $table->string('title', 160);
            $table->string('excerpt', 500)->nullable();
            $table->longText('body');                              // markdown
            $table->unsignedInteger('order')->default(0);
            $table->boolean('enabled')->default(true);
            $table->string('parent_slug', 80)->nullable();
            $table->timestamps();

            // Common query: "enabled sub-pages of a parent, in order".
            $table->index(['parent_slug', 'enabled', 'order']);
            // Common query: "top-level pages in order".
            $table->index(['enabled', 'order']);
        });

        // Seed the same content that's currently in About.vue and
        // Contact.vue so the public site looks identical after the
        // migration runs. Admins can edit / disable / reorder / add
        // from the new /admin/settings/pages list.
        $now = now();
        DB::table('pages')->insert([
            [
                'slug' => 'about',
                'title' => 'About Us',
                'excerpt' => 'A short intro to the blog.',
                'body' => "Welcome to Jarir Blog, your trusted source for insightful articles, news, and stories from around the world.",
                'order' => 1,
                'enabled' => true,
                'parent_slug' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => 'about/our-mission',
                'title' => 'Our Mission',
                'excerpt' => 'What we set out to do.',
                'body' => "We are dedicated to providing high-quality content that informs, educates, and inspires our readers. Our team of expert writers covers a wide range of topics including technology, lifestyle, business, travel, and more.",
                'order' => 1,
                'enabled' => true,
                'parent_slug' => 'about',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => 'about/what-we-offer',
                'title' => 'What We Offer',
                'excerpt' => 'What readers can expect from us.',
                'body' => "- In-depth articles on trending topics\n- Expert analysis and insights\n- Regular updates with fresh content\n- Community-driven discussions\n- Free access to all articles",
                'order' => 2,
                'enabled' => true,
                'parent_slug' => 'about',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => 'about/our-team',
                'title' => 'Our Team',
                'excerpt' => 'Who writes and edits.',
                'body' => "Our diverse team of writers and editors brings years of experience across various industries. We are passionate about delivering accurate, engaging, and valuable content to our readers.",
                'order' => 3,
                'enabled' => true,
                'parent_slug' => 'about',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => 'about/contact-us',
                'title' => 'Contact Us',
                'excerpt' => 'How to reach us.',
                'body' => "Have questions or suggestions? We'd love to hear from you! Visit our contact page to get in touch.",
                'order' => 4,
                'enabled' => true,
                'parent_slug' => 'about',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => 'contact',
                'title' => 'Contact Us',
                'excerpt' => 'Get in touch with us.',
                'body' => "Have questions or feedback? We'd love to hear from you! Fill out the form below and we'll get back to you as soon as possible.",
                'order' => 2,
                'enabled' => true,
                'parent_slug' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
