<?php

namespace App\Support\StartEntreprise;

use App\Models\ContactRequest;

/**
 * Maps a stored contact request to the StartEntreprise commercial lead contract. The result only
 * depends on the stored row, so every retry sends the same payload under the same externalId.
 * Organization, status, source and assignee are never sent: StartEntreprise derives them from the
 * integration credentials and rejects them in the body.
 */
class LeadPayload
{
    /** Offers as shown to the visitor; "Conseil" is the "advise me" choice. */
    private const OFFER_LABELS = ['Conseil' => 'Souhaite être conseillé'];

    /**
     * @return array<string, mixed>
     */
    public static function from(ContactRequest $contactRequest): array
    {
        $offer = $contactRequest->offer === null
            ? null
            : (self::OFFER_LABELS[$contactRequest->offer] ?? $contactRequest->offer);

        return [
            'externalId' => $contactRequest->public_id,
            'submittedAt' => $contactRequest->created_at->copy()->utc()->format('Y-m-d\TH:i:s\Z'),
            'contact' => [
                'fullName' => $contactRequest->name,
                'email' => $contactRequest->email,
                'phone' => $contactRequest->phone,
                // The NESEL form does not ask for a company: the CRM records an individual.
                'companyName' => null,
            ],
            'inquiry' => [
                'subject' => self::subject($contactRequest->city, $offer),
                'message' => $contactRequest->message,
                'city' => $contactRequest->city,
                'offer' => $offer,
            ],
            'attribution' => [
                'landingPageUrl' => self::httpsUrl($contactRequest->landing_page_url),
                'referrerUrl' => self::httpsUrl($contactRequest->referrer_url),
                'utm' => [
                    'source' => $contactRequest->utm_source,
                    'medium' => $contactRequest->utm_medium,
                    'campaign' => $contactRequest->utm_campaign,
                    'term' => $contactRequest->utm_term,
                    'content' => $contactRequest->utm_content,
                ],
            ],
        ];
    }

    private static function subject(string $city, ?string $offer): string
    {
        return "Demande de domiciliation — {$city}".($offer === null ? '' : " — {$offer}");
    }

    /**
     * The API only accepts https:// URLs; anything else (e.g. a local http:// landing page) is
     * left out rather than making the whole lead invalid.
     */
    private static function httpsUrl(?string $url): ?string
    {
        return $url !== null && str_starts_with($url, 'https://') ? $url : null;
    }
}
