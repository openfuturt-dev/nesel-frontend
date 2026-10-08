<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomePageTest extends TestCase
{
    public function test_home_page_renders_the_domiciliation_offer(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('Votre entreprise mérite une')
            ->assertSee('adresse qui compte.')
            ->assertSee('Marrakech')
            ->assertSee('Casablanca')
            ->assertSee('Demander à être rappelé');
    }

    public function test_submit_button_exposes_the_label_swapped_while_submitting(): void
    {
        $this->get('/')->assertSee('<span data-submit-label aria-live="polite">Demander à être rappelé</span>', false);
    }

    public function test_contact_form_carries_a_fresh_submission_token(): void
    {
        $tokenPattern = '/name="submission_token" value="([0-9a-f-]{36})"/';

        preg_match($tokenPattern, $this->get('/')->getContent(), $first);
        preg_match($tokenPattern, $this->get('/')->getContent(), $second);

        $this->assertNotEmpty($first);
        $this->assertNotSame($first[1], $second[1]);
    }
}
