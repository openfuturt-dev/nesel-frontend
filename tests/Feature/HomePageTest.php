<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomePageTest extends TestCase
{
    public function test_home_page_renders_the_domiciliation_offer(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('Domiciliez votre entreprise à')
            ->assertSee('Une adresse professionnelle et un accompagnement personnalisé pour lancer votre activité sereinement.')
            ->assertSee('Marrakech')
            ->assertSee('Casablanca')
            ->assertSee('Demander à être rappelé');
    }

    public function test_hero_ctas_lead_to_the_offers_and_to_an_advisor(): void
    {
        $this->get('/')->assertSeeInOrder([
            'href="'.route('offers').'"',
            'Découvrir nos offres de domiciliation',
            'href="#contact"',
            'Parler à un conseiller',
        ], false);
    }

    public function test_home_page_presents_the_four_service_categories(): void
    {
        $this->get('/')->assertSeeInOrder([
            'Domiciliation professionnelle',
            'Accompagnement à la constitution',
            'Suivi de votre dossier',
            'Assistance aux formalités de démarrage',
        ]);
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
