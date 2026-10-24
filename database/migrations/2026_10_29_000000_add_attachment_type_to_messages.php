<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Chat messages can carry a photo, a video or a voice message. Older rows with a file are photos. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->string('attachment_type', 10)->nullable()->after('attachment_path');
        });

        \Illuminate\Support\Facades\DB::table('messages')
            ->whereNotNull('attachment_path')
            ->whereNull('attachment_type')
            ->update(['attachment_type' => 'image']);
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn('attachment_type');
        });
    }
};
