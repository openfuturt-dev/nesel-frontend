<?php

namespace Tests\Feature\Http\Controllers;

use App\Enums\DeliveryStatus;
use App\Mail\ContactRequestSubmitted;
use App\Models\ContactRequest;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\Concerns\InteractsWithMailTransport;
use Tests\TestCase;

class ContactRequestControllerTest extends TestCase
{
    use InteractsWithMailTransport, RefreshDatabase;

    private const SUCCESS_MESSAGE = 'Merci ! Votre demande a bien été envoyée. Un conseiller Nesel vous recontactera rapidement.';

    public function test_valid_request_is_stored_emailed_and_confirmed(): void
    {
        config()->set('mail.from.address', 'contact@ne-sel.com');
        config()->set('mail.from.name', 'Nesel');
        config()->set('services.contact.recipient', 'majd.chraibi@gmail.com');
        Mail::fake();
        $token = (string) Str::uuid();

        $response = $this->post(route('contact-requests.store'), $this->validPayload(['submission_token' => $token]));

        $response->assertRedirect(route('home').'#contact')
            ->assertSessionHas('contact_success', self::SUCCESS_MESSAGE);

        Mail::assertSent(ContactRequestSubmitted::class, function (ContactRequestSubmitted $mail): bool {
            return $mail->hasTo('majd.chraibi@gmail.com')
                && $mail->hasFrom('contact@ne-sel.com', 'Nesel')
                && $mail->name === 'Nassim Namous'
                && $mail->email === 'nassim@example.com'
                && $mail->phone === '+212 6 12 34 56 78'
                && $mail->city === 'Marrakech'
                && $mail->details === 'Je souhaite domicilier une nouvelle société.';
        });

        $contactRequest = ContactRequest::sole();
        $this->assertTrue(Str::isUlid($contactRequest->public_id));
        $this->assertSame($token, $contactRequest->submission_token);
        $this->assertSame([
            'name' => 'Nassim Namous',
            'email' => 'nassim@example.com',
            'phone' => '+212 6 12 34 56 78',
            'city' => 'Marrakech',
            'offer' => null,
            'message' => 'Je souhaite domicilier une nouvelle société.',
        ], $contactRequest->only(['name', 'email', 'phone', 'city', 'offer', 'message']));
        $this->assertSame(DeliveryStatus::Delivered, $contactRequest->notification_status);
        $this->assertSame(1, $contactRequest->notification_attempts);
        $this->assertNotNull($contactRequest->notification_sent_at);
        $this->assertSame(DeliveryStatus::Pending, $contactRequest->crm_status);
        $this->assertSame(0, $contactRequest->crm_attempts);
    }

    public function test_email_is_sent_after_the_response_not_before(): void
    {
        Mail::fake();
        $kernel = $this->app->make(Kernel::class);
        $request = Request::create(route('contact-requests.store'), 'POST', $this->validPayload());

        $response = $kernel->handle($request);

        $this->assertTrue($response->isRedirect(route('home').'#contact'));
        $this->assertSame(DeliveryStatus::Pending, ContactRequest::sole()->notification_status);
        Mail::assertNothingSent();

        $kernel->terminate($request, $response);

        Mail::assertSentCount(1);
        $this->assertSame(DeliveryStatus::Delivered, ContactRequest::sole()->notification_status);
    }

    public function test_missing_required_fields_are_rejected_without_storing_or_emailing(): void
    {
        Mail::fake();

        $response = $this->from(route('home'))->post(route('contact-requests.store'));

        $response->assertRedirect(route('home').'#contact')
            ->assertSessionHasErrors([
                'name' => 'Veuillez indiquer votre nom complet.',
                'email' => 'Veuillez indiquer votre adresse e-mail.',
                'phone' => 'Veuillez indiquer votre numéro de téléphone.',
                'city' => 'Veuillez choisir une ville.',
            ]);

        $this->assertDatabaseEmpty('contact_requests');
        Mail::assertNothingSent();
    }

    public function test_invalid_contact_details_are_rejected_without_storing_or_emailing(): void
    {
        Mail::fake();

        $response = $this->from(route('home'))->post(route('contact-requests.store'), [
            'name' => str_repeat('a', 101),
            'email' => 'not-an-email',
            'phone' => 'not-a-phone',
            'city' => 'Rabat',
            'message' => str_repeat('a', 2001),
        ]);

        $response->assertRedirect(route('home').'#contact')
            ->assertSessionHasErrors([
                'name' => 'Le nom complet ne peut pas dépasser 100 caractères.',
                'email' => 'Veuillez indiquer une adresse e-mail valide.',
                'phone' => 'Veuillez indiquer un numéro de téléphone valide.',
                'city' => 'La ville choisie doit être Marrakech ou Casablanca.',
                'message' => 'Votre message ne peut pas dépasser 2 000 caractères.',
            ]);

        $this->assertDatabaseEmpty('contact_requests');
        Mail::assertNothingSent();
    }

    public function test_email_addresses_the_mailer_cannot_use_are_rejected(): void
    {
        Mail::fake();

        $this->post(route('contact-requests.store'), $this->validPayload(['email' => 'nassim(comment)@example.com']))
            ->assertSessionHasErrors(['email' => 'Veuillez indiquer une adresse e-mail valide.']);

        $this->assertDatabaseEmpty('contact_requests');
        Mail::assertNothingSent();
    }

    public function test_resubmitting_the_same_token_stores_and_emails_once(): void
    {
        Mail::fake();
        $payload = $this->validPayload(['submission_token' => (string) Str::uuid()]);

        $this->post(route('contact-requests.store'), $payload)->assertSessionHas('contact_success');
        $this->post(route('contact-requests.store'), $payload)->assertSessionHas('contact_success');

        $this->assertDatabaseCount('contact_requests', 1);
        Mail::assertSentCount(1);
    }

    public function test_already_stored_token_is_confirmed_without_a_new_record_or_email(): void
    {
        Mail::fake();
        $existing = ContactRequest::factory()->create();

        $this->post(route('contact-requests.store'), $this->validPayload(['submission_token' => $existing->submission_token]))
            ->assertRedirect(route('home').'#contact')
            ->assertSessionHas('contact_success', self::SUCCESS_MESSAGE);

        $this->assertDatabaseCount('contact_requests', 1);
        $this->assertSame($existing->name, ContactRequest::sole()->name);
        Mail::assertNothingSent();
    }

    public function test_request_without_token_is_stored_with_a_generated_token(): void
    {
        Mail::fake();

        $this->post(route('contact-requests.store'), $this->validPayload())
            ->assertSessionHas('contact_success');

        $this->assertTrue(Str::isUuid(ContactRequest::sole()->submission_token));
    }

    public function test_malformed_token_is_rejected(): void
    {
        Mail::fake();

        $this->post(route('contact-requests.store'), $this->validPayload(['submission_token' => 'not-a-uuid']))
            ->assertSessionHasErrors(['submission_token' => 'Le formulaire a expiré. Veuillez actualiser la page et réessayer.']);

        $this->assertDatabaseEmpty('contact_requests');
    }

    public function test_smtp_failure_keeps_the_request_confirms_it_and_schedules_a_retry(): void
    {
        $this->freezeSecond();
        $this->useFailingMailer();

        $response = $this->post(route('contact-requests.store'), $this->validPayload());

        $response->assertRedirect(route('home').'#contact')
            ->assertSessionHas('contact_success', self::SUCCESS_MESSAGE);

        $contactRequest = ContactRequest::sole();
        $this->assertSame('nassim@example.com', $contactRequest->email);
        $this->assertSame(DeliveryStatus::Pending, $contactRequest->notification_status);
        $this->assertSame(1, $contactRequest->notification_attempts);
        $this->assertSame('smtp_unavailable', $contactRequest->notification_last_error);
        $this->assertTrue($contactRequest->notification_next_attempt_at->equalTo(now()->addMinute()));
        $this->assertNull($contactRequest->notification_sent_at);
    }

    public function test_resubmitting_after_a_failed_email_does_not_send_a_second_one_early(): void
    {
        $this->useFailingMailer();
        $payload = $this->validPayload(['submission_token' => (string) Str::uuid()]);
        $this->post(route('contact-requests.store'), $payload);

        Mail::fake();
        $this->post(route('contact-requests.store'), $payload)->assertSessionHas('contact_success');

        $this->assertDatabaseCount('contact_requests', 1);
        Mail::assertNothingSent();
    }

    /**
     * @param  array<string, string>  $overrides
     * @return array<string, string>
     */
    private function validPayload(array $overrides = []): array
    {
        return [
            'name' => 'Nassim Namous',
            'email' => 'nassim@example.com',
            'phone' => '+212 6 12 34 56 78',
            'city' => 'Marrakech',
            'message' => 'Je souhaite domicilier une nouvelle société.',
            ...$overrides,
        ];
    }
}
