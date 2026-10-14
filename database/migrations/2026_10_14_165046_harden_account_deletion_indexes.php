<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The blind-index hash columns carrying the identity uniqueness.
     * A soft-deleted account must not lock its email / linked ids
     * forever: the uniqueness only applies to live accounts, so the
     * identifiers are freed as soon as the row is removed from the
     * active scope.
     */
    private const HASH_COLUMNS = [
        'email_hash',
        'discord_id_hash',
        'hytale_id_hash',
        'school_email_hash',
        'pending_school_email_hash',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach (self::HASH_COLUMNS as $column) {
            Schema::table('users', function (Blueprint $table) use ($column) {
                $table->dropUnique([$column]);
            });

            DB::statement(
                'CREATE UNIQUE INDEX users_'.$column.'_unique ON users ('.$column.') WHERE deleted_at IS NULL',
            );
        }

        Schema::table('invitations', function (Blueprint $table) {
            // A member erasing their account must not be blocked by the
            // invitation history: consumed invitations keep their row
            // for the invitee's trace, with the erased member's column
            // set to null instead of preventing the deletion.
            $table->dropForeign(['inviter_user_id']);
            $table->uuid('inviter_user_id')->nullable()->change();
            $table->foreign('inviter_user_id')->references('id')->on('users')->nullOnDelete();

            $table->dropForeign(['accepted_user_id']);
            $table->foreign('accepted_user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invitations', function (Blueprint $table) {
            $table->dropForeign(['accepted_user_id']);
            $table->foreign('accepted_user_id')->references('id')->on('users')->restrictOnDelete();

            $table->dropForeign(['inviter_user_id']);
            $table->uuid('inviter_user_id')->nullable(false)->change();
            $table->foreign('inviter_user_id')->references('id')->on('users')->restrictOnDelete();
        });

        foreach (self::HASH_COLUMNS as $column) {
            DB::statement('DROP INDEX users_'.$column.'_unique');

            Schema::table('users', function (Blueprint $table) use ($column) {
                // email_hash is the only non-nullable hash column.
                $table->string($column, 64)->nullable($column !== 'email_hash')->unique()->change();
            });
        }
    }
};
