<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Email one-time codes (forgot password, admin sign-in) and the admin's
 * "trusted devices". Also removes the old authenticator-app (TOTP) columns.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('email_otps')) {
            Schema::create('email_otps', function (Blueprint $table) {
                $table->id();
                $table->string('email')->index();
                $table->string('purpose', 32);              // password_reset | admin_login
                $table->string('code_hash')->nullable();    // HMAC of the 6 digits, never the digits
                $table->unsignedTinyInteger('attempts')->default(0);
                $table->timestamp('expires_at');
                $table->timestamp('last_sent_at')->nullable();
                $table->string('token_hash')->nullable();   // short-lived "code was correct" token (app reset)
                $table->timestamps();
                $table->unique(['email', 'purpose']);
            });
        }

        if (! Schema::hasTable('admin_trusted_devices')) {
            Schema::create('admin_trusted_devices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('token_hash', 64)->unique();
                $table->string('label')->nullable();        // "Chrome on Windows"
                $table->string('ip', 45)->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamp('expires_at');
                $table->timestamps();
            });
        }

        foreach (['two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at'] as $column) {
            if (Schema::hasColumn('users', $column)) {
                Schema::table('users', fn (Blueprint $table) => $table->dropColumn($column));
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_trusted_devices');
        Schema::dropIfExists('email_otps');
    }
};
