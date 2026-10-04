<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Services\Hosting\Handlers;

use Rbn\Framework\Core\Base\BaseComponent;
use Rbn\Framework\Core\Services\Hosting\Data\CpanelData;

/**
 * CPanelMailHandler - Specialized Bridge for Email Management 🏰📧
 * 
 * RBN 3.5 Masterpiece Standard.
 * @property \Rbn\Framework\Core\Services\Hosting\Providers\CPanelProvider $CPanelProvider
 */
class CPanelMailHandler extends BaseComponent
{
    /**
     * List all mailbox accounts 📋
     */
    public function list(string $domain): array
    {
        // 🎼 RBN 3.5: Accessing the provider directly via autonomous discovery 🏗️⚓
        $result = $this->CPanelProvider->call('Email', 'list_pops_with_disk', [
            'domain' => $domain
        ]);

        return $result['data'] ?? [];
    }

    /**
     * Create a new mailbox 📨
     */
    public function create(string $email, string $password, string $domain, int $quota = 0): array
    {
        return $this->CPanelProvider->call('Email', 'add_pop', [
            'email'    => $email,
            'password' => $password,
            'domain'   => $domain,
            'quota'    => $quota ?: CpanelData::DEFAULT_QUOTA
        ]);
    }

    /**
     * Delete a mailbox 🗑️
     */
    public function delete(string $email, string $domain): array
    {
        return $this->CPanelProvider->call('Email', 'delete_pop', [
            'email'  => $email,
            'domain' => $domain
        ]);
    }

    /**
     * Create a Webmail session 🔗⚡
     */
    public function getWebmailSession(string $email): array
    {
        $email = (string) $email;
        if (!str_contains($email, '@')) {
            return [
                'success' => false,
                'message' => 'Geçersiz e-posta adresi.'
            ];
        }
        list($login, $domain) = explode('@', $email);

        $result = $this->CPanelProvider->call('Session', 'create_webmail_session_for_mail_user', [
            'login'  => $login,
            'domain' => $domain
        ]);

        if (isset($result['status']) && $result['status'] == 1) {
            $host = $this->CPanelProvider->host(); // [B-1] sır dosyasından (fail-closed)
            $token = $result['data']['token'];
            return [
                'success' => true,
                'url'     => "https://{$host}:2096" . $token . "/login",
                'session' => $result['data']['session']
            ];
        }

        return [
            'success' => false,
            'message' => $result['errors'][0] ?? 'Webmail session failed'
        ];
    }

    /**
     * Change a mailbox password 🔐
     */
    public function changePassword(string $email, string $password, string $domain): array
    {
        return $this->CPanelProvider->call('Email', 'passwd_pop', [
            'email'    => $email,
            'password' => $password,
            'domain'   => $domain
        ]);
    }

    /**
     * List all mailbox accounts across all domains 📋
     */
    public function listAll(): array
    {
        return $this->CPanelProvider->call('Email', 'list_pops_with_disk');
    }

    /**
     * Edit mailbox quota 📝
     */
    public function editQuota(string $email, string $domain, int $quota): array
    {
        return $this->CPanelProvider->call('Email', 'edit_pop_quota', [
            'email'  => $email,
            'domain' => $domain,
            'quota'  => $quota
        ]);
    }
}
