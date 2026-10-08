<?php

namespace Tests\Feature\ContactForm;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\SubmitsContactForm;
use Tests\TestCase;

class RateLimitingTest extends TestCase
{
    use RefreshDatabase, SubmitsContactForm;

    private const THROTTLED = 'Plusieurs envois ont été effectués en peu de temps. Veuillez patienter une minute avant de réessayer.';

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    public function test_a_sixth_submission_within_a_minute_is_refused_with_a_friendly_message_and_kept_input(): void
    {
        $this->exhaustLimit();

        $this->post(route('contact-requests.store'), $this->contactPayload())
            ->assertRedirect(route('home').'#contact')
            ->assertHeader('Retry-After')
            ->assertSessionHasErrors(['throttle' => self::THROTTLED])
            ->assertSessionHasInput('name', 'Nassim Namous')
            ->assertSessionHasInput('email', 'nassim@example.com');

        $this->assertDatabaseEmpty('contact_requests');
    }

    public function test_the_throttle_message_replaces_the_generic_banner_on_the_form(): void
    {
        $this->exhaustLimit();

        $this->followingRedirects()
            ->post(route('contact-requests.store'), $this->contactPayload())
            ->assertSee(self::THROTTLED)
            ->assertDontSee('Veuillez corriger les informations indiquées ci-dessous.')
            ->assertSee('value="Nassim Namous"', false)
            ->assertDontSee('Too Many Requests');
    }

    public function test_the_limit_lifts_after_a_minute(): void
    {
        $this->exhaustLimit();
        $this->travel(61)->seconds();

        $this->post(route('contact-requests.store'), $this->contactPayload())->assertSessionHas('contact_success');
    }

    public function test_forged_forwarded_for_headers_do_not_bypass_the_limit(): void
    {
        foreach (range(1, 5) as $attempt) {
            $this->post(route('contact-requests.store'), [], ['X-Forwarded-For' => "203.0.113.{$attempt}"]);
        }

        $this->post(route('contact-requests.store'), $this->contactPayload(), ['X-Forwarded-For' => '198.51.100.7'])
            ->assertSessionHasErrors(['throttle' => self::THROTTLED]);
    }

    public function test_a_configured_proxy_is_trusted_to_report_the_client_ip(): void
    {
        config()->set('trustedproxy.proxies', '10.0.0.1');
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.1']);

        foreach (range(1, 5) as $attempt) {
            $this->post(route('contact-requests.store'), [], ['X-Forwarded-For' => '203.0.113.5']);
        }

        // Another visitor behind the same proxy keeps their own allowance...
        $this->post(route('contact-requests.store'), $this->contactPayload(), ['X-Forwarded-For' => '198.51.100.7'])
            ->assertSessionHas('contact_success');
        // ...while the first one is limited.
        $this->post(route('contact-requests.store'), $this->contactPayload(), ['X-Forwarded-For' => '203.0.113.5'])
            ->assertSessionHasErrors(['throttle' => self::THROTTLED]);
    }

    public function test_validation_errors_keep_the_visitor_input(): void
    {
        $this->followingRedirects()
            ->post(route('contact-requests.store'), $this->contactPayload(['email' => 'pas-une-adresse', 'offer' => 'Golden']))
            ->assertSee('Veuillez indiquer une adresse e-mail valide.')
            ->assertSee('value="Nassim Namous"', false)
            ->assertSee('value="pas-une-adresse"', false)
            ->assertSee('<option value="Golden" selected>', false)
            ->assertSee('Je souhaite domicilier une nouvelle société.');
    }

    /**
     * Use up the five submissions allowed per minute (invalid ones still count).
     */
    private function exhaustLimit(): void
    {
        foreach (range(1, 5) as $attempt) {
            $this->post(route('contact-requests.store'))->assertSessionHasErrors('name');
        }
    }
}
