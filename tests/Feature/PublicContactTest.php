<?php

namespace Tests\Feature;

use App\Mail\PublicContactMessage;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

class PublicContactTest extends TestCase
{
    public function test_contact_page_has_published_contacts_and_a_message_form(): void
    {
        $this->get(route('website.contact'))->assertOk()
            ->assertSee('Send your message')
            ->assertSee('mailto:info@frenchvillage.edu.ng', false)
            ->assertSee('mailto:registrar@frenchvillage.edu.ng', false)
            ->assertSee('mailto:academicaffairs@frenchvillage.edu.ng', false)
            ->assertSee('Badagry, Lagos State, Nigeria')
            ->assertSee('action="'.route('website.contact.send').'"', false);
    }

    public function test_a_valid_enquiry_is_sent_to_nflv_with_the_senders_reply_address(): void
    {
        Mail::fake();
        $this->post(route('website.contact.send'), [
            'name' => 'Test Enquirer', 'email' => 'enquirer@example.test',
            'subject' => 'French programme enquiry', 'message' => 'Please send information about the programme.',
        ])->assertRedirect(route('website.contact').'#contact-message')->assertSessionHas('contact_status');

        Mail::assertSent(PublicContactMessage::class, fn ($mail) => $mail->hasTo('info@frenchvillage.edu.ng')
            && $mail->envelope()->replyTo[0]->address === 'enquirer@example.test'
            && $mail->enquiry['subject'] === 'French programme enquiry');
    }

    public function test_invalid_enquiries_are_rejected_without_sending_mail(): void
    {
        Mail::fake();
        $this->post(route('website.contact.send'), ['name' => '', 'email' => 'invalid', 'subject' => '', 'message' => ''])
            ->assertSessionHasErrors(['name', 'email', 'subject', 'message']);
        Mail::assertNothingSent();
    }

    public function test_mail_failure_preserves_the_message_and_provides_a_contact_alternative(): void
    {
        Mail::shouldReceive('to')->once()->with('info@frenchvillage.edu.ng')->andReturnSelf();
        Mail::shouldReceive('send')->once()->andThrow(new TransportException('Mail temporarily unavailable'));
        $this->from(route('website.contact'))->post(route('website.contact.send'), [
            'name' => 'Test Enquirer', 'email' => 'enquirer@example.test', 'subject' => 'Question', 'message' => 'Please help.',
        ])->assertRedirect(route('website.contact'))->assertSessionHasErrors('delivery')
            ->assertSessionHasInput('message', 'Please help.');
    }

    public function test_enquiry_content_is_escaped_in_the_mail(): void
    {
        $mail = new PublicContactMessage([
            'name' => '<script>alert(1)</script>', 'email' => 'enquirer@example.test',
            'subject' => '<b>Question</b>', 'message' => '<img src=x onerror=alert(1)>',
        ]);
        $html = $mail->render();
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('<img src=x', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }
}
