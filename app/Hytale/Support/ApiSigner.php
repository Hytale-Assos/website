<?php

namespace App\Hytale\Support;

use Illuminate\Http\Client\PendingRequest;

/**
 * Signs outgoing write requests per docs/api.md.
 *
 * GET requests only need `X-Api-Key`.
 * POST/DELETE requests need `X-Api-Key`, `X-Timestamp` and `X-Signature`,
 * where `X-Signature` is the hex HMAC-SHA256 of `"{timestamp}.{raw body}"`.
 */
final class ApiSigner
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $hmacSecret,
    ) {}

    /**
     * Attach authentication headers to a GET request.
     */
    public function signRead(PendingRequest $request): PendingRequest
    {
        return $request->withHeaders([
            'X-Api-Key' => $this->apiKey,
        ]);
    }

    /**
     * Attach authentication headers to a POST/DELETE request and return the
     * exact raw body that must be sent (the signature covers it).
     *
     * A null payload produces an empty raw body (used for DELETE, which has no
     * documented body); the signature then covers `"{timestamp}."`.
     *
     * @param  array<string, mixed>|null  $payload
     */
    public function signWrite(PendingRequest $request, ?array $payload = null): PendingRequest
    {
        $timestamp = (string) now()->getTimestamp();
        $body = $payload === null
            ? ''
            : (json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}');

        $signature = hash_hmac('sha256', $timestamp.'.'.$body, $this->hmacSecret);

        return $request
            ->withHeaders([
                'X-Api-Key' => $this->apiKey,
                'X-Timestamp' => $timestamp,
                'X-Signature' => $signature,
            ])
            ->withBody($body, 'application/json');
    }

    /**
     * Compute the signature for a raw body and timestamp.
     */
    public function signature(string $timestamp, string $body): string
    {
        return hash_hmac('sha256', $timestamp.'.'.$body, $this->hmacSecret);
    }
}
