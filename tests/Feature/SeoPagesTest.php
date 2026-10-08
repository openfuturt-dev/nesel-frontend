<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SeoPagesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.url', 'https://nesel.test');
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function publicPages(): array
    {
        return [
            'home' => ['/', 'Domiciliation d’entreprise à Marrakech et Casablanca | Nesel'],
            'services' => ['/services', 'Services de domiciliation et accompagnement d’entreprise | Nesel'],
            'offers' => ['/offres', 'Offres de domiciliation Silver, Golden et Diamond | Nesel'],
            'marrakech' => ['/domiciliation-marrakech', 'Domiciliation d’entreprise à Marrakech | Nesel'],
            'casablanca' => ['/domiciliation-casablanca', 'Domiciliation d’entreprise à Casablanca | Nesel'],
        ];
    }

    #[DataProvider('publicPages')]
    public function test_public_page_renders_its_seo_metadata(string $path, string $title): void
    {
        $canonicalUrl = 'https://nesel.test'.$path;
        $keywords = 'domiciliation entreprise Marrakech, domiciliation société Marrakech, adresse professionnelle Marrakech, domiciliation entreprise, domiciliation entreprise Casablanca, domiciliation société Maroc, services de domiciliation, adresse siège social, domiciliation siège social, adresse professionnelle entreprise, adresse professionnelle Casablanca, siège social Marrakech, siège social Casablanca, accompagnement entrepreneurial, accompagnement entrepreneurial Marrakech, bureau professionnel Marrakech';

        $this->get($path)
            ->assertOk()
            ->assertSee("<title>{$title}</title>", false)
            ->assertSee('<meta name="keywords" content="'.$keywords.'">', false)
            ->assertSee('<link rel="canonical" href="'.$canonicalUrl.'">', false)
            ->assertSee('<meta property="og:url" content="'.$canonicalUrl.'">', false)
            ->assertSee('<meta name="twitter:card" content="summary_large_image">', false)
            ->assertSee('"@type":"Organization"', false)
            ->assertSee(config('business.private_company_disclaimer'))
            ->assertSee('n’est pas un organisme gouvernemental')
            ->assertSee('ne délivre aucun document administratif officiel')
            ->assertDontSee('"@type":"Government', false);
    }

    #[DataProvider('publicPages')]
    public function test_public_page_has_exactly_one_h1(string $path, string $_title): void
    {
        $this->assertSame(1, substr_count($this->get($path)->getContent(), '<h1'));
    }

    #[DataProvider('publicPages')]
    public function test_public_page_is_indexable_when_indexing_is_enabled(string $path, string $_title): void
    {
        config()->set('seo.indexing_enabled', true);

        $this->get($path)
            ->assertSee('<meta name="robots" content="index, follow">', false)
            ->assertDontSee('noindex', false);
    }

    #[DataProvider('publicPages')]
    public function test_public_page_is_not_indexable_when_indexing_is_disabled(string $path, string $_title): void
    {
        config()->set('seo.indexing_enabled', false);

        $this->get($path)
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
            ->assertDontSee('content="index, follow"', false);
    }

    public function test_homepage_h1_targets_domiciliation_in_marrakech(): void
    {
        $this->get(route('home'))
            ->assertSeeInOrder([
                'Domiciliation d’entreprise · Marrakech et Casablanca',
                '<h1',
                'Domiciliez votre entreprise à',
                'Marrakech',
                '</h1>',
            ], false);
    }

    /**
     * Claims that would present Nesel as a public body or as the issuer of official
     * documents. Factual terms (création de société, statuts, administration…) stay allowed.
     *
     * @return array<string, string>
     */
    public static function misleadingClaims(): array
    {
        return [
            'government affiliation' => '/\b(site|organisme|service|portail|partenaire|prestataire)\s+(officiel|gouvernemental)/iu',
            'state approval' => '/\b(agréée?|mandatée?|habilitée?|accréditée?)\s+par\s+(l[’\']État|l[’\']administration|le gouvernement)/iu',
            'acting for the administration' => '/au\s+nom\s+de\s+(l[’\']État|l[’\']administration)/iu',
            'issuing documents' => '/\b(nous\s+délivrons|Nesel\s+délivre|délivré(e)?s?\s+par\s+Nesel)\b/iu',
            'direct-acquisition CTA' => '/\b(obten(ir|ez)|recev(oir|ez)|command(er|ez))\s+(votre|vos|un|une)\s+(RC|registre|ICE|IF\b|identifiant|immatriculation|numéro\s+d[’\']immatriculation|certificat\s+négatif|taxe\s+professionnelle|patente)/iu',
        ];
    }

    #[DataProvider('publicPages')]
    public function test_public_page_makes_no_misleading_government_or_official_document_claim(string $path, string $_title): void
    {
        // The disclaimer itself states what Nesel is not ("n’est pas un organisme gouvernemental").
        $content = str_replace(config('business.private_company_disclaimer'), '', $this->get($path)->getContent());

        foreach (self::misleadingClaims() as $claim => $pattern) {
            $this->assertDoesNotMatchRegularExpression($pattern, $content, "{$path}: {$claim}");
        }
    }

    public function test_misleading_claim_patterns_catch_the_claims_they_describe(): void
    {
        $examples = [
            'government affiliation' => 'Nesel, partenaire officiel de l’administration',
            'state approval' => 'Une société agréée par l’État',
            'acting for the administration' => 'Nous agissons au nom de l’administration',
            'issuing documents' => 'Nous délivrons vos documents en 48 h',
            'direct-acquisition CTA' => 'Obtenez votre RC en ligne',
        ];

        foreach (self::misleadingClaims() as $claim => $pattern) {
            $this->assertMatchesRegularExpression($pattern, $examples[$claim], $claim);
        }

        foreach (['Accompagnement à la constitution de votre société', 'création de société ou transfert de siège', 'vos échanges avec l’administration'] as $factualTerm) {
            foreach (self::misleadingClaims() as $claim => $pattern) {
                $this->assertDoesNotMatchRegularExpression($pattern, $factualTerm, "{$claim}: {$factualTerm}");
            }
        }
    }

    #[DataProvider('publicPages')]
    public function test_public_page_has_no_unfinished_placeholder(string $path, string $_title): void
    {
        $this->get($path)->assertDontSee('À compléter');
    }

    public function test_canonical_url_ignores_request_host_and_query_string(): void
    {
        $this->get('http://localhost/?ville=Marrakech')
            ->assertSee('<link rel="canonical" href="https://nesel.test/">', false);
    }

    public function test_marrakech_page_renders_its_h1_and_links_to_casablanca(): void
    {
        $this->get(route('domiciliation.marrakech'))
            ->assertOk()
            ->assertSeeInOrder(['<h1', 'Domiciliation d’entreprise à', 'Marrakech', '</h1>'], false)
            ->assertSee(route('domiciliation.casablanca'), false)
            ->assertSee('"@type":"BreadcrumbList"', false)
            ->assertSee('"@type":"FAQPage"', false);
    }

    public function test_casablanca_page_renders_its_h1_and_links_to_marrakech(): void
    {
        $this->get(route('domiciliation.casablanca'))
            ->assertOk()
            ->assertSeeInOrder(['<h1', 'Domiciliation d’entreprise à', 'Casablanca', '</h1>'], false)
            ->assertSee(route('domiciliation.marrakech'), false);
    }

    public function test_local_business_data_is_only_rendered_once_an_address_is_configured(): void
    {
        config()->set('business.locations.marrakech.street_address', null);

        $this->get(route('domiciliation.marrakech'))
            ->assertDontSee('"@type":"LocalBusiness"', false);

        config()->set('business.locations.marrakech.street_address', '1 rue Exemple');

        $this->get(route('domiciliation.marrakech'))
            ->assertSee('"@type":"LocalBusiness"', false)
            ->assertSee('"addressCountry":"MA"', false);
    }

    public function test_homepage_city_cards_link_to_city_pages(): void
    {
        $this->get(route('home'))
            ->assertSee(route('domiciliation.marrakech'), false)
            ->assertSee(route('domiciliation.casablanca'), false);
    }

    public function test_city_query_preselects_the_contact_form_city(): void
    {
        $this->get(route('home', ['ville' => 'Casablanca']))
            ->assertSee('<option value="Casablanca" selected>', false);
    }

    public function test_sitemap_lists_indexable_pages_as_xml(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSeeInOrder([
                '<loc>https://nesel.test/</loc>',
                '<loc>https://nesel.test/domiciliation-marrakech</loc>',
                '<loc>https://nesel.test/domiciliation-casablanca</loc>',
            ], false);

        $this->assertNotFalse(simplexml_load_string($response->getContent()));
    }

    public function test_robots_txt_allows_crawling_and_references_the_sitemap(): void
    {
        $this->get('/robots.txt')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSee('Allow: /')
            ->assertSee('Sitemap: https://nesel.test/sitemap.xml');
    }
}
