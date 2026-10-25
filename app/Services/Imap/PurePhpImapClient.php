<?php

namespace App\Services\Imap;

use RuntimeException;

class PurePhpImapClient
{
    private $socket = null;

    private string $host;

    private int $port;

    private string $encryption;

    private bool $validateCert;

    private int $timeout;

    private string $tag = 'A1';

    public function __construct(array $config)
    {
        $this->host = (string) $config['host'];
        $this->port = (int) $config['port'];
        $this->encryption = strtolower((string) ($config['encryption'] ?? 'ssl'));
        $this->validateCert = (bool) ($config['validate_cert'] ?? true);
        $this->timeout = (int) ($config['connection_timeout'] ?? 15);
    }

    public function connect(string $username, string $password): void
    {
        $scheme = $this->encryption === 'ssl' ? 'ssl' : 'tcp';
        $remote = ($scheme === 'ssl' ? 'ssl://' : '').$this->host.':'.$this->port;

        $errno = 0;
        $errstr = '';
        $ctx = $this->encryption === 'tls'
            ? stream_context_create(['ssl' => ['verify_peer' => $this->validateCert, 'verify_peer_name' => $this->validateCert]])
            : null;

        $this->socket = @stream_socket_client(
            $remote,
            $errno,
            $errstr,
            $this->timeout,
            STREAM_CLIENT_CONNECT,
            $ctx
        );

        if (! $this->socket) {
            throw new RuntimeException("IMAP connect failed: {$errstr} ({$errno}) to {$this->host}:{$this->port}");
        }
        stream_set_timeout($this->socket, $this->timeout);

        if ($this->encryption === 'tls') {
            if (! stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT)) {
                $this->disconnect();
                throw new RuntimeException('IMAP STARTTLS failed');
            }
        }

        $this->readGreeting();

        // command() already consumes the reply up to the tagged line; check that reply
        // instead of reading again (a second read waits for a reply that never comes).
        $this->expectOk($this->command('LOGIN "%s" "%s"', $username, $password));
        $this->expectOk($this->command('CAPABILITY'));
    }

    public function select(string $folder): void
    {
        $this->expectOk($this->command('SELECT "%s"', $folder));
    }

    /**
     * Search unseen messages received after a given unix timestamp.
     * Returns an array of IMAP UIDs.
     */
    public function unseenUidsSince(int $sinceUnix): array
    {
        $criteria = sprintf('UNSEEN UNANSWERED');
        if ($sinceUnix > 0) {
            $since = date('d-M-Y', $sinceUnix);
            $criteria = sprintf('UNSEEN UNANSWERED SINCE %s', $since);
        }

        $line = $this->command('UID SEARCH %s', $criteria);
        $this->expectOk($line);

        return $this->parseSearchResponse($line);
    }

    /**
     * Fetch raw RFC822 message by UID.
     */
    public function fetchRawByUid(int $uid): string
    {
        // BODY.PEEK[] leaves the message unread; markSeen() is called only after it was processed.
        $line = $this->command('UID FETCH %d BODY.PEEK[]', $uid);
        $this->expectOk($line);

        return $this->parseFetchedBody($line);
    }

    /**
     * Mark a UID as \Seen (so we don't re-pull).
     */
    public function markSeen(int $uid): void
    {
        $this->expectOk($this->command('UID STORE %d +FLAGS (\\Seen)', $uid));
    }

    public function disconnect(): void
    {
        if ($this->socket) {
            @fwrite($this->socket, "{$this->tag} LOGOUT\r\n");
            @fclose($this->socket);
            $this->socket = null;
        }
    }

    public function __destruct()
    {
        $this->disconnect();
    }

    private function command(string $cmd, string ...$args): string
    {
        $this->tag = $this->nextTag();
        $formatted = vsprintf($cmd, $this->quoteArgs($args));

        $payload = "{$this->tag} {$formatted}\r\n";

        if (@fwrite($this->socket, $payload) === false) {
            throw new RuntimeException("IMAP write failed for: {$cmd}");
        }

        return $this->readUntilTagged();
    }

    private function readGreeting(): void
    {
        $line = fgets($this->socket);
        if (! $line || ! str_starts_with($line, '* OK')) {
            throw new RuntimeException('IMAP greeting failed: '.trim((string) $line));
        }
    }

    private function expectOk(?string $response = null): void
    {
        $r = $response ?? $this->readUntilTagged();
        // Untagged lines ("* CAPABILITY ...") come first, so match the tagged line anywhere.
        if (! preg_match('/^'.preg_quote($this->tag, '/').' OK/im', $r)) {
            $cleanResp = trim((string) $r);
            throw new RuntimeException('IMAP command failed: '.($cleanResp !== '' ? $cleanResp : 'No response from server'));
        }
    }

    private function nextTag(): string
    {
        static $n = 0;
        $n++;

        return 'A'.(string) $n;
    }

    private function quoteArgs(array $args): array
    {
        return array_map(static function ($a) {
            return str_replace('"', '\\"', $a);
        }, $args);
    }

    private function readUntilTagged(): string
    {
        $buffer = '';
        while (! feof($this->socket)) {
            $line = fgets($this->socket);
            if ($line === false) {
                break;
            }
            $buffer .= $line;
            $trim = trim($line);
            if (str_starts_with($trim, $this->tag.' ')) {
                return $buffer;
            }
            if (preg_match('/^\* BYE /i', $line)) {
                throw new RuntimeException('IMAP server closed: '.trim($line));
            }
        }

        return $buffer;
    }

    private function parseSearchResponse(string $buffer): array
    {
        $uids = [];
        foreach (preg_split("/\r\n|\n|\r/", $buffer) as $line) {
            $line = trim((string) $line);
            if (preg_match('/^\* SEARCH (.*)$/', $line, $m)) {
                foreach (preg_split('/\s+/', trim($m[1])) as $tok) {
                    if (ctype_digit((string) $tok)) {
                        $uids[] = (int) $tok;
                    }
                }
            }
        }

        return $uids;
    }

    /**
     * The message is an IMAP literal: "{size}
" followed by exactly size bytes.
     */
    private function parseFetchedBody(string $buffer): string
    {
        if (! preg_match('/\{(\d+)\}\r\n/', $buffer, $m, PREG_OFFSET_CAPTURE)) {
            return '';
        }

        $start = $m[0][1] + strlen($m[0][0]);

        return substr($buffer, $start, (int) $m[1][0]);
    }
}
