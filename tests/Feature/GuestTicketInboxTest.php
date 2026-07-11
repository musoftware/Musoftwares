<?php

namespace Tests\Feature;

use App\Models\GuestTicket;
use App\Models\GuestTicketMessage;
use App\Services\GuestTicketInbox;
use App\Services\Imap\ImapMessageParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestTicketInboxTest extends TestCase
{
    use RefreshDatabase;

    private function buildRfc822(string $from, string $subject, string $body, ?string $inReplyTo = null, ?string $references = null): string
    {
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: quoted-printable',
            'Date: '.date('r'),
            'From: '.$from,
            'To: admin@musoftwares.com',
            'Subject: '.$subject,
        ];

        if ($inReplyTo) {
            $headers[] = 'In-Reply-To: '.$inReplyTo;
        }
        if ($references) {
            $headers[] = 'References: '.$references;
        }
        $headers[] = 'Message-ID: <test-'.uniqid().'@example.com>';

        $encodedBody = quoted_printable_encode($body);

        return implode("\r\n", $headers)."\r\n\r\n".$encodedBody;
    }

    public function test_matches_by_in_reply_to_header(): void
    {
        $parser = $this->app->make(ImapMessageParser::class);
        $ticket = GuestTicket::create([
            'name' => 'Guest',
            'email' => 'guest@example.com',
            'mobile' => '0123',
            'subject' => 'Help',
            'body' => 'Please help',
            'status' => 'replied',
        ]);

        $existing = GuestTicketMessage::create([
            'guest_ticket_id' => $ticket->id,
            'direction' => 'outbound',
            'from_email' => 'admin@musoftwares.com',
            'to_email' => 'guest@example.com',
            'subject' => 'Reply',
            'body_text' => 'Reply text',
            'message_id' => 'fixed-msgid@example.com',
            'sent_at' => now(),
        ]);

        $raw = $this->buildRfc822(
            'Guest <guest@example.com>',
            'Re: Help',
            'Thanks!',
            $existing->message_id
        );

        $parsed = $parser->parse($raw);

        $inbox = new GuestTicketInbox($parser);
        $result = $inbox->processMessage($parsed);

        $this->assertSame($ticket->id, $result['ticket_id']);
        $this->assertDatabaseHas('guest_ticket_messages', [
            'guest_ticket_id' => $ticket->id,
            'direction' => 'inbound',
        ]);
    }

    public function test_matches_by_subject_tag_fallback(): void
    {
        $parser = $this->app->make(ImapMessageParser::class);
        $ticket = GuestTicket::create([
            'name' => 'Guest',
            'email' => 'guest2@example.com',
            'mobile' => '0123',
            'subject' => 'Help',
            'body' => 'Please help',
            'status' => 'pending',
        ]);

        $raw = $this->buildRfc822(
            'Guest <guest2@example.com>',
            '[GuestTicket#'.$ticket->id.'] Help',
            'Tag-match me'
        );

        $parsed = $parser->parse($raw);
        $inbox = new GuestTicketInbox($parser);
        $result = $inbox->processMessage($parsed);

        $this->assertSame($ticket->id, $result['ticket_id']);
    }

    public function test_creates_new_ticket_when_no_match(): void
    {
        $parser = $this->app->make(ImapMessageParser::class);

        $raw = $this->buildRfc822(
            'New Guest <newguest@example.com>',
            'Brand new request',
            'Nobody knows me yet'
        );

        $parsed = $parser->parse($raw);
        $inbox = new GuestTicketInbox($parser);
        $result = $inbox->processMessage($parsed);

        $this->assertDatabaseHas('guest_tickets', [
            'id' => $result['ticket_id'],
            'email' => 'newguest@example.com',
        ]);
    }
}
