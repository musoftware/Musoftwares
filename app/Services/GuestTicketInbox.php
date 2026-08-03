<?php

namespace App\Services;

use App\Enums\GuestTicketStatus;
use App\Models\GuestTicket;
use App\Models\GuestTicketMessage;
use App\Services\Imap\ImapMessageParser;
use App\Services\Imap\PurePhpImapClient;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GuestTicketInbox
{
    public function __construct(
        private readonly ImapMessageParser $parser
    ) {}

    /**
     * Pull unseen IMAP messages and persist them as GuestTicketMessage rows.
     *
     * @return array{fetched:int,matched:int,created_tickets:int,errors:int}
     */
    public function pull(bool $dryRun = false): array
    {
        $cfg = config('imap', []);
        $sinceUnix = now()->subDays((int) ($cfg['lookback_days'] ?? 14))->getTimestamp();

        $stats = ['fetched' => 0, 'matched' => 0, 'created_tickets' => 0, 'errors' => 0];

        $host = (string) ($cfg['host'] ?? '');
        $username = (string) ($cfg['username'] ?? '');
        $password = (string) ($cfg['password'] ?? '');

        if ($host === '' || $username === '' || $password === '') {
            Log::info('IMAP pull skipped: Host or credentials not configured.');

            return $stats;
        }

        $client = new PurePhpImapClient($cfg);

        try {
            $client->connect($username, $password);
            $client->select((string) ($cfg['folder'] ?? 'INBOX'));
            $uids = $client->unseenUidsSince($sinceUnix);
        } catch (\Throwable $e) {
            Log::warning('IMAP connect failed: '.$e->getMessage());

            return $stats;
        }

        foreach ($uids as $uid) {
            try {
                $raw = $client->fetchRawByUid((int) $uid);
                $parsed = $this->parser->parse($raw);
                $stats['fetched']++;
                if ($dryRun) {
                    Log::info('IMAP dry-run', ['uid' => $uid, 'subject' => $parsed['subject'], 'from' => $parsed['from_email']]);

                    continue;
                }
                $matched = $this->processMessage($parsed);
                if ($matched) {
                    $stats['matched']++;
                    if ($matched['created']) {
                        $stats['created_tickets']++;
                    }
                }
                $client->markSeen((int) $uid);
            } catch (\Throwable $e) {
                $stats['errors']++;
                Log::warning('IMAP message skipped', ['uid' => $uid, 'error' => $e->getMessage()]);
            }
        }

        $client->disconnect();

        return $stats;
    }

    /**
     * Persist a single parsed message against an existing or new ticket.
     *
     * @return array{created:bool}
     */
    public function processMessage(array $parsed): array
    {
        $ticket = $this->resolveTicket($parsed);

        $bodyText = trim((string) ($parsed['body_text'] ?? ''));
        $bodyHtml = trim((string) ($parsed['body_html'] ?? ''));
        if ($bodyText === '' && $bodyHtml !== '') {
            $bodyText = trim(strip_tags($bodyHtml));
        }

        $attachments = $this->storeAttachments($ticket->id, $parsed['attachments'] ?? []);

        DB::transaction(function () use ($ticket, $parsed, $bodyText, $bodyHtml, $attachments) {
            GuestTicketMessage::create([
                'guest_ticket_id' => $ticket->id,
                'direction' => GuestTicket::DIRECTION_INBOUND,
                'from_email' => $parsed['from_email'] ?? null,
                'to_email' => $parsed['to_email'] ?? null,
                'subject' => $parsed['subject'] ?? null,
                'body_text' => $bodyText ?: null,
                'body_html' => $bodyHtml ?: null,
                'message_id' => $this->cleanHeaderValue($parsed['message_id'] ?? null),
                'in_reply_to' => $this->cleanHeaderValue($parsed['in_reply_to'] ?? null),
                'references' => $parsed['references'] ?? null,
                'headers_json' => $parsed['headers'] ?? [],
                'attachments_json' => $attachments,
                'received_at' => isset($parsed['date']) ? Carbon::parse($parsed['date']) : now(),
                'sent_at' => isset($parsed['date']) ? Carbon::parse($parsed['date']) : null,
            ]);

            $ticket->update([
                'last_message_at' => isset($parsed['date']) ? Carbon::parse($parsed['date']) : now(),
                'last_message_message_id' => $parsed['message_id'] ?? null,
            ]);

            $this->applyInboundStatusTransition($ticket);
        });

        return ['created' => false, 'ticket_id' => $ticket->id];
    }

    private function resolveTicket(array $parsed): GuestTicket
    {
        $ticket = $this->findByInReplyTo($parsed['in_reply_to'] ?? null)
            ?? $this->findByReferences($parsed['references'] ?? null)
            ?? $this->findBySubjectTag($parsed['subject'] ?? null)
            ?? $this->createFromParsed($parsed);

        return $ticket;
    }

    private function findByInReplyTo(?string $inReplyTo): ?GuestTicket
    {
        if (! $inReplyTo) {
            return null;
        }
        $clean = $this->cleanHeaderValue($inReplyTo);
        if (! $clean) {
            return null;
        }
        $message = GuestTicketMessage::where('message_id', $clean)->first();

        return $message?->ticket;
    }

    private function findByReferences(?string $references): ?GuestTicket
    {
        if (! $references) {
            return null;
        }
        $ids = preg_split('/\s+/', trim($references)) ?: [];
        foreach (array_reverse($ids) as $id) {
            $clean = $this->cleanHeaderValue($id);
            if (! $clean) {
                continue;
            }
            $message = GuestTicketMessage::where('message_id', $clean)->first();
            if ($message) {
                return $message->ticket;
            }
        }

        return null;
    }

    private function findBySubjectTag(?string $subject): ?GuestTicket
    {
        if (! $subject || ! preg_match('/\[GuestTicket#(\d+)\]/', $subject, $m)) {
            return null;
        }
        $id = (int) $m[1];

        return GuestTicket::find($id);
    }

    private function createFromParsed(array $parsed): GuestTicket
    {
        $subject = $parsed['subject'] ?? null;
        $body = $parsed['body_text'] ?? '';
        $fromEmail = $parsed['from_email'] ?? null;

        $name = $parsed['from_name'] ?? null;
        if (! $name) {
            $name = $fromEmail ? Str::before($fromEmail, '@') : 'Unknown';
        }

        return GuestTicket::create([
            'name' => $name,
            'email' => $fromEmail,
            'mobile' => '',
            'subject' => $subject,
            'body' => $body,
            'status' => 'pending',
        ]);
    }

    private function storeAttachments(int $ticketId, array $attachments): array
    {
        if (! $attachments) {
            return [];
        }
        $disk = Storage::disk('local');
        $base = "guest-tickets/{$ticketId}";
        $stored = [];
        foreach ($attachments as $att) {
            $raw = (string) ($att['raw'] ?? '');
            if ($raw === '') {
                continue;
            }
            $decoded = $this->decodeAttachment($raw, $att['transfer-encoding'] ?? '7bit');
            $name = (string) ($att['name'] ?? 'attachment-'.uniqid());
            $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', $name);
            $path = $base.'/'.$safeName;
            $disk->put($path, $decoded);
            $stored[] = [
                'name' => $safeName,
                'mime' => $att['mime'] ?? 'application/octet-stream',
                'size' => strlen($decoded),
                'path' => $path,
            ];
        }

        return $stored;
    }

    private function decodeAttachment(string $body, string $encoding): string
    {
        return match (strtolower($encoding)) {
            'base64' => base64_decode($body),
            'quoted-printable' => quoted_printable_decode($body),
            default => $body,
        };
    }

    private function cleanHeaderValue(?string $value): ?string
    {
        if (! $value) {
            return null;
        }
        $v = trim($value, " \t\n\r\0\x08");
        if (str_starts_with($v, '<') && str_ends_with($v, '>')) {
            $v = substr($v, 1, -1);
        }

        return $v !== '' ? $v : null;
    }

    private function applyInboundStatusTransition(GuestTicket $ticket): void
    {
        if ($ticket->status === GuestTicketStatus::Closed->value) {
            $ticket->status = GuestTicketStatus::Replied->value;
        } elseif ($ticket->status === GuestTicketStatus::Pending->value) {
            $ticket->status = GuestTicketStatus::Replied->value;
        }
        $ticket->save();
    }
}
