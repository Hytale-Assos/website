<?php

namespace App\Models;

use App\Casts\EncryptedEmailWithHash;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $inviter_user_id
 * @property string $email
 * @property string $email_hash
 * @property Carbon $expires_at
 * @property Carbon|null $accepted_at
 * @property string|null $accepted_user_id
 * @property Carbon|null $revoked_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['inviter_user_id', 'email', 'email_hash', 'expires_at', 'accepted_at', 'accepted_user_id', 'revoked_at'])]
#[Hidden(['email_hash'])]
class Invitation extends Model
{
    /** @use HasFactory<InvitationFactory> */
    use HasFactory, HasUuids;

    /**
     * The invited email is the login email the guest registers with. It is
     * encrypted at rest, like every personal value in this application.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email' => EncryptedEmailWithHash::class.':email_hash',
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /**
     * The internal member who sent the invitation.
     */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inviter_user_id');
    }

    /**
     * The account created with this invitation, once consumed.
     */
    public function acceptedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_user_id');
    }

    /**
     * Whether the invitation can still be used to register: not consumed,
     * not revoked, not expired.
     */
    public function isUsable(): bool
    {
        return $this->accepted_at === null
            && $this->revoked_at === null
            && $this->expires_at->isFuture();
    }

    /**
     * The invitation's derived status.
     */
    public function status(): string
    {
        return match (true) {
            $this->accepted_at !== null => 'accepted',
            $this->revoked_at !== null => 'revoked',
            ! $this->expires_at->isFuture() => 'expired',
            default => 'pending',
        };
    }
}
