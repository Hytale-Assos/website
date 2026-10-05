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
            // The school email anchors the internal member status. It is
            // set at registration when the login email belongs to a school
            // domain, and stays editable later to follow domain changes,
            // but it never re-derives the member status. Encrypted at
            // rest; the keyed hash column carries the uniqueness (one
            // account per school email).
            $table->text('school_email')->nullable();
            $table->string('school_email_hash', 64)->nullable()->unique();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['school_email_hash']);
            $table->dropColumn(['school_email', 'school_email_hash']);
        });
    }
};
