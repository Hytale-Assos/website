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
            // A school email change must be confirmed by clicking a signed
            // link sent to the NEW address: until then the claim sits here
            // and the confirmed school_email column stays untouched. A
            // pending claim is unique against other pending claims, but
            // blocks nothing else — an unconfirmed claim must not prevent
            // the real owner of the address from registering.
            $table->text('pending_school_email')->nullable();
            $table->string('pending_school_email_hash', 64)->nullable()->unique();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['pending_school_email_hash']);
            $table->dropColumn(['pending_school_email', 'pending_school_email_hash']);
        });
    }
};
