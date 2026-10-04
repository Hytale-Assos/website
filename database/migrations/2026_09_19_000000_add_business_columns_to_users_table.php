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
        Schema::table('users', function (Blueprint $table) {
            // Discord ids are snowflakes (numeric strings). The id and
            // nickname are stored encrypted via the `encrypted` Eloquent
            // cast, so the deterministic SHA-256 hash column carries the
            // one-discord-account-per-user uniqueness.
            $table->text('discord_id')->nullable();
            $table->string('discord_id_hash', 64)->nullable()->unique();
            $table->text('discord_nickname')->nullable();

            // Hytale ids are UUIDs, self-declared until the Hytale OAuth
            // exists; hytale_account_verified_at is set only by the
            // application (not fillable), once the OAuth confirms them.
            // Same encryption pattern as Discord: the id and nickname are
            // encrypted, the hash column carries the uniqueness.
            $table->text('hytale_id')->nullable();
            $table->string('hytale_id_hash', 64)->nullable()->unique();
            $table->text('hytale_nickname')->nullable();
            $table->timestamp('hytale_account_verified_at')->nullable();

            $table->text('firstname')->nullable();
            $table->text('lastname')->nullable();
            $table->text('grade_level')->nullable();
            // The internal/external flags are stored encrypted so rows
            // cannot be identified as internal or external from a database
            // dump alone. The `is_public` flag stays in clear because it
            // must remain SQL-filterable for public profiles (leaderboard).
            $table->text('is_internal')->nullable();
            $table->text('is_external')->nullable();
            $table->boolean('is_public')->default(false);
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['discord_id_hash']);
            $table->dropUnique(['hytale_id_hash']);
            $table->dropSoftDeletes();
            $table->dropColumn([
                'discord_id',
                'discord_id_hash',
                'discord_nickname',
                'hytale_id',
                'hytale_id_hash',
                'hytale_nickname',
                'hytale_account_verified_at',
                'firstname',
                'lastname',
                'grade_level',
                'is_internal',
                'is_external',
                'is_public',
            ]);
        });
    }
};
