<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Proof a member can attach to a rating: up to 3 photos and 2 videos (50 MB
 * each). The files live on the public disk under rating-proofs/.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('rating_attachments')) {
            return;
        }

        Schema::create('rating_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rating_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // image | video
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->unsignedBigInteger('size')->nullable(); // bytes
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rating_attachments');
    }
};
