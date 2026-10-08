<?php

namespace Tests\Feature\Mail;

use App\Mail\ContactRequestSubmitted;
use Tests\TestCase;

class ContactRequestSubmittedTest extends TestCase
{
    public function test_email_renders_contact_details_in_html_and_plain_text(): void
    {
        $mail = new ContactRequestSubmitted(
            name: 'Nassim Namous',
            email: 'nassim@example.com',
            phone: '+212 6 12 34 56 78',
            city: 'Casablanca',
            details: 'Création d’entreprise',
        );

        $mail->assertHasSubject('Nouvelle demande de domiciliation — Casablanca')
            ->assertSeeInHtml('Nassim Namous')
            ->assertHasReplyTo('nassim@example.com', 'Nassim Namous')
            ->assertSeeInHtml('nassim@example.com')
            ->assertSeeInHtml('+212 6 12 34 56 78')
            ->assertSeeInHtml('Casablanca')
            ->assertSeeInHtml('Création d’entreprise')
            ->assertSeeInText('Nassim Namous')
            ->assertSeeInText('nassim@example.com')
            ->assertSeeInText('+212 6 12 34 56 78')
            ->assertSeeInText('Casablanca')
            ->assertSeeInText('Création d’entreprise');
    }

    public function test_every_send_of_a_request_carries_the_same_message_id(): void
    {
        config()->set('mail.from.address', 'contact@ne-sel.com');
        $mail = new ContactRequestSubmitted(
            name: 'Nassim Namous',
            email: 'nassim@example.com',
            phone: '+212 6 12 34 56 78',
            city: 'Marrakech',
            details: null,
            reference: '01k6zc2x9ym1q8f4t3w5v7b2nd',
        );

        $this->assertSame('contact-request.01k6zc2x9ym1q8f4t3w5v7b2nd@ne-sel.com', $mail->headers()->messageId);
        $this->assertNull((new ContactRequestSubmitted('A', 'a@example.com', '0600000000', 'Marrakech', null))->headers()->messageId);
    }

    public function test_email_escapes_user_provided_html(): void
    {
        $dangerousContent = '<script>alert("xss")</script>';
        $mail = new ContactRequestSubmitted(
            name: $dangerousContent,
            email: 'nassim@example.com',
            phone: '+212600000000',
            city: 'Marrakech',
            details: $dangerousContent,
        );

        $mail->assertSeeInHtml($dangerousContent)
            ->assertDontSeeInHtml($dangerousContent, false);
    }
}
