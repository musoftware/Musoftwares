<?php

namespace App\Services\Imap;

class ImapMessageParser
{
    /**
     * @return array{
     *     message_id: ?string,
     *     in_reply_to: ?string,
     *     references: ?string,
     *     subject: ?string,
     *     from_email: ?string,
     *     from_name: ?string,
     *     to_email: ?string,
     *     date: ?string,
     *     headers: array<string, string>,
     *     body_text: ?string,
     *     body_html: ?string,
     *     attachments: array<int, array{name: string, mime: ?string, size: int, path: ?string}>,
     * }
     */
    public function parse(string $raw): array
    {
        $raw = $this->normalizeHeaders($raw);

        [$headerBlock, $bodyBlock] = $this->splitHeaderBody($raw);
        $parsedHeaders = $this->parseHeaders($headerBlock);

        $boundary = $parsedHeaders['content-type-boundary'] ?? null;
        $parts = $boundary ? $this->splitMultipart($bodyBlock, $boundary) : [['headers' => '', 'body' => $bodyBlock, 'is_body' => true]];

        $bodyText = null;
        $bodyHtml = null;
        $attachments = [];

        if (! $boundary || count($parts) === 1) {
            $ct = strtolower($parsedHeaders['content-type'] ?? 'text/plain');
            if (str_contains($ct, 'text/html')) {
                $bodyHtml = $this->decodeBody($bodyBlock, $parsedHeaders);
            } else {
                $bodyText = $this->decodeBody($bodyBlock, $parsedHeaders);
            }
        } else {
            foreach ($parts as $part) {
                $ph = $this->parseHeaders($part['headers']);
                $pct = strtolower($ph['content-type'] ?? 'text/plain');
                if (str_contains($pct, 'multipart')) {
                    continue;
                }
                if (str_starts_with($pct, 'text/html')) {
                    $bodyHtml = $this->decodeBody($part['body'], $ph);
                } elseif (str_starts_with($pct, 'text/plain')) {
                    $bodyText = $this->decodeBody($part['body'], $ph);
                } else {
                    $attachments[] = [
                        'name' => $ph['content-disposition-filename'] ?? 'attachment-'.count($attachments),
                        'mime' => $pct,
                        'size' => strlen($part['body']),
                        'raw' => $part['body'],
                        'transfer-encoding' => $ph['content-transfer-encoding'] ?? '7bit',
                    ];
                }
            }
        }

        return [
            'message_id' => $parsedHeaders['message-id'] ?? null,
            'in_reply_to' => $parsedHeaders['in-reply-to'] ?? null,
            'references' => $parsedHeaders['references'] ?? null,
            'subject' => $parsedHeaders['subject'] ?? null,
            'from_email' => $parsedHeaders['from-email'] ?? null,
            'from_name' => $parsedHeaders['from-name'] ?? null,
            'to_email' => $parsedHeaders['to-email'] ?? null,
            'date' => $parsedHeaders['date'] ?? null,
            'headers' => $parsedHeaders,
            'body_text' => $bodyText,
            'body_html' => $bodyHtml,
            'attachments' => $attachments,
        ];
    }

    private function normalizeHeaders(string $raw): string
    {
        return preg_replace("/\r\n[ \t]+/", ' ', $raw) ?? $raw;
    }

    private function splitHeaderBody(string $raw): array
    {
        $pos = strpos($raw, "\r\n\r\n");
        if ($pos === false) {
            $pos = strpos($raw, "\n\n");
            if ($pos === false) {
                return [$raw, ''];
            }

            return [substr($raw, 0, $pos + 1), substr($raw, $pos + 2)];
        }

        return [substr($raw, 0, $pos + 1), substr($raw, $pos + 4)];
    }

    private function parseHeaders(string $block): array
    {
        $lines = preg_split("/\r\n|\n/", trim($block));
        $out = [];
        $currentKey = null;
        foreach ($lines as $line) {
            if (preg_match('/^([A-Za-z\-]+):\s?(.*)$/', $line, $m)) {
                $currentKey = strtolower($m[1]);
                $out[$currentKey] = trim($m[2]);
            } elseif ($currentKey !== null && preg_match('/^\s+(.*)$/', $line, $m)) {
                $out[$currentKey] .= ' '.trim($m[1]);
            }
        }

        $out['from-email'] = $this->extractAddress($out['from'] ?? '');
        $out['from-name'] = $this->extractName($out['from'] ?? '');
        $out['to-email'] = $this->extractAddress($out['to'] ?? '');
        if (! empty($out['content-type']) && preg_match('/boundary="?([^";]+)"?/i', $out['content-type'], $m)) {
            $out['content-type-boundary'] = $m[1];
        }
        if (! empty($out['content-disposition']) && preg_match('/filename="?([^";]+)"?/i', $out['content-disposition'], $m)) {
            $out['content-disposition-filename'] = $m[1];
        }

        return $out;
    }

    private function extractAddress(string $field): ?string
    {
        if (preg_match('/<([^>]+)>/', $field, $m)) {
            return strtolower(trim($m[1]));
        }
        $field = trim($field);
        if ($field === '') {
            return null;
        }

        return strtolower($field);
    }

    private function extractName(string $field): ?string
    {
        if (preg_match('/^"?([^"<]+?)"?\s*<[^>]+>/', $field, $m)) {
            return trim($m[1]);
        }

        return null;
    }

    private function splitMultipart(string $body, string $boundary): array
    {
        $delim = '--'.$boundary;
        $parts = explode($delim, $body);
        $result = [];
        foreach (array_slice($parts, 1) as $part) {
            if ($part === '' || trim($part) === '--') {
                continue;
            }
            $part = ltrim($part, "\r\n");
            $result[] = ['headers' => $part, 'body' => '', 'is_body' => false];
        }

        $final = [];
        foreach ($result as &$piece) {
            [$h, $b] = $this->splitHeaderBody($piece['headers']);
            $piece['headers'] = $h;
            $piece['body'] = $b;
            $final[] = $piece;
        }

        return $final;
    }

    private function decodeBody(string $body, array $headers): string
    {
        $enc = strtolower($headers['content-transfer-encoding'] ?? '7bit');
        $charset = $headers['charset'] ?? 'utf-8';

        switch ($enc) {
            case 'quoted-printable':
                $body = quoted_printable_decode($body);
                break;
            case 'base64':
                $body = base64_decode($body);
                break;
        }

        if (strtolower($charset) !== 'utf-8' && function_exists('mb_convert_encoding')) {
            $body = mb_convert_encoding($body, 'UTF-8', $charset);
        }

        return $body;
    }
}
