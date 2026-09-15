<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
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

public function down(): void
{
    Schema::dropIfExists('exchanges');
}
};
