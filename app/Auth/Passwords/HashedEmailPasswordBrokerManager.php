<?php

namespace App\Auth\Passwords;

use Illuminate\Auth\Passwords\PasswordBrokerManager;
use Illuminate\Auth\Passwords\TokenRepositoryInterface;

/**
 * Password broker manager building the token repository that stores the
 * deterministic email hash instead of the email address.
 */
class HashedEmailPasswordBrokerManager extends PasswordBrokerManager
{
    /**
     * Create a token repository instance based on the given configuration.
     *
     * @return TokenRepositoryInterface
     */
    protected function createTokenRepository(array $config)
    {
        $key = $this->app['config']['app.key'];

        if (str_starts_with($key, 'base64:')) {
            $key = base64_decode(substr($key, 7));
        }

        if (isset($config['driver']) && $config['driver'] === 'cache') {
            return parent::createTokenRepository($config);
        }

        return new HashedEmailTokenRepository(
            $this->app['db']->connection($config['connection'] ?? null),
            $this->app['hash'],
            $config['table'],
            $key,
            ($config['expire'] ?? 60) * 60,
            $config['throttle'] ?? 0,
        );
    }
}
