<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The admin panel still manages listings and exchanges, but
 * 2026_09_25_000000_drop_peer_exchange_tables drops both tables.
 *
 * This migration brings them back so the migrations that follow it
 * (add_category_id_to_listings, migrate_listing_categories_to_category_ids,
 * remove_category_from_listings) can run unchanged.
 *
 * `listings` is recreated in its ORIGINAL shape (with the old string
 * `category` column) on purpose: add_category_id adds category_id next to it,
 * the data migration copies across, and remove_category then drops it.
 *
 * Both tables are guarded with hasTable(), so this is safe on a database that
 * still has them.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('listings')) {
            Schema::create('listings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('title');
                $table->string('category');
                $table->text('description');
                $table->enum('status', ['active', 'pending', 'inactive'])->default('pending');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('exchanges')) {
            Schema::create('exchanges', function (Blueprint $table) {
                $table->id();
                $table->foreignId('provider_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('learner_id')->constrained('users')->onDelete('cascade');
                $table->string('skill_offered');
                $table->string('skill_wanted');
                $table->enum('status', ['ongoing', 'completed', 'pending', 'disputed'])->default('pending');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('exchanges');
        Schema::dropIfExists('listings');
    }
};
