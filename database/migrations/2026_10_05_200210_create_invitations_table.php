<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invitations', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // The internal member who sent the invitation. Kept forever:
            // a future moderation system can act on the invitee through
            // the inviter, so the link is never deleted.
            $table->foreignUuid('inviter_user_id')
                ->constrained('users')
                ->restrictOnDelete();

            // The invited email: the login email the guest will register
            // with. Encrypted at rest; the keyed hash column carries the
            // lookups. Partial unique index: at most one usable invitation
            // per email (pending or not yet consumed), which also makes
            // double consumption impossible.
            $table->text('email');
            $table->string('email_hash', 64);

            $table->timestamp('expires_at');

            // Consumption and revocation, both keeping the row for
            // traceability.
            $table->timestamp('accepted_at')->nullable();
            $table->foreignUuid('accepted_user_id')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();
            $table->timestamp('revoked_at')->nullable();

            $table->timestamps();
        });

        // Partial unique index: at most one usable invitation per email
        // (not consumed, not revoked). Consumed or revoked rows free the
        // email for a new invitation, and concurrent registrations cannot
        // double-consume. Blueprint has no partial index support, hence
        // the raw statement.
        DB::statement(
            'CREATE UNIQUE INDEX invitations_email_hash_unique ON invitations (email_hash) '.
            'WHERE accepted_at IS NULL AND revoked_at IS NULL'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invitations');
    }
};
