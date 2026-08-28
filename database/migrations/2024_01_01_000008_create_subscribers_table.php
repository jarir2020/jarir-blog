<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 3 — newsletter subscriber list.
     *
     * No real provider yet. The /api/subscribe endpoint just records the
     * email; a future job can sync to Mailchimp / Buttondown / etc.
     */
    public function up(): void
    {
        Schema::create('subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->timestamp('subscribed_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscribers');
    }
};
