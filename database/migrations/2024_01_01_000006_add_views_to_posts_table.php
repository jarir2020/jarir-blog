<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 3 — track view counts on posts.
     *
     * Bumped in PostController@show on every successful fetch. Used by the
     * sidebar's "Popular posts" widget and the future RSS feed sort.
     */
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->unsignedBigInteger('views')->default(0)->after('is_featured');
            $table->index('views');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex(['views']);
            $table->dropColumn('views');
        });
    }
};
