<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Feed / Find: comments (with replies) and likes on a listing.
 * A reply points at its parent comment (parent_id); deleting a comment deletes its replies.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('listing_comments')) {
            Schema::create('listing_comments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('parent_id')->nullable()->constrained('listing_comments')->cascadeOnDelete();
                $table->text('body');
                $table->timestamps();
                $table->index(['listing_id', 'created_at']);
            });
        }

        if (! Schema::hasTable('listing_likes')) {
            Schema::create('listing_likes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['listing_id', 'user_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_likes');
        Schema::dropIfExists('listing_comments');
    }
};
