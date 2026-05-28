<?php
/**
 * core/Mailer.php
 *
 * Lightweight SMTP mailer. Reads credentials from environment variables.
 * Falls back to PHP mail() when SMTP_HOST is not set.
 *
 * Usage:
 *   $mailer = new Mailer();
 *   $mailer->send('user@example.com', 'Subject', '<p>HTML body</p>');
 *
 * Required .env vars (when using SMTP):
 *   SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASS, SMTP_FROM, SMTP_FROM_NAME
 */
class Mailer
{
    private string $host;
    private int    $port;
    private string $user;
    private string $pass;
    private string $from;
    private string $fromName;
    private bool   $debug;

    public function __construct()
    {
        $this->host     = (string) (getenv('SMTP_HOST') ?: '');
        $this->port     = (int)   (getenv('SMTP_PORT') ?: 587);
        $this->user     = (string) (getenv('SMTP_USER') ?: '');
        $this->pass     = (string) (getenv('SMTP_PASS') ?: '');
        $this->from     = (string) (getenv('SMTP_FROM') ?: $this->user);
        $this->fromName = (string) (getenv('SMTP_FROM_NAME') ?: 'UX Pacific Shop');
        $this->debug    = (getenv('APP_DEBUG') === 'true');
    }

    /**
     * Send an email.
     * @param  string $to      Recipient address.
     * @param  string $subject Email subject.
     * @param  string $html    HTML body.
     * @param  string $text    Plain-text fallback (auto-derived from $html if empty).
     * @return bool
     */
    public function send(string $to, string $subject, string $html, string $text = ''): bool
    {
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            error_log("Mailer: invalid recipient address '{$to}'");
            return false;
        }

        if ($text === '') {
            $text = strip_tags(preg_replace('/<br\s*\/?>/i', "\n", $html) ?? $html);
        }

        if ($this->host !== '' && $this->user !== '' && $this->pass !== '') {
            return $this->sendSmtp($to, $subject, $html, $text);
        }

        return $this->sendNative($to, $subject, $html, $text);
    }

    // ── SMTP send ────────────────────────────────────────────────────────────────

    private function sendSmtp(string $to, string $subject, string $html, string $text): bool
    {
        try {
            $boundary = '=_' . bin2hex(random_bytes(12));
            $msgId    = '<' . bin2hex(random_bytes(12)) . '@uxpacific.com>';
            $date     = date('r');

            $mimeBody = $this->buildMime($html, $text, $boundary);

            $headers  = "Date: {$date}\r\n";
            $headers .= 'From: ' . $this->encodeHeader($this->fromName) . " <{$this->from}>\r\n";
            $headers .= "To: <{$to}>\r\n";
            $headers .= 'Subject: ' . $this->encodeHeader($subject) . "\r\n";
            $headers .= "Message-ID: {$msgId}\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
            $headers .= "X-Mailer: UX-Pacific-Mailer/1.0\r\n";

            $message = $headers . "\r\n" . $mimeBody;

            $sock = $this->smtpConnect();

            $this->expect($sock, '220');
            $this->cmd($sock, 'EHLO ' . $this->safeHostname());
            $greeting = $this->expect($sock, '250');

            // STARTTLS upgrade (ports 587 / 25)
            if ($this->port !== 465 && stripos($greeting, 'STARTTLS') !== false) {
                $this->cmd($sock, 'STARTTLS');
                $this->expect($sock, '220');
                if (!stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new RuntimeException('STARTTLS negotiation failed.');
                }
                $this->cmd($sock, 'EHLO ' . $this->safeHostname());
                $this->expect($sock, '250');
            }

            // AUTH LOGIN
            $this->cmd($sock, 'AUTH LOGIN');
            $this->expect($sock, '334');
            $this->cmd($sock, base64_encode($this->user));
            $this->expect($sock, '334');
            $this->cmd($sock, base64_encode($this->pass));
            $this->expect($sock, '235');

            // Envelope + data
            $this->cmd($sock, "MAIL FROM:<{$this->from}>");
            $this->expect($sock, '250');
            $this->cmd($sock, "RCPT TO:<{$to}>");
            $this->expect($sock, '25'); // matches 250 and 251
            $this->cmd($sock, 'DATA');
            $this->expect($sock, '354');
            fwrite($sock, $message . "\r\n.\r\n");
            $this->expect($sock, '250');
            $this->cmd($sock, 'QUIT');
            fclose($sock);

            return true;

        } catch (Throwable $e) {
            error_log('Mailer SMTP error: ' . $e->getMessage());
            return false;
        }
    }

    /** Open a raw socket to the SMTP server (implicit TLS on 465, plain otherwise). */
    private function smtpConnect()
    {
        $errno = 0; $errstr = '';
        $timeout = 30;

        if ($this->port === 465) {
            $context = stream_context_create(['ssl' => [
                'verify_peer'      => true,
                'verify_peer_name' => true,
            ]]);
            $sock = stream_socket_client(
                "ssl://{$this->host}:{$this->port}", $errno, $errstr, $timeout,
                STREAM_CLIENT_CONNECT, $context
            );
        } else {
            $sock = stream_socket_client(
                "tcp://{$this->host}:{$this->port}", $errno, $errstr, $timeout
            );
        }

        if ($sock === false) {
            throw new RuntimeException("SMTP connect to {$this->host}:{$this->port} failed ({$errno}): {$errstr}");
        }

        stream_set_timeout($sock, $timeout);
        return $sock;
    }

    /** Write a command to the SMTP socket. */
    private function cmd($sock, string $cmd): void
    {
        if ($this->debug) {
            // Mask credentials in debug output
            $safe = preg_match('/^[A-Za-z0-9+\/=]{20,}$/', $cmd) ? '[base64-redacted]' : $cmd;
            error_log("SMTP >> {$safe}");
        }
        fwrite($sock, $cmd . "\r\n");
    }

    /**
     * Read lines from the SMTP socket until the final response line (4th char = space).
     * Returns the full multi-line response as a string.
     * Throws if the first $codeLen characters don't match $code.
     */
    private function expect($sock, string $code): string
    {
        $response = '';
        while ($line = fgets($sock, 512)) {
            if ($this->debug) {
                error_log('SMTP << ' . rtrim($line));
            }
            $response .= $line;
            // RFC 821: continuation lines have '-' at position 3; final line has ' '
            if (strlen($line) >= 4 && $line[3] === ' ') {
                break;
            }
        }
        if (strncmp($response, $code, strlen($code)) !== 0) {
            throw new RuntimeException("SMTP expected {$code}, got: " . rtrim($response));
        }
        return $response;
    }

    // ── PHP mail() fallback ───────────────────────────────────────────────────────

    private function sendNative(string $to, string $subject, string $html, string $text): bool
    {
        $boundary = '=_' . bin2hex(random_bytes(12));
        $headers  = 'From: ' . $this->encodeHeader($this->fromName) . " <{$this->from}>\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
        $headers .= "X-Mailer: UX-Pacific-Mailer/1.0\r\n";

        $body   = $this->buildMime($html, $text, $boundary);
        $result = mail($to, $this->encodeHeader($subject), $body, $headers);

        if (!$result) {
            error_log("Mailer: mail() returned false for {$to}");
        }
        return (bool) $result;
    }

    // ── MIME helpers ──────────────────────────────────────────────────────────────

    private function buildMime(string $html, string $text, string $boundary): string
    {
        $body  = "--{$boundary}\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
        $body .= quoted_printable_encode($text) . "\r\n\r\n";
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
        $body .= quoted_printable_encode($html) . "\r\n\r\n";
        $body .= "--{$boundary}--\r\n";
        return $body;
    }

    /** RFC 2047 encode a header value that contains non-ASCII characters. */
    private function encodeHeader(string $str): string
    {
        if (preg_match('/[^\x20-\x7E]/', $str)) {
            return '=?UTF-8?B?' . base64_encode($str) . '?=';
        }
        return $str;
    }

    /** Safe hostname for EHLO — falls back to 'localhost' if gethostname() fails. */
    private function safeHostname(): string
    {
        $h = gethostname();
        return ($h !== false && $h !== '') ? $h : 'localhost';
    }
}
