<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skill_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->enum('name', ['easy', 'moderate', 'hard', 'expert']);
            $table->unsignedTinyInteger('order'); // 1=easy, 2=moderate, 3=hard, 4=expert
            $table->timestamps();

            $table->unique(['skill_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skill_levels');
    }
};
