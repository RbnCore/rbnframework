<?php

declare(strict_types=1);

namespace Rbn\Framework\Bundles\RbnSuite\RbnAdmin\Controllers;

use Rbn\Framework\Core\Base\Attributes\SubModule;

/**
 * HostmailhubController - RBN Framework Email Management Hub 🏰📧
 * Handles the interaction between RbnAdmin UI and cPanel UAPI.
 * 
 * RBN Framework: Standard.
 * Controller and Link are now identical: hostmailhub
 */
#[SubModule(
    entity: 'hostmailhub',
    handler: 'cpanelMail'
)]
class HostmailhubController extends RbnAdminController
{
    /**
     * List all mail accounts for the current project context 🎻
     */
    public function index()
    {
        // 🎼 RBN Framework: All Projects for group dropdown
        $projects = group_projects();
        $projectKey = $this->activeProjectKey();

        // Find the domain of the selected project
        $selectedDomain = null;
        if (!empty($projects)) {
            foreach ($projects as $proj) {
                if ($proj['project_key'] === $projectKey) {
                    $selectedDomain = $proj['domain'] ?? null;
                    break;
                }
            }
        }

        // Fallback if not found in group projects
        if (empty($selectedDomain)) {
            $selectedDomain = $this->request->header('host') ?? 'localhost';
            if (str_contains($selectedDomain, ':')) {
                $selectedDomain = explode(':', $selectedDomain)[0];
            }
        }

        // Validate selected domain is allowed in group context
        $domains = $this->getActiveDomains();
        if (!in_array($selectedDomain, $domains)) {
            $selectedDomain = !empty($domains) ? $domains[0] : $selectedDomain;
        }

        // 🎼 RBN Framework: Query accounts only for the selected domain (with CacheProvider targeting target project) 🛰️⚓
        $cache = $this->service('storage')->cache();
        $accounts = $cache->withProject($projectKey)->remember('api_cpanel_mail', function () use ($selectedDomain) {
            return $this->service('cpanel')->mail()->list((string) $selectedDomain) ?: [];
        }, 600); // 10 minutes cache

        if (!is_array($accounts)) {
            $accounts = [];
        }

        return $this->render('Hostmailhub/index', [
            'accounts' => $accounts,
            'domains' => $domains,
            'projects' => $projects,
            'projectKey' => $projectKey,
            'selected_domain' => $selectedDomain,
            'default_quota' => \Rbn\Framework\Core\Services\Hosting\Data\CpanelData::DEFAULT_QUOTA
        ]);
    }

    /**
     * Modal Data Provider 🎻🛰️⚓
     */
    protected function getModalData($id): array
    {
        $domains = $this->getActiveDomains();

        $emailParam = $this->request->input('id');
        $email = null;
        $emailUser = null;
        $emailDomain = null;

        if ($emailParam && str_contains($emailParam, '@')) {
            $email = $emailParam;
            list($emailUser, $emailDomain) = explode('@', $emailParam);
        }

        return [
            'domains' => $domains,
            'email' => $email,
            'email_user' => $emailUser,
            'email_domain' => $emailDomain
        ];
    }

    /**
     * Create a new mailbox 📨
     */
    public function create(): void
    {
        $email = $this->request->input('host_mail_address');
        $password = $this->request->input('host_mail_password');
        $domain = $this->request->input('domain') ?? ($this->request->header('host') ?? 'localhost');
        $quota = (int) $this->request->input('host_mail_quota', 0);

        // Validate domain is allowed
        $domains = $this->getActiveDomains();

        if (!in_array($domain, $domains)) {
            $this->handleResult(false, "Geçersiz Alan Adı: '{$domain}'", false, 'create');
            return;
        }

        $result = $this->service('cpanel')->mail()->create($email, $password, (string) $domain, $quota);

        $success = (isset($result['status']) && $result['status'] == 1);
        if ($success) {
            $projectKey = project_key() ?: 'default';
            foreach (group_projects() as $proj) {
                if (($proj['domain'] ?? '') === $domain) {
                    $projectKey = $proj['project_key'];
                    break;
                }
            }
            $this->service('storage')->cache()->withProject($projectKey)->delete('api_cpanel_mail');
        }

        $this->handleResult($success, "Mailbox '{$email}@{$domain}'", false, 'create');
    }

    /**
     * Delete a mailbox 🗑️
     */
    public function delete($id): void
    {
        $email = $this->request->input('email') ?? $id;
        $domain = $this->request->input('domain') ?? ($this->request->header('host') ?? 'localhost');

        // Validate domain is allowed
        $domains = $this->getActiveDomains();

        if (!in_array($domain, $domains)) {
            $this->handleResult(false, "Geçersiz Alan Adı: '{$domain}'", false, 'delete');
            return;
        }

        $result = $this->service('cpanel')->mail()->delete((string) $email, (string) $domain);

        $success = (isset($result['status']) && $result['status'] == 1);
        if ($success) {
            $projectKey = project_key() ?: 'default';
            foreach (group_projects() as $proj) {
                if (($proj['domain'] ?? '') === $domain) {
                    $projectKey = $proj['project_key'];
                    break;
                }
            }
            $this->service('storage')->cache()->withProject($projectKey)->delete('api_cpanel_mail');
        }

        $this->handleResult($success, "Mailbox '{$email}@{$domain}'", false, 'delete');
    }

    /**
     * Change a mailbox password 🔐
     */
    public function changePassword(): void
    {
        $email = $this->request->input('email');
        $domain = $this->request->input('domain');
        $password = $this->request->input('password');

        // Validate domain is allowed
        $domains = $this->getActiveDomains();

        if (!in_array($domain, $domains)) {
            $this->handleResult(false, "Geçersiz Alan Adı: '{$domain}'", false, 'update');
            return;
        }

        $result = $this->service('cpanel')->mail()->changePassword((string) $email, (string) $password, (string) $domain);

        $success = (isset($result['status']) && $result['status'] == 1);
        if ($success) {
            $projectKey = project_key() ?: 'default';
            foreach (group_projects() as $proj) {
                if (($proj['domain'] ?? '') === $domain) {
                    $projectKey = $proj['project_key'];
                    break;
                }
            }
            $this->service('storage')->cache()->withProject($projectKey)->delete('api_cpanel_mail');
        }

        $this->handleResult($success, "Şifre güncellendi: '{$email}@{$domain}'", false, 'update');
    }

    /**
     * Eternal Link Gateway - Auto-login to Webmail 🔗⚡
     */
    public function eternalLink()
    {
        $email = $this->request->input('email');
        $this->service('cpanel')->sso()->redirect('webmail', (string) $email);
    }

    /**
     * Get active domains from group projects cache 🌐
     */
    protected function getActiveDomains(): array
    {
        $domains = array_map(fn($p) => $p['domain'], group_projects());
        return !empty($domains) ? $domains : [$this->request->header('host') ?? 'localhost'];
    }
}
