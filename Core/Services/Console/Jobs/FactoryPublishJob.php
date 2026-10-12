<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Services\Console\Jobs;

use Rbn\Framework\Core\Base\BaseComponent;

/**
 * FactoryPublishJob - İçerik Fabrikası yayın denetimi (tüm projeler, TEK çalışma) 🏭⏱️
 *
 * Aktif projeleri dolaşır; fabrikası açık olanlarda (ayar dosyası + etkin imza anahtarı) kendi proje
 * bağlamında zamanı gelmiş pasif yazıları denetler, yayınlar ve önbelleği tazeler.
 * Bir projenin hatası diğerlerini durdurmaz; sonuç proje bazlı özetlenir.
 * RbnFactory paketi yüklü değilse (servis/işleyici çözümlenmezse) iş sessizce atlanır.
 * Kayıt: master `cron_jobs` tablosunda TEK satır (SystemTask → task_key=factory_publish_audit).
 */
class FactoryPublishJob extends BaseComponent
{
    /**
     * @return array{success:bool, status:string, message:string, trace:array}
     */
    public function execute(array $params = []): array
    {
        // Global iş: yalnız ana proje bağlamından koşar (CleanupJob ile aynı kural)
        $currentProjectKey = (string) ($params['project_key'] ?? $this->projectKey ?? 'rbncore');
        if ($currentProjectKey !== 'rbncore') {
            return $this->result(false, 'failed', "Global fabrika denetimi yetkisiz proje bağlamında engellendi: [{$currentProjectKey}]");
        }

        $config = $this->handler('factoryConfig');
        $projects = $this->repository('masterProjects')->getActiveProjectKeys();
        if (!$config || !$this->service('factoryAudit')) {
            return $this->result(true, 'skipped', 'RbnFactory paketi yüklü değil');
        }

        $summary = [];
        $failed = 0;
        $active = 0;

        foreach ($projects as $projectKey) {
            try {
                $outcome = $this->runInProjectContext($projectKey, fn(): array => $this->auditProject($projectKey));
            } catch (\Throwable $e) {
                $outcome = ['state' => 'failed', 'message' => mb_substr($e->getMessage(), 0, 200), 'result' => null];
            }
            $summary[$projectKey] = $outcome;
            if ($outcome['state'] === 'failed') {
                $failed++;
            }
            if ($outcome['state'] !== 'skipped') {
                $active++;
            }
        }
        $config->useSite('');

        if ($active === 0) {
            return $this->result(true, 'skipped', sprintf('fabrikası açık proje yok (%d proje tarandı)', count($projects)), $summary);
        }

        $lines = [];
        foreach ($summary as $projectKey => $outcome) {
            if ($outcome['state'] !== 'skipped') {
                $lines[] = "{$projectKey}[{$outcome['state']}]: {$outcome['message']}";
            }
        }
        $message = sprintf('%d proje tarandı, %d fabrikalı, %d hatalı | ', count($projects), $active, $failed) . implode(' | ', $lines);
        return $this->result($failed === 0, $failed === 0 ? 'success' : 'failed', $message, $summary);
    }

    /**
     * Tek projenin denetimi (çağıran proje bağlamını önceden değiştirmiştir).
     *
     * @return array{state:string, message:string, result:?array}
     */
    private function auditProject(string $projectKey): array
    {
        $config = $this->handler('factoryConfig');
        $config->useSite($projectKey);
        if (!$config->exists()) {
            return ['state' => 'skipped', 'message' => 'fabrika kapalı (ayar dosyası ya da anahtar yok)', 'result' => null];
        }

        $result = $this->service('factoryAudit')->run(function (): bool {
            // Sitenin kendi yayın yolu: yazı kaydında flushContentCache() çağrılır
            $name = (string) $this->handler('factoryConfig')->get('publishing.cache_service', '');
            $service = $name !== '' ? $this->service($name) : null;
            return $service && method_exists($service, 'flushContentCache') ? (bool) $service->flushContentCache() : false;
        });

        $message = sprintf(
            'zamanı gelen=%d yayınlanan=%d eksik=%d taslak_silinen=%d kalici_hata=%d',
            $result['due'],
            count($result['published']),
            count($result['missing']),
            $result['drafts_deleted'],
            $result['permanent_errors']
        );
        if (!empty($result['renewal']['enabled'])) {
            $renewal = $result['renewal'];
            $message .= sprintf(
                ' | yenileme: yenilenen=%d bugün=%d/%d bekleyen=%d kalan=%d bu_turda=%d',
                $renewal['renewed'],
                $renewal['today'],
                $renewal['per_day'],
                $renewal['waiting'],
                $renewal['remaining'],
                count($result['renewed'])
            );
        }
        foreach ($result['delete_failures'] as $failure) {
            $message .= " | taslak silinemedi {$failure['job_no']}: {$failure['reason']}";
        }
        foreach ($result['missing'] as $missing) {
            $message .= " | {$missing['job_no']}: {$missing['reason']}";
        }
        // Bildirim hatası denetimi bozmaz; sonuç iz kaydına yazılır
        try {
            $result['notices'] = $this->service('factoryNotify')->run();
        } catch (\Throwable $e) {
            $result['notices'] = ['error' => mb_substr($e->getMessage(), 0, 120)];
        }
        return ['state' => 'ok', 'message' => $message, 'result' => $result];
    }

    private function result(bool $success, string $status, string $message, array $summary = []): array
    {
        return ['success' => $success, 'status' => $status, 'message' => $message, 'trace' => ['projects' => $summary]];
    }
}
