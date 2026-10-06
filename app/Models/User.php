<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Casts\EncryptedBoolean;
use App\Casts\EncryptedEmailWithHash;
use App\Casts\EncryptedWithHash;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property string $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $discord_id
 * @property string|null $discord_id_hash
 * @property string|null $hytale_id
 * @property string|null $hytale_id_hash
 * @property string|null $discord_nickname
 * @property string|null $hytale_nickname
 * @property Carbon|null $hytale_account_verified_at
 * @property string|null $school_email
 * @property string|null $school_email_hash
 * @property string|null $firstname
 * @property string|null $lastname
 * @property string|null $grade_level
 * @property bool $is_internal
 * @property bool $is_external
 * @property bool $is_public
 * @property string|null $remember_token
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'email_hash', 'password', 'discord_id', 'discord_id_hash', 'hytale_id', 'hytale_id_hash', 'discord_nickname', 'hytale_nickname', 'school_email', 'school_email_hash', 'firstname', 'lastname', 'grade_level', 'is_internal', 'is_external', 'is_public'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasUuids, Notifiable, PasskeyAuthenticatable, SoftDeletes, TwoFactorAuthenticatable;

    /**
     * The invitations this member has sent.
     *
     * @return HasMany<Invitation, $this>
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class, 'inviter_user_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'name' => 'encrypted',
            'email' => EncryptedEmailWithHash::class.':email_hash',
            'email_verified_at' => 'datetime',
            'hytale_account_verified_at' => 'datetime',
            'discord_id' => EncryptedWithHash::class.':discord_id_hash',
            'discord_nickname' => 'encrypted',
            'hytale_id' => EncryptedWithHash::class.':hytale_id_hash',
            'hytale_nickname' => 'encrypted',
            'school_email' => EncryptedEmailWithHash::class.':school_email_hash',
            'firstname' => 'encrypted',
            'lastname' => 'encrypted',
            'grade_level' => 'encrypted',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'is_internal' => EncryptedBoolean::class,
            'is_external' => EncryptedBoolean::class,
            'is_public' => 'boolean',
            'deleted_at' => 'datetime',
        ];
    }
}
