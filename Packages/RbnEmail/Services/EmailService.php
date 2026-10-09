<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnEmail\Services;

use Rbn\Framework\Core\Base\Services\BaseService;

/**
 * EmailService - The Orchestra Chef 👨‍🍳🎻📧
 * 
 * RBN Framework: Standard (Orchestra Pattern).
 * Primary entry point for all email operations.
 */
class EmailService extends BaseService
{
    /**
     * Send an email based on a project template (View) 📨
     */
    public function send(string $to, string $subject, string $view, array $data = [], ?string $toName = null, bool $useMaster = false): array
    {
        try {
            // 🛡️ [ GUARD ] - Outbound identity & Reputation check ⚔️
            $guard = $this->handler('emailGuard')->check($to);
            if (!$guard['success']) return $guard;

            // 1. Render the template autonomously (Worker: Render)
            $html = $this->handler('emailRender')->view($view, $data, $subject);

            // 2. Dispatch via SMTP (Worker: Transport)
            return $this->handler('emailTransport')->send([
                'to'         => $to,
                'to_name'    => $toName,
                'subject'    => $subject,
                'body'       => $html,
                'use_master' => $useMaster
            ]);

        } catch (\Exception $e) {
            return $this->sendError('EmailService Error: ' . $e->getMessage());
        }
    }

    /**
     * Send direct HTML content wrapped in the premium layout 🎨
     */
    public function direct(string $to, string $subject, string $body, ?string $toName = null, bool $useMaster = false): array
    {
        try {
            // 🛡️ [ GUARD ] - Outbound identity & Reputation check ⚔️
            $guard = $this->handler('emailGuard')->check($to);
            if (!$guard['success']) return $guard;

            // 1. Wrap raw content (Worker: Render)
            $html = $this->handler('emailRender')->render($body, [], $subject);

            // 2. Dispatch via SMTP (Worker: Transport)
            return $this->handler('emailTransport')->send([
                'to'         => $to,
                'to_name'    => $toName,
                'subject'    => $subject,
                'body'       => $html,
                'use_master' => $useMaster
            ]);

        } catch (\Exception $e) {
            return $this->sendError('EmailService Direct Error: ' . $e->getMessage());
        }
    }

    /**
     * Queue an email for background dispatch 📨⚡
     */
    public function queueEmail(string $to, string $subject, string $view, array $data = [], ?string $toName = null): array
    {
        try {
            // 🛡️ [ GUARD ] - Outbound identity & Reputation check ⚔️
            $guard = $this->handler('emailGuard')->check($to);
            if (!$guard['success']) return $guard;

            // 🎼 Dispatch to the modernized Cron/Queue Engine 🛰️⚓
            $dispatched = $this->service('cron')->dispatch(\Rbn\Framework\Packages\RbnEmail\Tasks\EmailQueueTask::class, [
                'to'      => $to,
                'to_name' => $toName,
                'subject' => $subject,
                'view'    => $view,
                'data'    => $data
            ]);

            if ($dispatched) {
                return $this->sendSuccess("Email successfully queued for background dispatch.");
            }

            return $this->sendError("Failed to add email to the dispatch queue.");

        } catch (\Exception $e) {
            return $this->sendError('EmailService Queue Error: ' . $e->getMessage());
        }
    }

    /**
     * Check if email system is globally enabled 📡
     */
    public function isEnabled(): bool
    {
        return $this->handler('emailConfig')->resolve()['enabled'] ?? false;
    }
}
