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
            $table->uuid('discord_id')->nullable()->unique();
            $table->uuid('hytale_id')->nullable()->unique();
            $table->string('discord_nickname')->nullable();
            $table->string('hytale_nickname')->nullable();
            $table->string('firstname')->nullable();
            $table->string('lastname')->nullable();
            $table->string('grade_level')->nullable();
            $table->boolean('is_internal')->default(false);
            $table->boolean('is_external')->default(false);
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
            $table->dropUnique(['discord_id']);
            $table->dropUnique(['hytale_id']);
            $table->dropSoftDeletes();
            $table->dropColumn([
                'discord_id',
                'hytale_id',
                'discord_nickname',
                'hytale_nickname',
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
