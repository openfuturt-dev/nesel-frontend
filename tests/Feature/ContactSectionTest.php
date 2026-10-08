<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ContactSectionTest extends TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function cityPages(): array
    {
        return [
            'marrakech' => ['domiciliation.marrakech', 'Marrakech'],
            'casablanca' => ['domiciliation.casablanca', 'Casablanca'],
        ];
    }

    #[DataProvider('cityPages')]
    public function test_city_page_embeds_the_contact_form_with_its_city_preselected(string $routeName, string $city): void
    {
        $this->get(route($routeName))
            ->assertOk()
            ->assertSee('id="contact"', false)
            ->assertSee('action="'.route('contact-requests.store').'"', false)
            ->assertSee('name="submission_token"', false)
            ->assertSee('<option value="'.$city.'" selected>', false)
            ->assertSee('<span data-submit-label aria-live="polite">Demander à être rappelé</span>', false);
    }

    #[DataProvider('cityPages')]
    public function test_city_page_contact_links_stay_on_the_page(string $routeName, string $_city): void
    {
        $content = $this->get(route($routeName))->getContent();

        $this->assertStringNotContainsString(route('home').'#contact', $content);
        $this->assertStringNotContainsString(route('home').'?ville=', $content);
        $this->assertGreaterThanOrEqual(3, substr_count($content, 'href="#contact"'));
    }

    public function test_city_query_overrides_the_page_default_city(): void
    {
        $this->get(route('domiciliation.marrakech', ['ville' => 'Casablanca']))
            ->assertSee('<option value="Casablanca" selected>', false)
            ->assertDontSee('<option value="Marrakech" selected>', false);
    }

    public function test_pages_without_a_form_send_contact_links_to_the_homepage_form(): void
    {
        $this->get(route('services'))
            ->assertDontSee('id="callback-form"', false)
            ->assertSee('href="'.route('home').'#contact"', false);
    }

    public function test_contact_form_is_rendered_once_per_page(): void
    {
        foreach (['home', 'domiciliation.marrakech', 'domiciliation.casablanca'] as $routeName) {
            $this->assertSame(1, substr_count($this->get(route($routeName))->getContent(), 'id="callback-form"'), $routeName);
        }
    }

    public function test_public_email_is_shown_and_the_internal_recipient_is_not(): void
    {
        config()->set('services.contact.recipient', 'internal-recipient@example.com');

        $this->get(route('domiciliation.marrakech'))
            ->assertSee('href="mailto:contact@ne-sel.com"', false)
            ->assertSee('"email":"contact@ne-sel.com"', false)
            ->assertDontSee('internal-recipient@example.com');
    }

    public function test_telephone_is_only_shown_once_configured(): void
    {
        $this->get(route('home'))->assertDontSee('href="tel:', false);

        config()->set('business.telephone', '+212 5 24 00 00 00');

        $this->get(route('home'))->assertSee('href="tel:+212524000000"', false);
    }
}
