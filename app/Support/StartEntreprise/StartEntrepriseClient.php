<?php

namespace App\Support\StartEntreprise;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Server-to-server client for the StartEntreprise public API (OAuth2 client credentials).
 *
 * Tokens are cached encrypted until shortly before Keycloak's expires_in, and renewed once when
 * the API answers 401. TLS is always verified, redirects are never followed (they could carry the
 * bearer token elsewhere) and every call has finite timeouts. Nothing here logs tokens or secrets.
 */
class StartEntrepriseClient
{
    public const LEADS_PATH = '/api/public/v1/commercial/leads';

    /** Renew the token this many seconds before it expires. */
    private const TOKEN_EXPIRY_MARGIN = 30;

    public function enabled(): bool
    {
        return (bool) config('services.startentreprise.leads_enabled');
    }

    /**
     * Settings that prevent synchronization, described without their values.
     *
     * @return list<string>
     */
    public function configurationProblems(): array
    {
        $problems = [];

        foreach (['api_url', 'token_url'] as $key) {
            if (! str_starts_with((string) config("services.startentreprise.{$key}"), 'https://')) {
                $problems[] = "{$key} must be an https:// URL";
            }
        }

        foreach (['client_id', 'client_secret'] as $key) {
            if (blank(config("services.startentreprise.{$key}"))) {
                $problems[] = "{$key} is missing";
            }
        }

        if ($this->syncFrom() === null) {
            $problems[] = 'leads_sync_from is missing or not a valid date';
        }

        return $problems;
    }

    /**
     * Requests created at or after this instant are synchronized automatically.
     */
    public function syncFrom(): ?CarbonImmutable
    {
        $value = config('services.startentreprise.leads_sync_from');

        if (blank($value)) {
            return null;
        }

        try {
            return CarbonImmutable::parse((string) $value)->utc();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Send a lead; the Idempotency-Key is its externalId, so a retry can never create a second
     * prospect.
     *
     * @param  array<string, mixed>  $payload
     *
     * @throws StartEntrepriseTokenException when no access token can be obtained
     * @throws ConnectionException on a network failure or timeout
     */
    public function submitLead(array $payload): Response
    {
        $response = $this->postLead($payload, $this->accessToken());

        if ($response->status() === 401) {
            // The cached token may have been revoked or expired early: renew it once.
            $this->forgetToken();
            $response = $this->postLead($payload, $this->accessToken());
        }

        return $response;
    }

    public function forgetToken(): void
    {
        Cache::forget($this->tokenCacheKey());
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function postLead(array $payload, string $token): Response
    {
        return $this->http()
            ->withToken($token)
            ->withHeaders([
                'Idempotency-Key' => $payload['externalId'],
                'X-Request-Id' => 'nesel-'.$payload['externalId'],
            ])
            ->post(rtrim((string) config('services.startentreprise.api_url'), '/').self::LEADS_PATH, $payload);
    }

    private function accessToken(): string
    {
        $cached = Cache::get($this->tokenCacheKey());

        if (is_string($cached)) {
            try {
                return Crypt::decryptString($cached);
            } catch (DecryptException) {
                $this->forgetToken();
            }
        }

        $response = $this->http()->asForm()->post((string) config('services.startentreprise.token_url'), [
            'grant_type' => 'client_credentials',
            'client_id' => (string) config('services.startentreprise.client_id'),
            'client_secret' => (string) config('services.startentreprise.client_secret'),
        ]);

        if (! $response->successful()) {
            // Keycloak's error ("invalid_client", "unauthorized_client"…) is a constant, safe to keep.
            $error = $response->json('error');

            throw new StartEntrepriseTokenException(
                'token_'.(is_string($error) && preg_match('/^[a-z_]{1,50}$/', $error) ? $error : 'http_'.$response->status()),
                retryable: $response->serverError() || $response->status() === 429,
            );
        }

        $token = $response->json('access_token');
        $type = $response->json('token_type');
        $expiresIn = $response->json('expires_in');

        if (! is_string($token) || $token === '' || (is_string($type) && strcasecmp($type, 'bearer') !== 0)) {
            throw new StartEntrepriseTokenException('token_invalid_response', retryable: false);
        }

        $lifetime = is_numeric($expiresIn) && (int) $expiresIn > 0 ? (int) $expiresIn : 60;
        $cacheSeconds = $lifetime > 2 * self::TOKEN_EXPIRY_MARGIN ? $lifetime - self::TOKEN_EXPIRY_MARGIN : intdiv($lifetime, 2);

        if ($cacheSeconds > 0) {
            Cache::put($this->tokenCacheKey(), Crypt::encryptString($token), $cacheSeconds);
        }

        return $token;
    }

    private function http(): PendingRequest
    {
        return Http::acceptJson()
            ->connectTimeout((int) config('services.startentreprise.connect_timeout', 5))
            ->timeout((int) config('services.startentreprise.timeout', 15))
            ->withoutRedirecting()
            ->withOptions(['verify' => true]);
    }

    private function tokenCacheKey(): string
    {
        return 'startentreprise:access-token:'.hash('sha256',
            config('services.startentreprise.token_url').'|'.config('services.startentreprise.client_id'));
    }
}
