<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnEmail\Handlers;

use Rbn\Framework\Core\Base\BaseComponent;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use Rbn\Framework\Packages\RbnEmail\Models\EmailConstant;

/**
 * EmailTransportHandler - The Dispatcher (Worker) 🚚🎻📧
 * 
 * RBN 3.5: Masterpiece Standard (BaseComponent Actor).
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
            
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer'       => false,
                    'verify_peer_name'  => false,
                    'allow_self_signed' => true
                ]
            ];

            $mail->Timeout    = EmailConstant::TIMEOUT;
            $mail->CharSet    = EmailConstant::CHARSET;

            // 3. Debug Integration
            if (EmailConstant::DEBUG) {
                $mail->SMTPDebug = SMTP::DEBUG_SERVER;
                $mail->Debugoutput = function ($str) {
                    $this->storage->logs()->channel(EmailConstant::DEBUG_LOG_FILE)->debug("PHPMailer: " . trim($str));
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
