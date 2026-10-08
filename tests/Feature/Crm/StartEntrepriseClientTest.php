<?php

namespace Tests\Feature\Crm;

use App\Support\StartEntreprise\StartEntrepriseClient;
use App\Support\StartEntreprise\StartEntrepriseTokenException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\FakesStartEntreprise;
use Tests\TestCase;

class StartEntrepriseClientTest extends TestCase
{
    use FakesStartEntreprise;

    private const PAYLOAD = ['externalId' => '01k6zc2x9ym1q8f4t3w5v7b2nd', 'contact' => ['fullName' => 'Ahmed Alaoui']];

    private StartEntrepriseClient $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->freezeSecond();
        $this->enableStartEntreprise();
        $this->client = $this->app->make(StartEntrepriseClient::class);
    }

    public function test_a_client_credentials_token_is_obtained_once_and_sent_with_the_idempotency_key(): void
    {
        $this->fakeStartEntreprise(self::leadResponse());

        $this->client->submitLead(self::PAYLOAD);
        $this->client->submitLead(self::PAYLOAD);

        Http::assertSentCount(3);
        Http::assertSent(fn (Request $request): bool => $request->url() === self::TOKEN_URL
            && $request->method() === 'POST'
            && $request->isForm()
            && $request['grant_type'] === 'client_credentials'
            && $request['client_id'] === 'integration-nesel-test'
            && $request['client_secret'] === 'test-client-secret');
        Http::assertSent(fn (Request $request): bool => $request->url() === self::LEADS_URL
            && $request->isJson()
            && $request->hasHeader('Authorization', 'Bearer access-token-1')
            && $request->hasHeader('Idempotency-Key', '01k6zc2x9ym1q8f4t3w5v7b2nd')
            && $request->hasHeader('X-Request-Id', 'nesel-01k6zc2x9ym1q8f4t3w5v7b2nd')
            && $request->data() === self::PAYLOAD);
    }

    public function test_the_token_is_renewed_shortly_before_keycloak_says_it_expires(): void
    {
        $this->fakeStartEntreprise(self::leadResponse(), Http::sequence()
            ->push(['access_token' => 'access-token-1', 'expires_in' => 300, 'token_type' => 'Bearer'])
            ->push(['access_token' => 'access-token-2', 'expires_in' => 300, 'token_type' => 'Bearer']));

        $this->client->submitLead(self::PAYLOAD);
        $this->travel(269)->seconds();
        $this->client->submitLead(self::PAYLOAD);
        $this->travel(2)->seconds();
        $this->client->submitLead(self::PAYLOAD);

        $tokens = collect(Http::recorded())->map(fn (array $pair) => $pair[0])
            ->filter(fn (Request $request): bool => $request->url() === self::LEADS_URL)
            ->map(fn (Request $request): string => $request->header('Authorization')[0])->values()->all();
        $this->assertSame(['Bearer access-token-1', 'Bearer access-token-1', 'Bearer access-token-2'], $tokens);
    }

    public function test_the_cached_token_is_encrypted(): void
    {
        $this->fakeStartEntreprise(self::leadResponse());

        $this->client->submitLead(self::PAYLOAD);

        $cached = Cache::get((fn () => $this->tokenCacheKey())->call($this->client));
        $this->assertIsString($cached);
        $this->assertStringNotContainsString('access-token-1', $cached);
    }

    public function test_a_401_renews_the_token_once_and_retries(): void
    {
        $this->fakeStartEntreprise(
            Http::sequence()->push(['code' => 'UNAUTHORIZED'], 401)->push(self::leadResponse()->wait()->getBody()->getContents(), 201),
            Http::sequence()->push(['access_token' => 'revoked', 'expires_in' => 300])->push(['access_token' => 'fresh', 'expires_in' => 300]),
        );

        $response = $this->client->submitLead(self::PAYLOAD);

        $this->assertSame(201, $response->status());
        Http::assertSent(fn (Request $request): bool => $request->url() === self::LEADS_URL
            && $request->hasHeader('Authorization', 'Bearer fresh'));
        Http::assertSentCount(4);
    }

    public function test_a_persistent_401_is_returned_after_a_single_renewal(): void
    {
        $this->fakeStartEntreprise(Http::response(['code' => 'UNAUTHORIZED'], 401));

        $this->assertSame(401, $this->client->submitLead(self::PAYLOAD)->status());

        Http::assertSentCount(4);
    }

    public function test_refused_credentials_are_a_non_retryable_token_error(): void
    {
        $this->fakeStartEntreprise(self::leadResponse(),
            Http::response(['error' => 'invalid_client', 'error_description' => 'Invalid client or Invalid client credentials'], 401));

        try {
            $this->client->submitLead(self::PAYLOAD);
            $this->fail('A token error was expected.');
        } catch (StartEntrepriseTokenException $error) {
            $this->assertSame('token_invalid_client', $error->errorCode);
            $this->assertFalse($error->retryable);
            $this->assertStringNotContainsString('test-client-secret', $error->getMessage());
        }
        Http::assertNotSent(fn (Request $request): bool => $request->url() === self::LEADS_URL);
    }

    public function test_an_unavailable_token_endpoint_is_retryable_and_a_malformed_answer_is_not(): void
    {
        $this->fakeStartEntreprise(self::leadResponse(), Http::sequence()
            ->push('Bad gateway', 502)
            ->push(['token_type' => 'Bearer', 'expires_in' => 300]));

        foreach ([['token_http_502', true], ['token_invalid_response', false]] as [$code, $retryable]) {
            try {
                $this->client->submitLead(self::PAYLOAD);
                $this->fail('A token error was expected.');
            } catch (StartEntrepriseTokenException $error) {
                $this->assertSame([$code, $retryable], [$error->errorCode, $error->retryable]);
            }
        }
    }

    public function test_network_failures_and_timeouts_surface_as_connection_errors(): void
    {
        $this->fakeStartEntreprise(Http::failedConnection('cURL error 28: Operation timed out after 15001 milliseconds'));

        $this->expectException(ConnectionException::class);

        $this->client->submitLead(self::PAYLOAD);
    }

    public function test_redirects_are_never_followed_with_the_bearer_token(): void
    {
        $this->fakeStartEntreprise(Http::response('', 302, ['Location' => 'https://elsewhere.example/leads']));

        $this->assertSame(302, $this->client->submitLead(self::PAYLOAD)->status());

        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), 'elsewhere.example'));
    }

    public function test_configuration_problems_are_reported_without_their_values(): void
    {
        $this->assertSame([], $this->client->configurationProblems());

        config()->set('services.startentreprise.api_url', 'http://api.startentreprise.test');
        config()->set('services.startentreprise.client_secret', '');
        config()->set('services.startentreprise.leads_sync_from', 'not a date');

        $this->assertSame([
            'api_url must be an https:// URL',
            'client_secret is missing',
            'leads_sync_from is missing or not a valid date',
        ], $this->client->configurationProblems());
    }
}
