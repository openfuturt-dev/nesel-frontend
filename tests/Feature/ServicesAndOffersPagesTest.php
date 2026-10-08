<?php

namespace Tests\Feature;

use App\Mail\ContactRequestSubmitted;
use App\Models\ContactRequest;
use App\Support\Seo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ServicesAndOffersPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.url', 'https://nesel.test');
    }

    public function test_services_page_presents_the_seven_service_groups(): void
    {
        $this->get(route('services'))
            ->assertOk()
            ->assertSeeInOrder(['<h1', 'Services de domiciliation et d’accompagnement pour votre entreprise', '</h1>'], false)
            ->assertSeeInOrder([
                'Domiciliation du siège social au Maroc',
                'Gestion professionnelle de votre courrier',
                'Réexpédition nationale et internationale',
                'Bureaux, coworking et salles de réunion',
                'Accompagnement à la création d’entreprise au Maroc',
                'Secrétariat et accompagnement administratif',
                'Services complémentaires pour investisseurs et entrepreneurs',
            ])
            ->assertSeeInOrder(['Opérations', 'Espaces professionnels', 'Accompagnement business'])
            ->assertSee('Préparation des informations et documents nécessaires')
            ->assertSee('Mise en relation avec des professionnels partenaires lorsque nécessaire')
            ->assertSee('href="'.route('offers').'"', false)
            ->assertSee('Découvrir nos offres de domiciliation')
            ->assertSee(route('home', ['ville' => 'Marrakech']).'#contact', false)
            ->assertSee(route('home', ['ville' => 'Casablanca']).'#contact', false);
    }

    public function test_offers_page_presents_the_three_offers_and_comparison(): void
    {
        $this->get(route('offers'))
            ->assertOk()
            ->assertSeeInOrder(['<h1', 'Nos offres de domiciliation', '</h1>'], false)
            ->assertSeeInOrder([
                'Silver', 'Domiciliation administrative essentielle',
                'Golden', 'Domiciliation exécutive et gestion administrative renforcée',
                'Diamond', 'Domiciliation Corporate Premium et accompagnement renforcé',
            ])
            ->assertSee('<table', false)
            ->assertSee('aria-label="Comparaison mobile des offres"', false)
            ->assertSee('En option')
            ->assertSee('Non inclus')
            ->assertSee('Quota mensuel selon les conditions de l’offre')
            ->assertSee('Accompagnement de base à la création et à l’organisation de l’entreprise')
            ->assertSee('Coordination des démarches liées à l’évolution de l’entreprise')
            ->assertSee('Quelle offre correspond à mon besoin ?')
            ->assertSee('Équilibre entre image et gestion')
            ->assertSee(route('home', ['offre' => 'Golden']).'#contact', false);
    }

    public function test_services_and_offers_pages_show_breadcrumbs(): void
    {
        $this->get(route('services'))
            ->assertSeeInOrder(['Fil d’Ariane', 'Accueil', 'Services'], false)
            ->assertSee('"@type":"BreadcrumbList"', false);

        $this->get(route('offers'))
            ->assertSeeInOrder(['Fil d’Ariane', 'Accueil', 'Offres'], false)
            ->assertSee('"@type":"BreadcrumbList"', false);
    }

    public function test_no_prices_are_published(): void
    {
        foreach (['home', 'services', 'offers', 'domiciliation.marrakech', 'domiciliation.casablanca'] as $routeName) {
            $content = $this->get(route($routeName))->getContent();

            $this->assertDoesNotMatchRegularExpression('/\d[\d\s.,]*\s?(DH|MAD|dirhams?|€|EUR)\b/iu', $content, $routeName);
            $this->assertDoesNotMatchRegularExpression('/"(price|priceCurrency|priceSpecification|aggregateRating|reviewCount)"/', $content, $routeName);
        }
    }

    public function test_every_page_has_valid_structured_data_and_unique_metadata(): void
    {
        $titles = [];
        $descriptions = [];

        foreach (Seo::INDEXABLE_ROUTES as $routeName) {
            $content = $this->get(route($routeName))->assertOk()->getContent();

            preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $content, $blocks);
            $this->assertNotEmpty($blocks[1], $routeName);

            foreach ($blocks[1] as $json) {
                $this->assertIsArray(json_decode($json, true, flags: JSON_THROW_ON_ERROR), $routeName);
            }

            preg_match('#<title>(.*?)</title>#', $content, $title);
            preg_match('#<meta name="description" content="(.*?)">#', $content, $description);
            $titles[] = $title[1];
            $descriptions[] = $description[1];
        }

        $this->assertSame($titles, array_unique($titles));
        $this->assertSame($descriptions, array_unique($descriptions));
    }

    public function test_services_and_offers_structured_data_describe_the_visible_content(): void
    {
        $this->get(route('services'))
            ->assertSee('"@type":"Service"', false)
            ->assertSee('"name":"Gestion professionnelle de votre courrier"', false);

        $this->get(route('offers'))
            ->assertSee('"@type":"OfferCatalog"', false)
            ->assertSee('"@type":"Offer","name":"Silver"', false)
            ->assertSee('"@type":"Offer","name":"Golden"', false)
            ->assertSee('"@type":"Offer","name":"Diamond"', false);
    }

    public function test_navigation_and_footer_link_to_services_and_offers(): void
    {
        $response = $this->get(route('domiciliation.marrakech'));

        foreach (['Navigation principale', 'Navigation mobile', 'Liens de pied de page'] as $navigation) {
            $response->assertSeeInOrder([$navigation, 'href="'.route('services').'"', 'href="'.route('offers').'"'], false);
        }

        $response->assertSee('Découvrez l’ensemble de nos services de domiciliation')
            ->assertSee('Comparer les offres Silver, Golden et Diamond');

        $this->get(route('home'))
            ->assertSee('Découvrir tous nos services')
            ->assertSee('Comparer les offres')
            ->assertSee('Essentiel')
            ->assertSee('Premium');
    }

    public function test_sitemap_lists_services_and_offers(): void
    {
        $this->get('/sitemap.xml')
            ->assertSee('<loc>https://nesel.test/services</loc>', false)
            ->assertSee('<loc>https://nesel.test/offres</loc>', false);
    }

    public function test_offer_query_preselects_the_contact_form_offer(): void
    {
        $this->get(route('home', ['offre' => 'Diamond', 'ville' => 'Casablanca']))
            ->assertSee('<option value="Diamond" selected>', false)
            ->assertSee('<option value="Casablanca" selected>', false);
    }

    public function test_contact_request_can_include_an_optional_offer(): void
    {
        Mail::fake();

        $this->post(route('contact-requests.store'), [
            'name' => 'Nassim Namous',
            'email' => 'nassim@example.com',
            'phone' => '+212 6 12 34 56 78',
            'city' => 'Marrakech',
            'offer' => 'Golden',
        ])->assertSessionHasNoErrors();

        Mail::assertSent(ContactRequestSubmitted::class, fn (ContactRequestSubmitted $mail): bool => $mail->offer === 'Golden');
        $this->assertSame('Golden', ContactRequest::sole()->offer);
    }

    public function test_contact_request_rejects_an_unknown_offer(): void
    {
        Mail::fake();

        $this->post(route('contact-requests.store'), [
            'name' => 'Nassim Namous',
            'email' => 'nassim@example.com',
            'phone' => '+212 6 12 34 56 78',
            'city' => 'Marrakech',
            'offer' => 'Platinum',
        ])->assertSessionHasErrors(['offer' => 'Veuillez choisir une offre proposée.']);

        $this->assertDatabaseEmpty('contact_requests');
        Mail::assertNothingSent();
    }
}
