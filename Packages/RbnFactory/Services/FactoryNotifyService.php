<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnFactory\Services;

use Rbn\Framework\Core\Base\Attributes\Component;
use Rbn\Framework\Core\Base\Services\BaseService;
use Rbn\Framework\Packages\RbnApi\Providers\TelegramProvider;
use Rbn\Framework\Packages\RbnFactory\Models\FactoryJobModel;

/**
 * FactoryNotifyService - Site sahibine Telegram bildirimi 🔔🎻
 * Olaylar: taslak azaldı/bitti, yayın özeti, kalıcı hata, yayında takıldı.
 * Bot token'ı ve sohbet numarası sitenin `project.settingsApi` deposundan gelir (TelegramProvider değişmeden
 * kullanılır); davranış ayarları sitenin Factory ayar dosyasındaki `notifications` bölümündedir. Gönderim kaydı bildirim tablosundadır.
 */
#[Component(alias: 'factoryNotify', type: 'service')]
class FactoryNotifyService extends BaseService
{
    public const CHAT_ID_KEY = 'TELEGRAM_CHAT_ID';

    /** Deneme gönderimlerinde mesaj başına eklenen ön ek (ör. "[TEST]"); canlıda boş. */
    private string $messagePrefix = '';

    public function setMessagePrefix(string $prefix): void
    {
        $this->messagePrefix = $prefix;
    }

    /**
     * Tüm olayları değerlendirir ve gönderir.
     * @return array<string,string> olay => sent|suppressed|disabled|skipped|no_condition|error:<kısa neden>
     */
    public function run(?int $now = null): array
    {
        $settings = $this->settings();
        if (empty($settings['enabled'])) {
            return ['_' => 'disabled'];
        }
        $this->handler('factorySchema')->ensure();
        $now ??= time();
        $out = [];
        $enabledEvents = (array) ($settings['events'] ?? []);

        foreach ([
            'draft_empty'     => fn() => $this->draftEmpty(),
            'draft_low'       => fn() => $this->draftLow((int) ($settings['low_draft_threshold'] ?? 5)),
            'permanent_error' => fn() => $this->permanentErrors(),
            'stuck'           => fn() => $this->stuck((int) ($settings['stuck_hours'] ?? 24)),
            'publish_summary' => fn() => $this->publishSummary($now, (int) ($settings['summary_hour'] ?? 20)),
            'renew_done'      => fn() => $this->renewDone(),
        ] as $event => $build) {
            if (!($enabledEvents[$event] ?? $event === 'renew_done')) { // renew_done: ayarda yoksa açık (VP-66)
                $out[$event] = 'disabled';
                continue;
            }
            try {
                $notices = $build();
                foreach ($notices as $notice) {
                    $out[$event . ':' . $notice['key']] = $this->send($event, $notice['key'], $notice['text']);
                }
                if ($notices === []) {
                    $out[$event] = 'no_condition';
                }
            } catch (\Throwable $e) {
                $out[$event] = 'error:' . mb_substr($e->getMessage(), 0, 80);
            }
        }
        return $out;
    }

    /** @return list<array{key:string,text:string}> */
    private function draftEmpty(): array
    {
        return array_sum($this->draftCounts()) === 0
            ? [['key' => 'all', 'text' => "🛑 <b>Taslak bitti</b>\nSunulacak taslak kalmadı; yeni içerik üretilmeyecek. Taslak listesini yenilemeniz gerekiyor."]]
            : [];
    }

    /** @return list<array{key:string,text:string}> */
    private function draftLow(int $threshold): array
    {
        $counts = $this->draftCounts();
        if (array_sum($counts) === 0) {
            return []; // tamamen bitti: ayrı ve daha acil olay
        }
        $low = array_filter($counts, static fn(int $n): bool => $n < $threshold);
        if ($low === []) {
            return [];
        }
        $lines = [];
        foreach ($low as $categoryId => $amount) {
            $name = $this->handler('factoryPostUrl')->categoryName((int) $categoryId) ?? ('Kategori #' . $categoryId);
            $lines[] = '• ' . $this->esc($name) . ': ' . $amount . ' taslak';
        }
        return [['key' => 'all', 'text' => "⚠️ <b>Taslak azaldı</b> (eşik {$threshold})\n" . implode("\n", $lines)]];
    }

    /** @return list<array{key:string,text:string}> her kalıcı hata işi için bir kez */
    private function permanentErrors(): array
    {
        $out = [];
        foreach ($this->repository('factory.job')->permanentFailures() as $job) {
            $out[] = ['key' => (string) $job['job_no'], 'text' => "❌ <b>Taslak üretilemedi</b> (2 denemede de)\n"
                . $this->esc((string) ($job['draft_title'] ?? $job['job_no'])) . "\nNeden: " . $this->esc((string) ($job['error_message'] ?? 'bilinmiyor'))];
        }
        return $out;
    }

    /** @return list<array{key:string,text:string}> */
    private function stuck(int $hours): array
    {
        $rows = $this->repository('factory.job')->stuck(date('Y-m-d H:i:s', time() - $hours * 3600));
        if ($rows === []) {
            return [];
        }
        $titles = $this->postTitles($rows);
        $lines = [];
        foreach ($rows as $job) {
            $lines[] = '• ' . $this->esc((string) ($titles[(int) $job['post_id']] ?? $job['job_no'])) . "\n  Neden: " . $this->esc((string) ($job['missing_reason'] ?? '?'));
        }
        return [['key' => 'all', 'text' => "⏳ <b>Yayında takıldı</b> ({$hours} saatten fazla)\n" . implode("\n", $lines)]];
    }

    /** @return list<array{key:string,text:string}> günde tek mesaj; özet saatinden sonra, bugün yayınlananlar */
    private function publishSummary(int $now, int $hour): array
    {
        if ((int) date('G', $now) < $hour) {
            return [];
        }
        $today = date('Y-m-d', $now);
        $rows = $this->repository('factory.job')->publishedOn($today);
        $renewed = $this->repository('factory.job')->renewalCounts($today)['today'];
        if ($rows === [] && $renewed === 0) {
            return [];
        }
        $posts = [];
        foreach ($rows as $job) {
            $posts[$job['content_type']][] = (int) $job['post_id'];
        }
        $found = [];
        foreach ($posts as $contentType => $ids) {
            $found[$contentType] = $this->repository('factory.post')->findMany((string) $contentType, $ids);
        }
        $base = rtrim((string) ($this->settings()['site_base_url'] ?? ''), '/');
        $urls = $this->handler('factoryPostUrl');
        $lines = [];
        $count = 0;
        foreach ($rows as $job) {
            $post = $found[$job['content_type']][(int) $job['post_id']] ?? null;
            if ($post === null) {
                continue;
            }
            $count++;
            $url = $urls->absolute((string) $job['content_type'], (string) $post['slug'], (int) ($post['category_id'] ?? 0), $base);
            $lines[] = '• ' . $this->esc((string) $post['title']) . ($url !== '' ? "\n  " . $this->esc($url) : '');
        }
        $text = $count === 0 ? '' : '✅ <b>Bugün yayınlananlar</b> (' . $count . ")\n" . implode("\n", $lines);
        if ($renewed > 0) {
            $text .= ($text === '' ? '' : "\n") . '🔄 <b>Bugün yenilenen yazı</b>: ' . $renewed;
        }
        return $text === '' ? [] : [['key' => $today, 'text' => $text]];
    }

    /**
     * Yenileme açık, yenilenecek yazı kalmadı ve en az bir iş sonuçlandı: tek mesaj. Anahtar yenilenen/yenilenemeyen
     * sayısını taşır; sonradan yeni aday çıkıp tamamlanırsa sayılar değişir ve yeni bir tek mesaj gider.
     * @return list<array{key:string,text:string}>
     */
    private function renewDone(): array
    {
        $service = $this->service('factory');
        $status = $service->renewalStatus();
        if (!$status['enabled'] || $status['remaining'] > 0 || $status['waiting'] > 0) {
            return [];
        }
        $renewed = (int) $status['renewed'];
        $failed = (int) $status['failed'];
        if ($renewed + $failed === 0) {
            return [];
        }
        $host = (string) parse_url((string) ($this->settings()['site_base_url'] ?? ''), PHP_URL_HOST);
        return [['key' => $renewed . '-' . $failed, 'text' => "✅ <b>Eski yazıların yenilenmesi tamamlandı</b>\n🌐 " . $this->esc($host !== '' ? $host : 'site')
            . "\n📝 Yenilenen: {$renewed} yazı · Yenilenemeyen: {$failed}\nYenileme kapatıldı, yeni yazı üretimi devam ediyor."]];
    }

    /**
     * Kategori => sunulabilir taslak sayısı (hiç taslağı kalmayan kategori dahil değil).
     * @return array<int,int>
     */
    private function draftCounts(): array
    {
        $types = array_keys($this->handler('factoryConfig')->enabledTypes());
        $drafts = $this->repository('factory.draft')->offerable($types);
        $jobs = $this->repository('factory.job')->byDraftIds(array_map(static fn(array $d): int => (int) $d['id'], $drafts));
        $published = [];
        $counts = [];
        foreach ($drafts as $draft) {
            $type = (string) $draft['type'];
            $published[$type] ??= array_flip($this->repository('factory.post')->slugs($type));
            if (isset($published[$type][$draft['slug']])) {
                continue;
            }
            $job = $jobs[(int) $draft['id']] ?? null;
            if ($job === null || $job['job_status'] === FactoryJobModel::STATUS_FAILED) {
                $category = (int) $draft['category_id'];
                $counts[$category] = ($counts[$category] ?? 0) + 1;
            }
        }
        return $counts;
    }

    /**
     * İş satırlarının yazı başlıkları.
     * @param array<int,array<string,mixed>> $jobs
     * @return array<int,string> post_id => başlık
     */
    private function postTitles(array $jobs): array
    {
        $ids = [];
        foreach ($jobs as $job) {
            $ids[$job['content_type']][] = (int) $job['post_id'];
        }
        $titles = [];
        foreach ($ids as $contentType => $postIds) {
            foreach ($this->repository('factory.post')->findMany((string) $contentType, $postIds) as $id => $post) {
                $titles[$id] = (string) $post['title'];
            }
        }
        return $titles;
    }

    /**
     * Tekrar bastırmayı uygular, gönderir, sonucu kaydeder. Kontrol-gönder-kaydet adımları olay+anahtar
     * kilidi altında çalışır: çakışan iki cron turunda ikincisi kaydı görür ve 'suppressed' döner.
     */
    private function send(string $event, string $key, string $text): string
    {
        return $this->handler('factoryLock')->synchronized(
            'notice:' . $event . ':' . $key,
            fn(): string => $this->sendLocked($event, $key, $text)
        );
    }

    private function sendLocked(string $event, string $key, string $text): string
    {
        $repeatHours = (int) (((array) ($this->settings()['repeat_hours'] ?? []))[$event] ?? ($event === 'renew_done' ? 0 : 24));
        $notices = $this->repository('factory.notice');
        if ($notices->alreadySent($event, $key, $repeatHours)) {
            return 'suppressed';
        }
        $provider = $this->provider('apiTelegram');
        $chatId = trim((string) ($provider->repository('project.settingsApi')?->getApiKey(self::CHAT_ID_KEY) ?? ''));
        if ($chatId === '' || $provider->botToken() === '') {
            return 'skipped'; // ayar yok: fail-closed, kayıt yazılmaz (ayar gelince gönderilir)
        }
        $response = $provider->sendMessage($chatId, ($this->messagePrefix !== '' ? $this->messagePrefix . ' ' : '') . $text);
        $ok = !empty($response['ok']);
        $notices->record($event, $key, $ok, $ok ? null : (string) $response['description']);
        usleep(TelegramProvider::MIN_CHAT_INTERVAL_MS * 1000);
        return $ok ? 'sent' : 'error:' . mb_substr((string) $response['description'], 0, 80);
    }

    /** @return array<string,mixed> */
    private function settings(): array
    {
        return (array) $this->handler('factoryConfig')->get('notifications', []);
    }

    private function esc(string $value): string
    {
        return htmlspecialchars(trim($value), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
