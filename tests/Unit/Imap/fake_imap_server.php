<?php

/**
 * Minimal scripted IMAP server for PurePhpImapClientTest.
 * Usage: php fake_imap_server.php <transcript-file>   (prints "ready <port>" once listening)
 * Replies like a real server: untagged lines before the tagged OK, and the
 * message body as a literal "{size}\r\n<bytes>". Every received command line
 * is appended to the transcript file so the test can assert on it.
 */
[$script, $transcript] = $argv;

$message = "From: guest@example.test\r\nSubject: Hello\r\n\r\nLine one\r\nA9 OK looks like a tag but is body\r\n";

// Port 0: the OS picks a free port (fixed ranges collide with reserved ports on Windows).
$server = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
if (! $server) {
    fwrite(STDERR, "listen failed: {$errstr}\n");
    exit(1);
}
$address = stream_socket_get_name($server, false);
fwrite(STDOUT, 'ready '.substr($address, strrpos($address, ':') + 1).PHP_EOL);

$conn = stream_socket_accept($server, 10);
if (! $conn) {
    exit(1);
}

fwrite($conn, "* OK [CAPABILITY IMAP4rev1] Fake ready\r\n");

while (($line = fgets($conn)) !== false) {
    file_put_contents($transcript, $line, FILE_APPEND);
    $tag = strtok(trim($line), ' ');
    $upper = strtoupper(trim(substr(trim($line), strlen($tag) + 1)));

    $reply = match (true) {
        str_starts_with($upper, 'LOGIN') => "{$tag} OK [CAPABILITY IMAP4rev1 IDLE] Logged in\r\n",
        str_starts_with($upper, 'CAPABILITY') => "* CAPABILITY IMAP4rev1 IDLE UIDPLUS\r\n{$tag} OK Capability completed\r\n",
        str_starts_with($upper, 'SELECT') => "* 3 EXISTS\r\n* 0 RECENT\r\n* OK [UIDVALIDITY 1] UIDs valid\r\n{$tag} OK [READ-WRITE] Select completed\r\n",
        str_starts_with($upper, 'UID SEARCH') => "* SEARCH 41 42\r\n{$tag} OK Search completed\r\n",
        str_starts_with($upper, 'UID FETCH') => '* 1 FETCH (UID 41 BODY[] {'.strlen($message)."}\r\n".$message.")\r\n{$tag} OK Fetch completed\r\n",
        str_starts_with($upper, 'UID STORE') => "* 1 FETCH (UID 41 FLAGS (\\Seen))\r\n{$tag} OK Store completed\r\n",
        str_starts_with($upper, 'LOGOUT') => "* BYE Logging out\r\n{$tag} OK Logout completed\r\n",
        default => "{$tag} BAD Unknown command\r\n",
    };

    fwrite($conn, $reply);
    if (str_starts_with($upper, 'LOGOUT')) {
        break;
    }
}

fclose($conn);
