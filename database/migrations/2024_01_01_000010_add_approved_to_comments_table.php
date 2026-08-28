<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 4 — comment moderation flag.
     *
     * Comments default to `approved = true` so existing rows (and any new
     * visitor comment) stay visible. The admin queue can flip this to
     * false to hide a comment without deleting it.
     */
    public function up(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->boolean('approved')->default(true)->after('body');
            $table->index('approved');
        });
    }

    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->dropIndex(['approved']);
            $table->dropColumn('approved');
        });
    }
};
