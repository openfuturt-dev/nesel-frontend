<?php

namespace Tests\Feature\Crm;

use App\Models\ContactRequest;
use App\Support\StartEntreprise\LeadPayload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadPayloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_contact_request_maps_to_the_strict_lead_contract(): void
    {
        $contactRequest = ContactRequest::factory()->create([
            'name' => 'Ahmed Alaoui', 'email' => 'ahmed@example.com', 'phone' => '+212 6 12 34 56 78',
            'city' => 'Marrakech', 'offer' => 'Golden', 'message' => "Bonjour,\nje souhaite une proposition.",
            'landing_page_url' => 'https://ne-sel.com/offres?utm_source=google&utm_medium=cpc',
            'referrer_url' => 'https://www.google.com/',
            'utm_source' => 'google', 'utm_medium' => 'cpc', 'utm_campaign' => 'domiciliation',
            'utm_term' => 'domiciliation marrakech', 'utm_content' => null,
            'created_at' => '2026-10-08 10:15:00',
        ]);

        $this->assertSame([
            'externalId' => $contactRequest->public_id,
            'submittedAt' => '2026-10-08T10:15:00Z',
            'contact' => [
                'fullName' => 'Ahmed Alaoui', 'email' => 'ahmed@example.com', 'phone' => '+212 6 12 34 56 78',
                'companyName' => null,
            ],
            'inquiry' => [
                'subject' => 'Demande de domiciliation — Marrakech — Golden',
                'message' => "Bonjour,\nje souhaite une proposition.",
                'city' => 'Marrakech',
                'offer' => 'Golden',
            ],
            'attribution' => [
                'landingPageUrl' => 'https://ne-sel.com/offres?utm_source=google&utm_medium=cpc',
                'referrerUrl' => 'https://www.google.com/',
                'utm' => [
                    'source' => 'google', 'medium' => 'cpc', 'campaign' => 'domiciliation',
                    'term' => 'domiciliation marrakech', 'content' => null,
                ],
            ],
        ], LeadPayload::from($contactRequest));
    }

    public function test_the_advice_offer_no_offer_and_non_https_urls_are_mapped_safely(): void
    {
        $advice = ContactRequest::factory()->create(['city' => 'Casablanca', 'offer' => 'Conseil']);
        $none = ContactRequest::factory()->create([
            'city' => 'Marrakech', 'offer' => null, 'landing_page_url' => 'http://localhost:8000/', 'referrer_url' => null,
        ]);

        $this->assertSame('Demande de domiciliation — Casablanca — Souhaite être conseillé', LeadPayload::from($advice)['inquiry']['subject']);
        $this->assertSame('Souhaite être conseillé', LeadPayload::from($advice)['inquiry']['offer']);
        $this->assertSame('Demande de domiciliation — Marrakech', LeadPayload::from($none)['inquiry']['subject']);
        $this->assertNull(LeadPayload::from($none)['inquiry']['offer']);
        $this->assertNull(LeadPayload::from($none)['attribution']['landingPageUrl']);
    }

    public function test_the_payload_is_stable_and_never_carries_crm_controlled_fields(): void
    {
        $contactRequest = ContactRequest::factory()->create();
        $payload = LeadPayload::from($contactRequest);

        $this->assertSame($payload, LeadPayload::from($contactRequest->fresh()));
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9._:-]{1,100}$/', $payload['externalId']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', $payload['submittedAt']);
        // Exactly the contract's keys at every level: no organization, status, source or assignee.
        $this->assertSame(['externalId', 'submittedAt', 'contact', 'inquiry', 'attribution'], array_keys($payload));
        $this->assertSame(['fullName', 'email', 'phone', 'companyName'], array_keys($payload['contact']));
        $this->assertSame(['subject', 'message', 'city', 'offer'], array_keys($payload['inquiry']));
        $this->assertSame(['landingPageUrl', 'referrerUrl', 'utm'], array_keys($payload['attribution']));
        $this->assertSame(['source', 'medium', 'campaign', 'term', 'content'], array_keys($payload['attribution']['utm']));
    }
}
