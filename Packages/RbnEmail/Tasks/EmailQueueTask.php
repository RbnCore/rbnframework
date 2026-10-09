<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnEmail\Tasks;

use Rbn\Framework\Core\Services\Console\Base\AbstractCronTask;

/**
 * EmailQueueTask - Background Email Dispatcher 📨⚙️🎻
 * 
 * RBN Framework: Now an otonomous task worker.
 */
class EmailQueueTask extends AbstractCronTask
{
    /**
     * Run the background email dispatch. 🧬
     */
    public function run(array $params = []): bool
    {
        $to      = $params['to'] ?? '';
        $subject = $params['subject'] ?? '';
        $view    = $params['view'] ?? '';
        $data    = $params['data'] ?? [];
        $toName  = $params['to_name'] ?? '';

        if (empty($to) || empty($subject) || empty($view)) {
            $this->setMessage("Error: Missing required email parameters (To/Subject/View).");
            return false;
        }

        try {
            // 🎷 Otonom Discovery: Direct Service Access 🎻⚓
            $result = $this->service('email')->send($to, $subject, $view, $data, $toName);

            if ($result['status'] === 'success') {
                $this->setMessage("Email successfully dispatched to: {$to}");
                return true;
            }

            $this->setMessage($result['message'] ?? "Email dispatch failed at Transport level.");
            return false;

        } catch (\Throwable $e) {
            $this->setMessage("EmailQueueTask Exception: " . $e->getMessage());
            return false;
        }
    }
}
