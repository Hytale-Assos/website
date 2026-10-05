<?php

namespace App\Models;

use App\Casts\EncryptedEmailWithHash;
use App\Support\EmailHasher;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
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
     * Invitations neither consumed nor revoked, whether or not they have
     * expired.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->whereNull('accepted_at')->whereNull('revoked_at');
    }

    /**
     * Pending invitations that have not expired: the ones that can still
     * register an account.
     */
    public function scopeUsable(Builder $query): Builder
    {
        return $query->pending()->where('expires_at', '>', now());
    }

    /**
     * Stamp the expired pending invitations for the given email as
     * superseded, freeing their unique email slot for a fresh invitation.
     *
     * The partial unique index only knows about accepted_at and revoked_at
     * (now() is not immutable, so expiry cannot be part of the index
     * predicate): stamping revoked_at is what frees the slot.
     */
    public static function supersedeExpiredFor(string $email): int
    {
        return static::query()
            ->where('email_hash', EmailHasher::hash($email))
            ->pending()
            ->where('expires_at', '<=', now())
            ->update(['revoked_at' => now()]);
    }

    /**
     * The usable invitation for the given email, if any. This is the
     * registration gate for external guests.
     */
    public static function usableFor(string $email): ?self
    {
        return static::query()
            ->where('email_hash', EmailHasher::hash($email))
            ->usable()
            ->first();
    }

    /**
     * Consume the invitation for the freshly created account. Guarded by
     * accepted_at: a concurrent registration cannot consume it twice
     * (the partial unique index holds the slot until it does).
     */
    public function consume(User $user): bool
    {
        return (bool) $this->newQuery()
            ->whereKey($this->id)
            ->whereNull('accepted_at')
            ->update([
                'accepted_at' => now(),
                'accepted_user_id' => $user->id,
            ]);
    }

    /**
     * The invitation's derived status.
     *
     * Expiry is reported even when the row was later stamped as revoked
     * (freeing the email for a fresh invitation): that stamp only marks
     * a superseded invitation, not a decision by the inviter.
     */
    public function status(): string
    {
        return match (true) {
            $this->accepted_at !== null => 'accepted',
            ! $this->expires_at->isFuture() => 'expired',
            $this->revoked_at !== null => 'revoked',
            default => 'pending',
        };
    }
}
