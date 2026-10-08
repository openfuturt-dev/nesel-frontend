<?php

namespace Tests\Feature\ContactForm;

use App\Models\ContactRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\SubmitsContactForm;
use Tests\TestCase;

class LeadAttributionTest extends TestCase
{
    use RefreshDatabase, SubmitsContactForm;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.url', 'https://nesel.test');
        Mail::fake();
    }

    /**
     * @param  array<string, string|null>  $expected
     */
    #[DataProvider('acquisitionChannels')]
    public function test_the_acquisition_channel_is_saved_with_the_request(string $uri, ?string $referrer, array $expected): void
    {
        $this->get($uri, array_filter(['Referer' => $referrer]))->assertOk();

        $this->submit();

        $this->assertSame($expected, $this->savedAttribution());
    }

    /**
     * @return array<string, array{string, string|null, array<string, string|null>}>
     */
    public static function acquisitionChannels(): array
    {
        return [
            'Google Ads (tagged)' => [
                '/offres?utm_source=google&utm_medium=cpc&utm_campaign=domiciliation-marrakech&utm_term=domiciliation+entreprise&utm_content=annonce-1&gclid=Cj0KCQ',
                'https://www.google.com/',
                self::attribution(
                    landing: 'https://nesel.test/offres?utm_source=google&utm_medium=cpc&utm_campaign=domiciliation-marrakech&utm_term=domiciliation+entreprise&utm_content=annonce-1',
                    referrer: 'https://www.google.com/',
                    utm: ['google', 'cpc', 'domiciliation-marrakech', 'domiciliation entreprise', 'annonce-1'],
                ),
            ],
            'Facebook ad' => [
                '/?utm_source=facebook&utm_medium=paid_social&fbclid=IwAR0abc',
                'https://l.facebook.com/l.php?u=https%3A%2F%2Fne-sel.com%2F&h=AT0secret',
                self::attribution(
                    landing: 'https://nesel.test/?utm_source=facebook&utm_medium=paid_social',
                    referrer: 'https://l.facebook.com/l.php',
                    utm: ['facebook', 'paid_social'],
                ),
            ],
            'Instagram link' => [
                '/domiciliation-casablanca',
                'https://l.instagram.com/',
                self::attribution(landing: 'https://nesel.test/domiciliation-casablanca', referrer: 'https://l.instagram.com/'),
            ],
            'organic search' => [
                '/',
                'https://www.google.com/',
                self::attribution(landing: 'https://nesel.test/', referrer: 'https://www.google.com/'),
            ],
            'referral website' => [
                '/services',
                'https://www.annuaire-entreprises.example/fiche/nesel?ref=123#avis',
                self::attribution(landing: 'https://nesel.test/services', referrer: 'https://www.annuaire-entreprises.example/fiche/nesel'),
            ],
            'direct' => [
                '/',
                null,
                self::attribution(landing: 'https://nesel.test/'),
            ],
        ];
    }

    public function test_a_submission_without_any_page_view_has_no_attribution(): void
    {
        $this->submit();

        $this->assertSame(self::attribution(), $this->savedAttribution());
    }

    public function test_internal_navigation_and_later_campaigns_do_not_overwrite_the_first_touch(): void
    {
        $this->get('/?utm_source=facebook&utm_medium=paid_social', ['Referer' => 'https://l.facebook.com/'])->assertOk();
        $this->get('/services', ['Referer' => 'https://nesel.test/'])->assertOk();
        $this->get('/offres?utm_source=newsletter', ['Referer' => 'https://mail.example.com/'])->assertOk();

        $this->submit();

        $this->assertSame(self::attribution(
            landing: 'https://nesel.test/?utm_source=facebook&utm_medium=paid_social',
            referrer: 'https://l.facebook.com/',
            utm: ['facebook', 'paid_social'],
        ), $this->savedAttribution());
    }

    public function test_internal_navigation_keeps_a_direct_first_touch(): void
    {
        $this->get('/services')->assertOk();
        $this->get('/', ['Referer' => 'https://nesel.test/services'])->assertOk();

        $this->submit();

        $this->assertSame(self::attribution(landing: 'https://nesel.test/services'), $this->savedAttribution());
    }

    public function test_a_campaign_after_a_direct_first_touch_is_recorded(): void
    {
        $this->get('/')->assertOk();
        $this->get('/offres?utm_source=google&utm_medium=cpc', ['Referer' => 'https://www.google.com/'])->assertOk();

        $this->submit();

        $this->assertSame(self::attribution(
            landing: 'https://nesel.test/offres?utm_source=google&utm_medium=cpc',
            referrer: 'https://www.google.com/',
            utm: ['google', 'cpc'],
        ), $this->savedAttribution());
    }

    public function test_attribution_survives_a_failed_submission(): void
    {
        $this->get('/?utm_source=google', ['Referer' => 'https://www.google.com/'])->assertOk();
        $this->post(route('contact-requests.store'), $this->contactPayload(['email' => 'invalide']))->assertSessionHasErrors('email');

        $this->submit();

        $this->assertSame('google', ContactRequest::sole()->utm_source);
    }

    #[DataProvider('unsafeReferrers')]
    public function test_unsafe_or_malformed_referrers_are_discarded(string $referrer): void
    {
        $this->get('/', ['Referer' => $referrer])->assertOk();

        $this->submit();

        $this->assertNull(ContactRequest::sole()->referrer_url);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function unsafeReferrers(): array
    {
        return [
            'plain http' => ['http://insecure.example/page'],
            'javascript scheme' => ['javascript:alert(document.cookie)'],
            'data scheme' => ['data:text/html,<script>alert(1)</script>'],
            'credentials in URL' => ['https://user:secret@evil.example/'],
            'not a URL' => ['not a url'],
            'invalid host' => ['https://exa mple.com/'],
            'path with whitespace' => ["https://evil.example/a\tb"],
            'overlong' => ['https://evil.example/'.str_repeat('a', 2048)],
            'own site (APP_URL)' => ['https://nesel.test/services'],
            'own site (request host)' => ['https://localhost/services'],
        ];
    }

    public function test_utm_values_are_sanitised_bounded_and_limited_to_recognised_keys(): void
    {
        $query = http_build_query([
            'utm_source' => "  goo\x07gle  ",
            'utm_campaign' => str_repeat('c', 300),
            'utm_medium' => ['cpc'],
            'utm_content' => '',
            'utm_id' => 'ignored',
            'email' => 'visitor@example.com',
        ]);

        $this->get("/?{$query}")->assertOk();
        $this->submit();

        $saved = $this->savedAttribution();
        $this->assertSame('google', $saved['utm_source']);
        $this->assertSame(str_repeat('c', 150), $saved['utm_campaign']);
        $this->assertNull($saved['utm_medium']);
        $this->assertNull($saved['utm_content']);
        $this->assertStringNotContainsString('utm_id', $saved['landing_page_url']);
        $this->assertStringNotContainsString('visitor', $saved['landing_page_url']);
    }

    private function submit(): void
    {
        $this->post(route('contact-requests.store'), $this->contactPayload())->assertSessionHas('contact_success');
    }

    /**
     * @return array<string, string|null>
     */
    private function savedAttribution(): array
    {
        return ContactRequest::sole()->only([
            'landing_page_url', 'referrer_url', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content',
        ]);
    }

    /**
     * @param  list<string>  $utm  In order: source, medium, campaign, term, content.
     * @return array<string, string|null>
     */
    private static function attribution(?string $landing = null, ?string $referrer = null, array $utm = []): array
    {
        return [
            'landing_page_url' => $landing,
            'referrer_url' => $referrer,
            'utm_source' => $utm[0] ?? null,
            'utm_medium' => $utm[1] ?? null,
            'utm_campaign' => $utm[2] ?? null,
            'utm_term' => $utm[3] ?? null,
            'utm_content' => $utm[4] ?? null,
        ];
    }
}
