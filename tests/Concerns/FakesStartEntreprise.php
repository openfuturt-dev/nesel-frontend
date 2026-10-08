<?php

namespace Tests\Concerns;

use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Support\Facades\Http;

/**
 * Points the StartEntreprise client at fake hosts; every call goes through Http::fake().
 */
trait FakesStartEntreprise
{
    protected const TOKEN_URL = 'https://auth.startentreprise.test/realms/startentreprise/protocol/openid-connect/token';

    protected const LEADS_URL = 'https://api.startentreprise.test/api/public/v1/commercial/leads';

    protected const PROSPECT_ID = '3f1c1d6e-7a2b-4c3d-8e9f-0a1b2c3d4e5f';

    protected function enableStartEntreprise(?string $syncFrom = null): void
    {
        config()->set('services.startentreprise', [
            'leads_enabled' => true,
            'api_url' => 'https://api.startentreprise.test',
            'token_url' => self::TOKEN_URL,
            'client_id' => 'integration-nesel-test',
            'client_secret' => 'test-client-secret',
            'leads_sync_from' => $syncFrom ?? now()->subHour()->toIso8601String(),
            'connect_timeout' => 5,
            'timeout' => 15,
        ]);
    }

    /**
     * @param  PromiseInterface|callable  $leads  Response (or sequence/callback) of the leads endpoint.
     */
    protected function fakeStartEntreprise(mixed $leads, mixed $token = null): void
    {
        Http::preventStrayRequests();
        Http::fake([
            self::TOKEN_URL => $token ?? self::tokenResponse(),
            self::LEADS_URL => $leads,
        ]);
    }

    protected static function tokenResponse(string $token = 'access-token-1', int $expiresIn = 300): PromiseInterface
    {
        return Http::response(['access_token' => $token, 'expires_in' => $expiresIn, 'token_type' => 'Bearer']);
    }

    protected static function leadResponse(int $status = 201, string $prospectId = self::PROSPECT_ID): PromiseInterface
    {
        return Http::response([
            'leadId' => '9a8b7c6d-5e4f-4a3b-9c2d-1e0f9a8b7c6d', 'prospectId' => $prospectId, 'status' => 'NOUVEAU',
            'externalId' => 'ignored-by-nesel', 'receivedAt' => '2026-10-08T10:15:01Z',
        ], $status);
    }

    /**
     * @param  array<string, string>  $headers
     * @param  list<string>  $fields
     */
    protected static function problemResponse(int $status, string $code, array $headers = [], array $fields = []): PromiseInterface
    {
        return Http::response([
            'type' => 'about:blank', 'status' => $status, 'code' => $code, 'requestId' => 'req-1',
            'fieldErrors' => array_map(fn (string $field): array => ['field' => $field, 'message' => 'invalid'], $fields),
        ], $status, ['Content-Type' => 'application/problem+json'] + $headers);
    }
}
