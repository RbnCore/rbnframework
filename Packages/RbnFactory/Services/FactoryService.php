<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnFactory\Services;

use Rbn\Framework\Core\Base\Attributes\Component;
use Rbn\Framework\Core\Base\Services\BaseService;
use Rbn\Framework\Core\System\Paths\Paths;
use Rbn\Framework\Packages\RbnFactory\Models\FactoryContract;
use Rbn\Framework\Packages\RbnFactory\Models\FactoryJobModel;

/**
 * FactoryService - İçerik fabrikası site tarafı: iş sunumu ve sonuç kabulü 🏭🎻
 *
 * VPS siteyi yoklar: `offerJobs()` sunulacak taslakları seçer, `handleResult()` gelen yazıyı
 * PASİF kayıt olarak yazar. Yayın denetimi FactoryAuditService'te, bildirimler FactoryNotifyService'tedir.
 */
#[Component(alias: 'factory', type: 'service')]
class FactoryService extends BaseService
{
    /**
     * Sunulacak işleri sözleşme alan adlarıyla döndürür: yeni yazı işleri + (ayarda açıksa) yenileme işleri.
     * İkisi birbirinden bağımsızdır; birinin sınırı ya da boşluğu diğerini engellemez.
     * @return array<int,array<string,mixed>>
     */
    public function offerJobs(): array
    {
        $this->handler('factorySchema')->ensure();
        return array_merge($this->offerNewJobs(), $this->offerRenewals());
    }

    /**
     * Dengeli ya da sıralı seçimle sunulacak yeni yazı işleri.
     * @return array<int,array<string,mixed>>
     */
    private function offerNewJobs(): array
    {
        $config = $this->handler('factoryConfig');
        $types = array_keys($config->enabledTypes());
        if ($types === []) {
            return [];
        }

        // Günlük güvenlik sınırı: yayın bekleyen yazı sınıra ulaştıysa yeni iş sunulmaz
        $maxPending = (int) $config->get('selection.max_pending', 0);
        if ($maxPending > 0 && $this->repository('factory.job')->countWaiting() >= $maxPending) {
            return [];
        }

        $candidates = $this->offerableDrafts($types);
        if ($candidates === []) {
            return [];
        }

        $perRequest = max(1, (int) $config->get('selection.jobs_per_request', 1));
        if ($config->get('selection.mode', 'balanced') === 'ordered') {
            $candidates = array_slice($candidates, 0, $perRequest);
        } else {
            $candidates = $this->balanced($candidates, $perRequest);
        }

        $jobs = [];
        foreach ($candidates as $draft) {
            $jobs[] = $this->offer($draft);
        }
        return $jobs;
    }

    /**
     * Sitedeki mevcut yazılar (aktif + pasif), en yeni önce; VPS benzersizlik kapısı için.
     * Sınır `uniqueness.existing_posts_limit` (varsayılan 300); fabrika kapalıysa boş.
     * @return array<int,array<string,string>>
     */
    public function existingPosts(): array
    {
        $config = $this->handler('factoryConfig');
        $limit = max(1, (int) $config->get('uniqueness.existing_posts_limit', 300));
        $fields = FactoryContract::EXISTING_POST;
        $posts = [];
        foreach (array_keys($config->enabledTypes()) as $type) {
            foreach ($this->repository('factory.post')->recent((string) $type, $limit) as $row) {
                $posts[] = [
                    $fields['slug']       => (string) $row['slug'],
                    $fields['meta_title'] => (string) ($row['seo_title'] ?? ''),
                    $fields['title']      => (string) $row['title'],
                ];
            }
        }
        return array_slice($posts, 0, $limit);
    }

    /**
     * Yenileme durumu (fabrika durum bilgisi için): toplam yenilenen, bugün yenilenen, yayın saatini bekleyen,
     * yenilenmeyi bekleyen (kalan) yazı sayısı.
     * @return array{enabled:bool, per_day:int, renewed:int, today:int, waiting:int, failed:int, remaining:int}
     */
    public function renewalStatus(): array
    {
        $config = $this->handler('factoryConfig');
        $jobs = $this->repository('factory.job');
        return [
            'enabled'   => (bool) $config->get('renew.enabled', false),
            'per_day'   => (int) $config->get('renew.per_day', 0),
            'remaining' => $this->remainingRenewals(),
        ] + $jobs->renewalCounts(date('Y-m-d'));
    }

    /**
     * Yenilenmeyi bekleyen yazı sayısı: aktif, yeniden yazılmamış ve yenileme işi yok / bekliyor / yeniden denenecek.
     * Açık ya da bekleyen yenileme işi olan yazı da burada sayılır; 0 = yenilenecek bir şey kalmadı.
     */
    public function remainingRenewals(): int
    {
        $jobs = $this->repository('factory.job');
        $remaining = 0;
        $oldestFirst = true; // sıra sayıyı etkilemez
        foreach (array_keys($this->handler('factoryConfig')->enabledTypes()) as $type) {
            $known = $jobs->renewalsByPost((string) $type);
            foreach ($this->repository('factory.post')->activeByAge((string) $type, $oldestFirst) as $post) {
                $status = $known[(int) $post['id']]['job_status'] ?? null;
                if ($status === null || $status === FactoryJobModel::STATUS_PENDING || $status === FactoryJobModel::STATUS_FAILED) {
                    $remaining++;
                }
            }
        }
        return $remaining;
    }

    /**
     * Gelen sonucu işler.
     * @return array{0:int,1:array<string,mixed>} [HTTP durumu, cevap gövdesi]
     */
    public function handleResult(array $in): array
    {
        $this->handler('factorySchema')->ensure();
        $result = FactoryContract::RESULT;
        $reply = FactoryContract::REPLY;
        $replyStatus = FactoryContract::REPLY_STATUS;

        $jobNo = (string) ($in[$result['job_no']] ?? '');
        if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{0,63}$/', $jobNo)) {
            return $this->failure(400, 'is_no geçersiz');
        }
        $jobs = $this->repository('factory.job');
        $job = $jobs->findByJobNo($jobNo);
        if ($job === null) {
            return $this->failure(404, 'bilinmeyen is_no');
        }
        if (($in[$result['content_type']] ?? '') !== $job['content_type']) {
            return $this->failure(422, 'icerik_turu işle uyuşmuyor');
        }
        // Tekrar gönderim: çift kayıt YOK, yine 200
        if ($job['job_status'] !== FactoryJobModel::STATUS_PENDING) {
            return [200, [$reply['status'] => $replyStatus['already_received'], $reply['job_no'] => $jobNo]];
        }

        $status = $in[$result['status']] ?? '';
        if ($status === FactoryContract::RESULT_STATUS['error']) {
            $this->markFailed($jobNo, (string) ($in[$result['error']] ?? 'hata bildirildi'));
            return [200, [$reply['status'] => $replyStatus['error_recorded'], $reply['job_no'] => $jobNo]];
        }
        if ($status !== FactoryContract::RESULT_STATUS['ok']) {
            return $this->failure(422, 'durum ok|hata olmalı');
        }

        if (($job['kind'] ?? FactoryJobModel::KIND_NEW) === FactoryJobModel::KIND_RENEW) {
            return $this->handleRenewalResult($in, $job);
        }

        [$post, $error] = $this->buildPost($in, (string) $job['content_type']);
        if ($error !== null) {
            return $this->failure(422, $error);
        }
        if ($this->repository('factory.post')->slugExists((string) $job['content_type'], $post['slug'])) {
            return $this->slugConflict($jobNo, $post['slug']);
        }

        $draft = $this->repository('factory.draft')->findDraft((int) $job['draft_id']);
        $job['category_id'] = $draft['category_id'] ?? $job['draft_category_id'];
        try {
            // Aynı slug için sonuç kabulü süreçler arası seri çalışır (tabloda slug indeksi yok)
            $saved = $this->handler('factoryLock')->synchronized(
                'result-slug:' . $job['content_type'] . ':' . $post['slug'],
                fn(): ?array => $jobs->deliver($job, $post, fn(array $taken): string => $this->nextFreeSlot($taken))
            );
        } catch (\DomainException) {
            return $this->slugConflict($jobNo, $post['slug']);
        } catch (\Throwable $e) {
            error_log('[factory] result kaydı başarısız job_no=' . $jobNo . ': ' . $e->getMessage());
            return $this->failure(500, 'kayıt başarısız'); // VPS geçici hata sayıp yeniden dener
        }
        if ($saved === null) { // yarışı başka istek kazandı: çift kayıt YOK
            return [200, [$reply['status'] => $replyStatus['already_received'], $reply['job_no'] => $jobNo]];
        }
        return [200, [
            $reply['status']       => $replyStatus['received'],
            $reply['job_no']       => $jobNo,
            $reply['scheduled_at'] => $saved['scheduled_at'],
        ]];
    }

    /**
     * Yenileme sonucu: yeni içerik yazıya HEMEN yazılmaz; iş kaydında bekler, yenileme saatinde yayın işi uygular.
     * Adres yalnız VPS `new_slug` önerdiyse (ve boşsa) değişir; `slug` alanı yenilemede yok sayılır.
     * @param array<string,mixed> $job
     * @return array{0:int,1:array<string,mixed>}
     */
    private function handleRenewalResult(array $in, array $job): array
    {
        $result = FactoryContract::RESULT;
        $renew = FactoryContract::RESULT_RENEW;
        $reply = FactoryContract::REPLY;
        $jobNo = (string) $job['job_no'];
        $type = (string) $job['content_type'];
        $postId = (int) $job['post_id'];

        if (isset($in[$renew['post_id']]) && (string) $in[$renew['post_id']] !== (string) $postId) {
            return $this->failure(422, 'post_id işle uyuşmuyor');
        }
        if (isset($in[$renew['kind']]) && $in[$renew['kind']] !== FactoryContract::KIND_RENEW) {
            return $this->failure(422, 'kind işle uyuşmuyor');
        }
        $current = $this->repository('factory.post')->findPost($type, $postId);
        if ($current === null || (int) $current['is_active'] !== 1) {
            $this->markFailed($jobNo, 'yenilenecek yazı yok ya da pasif');
            return $this->failure(422, 'yenilenecek yazı yok');
        }

        // buildPost slug biçimini denetler; yenilemede adres aşağıda ayrıca belirlenir, bu yüzden geçerli bir yer tutucu verilir
        $in[$result['slug']] = 'renewal';
        [$post, $error] = $this->buildPost($in, $type, true);
        if ($error !== null) {
            return $this->failure(422, $error);
        }
        // Kapak YALNIZ yazının kapağı yoksa (boş ya da dosyası yok) kabul edilir; kapaklı yazıda gelen kapak yok sayılır (kapak değişmez)
        if ($post['image'] === null || $this->service('factoryAudit')->hasCover((string) ($current['image'] ?? ''), $type)) {
            unset($post['image']);
        }
        $post['slug'] = $this->renewalSlug($type, (string) $current['slug'], (string) ($in[$renew['new_slug']] ?? ''));

        try {
            $saved = $this->handler('factoryLock')->synchronized(
                'renew-slot:' . $type,
                fn(): ?array => $this->repository('factory.job')->deliverRenewal($job, $post, fn(array $taken): string => $this->nextRenewalSlot($taken))
            );
        } catch (\Throwable $e) {
            error_log('[factory] renew kaydı başarısız job_no=' . $jobNo . ': ' . $e->getMessage());
            return $this->failure(500, 'kayıt başarısız');
        }
        if ($saved === null) {
            return [200, [$reply['status'] => FactoryContract::REPLY_STATUS['already_received'], $reply['job_no'] => $jobNo]];
        }
        return [200, [
            $reply['status']       => FactoryContract::REPLY_STATUS['received'],
            $reply['job_no']       => $jobNo,
            $reply['scheduled_at'] => $saved['scheduled_at'],
        ]];
    }

    /** Yenilenen yazının slug'ı: geçerli, başka yazıya ait olmayan `new_slug` varsa o; yoksa mevcut slug korunur (adres sabit). */
    private function renewalSlug(string $contentType, string $currentSlug, string $suggested): string
    {
        $suggested = trim($suggested);
        if ($suggested === '' || $suggested === $currentSlug
            || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $suggested) || strlen($suggested) > 255
            || $this->repository('factory.post')->slugExists($contentType, $suggested)) {
            return $currentSlug;
        }
        return $suggested;
    }

    /** Slug başka yazıya ait: iş aynı anda başka istekle teslim edildiyse tekrar sayılır, değilse hata kaydı + 409. */
    private function slugConflict(string $jobNo, string $slug): array
    {
        $current = $this->repository('factory.job')->findByJobNo($jobNo);
        if ($current !== null && $current['job_status'] !== FactoryJobModel::STATUS_PENDING) {
            return [200, [FactoryContract::REPLY['status'] => FactoryContract::REPLY_STATUS['already_received'], FactoryContract::REPLY['job_no'] => $jobNo]];
        }
        $this->markFailed($jobNo, 'slug çakışması: ' . $slug);
        return $this->failure(409, 'slug zaten var');
    }

    /** Yayın takvimine göre ilk boş zaman (şimdiden sonra, başka yazıya atanmamış). */
    public function nextFreeSlot(array $taken): string
    {
        $config = $this->handler('factoryConfig');
        return $this->freeSlot($taken, (array) $config->get('schedule.hours', []), (array) $config->get('schedule.days', []));
    }

    /** Yenileme takvimine (`renew.hours`, her gün) göre ilk boş zaman. */
    public function nextRenewalSlot(array $taken): string
    {
        return $this->freeSlot($taken, (array) $this->handler('factoryConfig')->get('renew.hours', []), range(1, 7));
    }

    /**
     * @param string[] $taken dolu zamanlar
     * @param int[] $hours saatler; @param int[] $days 1=Pazartesi … 7=Pazar
     */
    private function freeSlot(array $taken, array $hours, array $days): string
    {
        $taken = array_flip($taken);
        sort($hours);
        $now = time();
        for ($offset = 0; $offset < 400; $offset++) {
            $day = strtotime("+{$offset} day", strtotime(date('Y-m-d', $now)));
            if (!in_array((int) date('N', $day), $days, true)) {
                continue;
            }
            foreach ($hours as $hour) {
                $slot = date('Y-m-d', $day) . sprintf(' %02d:00:00', (int) $hour);
                if (strtotime($slot) > $now && !isset($taken[$slot])) {
                    return $slot;
                }
            }
        }
        throw new \RuntimeException('Yayın takviminde boş zaman bulunamadı (schedule/renew days ve hours ayarları).');
    }

    /**
     * Sunulabilir (yayınlanmamış, işi yok / yeniden sunulabilir) taslaklar.
     * @param string[] $types
     * @return array<int,array<string,mixed>> taslak satırı + prev_job_no, prev_status, retry_count
     */
    private function offerableDrafts(array $types): array
    {
        $config = $this->handler('factoryConfig');
        $drafts = $this->repository('factory.draft')->offerable($types);
        $jobs = $this->repository('factory.job')->byDraftIds(array_map(static fn(array $d): int => (int) $d['id'], $drafts));

        $now = time();
        $requestCutoff = date('Y-m-d H:i:s', $now - (int) $config->get('selection.reoffer_after_minutes', 120) * 60);
        $retryCutoff = date('Y-m-d H:i:s', $now - (int) $config->get('selection.retry_after_hours', 24) * 3600);
        $maxRetries = (int) $config->get('selection.max_retries', 2);

        $publishedSlugs = [];
        $offerable = [];
        foreach ($drafts as $draft) {
            $type = (string) $draft['type'];
            $publishedSlugs[$type] ??= array_flip($this->repository('factory.post')->slugs($type));
            if (isset($publishedSlugs[$type][$draft['slug']])) {
                continue;
            }
            $job = $jobs[(int) $draft['id']] ?? null;
            $eligible = $job === null
                || ($job['job_status'] === FactoryJobModel::STATUS_PENDING && (string) $job['requested_at'] < $requestCutoff)
                || ($job['job_status'] === FactoryJobModel::STATUS_FAILED
                    && (int) $job['retry_count'] < $maxRetries
                    && !empty($job['failed_at']) && (string) $job['failed_at'] <= $retryCutoff);
            if (!$eligible) {
                continue;
            }
            $draft['prev_job_no'] = $job['job_no'] ?? null;
            $draft['prev_status'] = $job['job_status'] ?? null;
            $draft['retry_count'] = (int) ($job['retry_count'] ?? 0);
            $offerable[] = $draft;
        }
        return $offerable;
    }

    /**
     * Yazı + bekleyen iş sayısı en az olan kategoriden seçer (eşitlikte rastgele kategori).
     * @param array<int,array<string,mixed>> $candidates
     * @return array<int,array<string,mixed>>
     */
    private function balanced(array $candidates, int $limit): array
    {
        $totals = $this->categoryTotals();
        $available = array_values(array_unique(array_map(static fn(array $d): int => (int) $d['category_id'], $candidates)));
        $minimum = min(array_map(static fn(int $category): int => $totals[$category] ?? 0, $available));
        $lowest = array_values(array_filter(
            $candidates,
            static fn(array $d): bool => ($totals[(int) $d['category_id']] ?? 0) === $minimum
        ));
        $tied = array_values(array_unique(array_map(static fn(array $d): int => (int) $d['category_id'], $lowest)));
        $categoryId = $tied[array_rand($tied)];
        $chosen = array_values(array_filter($lowest, static fn(array $d): bool => (int) $d['category_id'] === $categoryId));
        return array_slice($chosen, 0, $limit);
    }

    /** @return array<int,int> kategori => yayında/yayın bekleyen yazı + yanıt beklenen iş sayısı */
    private function categoryTotals(): array
    {
        $jobs = $this->repository('factory.job');
        $unpublished = [];
        $totals = [];
        foreach (array_keys($this->handler('factoryConfig')->enabledTypes()) as $type) {
            $unpublished = array_flip($jobs->unpublishedPostIds((string) $type));
            foreach ($this->repository('factory.post')->balanceRows((string) $type) as $post) {
                if ((int) $post['is_active'] === 1 || isset($unpublished[(int) $post['id']])) {
                    $category = (int) $post['category_id'];
                    $totals[$category] = ($totals[$category] ?? 0) + 1;
                }
            }
        }
        foreach ($jobs->pendingRows() as $job) {
            $category = (int) ($job['draft_category_id'] ?? 0);
            $totals[$category] = ($totals[$category] ?? 0) + 1;
        }
        return $totals;
    }

    /** Taslağı iş olarak kaydeder ve VPS'e giden sözleşme gövdesini üretir. */
    private function offer(array $draft): array
    {
        $retrying = $draft['prev_status'] === FactoryJobModel::STATUS_FAILED;
        $retryCount = $draft['retry_count'] + ($retrying ? 1 : 0);
        $jobNo = $retrying
            ? sprintf('S-%s-%06d-R%d', date('Y'), (int) $draft['id'], $retryCount)
            : ($draft['prev_job_no'] ?: sprintf('S-%s-%06d', date('Y'), (int) $draft['id']));

        $this->repository('factory.job')->request((int) $draft['id'], [
            'job_no'            => $jobNo,
            'content_type'      => (string) $draft['type'],
            'draft_title'       => (string) $draft['title'],
            'draft_category_id' => (int) $draft['category_id'],
            'retry_count'       => $retryCount,
        ]);

        return $this->jobPayload($jobNo, (string) $draft['title'], (string) $draft['type'], (int) $draft['category_id'], (string) $draft['slug']);
    }

    /**
     * VPS'e giden iş gövdesi (yeni yazı ve yenileme için ortak): yön ayarları + iç bağlantı adayları.
     * @param string $excludeSlug iç bağlantı adaylarından çıkarılacak slug (taslağın / yenilenen yazının kendisi)
     * @return array<string,mixed>
     */
    private function jobPayload(string $jobNo, string $topic, string $type, int $categoryId, string $excludeSlug): array
    {
        $config = $this->handler('factoryConfig');
        $fields = FactoryContract::JOB;
        $direction = (array) $config->get('direction', []);
        $payload = [
            $fields['job_no']       => $jobNo,
            $fields['topic']        => $topic,
            $fields['content_type'] => $type,
            $fields['image_dir']    => (string) $config->get('content_types.' . $type . '.image_dir', ''),
            $fields['site_docroot'] => rtrim(Paths::publicRoot(), '/\\'), // yayın denetiminin dosya varlığına baktığı kök (FactoryAuditService)
            $fields['site_note']    => $direction['site_note'] ?? '',
            $fields['location']     => trim((string) ($direction['location'] ?? '')),
            $fields['site_domain']  => trim((string) ($direction['site_domain'] ?? '')), // sitenin KENDİ alan adı: VPS yalnız bunu bilir, başka alan adı uyduramaz
            $fields['tone']         => $direction['tone'] ?? '',
            $fields['banned_words'] => $direction['banned_words'] ?? [],
            $fields['options']      => array_map('boolval', (array) $config->get('rules.optional_fields', [])),
            $fields['internal_links'] => array_map(static fn(array $link): array => [
                FactoryContract::CANDIDATE['url']   => (string) ($link['url'] ?? ''),
                FactoryContract::CANDIDATE['title'] => (string) ($link['title'] ?? ''),
            ], (array) ($direction['internal_links'] ?? [])),
        ];
        $payload = array_filter($payload, static fn($value): bool => $value !== '' && $value !== []);
        $payload[$fields['link_candidates']] = $this->handler('factoryInternalLink')->candidates($type, $topic, $categoryId, $excludeSlug);
        return $payload;
    }

    /**
     * Yenileme işleri: günlük sınır dolmadıysa en eski yayındaki, henüz yenileme işi olmayan yazılar.
     * ESKİ İÇERİK GÖNDERİLMEZ; VPS yalnız başlığı (konu ipucu), kategoriyi ve adresi alır.
     * @return array<int,array<string,mixed>>
     */
    private function offerRenewals(): array
    {
        $config = $this->handler('factoryConfig');
        $perDay = (int) $config->get('renew.per_day', 0);
        if (!$config->get('renew.enabled', false) || $perDay < 1 || (array) $config->get('renew.hours', []) === []) {
            return [];
        }
        if ($this->remainingRenewals() === 0) {
            return []; // yenilenecek yazı kalmadı: bu site için yenileme çalışma anında kapalı sayılır (ayar dosyası değişmez)
        }

        $jobs = $this->repository('factory.job');
        $now = time();
        $dayStart = date('Y-m-d 00:00:00', $now);
        $requestCutoff = date('Y-m-d H:i:s', $now - (int) $config->get('selection.reoffer_after_minutes', 120) * 60);
        $retryCutoff = date('Y-m-d H:i:s', $now - (int) $config->get('selection.retry_after_hours', 24) * 3600);
        $maxRetries = (int) $config->get('selection.max_retries', 2);
        $oldestFirst = $config->get('renew.order', 'oldest') !== 'newest';

        // Bugün sunulan + teslim alınan sayısı; yanıtsız kalmış (yeniden sunulabilir) sunumlar sayılmaz
        $left = $perDay - $jobs->countRenewalsRequestedSince($dayStart, $requestCutoff);
        $left = min($left, max(1, (int) $config->get('selection.jobs_per_request', 1)));
        if ($left < 1) {
            return [];
        }

        $offers = [];
        foreach (array_keys($config->enabledTypes()) as $type) {
            $known = $jobs->renewalsByPost((string) $type);
            foreach ($this->repository('factory.post')->activeByAge((string) $type, $oldestFirst) as $post) {
                $job = $known[(int) $post['id']] ?? null;
                $eligible = $job === null
                    || ($job['job_status'] === FactoryJobModel::STATUS_PENDING && (string) $job['requested_at'] < $requestCutoff)
                    || ($job['job_status'] === FactoryJobModel::STATUS_FAILED
                        && (int) $job['retry_count'] < $maxRetries
                        && !empty($job['failed_at']) && (string) $job['failed_at'] <= $retryCutoff);
                if (!$eligible) {
                    continue;
                }
                $offer = $this->offerRenewal((string) $type, $post, $job);
                if ($offer === null) {
                    break 2; // eşzamanlı istek bu yazıyı açtı: bu turda sunum yok (günlük sınır aşılmasın)
                }
                $offers[] = $offer;
                if (--$left < 1) {
                    break 2;
                }
            }
        }
        return $offers;
    }

    /**
     * Yazıyı yenileme işi olarak kaydeder ve VPS'e giden gövdeyi üretir.
     * @param array<string,mixed> $post
     * @param array<string,mixed>|null $previous bu yazının önceki yenileme işi (yeniden sunum / deneme)
     * @return array<string,mixed>|null null = iş başka istek tarafından açıldı
     */
    private function offerRenewal(string $type, array $post, ?array $previous): ?array
    {
        $postId = (int) $post['id'];
        $retrying = $previous !== null && $previous['job_status'] === FactoryJobModel::STATUS_FAILED;
        $retryCount = (int) ($previous['retry_count'] ?? 0) + ($retrying ? 1 : 0);
        $base = $previous !== null ? (string) preg_replace('/-R\d+$/', '', (string) $previous['job_no']) : sprintf('SR-%s-%06d', date('Y'), $postId);
        $jobNo = $retrying ? $base . '-R' . $retryCount : $base;

        $opened = $this->repository('factory.job')->requestRenewal($type, $postId, [
            'job_no'            => $jobNo,
            'draft_title'       => mb_substr((string) $post['title'], 0, 255),
            'draft_category_id' => (int) $post['category_id'],
            'retry_count'       => $retryCount,
        ]);
        if (!$opened) {
            return null; // eşzamanlı istek bu yazının işini zaten açtı
        }

        $urls = $this->handler('factoryPostUrl');
        $categoryId = (int) ($post['category_id'] ?? 0);
        $category = [
            FactoryContract::JOB_RENEW_CATEGORY['name'] => (string) ($urls->categoryName($categoryId) ?? ''),
            FactoryContract::JOB_RENEW_CATEGORY['slug'] => $urls->categorySlug($categoryId),
        ];
        $baseUrl = (string) $this->handler('factoryConfig')->get('notifications.site_base_url', '');
        $fields = FactoryContract::JOB_RENEW;

        $payload = $this->jobPayload($jobNo, (string) $post['title'], $type, $categoryId, (string) $post['slug']);
        return $payload + array_filter([
            $fields['kind']         => FactoryContract::KIND_RENEW,
            $fields['post_id']      => $postId,
            $fields['title']        => (string) $post['title'],
            $fields['category']     => array_filter($category, static fn(string $value): bool => $value !== ''),
            $fields['current_slug'] => (string) $post['slug'],
            $fields['has_cover']    => $this->service('factoryAudit')->hasCover((string) ($post['image'] ?? ''), $type),
            $fields['url']          => $urls->absolute($type, (string) $post['slug'], $categoryId, $baseUrl) ?: $urls->path($type, (string) $post['slug'], $categoryId),
        ], static fn($value): bool => $value !== '' && $value !== []);
    }

    private function markFailed(string $jobNo, string $reason): void
    {
        $permanent = $this->repository('factory.job')->markFailed(
            $jobNo,
            $reason,
            (int) $this->handler('factoryConfig')->get('selection.max_retries', 2)
        );
        if ($permanent) {
            error_log('[factory] permanent_failure job_no=' . $jobNo);
        }
    }

    /** @return array{0:int,1:array<string,string>} */
    private function failure(int $status, string $message): array
    {
        return [$status, [FactoryContract::REPLY['error'] => $message]];
    }

    /**
     * @param bool $renew yenileme sonucu: yazı içi görsel TAM 1 olmalı (kapak üretilmez)
     * @return array{0:?array,1:?string} [yazı satırı, hata]
     */
    private function buildPost(array $in, string $contentType, bool $renew = false): array
    {
        $result = FactoryContract::RESULT;
        $title = trim((string) ($in[$result['title']] ?? ''));
        $slug = (string) ($in[$result['slug']] ?? '');
        $html = (string) ($in[$result['html']] ?? '');
        if ($title === '' || $html === '' || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) || strlen($slug) > 255) {
            return [null, 'baslik/html/slug geçersiz'];
        }
        $imageDir = trim((string) $this->handler('factoryConfig')->get('content_types.' . $contentType . '.image_dir', ''), '/');
        $roles = FactoryContract::IMAGE_ROLE;
        $cover = null;
        foreach ((array) ($in[$result['images']] ?? []) as $image) {
            $path = (string) ($image[FactoryContract::IMAGE['path']] ?? '');
            $role = $image[FactoryContract::IMAGE['role']] ?? '';
            if (!in_array($role, $roles, true) || $path === '' || str_contains($path, '..') || !str_starts_with(ltrim($path, '/'), $imageDir . '/')) {
                return [null, 'görsel rol/yol geçersiz'];
            }
            if ($role === $roles['cover'] && $cover === null) {
                $cover = '/' . ltrim($path, '/');
            }
        }
        [$postImages, $imagesError] = $this->buildPostImages((array) ($in[$result['post_images']] ?? []), $imageDir, $contentType, $renew);
        if ($imagesError !== null) {
            return [null, $imagesError];
        }
        $faqs = [];
        foreach ((array) ($in[$result['faqs']] ?? []) as $item) {
            $faqs[] = [
                'question' => (string) ($item[FactoryContract::FAQ['question']] ?? ''),
                'answer'   => (string) ($item[FactoryContract::FAQ['answer']] ?? ''),
            ];
        }
        $meta = (array) ($in[$result['meta']] ?? []);
        $encode = static fn($value): ?string => is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : null;
        $authorComment = (string) ($in[$result['author_comment']] ?? '');
        $row = [
            'title'           => $title,
            'slug'            => $slug,
            'summary'         => (string) ($in[$result['summary']] ?? ''),
            'content'         => $html,
            'image'           => $cover,
            'seo_title'       => (string) ($meta[FactoryContract::META['title']] ?? ''),
            'seo_description' => (string) ($meta[FactoryContract::META['description']] ?? ''),
            'seo_keywords'    => implode(', ', array_map('strval', (array) ($in[$result['tags']] ?? []))),
            'faqs'            => $faqs ? json_encode($faqs, JSON_UNESCAPED_UNICODE) : null,
            'cta'             => $encode($in[$result['cta']] ?? null),
            'pros_cons'       => $encode($in[$result['pros_cons']] ?? null),
            'author_comment'  => $authorComment !== '' ? $authorComment : null,
        ];
        if ($postImages !== []) {
            $row['images'] = json_encode($postImages, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        return [$row, null];
    }

    /**
     * Yazı içi görseller (`images`): içeriğe gömülmez, yazının `images` sütununa JSON olarak yazılır.
     * Yeni yazıda en çok 2, yenilemede TAM 1 öğe; yol görsel dizini altında olmalı; boyutlar pozitif.
     * @param array<int,mixed> $items
     * @return array{0:array<int,array<string,mixed>>,1:?string} [normalize liste, hata]
     */
    private function buildPostImages(array $items, string $imageDir, string $contentType, bool $renew): array
    {
        $fields = FactoryContract::POST_IMAGE;
        $max = $renew ? FactoryContract::POST_IMAGES_RENEW : FactoryContract::POST_IMAGES_MAX;
        if (count($items) > $max || ($renew && count($items) !== $max)) {
            return [[], $renew ? 'images yenilemede tam ' . $max . ' öğe olmalı' : 'images en çok ' . $max . ' öğe olabilir'];
        }
        $list = [];
        foreach (array_values($items) as $item) {
            $item = (array) $item;
            $src = trim((string) ($item[$fields['src']] ?? ''));
            $section = (string) ($item[$fields['after_section']] ?? '');
            $width = (int) ($item[$fields['width']] ?? 0);
            $height = (int) ($item[$fields['height']] ?? 0);
            if ($src === '' || str_contains($src, '..') || !str_starts_with(ltrim($src, '/'), $imageDir . '/')
                || !preg_match('/^[A-Za-z0-9][A-Za-z0-9_:.-]{0,127}$/', $section) || $width < 1 || $height < 1) {
                return [[], 'images öğesi geçersiz (src/after_section/width/height)'];
            }
            $list[] = [
                'src'           => '/' . ltrim($src, '/'),
                'alt'           => trim((string) ($item[$fields['alt']] ?? '')),
                'after_section' => $section,
                'width'         => $width,
                'height'        => $height,
            ];
        }
        if ($list !== [] && !$this->repository('factory.post')->hasColumn($contentType, 'images')) {
            return [[], 'yazı tablosunda images sütunu yok'];
        }
        return [$list, null];
    }
}
