<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnEmail\Handlers;

use Rbn\Framework\Core\Base\BaseComponent;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use Rbn\Framework\Packages\RbnEmail\Models\EmailConstant;
use Rbn\Framework\Packages\RbnEmail\Models\ImapConstant;
use Rbn\Framework\Packages\RbnEmail\Support\AccountMailer;
use Rbn\Framework\Packages\RbnEmail\Support\ImapConnection;
use Rbn\Framework\Core\Support\Exceptions\MailTransportException;

/**
 * EmailTransportHandler - The Dispatcher (Worker) 🚚🎻📧
 * 
 * RBN Framework: Standard (BaseComponent Actor).
 * Atomic PHPMailer wrapper for SMTP operations.
 */
class EmailTransportHandler extends BaseComponent
{
    /**
     * Physical dispatch of the email via SMTP 🛰️
     */
    public function send(array $data): array
    {
        try {
            // 1. Resolve Transport DNA
            $useMaster = !empty($data['use_master']);
            $config = $this->handler('emailConfig')->resolve($useMaster);
            
            if (!$config['enabled']) {
                return $this->sendError('Email delivery is globally disabled.');
            }

            $mail = new PHPMailer(true);

            // 2. SMTP Engine Configuration
            $mail->isSMTP();
            $mail->Host       = $config['host'];
            $mail->SMTPAuth   = EmailConstant::SMTP_AUTH;
            $mail->Username   = $config['username'];
            $mail->Password   = $config['password'];
            $mail->SMTPSecure = ($config['port'] === 465) ? PHPMailer::ENCRYPTION_SMTPS : (($config['port'] === 587) ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS);
            $mail->Port       = $config['port'];
            
            // Sistem/cron bildirimleri de hesap gönderimiyle aynı TLS kuralına tabidir:
            // sertifika ve ana makine adı doğrulanır (kimlik bilgisi bu kanaldan gider).
            $mail->SMTPOptions = ['ssl' => ImapConnection::tlsOptions((string) $config['host'])];

            $mail->Timeout    = EmailConstant::TIMEOUT;
            $mail->CharSet    = EmailConstant::CHARSET;

            // 3. Debug Integration
            // Yalnız sunucu yanıtları loga gider; istemci satırları (AUTH + base64 kimlik) süzülür.
            if (EmailConstant::DEBUG) {
                $mail->SMTPDebug = SMTP::DEBUG_SERVER;
                $mail->Debugoutput = function ($str) {
                    if (self::isServerDebugLine((string) $str)) {
                        $this->storage->logs()->channel(EmailConstant::DEBUG_LOG_FILE)->debug("PHPMailer: " . trim((string) $str));
                    }
                };
            }

            // 4. Sender & Recipient
            $mail->setFrom($config['from_address'], $config['from_name']);
            $mail->addAddress($data['to'], $data['to_name'] ?? '');

            // 5. Content Structure
            $mail->isHTML(true);
            $mail->Subject = $data['subject'];
            $mail->Body    = $data['body'];
            $mail->AltBody = strip_tags($data['body']);

            // 6. Action
            $sent = $mail->send();

            if ($sent) {
                $this->logSuccess($data['to'], $data['subject']);
                return $this->sendSuccess('Email successfully sent.', ['to' => $data['to']]);
            }

            return $this->sendError('Failed to dispatch email.');

        } catch (Exception $e) {
            $this->logFailure($data['to'], $e->getMessage());
            return $this->sendError('Email Transport Error: ' . $e->getMessage());
        }
    }

    /**
     * Bir posta hesabının KENDİ kimliğiyle gönderir (çoklu hesap posta paneli için).
     *
     * `send()`ten farkı: SMTP ayarı proje ayarından değil `$account`'tan gelir ve
     * sertifika/ana makine adı doğrulaması AÇIKTIR. Başarıda gönderilen ham ileti
     * döner; çağıran onu IMAP "Sent" klasörüne ekleyebilir.
     *
     * @param array $account host, port, security (ssl|starttls), username, password, from_email, from_name?, ca_file?
     * @param array $message to[], cc[], bcc[] (her biri {email, name}), subject, html?, text?, in_reply_to?, references?,
     *                       attachments? [{path, filename, content_type}] — base64, ad RFC 2231 (`AccountMailer`).
     *                       Dosya doğrulaması (boyut/tür) ÇAĞIRANIN işidir; burada yalnız okunur.
     * @return array{message_id:string, raw:string}
     * @throws MailTransportException AUTH_FAILED | TLS_VERIFY_FAILED | SMTP_FAILED
     */
    public function sendAs(array $account, array $message): array
    {
        $mail = $this->accountMailer($account, $debug);
        try {
            $mail->setFrom((string) $account['from_email'], (string) ($account['from_name'] ?? ''), false);
            foreach (['to' => 'addAddress', 'cc' => 'addCC', 'bcc' => 'addBCC'] as $field => $method) {
                foreach ((array) ($message[$field] ?? []) as $rcpt) {
                    $mail->{$method}((string) $rcpt['email'], (string) ($rcpt['name'] ?? ''));
                }
            }
            $mail->Subject = (string) ($message['subject'] ?? '');
            $html = (string) ($message['html'] ?? '');
            $text = (string) ($message['text'] ?? '');
            if ($html !== '') {
                $mail->isHTML(true);
                $mail->Body = $html;
                $mail->AltBody = $text !== '' ? $text : self::htmlToText($html);
            } else {
                $mail->isHTML(false);
                $mail->Body = $text;
            }
            if (!empty($message['in_reply_to'])) {
                $mail->addCustomHeader('In-Reply-To', (string) $message['in_reply_to']);
            }
            if (!empty($message['references'])) {
                $mail->addCustomHeader('References', (string) $message['references']);
            }
            foreach ((array) ($message['attachments'] ?? []) as $file) {
                $mail->addAttachment(
                    (string) $file['path'],
                    (string) ($file['filename'] ?? ''),
                    PHPMailer::ENCODING_BASE64,
                    (string) ($file['content_type'] ?? 'application/octet-stream')
                );
            }

            $mail->send();
            return ['message_id' => $mail->getLastMessageID(), 'raw' => $mail->getSentMIMEMessage()];
        } catch (Exception $e) {
            throw $this->classifySmtpFailure($mail, $debug, (string) ($account['host'] ?? ''));
        } finally {
            $mail->smtpClose();
        }
    }

    /**
     * Yalnız bağlanıp kimlik doğrular, ileti göndermez (hesap ekleme sınaması).
     *
     * @throws MailTransportException AUTH_FAILED | TLS_VERIFY_FAILED | SMTP_FAILED
     */
    public function testSmtp(array $account): void
    {
        $mail = $this->accountMailer($account, $debug);
        try {
            if (!$mail->smtpConnect()) {
                throw $this->classifySmtpFailure($mail, $debug, (string) ($account['host'] ?? ''));
            }
        } catch (Exception $e) {
            throw $this->classifySmtpFailure($mail, $debug, (string) ($account['host'] ?? ''));
        } finally {
            $mail->smtpClose();
        }
    }

    /**
     * Hesap kimliğiyle kurulmuş PHPMailer. Hata ayıklama çıktısı YALNIZ bellekte
     * tutulur (sınıflandırma için) — AUTH satırı parolayı taşıdığı için loga yazılmaz.
     */
    private function accountMailer(array $account, ?array &$debug): PHPMailer
    {
        $debug = [];
        $host = (string) ($account['host'] ?? '');
        $security = (string) ($account['security'] ?? ImapConstant::SECURITY_SSL);

        $mail = new AccountMailer(true);
        $mail->isSMTP();
        $mail->Host = $host;
        $mail->Port = (int) ($account['port'] ?? 465);
        $mail->SMTPAuth = true;
        $mail->Username = (string) ($account['username'] ?? '');
        $mail->Password = (string) ($account['password'] ?? '');
        $mail->SMTPSecure = $security === ImapConstant::SECURITY_STARTTLS ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
        $mail->SMTPAutoTLS = false;
        $mail->SMTPOptions = ['ssl' => ImapConnection::tlsOptions($host, $account['ca_file'] ?? null)];
        $mail->Timeout = (int) ($account['timeout'] ?? ImapConstant::TIMEOUT);
        $mail->CharSet = EmailConstant::CHARSET;
        $mail->Encoding = PHPMailer::ENCODING_QUOTED_PRINTABLE;
        $fromDomain = substr(strrchr((string) ($account['from_email'] ?? ''), '@') ?: '', 1);
        if ($fromDomain !== '') {
            $mail->Hostname = $fromDomain;
        }
        $mail->SMTPDebug = SMTP::DEBUG_CONNECTION;
        // Yalnız sunucu yanıtları ve bağlantı hataları tutulur; istemci satırları
        // (AUTH + base64 kimlik) belleğe bile alınmaz.
        $mail->Debugoutput = static function (string $line) use (&$debug): void {
            if (self::isServerDebugLine($line)) {
                $debug[] = $line;
            }
        };
        return $mail;
    }

    /** PHPMailer hata ayıklama satırı sunucu yanıtı ya da bağlantı hatası mı (istemci satırı değil)? */
    public static function isServerDebugLine(string $line): bool
    {
        return (bool) preg_match('/^(SERVER -> CLIENT|SMTP ERROR|Connection failed|SMTP Error)/i', ltrim($line));
    }

    /** HTML gövdeden düz metin alternatifi: blok etiketleri satır sonu olur, paragraflar birleşmez. */
    private static function htmlToText(string $html): string
    {
        $html = (string) preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', '', $html);
        $html = (string) preg_replace('#<br\s*/?>|</(p|div|h[1-6]|li|tr|blockquote|pre|table)>#i', "\n", $html);
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return trim((string) preg_replace("/\n{3,}/", "\n\n", (string) preg_replace('/[ \t]+\n/', "\n", $text)));
    }

    private function classifySmtpFailure(PHPMailer $mail, array $debug, string $host): MailTransportException
    {
        $text = $mail->ErrorInfo . ' ' . implode(' ', $debug);
        if (preg_match('/certificate|verify failed|did not match expected|SSL routines/i', $text)) {
            return MailTransportException::tls($host);
        }
        if (preg_match('/SERVER -> CLIENT: 53[45]|Could not authenticate|Username and Password not accepted/i', $text)) {
            return MailTransportException::auth();
        }
        if (preg_match('/getaddrinfo|Unable to connect|Failed to connect|timed out|Connection refused/i', $text)) {
            return MailTransportException::smtp('sunucuya ulaşılamadı');
        }
        if (preg_match('/^SMTP Error: (?:The following recipients failed|data not accepted)/im', $mail->ErrorInfo)) {
            return MailTransportException::smtp('alıcı reddedildi');
        }
        return MailTransportException::smtp('sunucu bağlantısı');
    }

    private function logSuccess(string $to, string $subject): void
    {
        if (EmailConstant::LOG_ENABLED) {
            $this->storage->logs()->channel(EmailConstant::LOG_FILE)->info("Email sent to: {$to} | Subject: {$subject}");
        }
    }

    private function logFailure(string $to, string $error): void
    {
        $this->storage->logs()->channel(EmailConstant::LOG_FILE)->error("Email failed to: {$to} | Error: {$error}");
    }
}
