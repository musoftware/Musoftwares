<?php

namespace Tests\Unit\Imap;

use App\Services\Imap\PurePhpImapClient;
use PHPUnit\Framework\TestCase;

/**
 * Talks to a real (scripted) IMAP server process over TCP — no mocks.
 * Regression for: every command waiting for a second reply ("No response from
 * server"), OK lines after untagged lines not being recognised, and body parsing
 * swallowing the server's closing lines.
 */
class PurePhpImapClientTest extends TestCase
{
    /** @var resource|null */
    private $process = null;

    /** @var array<int, resource> kept open for the whole test; closing them early can end the child process */
    private array $pipes = [];

    private string $transcript;

    private int $port;

    protected function setUp(): void
    {
        $this->transcript = tempnam(sys_get_temp_dir(), 'imap');
        $this->process = proc_open(
            [PHP_BINARY, __DIR__.'/fake_imap_server.php', $this->transcript],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $this->pipes
        );
        $ready = trim((string) fgets($this->pipes[1]));
        $this->assertMatchesRegularExpression('/^ready \d+$/', $ready, 'fake IMAP server did not start');
        $this->port = (int) substr($ready, 6);
    }

    protected function tearDown(): void
    {
        if (is_resource($this->process)) {
            proc_terminate($this->process);
            proc_close($this->process);
        }
        @unlink($this->transcript);
    }

    private function client(): PurePhpImapClient
    {
        return new PurePhpImapClient([
            'host' => '127.0.0.1',
            'port' => $this->port,
            'encryption' => 'none',
            'connection_timeout' => 3,
        ]);
    }

    public function test_full_session_logs_in_searches_fetches_and_marks_seen(): void
    {
        $client = $this->client();

        $started = microtime(true);
        $client->connect('inbox@example.test', 'secret');
        $client->select('INBOX');
        $uids = $client->unseenUidsSince(0);
        $body = $client->fetchRawByUid(41);
        $client->markSeen(41);
        $client->disconnect();

        // Before the fix each command waited for the 3s timeout and then failed.
        $this->assertLessThan(2.0, microtime(true) - $started);
        $this->assertSame([41, 42], array_map('intval', $uids));
        $this->assertStringContainsString('Subject: Hello', $body);
        $this->assertStringEndsWith("A9 OK looks like a tag but is body\r\n", $body);
        $this->assertStringNotContainsString('Fetch completed', $body);

        $sent = file_get_contents($this->transcript);
        $this->assertStringContainsString('BODY.PEEK[]', $sent, 'fetch must not mark the message as read');
        $this->assertStringContainsString('UID STORE 41 +FLAGS (\\Seen)', $sent);
    }
}
