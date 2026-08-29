<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 5 — add a `color` column to categories and tags.
 *
 * Used by the admin CRUD forms and (later) the public site's category
 * / tag chips. Default to a neutral gray so existing rows render
 * without an explicit choice.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('color', 7)->default('#6b7280')->after('description');
        });
        Schema::table('tags', function (Blueprint $table) {
            $table->string('color', 7)->default('#6b7280')->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('color');
        });
        Schema::table('tags', function (Blueprint $table) {
            $table->dropColumn('color');
        });
    }
};
