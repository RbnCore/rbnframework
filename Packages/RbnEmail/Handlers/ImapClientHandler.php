<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnEmail\Handlers;

use Rbn\Framework\Core\Base\BaseComponent;

/**
 * ImapClientHandler - Native Socket IMAP Protocol Engine 📬⚡
 * 
 * RBN 3.5 Sovereign Framework Standards.
 * PHP IMAP uzantısına bağımlı olmadan saf SSL/TLS Socket üzerinden
 * IMAP4rev1 protokolü ile mailleri ve başlıkları çeker.
 */
class ImapClientHandler extends BaseComponent
{
    private $socket = null;
    private int $tagIndex = 1;

    /**
     * IMAP Sunucusuna Bağlanır ve Giriş Yapar 🔌
     */
    public function connect(string $host, int $port, string $user, string $password, string $ssl = 'ssl'): bool
    {
        $this->disconnect();

        $prefix = ($ssl === 'ssl' || $port === 993) ? 'ssl://' : ($ssl === 'tls' ? 'tls://' : 'tcp://');
        $address = $prefix . $host . ':' . $port;

        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]);

        $this->socket = @stream_socket_client($address, $errno, $errstr, 10, STREAM_CLIENT_CONNECT, $context);
        if (!$this->socket) {
            return false;
        }

        stream_set_timeout($this->socket, 15);
        $this->readResponse(); // Sunucu karşılama mesajını oku

        $tag = $this->nextTag();
        $this->write("{$tag} LOGIN \"" . addcslashes($user, '"\\') . "\" \"" . addcslashes($password, '"\\') . "\"");
        $res = $this->readResponse($tag);

        return str_contains($res, "{$tag} OK");
    }

    /**
     * Mail Kutusunu Seçer 📥
     */
    public function selectFolder(string $folder = 'INBOX'): array
    {
        $tag = $this->nextTag();
        $this->write("{$tag} SELECT \"{$folder}\"");
        $response = $this->readResponse($tag);

        $exists = 0;
        $recent = 0;
        $unseen = 0;

        if (preg_match('/\* (\d+) EXISTS/i', $response, $m)) {
            $exists = (int) $m[1];
        }
        if (preg_match('/\* (\d+) RECENT/i', $response, $m)) {
            $recent = (int) $m[1];
        }
        if (preg_match('/\* OK \[UNSEEN (\d+)\]/i', $response, $m)) {
            $unseen = (int) $m[1];
        }

        return [
            'success' => str_contains($response, "{$tag} OK"),
            'exists' => $exists,
            'recent' => $recent,
            'unseen' => $unseen,
            'raw' => $response
        ];
    }

    /**
     * Son N Adet Mailin Başlıklarını Çeker 📬
     */
    public function fetchRecentHeaders(int $limit = 20): array
    {
        $select = $this->selectFolder('INBOX');
        $total = $select['exists'];
        if ($total <= 0) {
            return [];
        }

        $start = max(1, $total - $limit + 1);
        $range = "{$start}:{$total}";

        $tag = $this->nextTag();
        $this->write("{$tag} FETCH {$range} (UID FLAGS RFC822.SIZE INTERNALDATE BODY.PEEK[HEADER.FIELDS (FROM TO SUBJECT DATE MESSAGE-ID)])");
        $raw = $this->readResponse($tag);

        return $this->parseFetchHeaders($raw);
    }

    /**
     * Belirtilen UID'li Mailin Tam Gövdesini Çeker 📜
     */
    public function fetchMessageBody(string|int $uid): ?string
    {
        $tag = $this->nextTag();
        $this->write("{$tag} UID FETCH {$uid} (BODY.PEEK[])");
        $raw = $this->readResponse($tag);

        if (preg_match('/\{(\d+)\}\r?\n(.*)/s', $raw, $m)) {
            $expectedLength = (int) $m[1];
            return substr($m[2], 0, $expectedLength);
        }

        return null;
    }

    /**
     * Bağlantıyı Kapatır 🚪
     */
    public function disconnect(): void
    {
        if ($this->socket) {
            $tag = $this->nextTag();
            @$this->write("{$tag} LOGOUT");
            @fclose($this->socket);
            $this->socket = null;
        }
    }

    public function __destruct()
    {
        $this->disconnect();
    }

    private function nextTag(): string
    {
        return 'A' . sprintf('%04d', $this->tagIndex++);
    }

    private function write(string $command): void
    {
        if ($this->socket) {
            fwrite($this->socket, $command . "\r\n");
        }
    }

    private function readResponse(?string $targetTag = null): string
    {
        $output = '';
        if (!$this->socket) {
            return $output;
        }

        while (!feof($this->socket)) {
            $line = fgets($this->socket, 4096);
            if ($line === false) {
                break;
            }
            $output .= $line;

            if ($targetTag !== null) {
                if (str_starts_with($line, "{$targetTag} OK") || 
                    str_starts_with($line, "{$targetTag} NO") || 
                    str_starts_with($line, "{$targetTag} BAD")) {
                    break;
                }
            }
        }

        return $output;
    }

    private function parseFetchHeaders(string $raw): array
    {
        $messages = [];
        $blocks = explode('* ', $raw);

        foreach ($blocks as $block) {
            if (empty(trim($block)) || !str_contains($block, 'FETCH')) {
                continue;
            }

            $uid = null;
            if (preg_match('/UID\s+(\d+)/i', $block, $m)) {
                $uid = (int) $m[1];
            }

            $date = date('Y-m-d H:i:s');
            if (preg_match('/INTERNALDATE\s+"([^"]+)"/i', $block, $m)) {
                $date = date('Y-m-d H:i:s', strtotime($m[1]));
            }

            $isRead = str_contains(strtoupper($block), '\SEEN') ? 1 : 0;
            $isStarred = str_contains(strtoupper($block), '\FLAGGED') ? 1 : 0;

            $from = '';
            $fromName = '';
            $to = '';
            $subject = '(Konusuz)';
            $messageId = null;

            if (preg_match('/From:\s*([^\r\n]+)/i', $block, $m)) {
                $rawFrom = trim($m[1]);
                if (preg_match('/(.*)<(.+)>/', $rawFrom, $fm)) {
                    $fromName = trim($this->decodeMimeHeader($fm[1]), " \"'");
                    $from = trim($fm[2]);
                } else {
                    $from = $rawFrom;
                    $fromName = $rawFrom;
                }
            }

            if (preg_match('/To:\s*([^\r\n]+)/i', $block, $m)) {
                $to = trim($m[1]);
            }

            if (preg_match('/Subject:\s*([^\r\n]+)/i', $block, $m)) {
                $subject = $this->decodeMimeHeader(trim($m[1]));
            }

            if (preg_match('/Message-ID:\s*<([^>]+)>/i', $block, $m)) {
                $messageId = trim($m[1]);
            }

            if ($uid !== null && !empty($from)) {
                $messages[] = [
                    'uid' => $uid,
                    'from_email' => $from,
                    'from_name' => $fromName ?: $from,
                    'to_email' => $to,
                    'subject' => $subject,
                    'date' => $date,
                    'is_read' => $isRead,
                    'is_starred' => $isStarred,
                    'message_id_header' => $messageId
                ];
            }
        }

        return array_reverse($messages);
    }

    private function decodeMimeHeader(string $text): string
    {
        if (function_exists('mb_decode_mimeheader')) {
            return mb_decode_mimeheader($text);
        }
        if (function_exists('iconv_mime_decode')) {
            return iconv_mime_decode($text, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8');
        }
        return $text;
    }
}
