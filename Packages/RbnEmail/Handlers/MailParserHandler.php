<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnEmail\Handlers;

use Rbn\Framework\Core\Base\BaseComponent;
use Rbn\Framework\Packages\RbnEmail\Support\ImapEnvelope;

/**
 * MailParserHandler - MIME Multipart, HTML Body and Attachment Parser 📜📎
 * 
 * RBN Framework Framework Standards.
 */
class MailParserHandler extends BaseComponent
{
    /**
     * Ham RFC822 formatındaki mail metnini ayrıştırır (HTML, Text, Ekler)
     */
    public function parse(string $rawMessage): array
    {
        $bodyHtml = '';
        $bodyText = '';
        $attachments = [];

        $parts = preg_split('/\r?\n\r?\n/', $rawMessage, 2);
        $headersRaw = $parts[0] ?? '';
        $bodyRaw = $parts[1] ?? '';

        $boundary = null;
        if (preg_match('/Content-Type:\s*multipart\/[^;]+;\s*boundary="?([^";\r\n]+)"?/i', $headersRaw, $m)) {
            $boundary = trim($m[1]);
        }

        if ($boundary) {
            $parsed = $this->parseMultipart($bodyRaw, $boundary);
            $bodyHtml = $parsed['html'];
            $bodyText = $parsed['text'];
            $attachments = $parsed['attachments'];
        } else {
            $isHtml = (bool) preg_match('/Content-Type:\s*text\/html/i', $headersRaw);
            $encoding = '';
            if (preg_match('/Content-Transfer-Encoding:\s*([^\r\n]+)/i', $headersRaw, $m)) {
                $encoding = strtolower(trim($m[1]));
            }

            $decoded = ImapEnvelope::toUtf8($this->decodeContent($bodyRaw, $encoding), $this->charsetOf($headersRaw));
            if ($isHtml) {
                $bodyHtml = $decoded;
                $bodyText = strip_tags($decoded);
            } else {
                $bodyText = $decoded;
                $bodyHtml = nl2br(htmlspecialchars($decoded, ENT_QUOTES, 'UTF-8'));
            }
        }

        $cleanSnippet = preg_replace('/\s+/', ' ', trim($bodyText));
        $snippet = mb_substr($cleanSnippet, 0, 160, 'UTF-8');

        return [
            'body_html' => $bodyHtml,
            'body_text' => $bodyText,
            'preview_snippet' => $snippet,
            'has_attachments' => !empty($attachments) ? 1 : 0,
            'attachments' => $attachments
        ];
    }

    private function parseMultipart(string $content, string $boundary): array
    {
        $html = '';
        $text = '';
        $attachments = [];

        $sections = explode('--' . $boundary, $content);

        foreach ($sections as $section) {
            $section = trim($section);
            if (empty($section) || $section === '--') {
                continue;
            }

            $parts = preg_split('/\r?\n\r?\n/', $section, 2);
            $subHeaders = $parts[0] ?? '';
            $subBody = $parts[1] ?? '';

            if (preg_match('/Content-Type:\s*multipart\/[^;]+;\s*boundary="?([^";\r\n]+)"?/i', $subHeaders, $subM)) {
                $subParsed = $this->parseMultipart($subBody, trim($subM[1]));
                if (!empty($subParsed['html'])) $html = $subParsed['html'];
                if (!empty($subParsed['text'])) $text = $subParsed['text'];
                $attachments = array_merge($attachments, $subParsed['attachments']);
                continue;
            }

            $encoding = '';
            if (preg_match('/Content-Transfer-Encoding:\s*([^\r\n]+)/i', $subHeaders, $m)) {
                $encoding = strtolower(trim($m[1]));
            }

            $filename = null;
            if (preg_match('/filename="?([^";\r\n]+)"?/i', $subHeaders, $m) || 
                preg_match('/name="?([^";\r\n]+)"?/i', $subHeaders, $m)) {
                $filename = trim($m[1]);
            }

            $decoded = $this->decodeContent($subBody, $encoding);

            if (!$filename) {
                $decoded = ImapEnvelope::toUtf8($decoded, $this->charsetOf($subHeaders));
            }

            if ($filename) {
                $attachments[] = [
                    'filename' => ImapEnvelope::decodeHeader($filename),
                    'size' => strlen($decoded),
                    'content_type' => preg_match('/Content-Type:\s*([^;\r\n]+)/i', $subHeaders, $cm) ? trim($cm[1]) : 'application/octet-stream'
                ];
            } else {
                if (preg_match('/Content-Type:\s*text\/html/i', $subHeaders)) {
                    $html = $decoded;
                } elseif (preg_match('/Content-Type:\s*text\/plain/i', $subHeaders)) {
                    $text = $decoded;
                }
            }
        }

        if (empty($html) && !empty($text)) {
            $html = nl2br(htmlspecialchars($text, ENT_QUOTES, 'UTF-8'));
        }

        return [
            'html' => $html,
            'text' => $text,
            'attachments' => $attachments
        ];
    }

    /** Başlık bloğundaki `charset=` değeri (yoksa null). */
    private function charsetOf(string $headers): ?string
    {
        return preg_match('/charset\s*=\s*"?([A-Za-z0-9._:-]+)"?/i', $headers, $m) ? $m[1] : null;
    }

    private function decodeContent(string $data, string $encoding): string
    {
        return match ($encoding) {
            'base64' => base64_decode($data),
            'quoted-printable' => quoted_printable_decode($data),
            default => $data
        };
    }
}
