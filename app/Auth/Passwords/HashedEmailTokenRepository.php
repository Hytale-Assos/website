<?php

namespace App\Auth\Passwords;

use App\Support\EmailHasher;
use Illuminate\Auth\Passwords\DatabaseTokenRepository;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

/**
 * Password reset token repository that never stores the email address in
 * clear: the password_reset_tokens table is keyed by the deterministic
 * keyed hash of the email, exactly like the users table blind index.
 */
class HashedEmailTokenRepository extends DatabaseTokenRepository
{
    /**
     * Delete all existing reset tokens from the database.
     *
     * @return int
     */
    protected function deleteExisting(CanResetPasswordContract $user)
    {
        return $this->getTable()
            ->where('email', $this->emailKey($user))
            ->delete();
    }

    /**
     * Build the record payload for the table.
     *
     * @param  string  $email
     * @param  string  $token
     * @return array
     */
    protected function getPayload($email, #[\SensitiveParameter] $token)
    {
        return parent::getPayload(EmailHasher::hash($email), $token);
    }

    /**
     * Determine if a token record exists and is valid.
     *
     * @param  string  $token
     * @return bool
     */
    public function exists(CanResetPasswordContract $user, #[\SensitiveParameter] $token)
    {
        $record = (array) $this->getTable()->where(
            'email', $this->emailKey($user)
        )->first();

        return $record &&
               ! $this->tokenExpired($record['created_at']) &&
                 $this->hasher->check($token, $record['token']);
    }

    /**
     * Determine if the given user recently created a password reset token.
     *
     * @return bool
     */
    public function recentlyCreatedToken(CanResetPasswordContract $user)
    {
        $record = (array) $this->getTable()->where(
            'email', $this->emailKey($user)
        )->first();

        return $record && $this->tokenRecentlyCreated($record['created_at']);
    }

    /**
     * Get the deterministic hash of the user's email for password resets.
     */
    private function emailKey(CanResetPasswordContract $user): string
    {
        return EmailHasher::hash($user->getEmailForPasswordReset());
    }
}
