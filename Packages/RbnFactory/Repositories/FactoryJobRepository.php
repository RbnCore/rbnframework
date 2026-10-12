<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnFactory\Repositories;

use Rbn\Framework\Core\Base\Attributes\Component;
use Rbn\Framework\Core\Base\Data\BaseRepository;
use Rbn\Framework\Packages\RbnFactory\Models\FactoryJobModel;

/**
 * FactoryJobRepository - Takip (iş) tablosu erişimi 🏭📦
 * Tüm zamanlar PHP saatiyle yazılır/karşılaştırılır (yayın takvimi de PHP saatiyle üretilir).
 */
#[Component(alias: 'factory.job', type: 'repository')]
class FactoryJobRepository extends BaseRepository
{
    /** @var string Hedef model takma adı */
    protected $targetModel = 'factory.job';

    private function jobs(): object
    {
        return $this->model('factory.job')->withProjectScope($this->handler('factoryConfig')->projectKey());
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }

    /** İşlemi proje bağlantısında tek transaction içinde çalıştırır. */
    private function atomic(callable $callback): mixed
    {
        $db = $this->model('factory.job')->getDb();
        return $db->connectionScoped('database_project', fn() => $db->transaction(static fn() => $callback()));
    }

    /** @return array<string,mixed>|null */
    public function findByJobNo(string $jobNo): ?array
    {
        $rows = $this->jobs()->query()->where('job_no', $jobNo)->limit(1)->rows();
        return $rows[0] ?? null;
    }

    /**
     * @param int[] $draftIds
     * @return array<int,array<string,mixed>> draft_id => satır
     */
    public function byDraftIds(array $draftIds): array
    {
        if ($draftIds === []) {
            return [];
        }
        $out = [];
        foreach ($this->jobs()->query()->whereIn('draft_id', $draftIds)->rows() as $row) {
            $out[(int) $row['draft_id']] = $row;
        }
        return $out;
    }

    /** Yayın bekleyen (teslim edilmiş, yayın saati gelmemiş) iş sayısı. */
    public function countWaiting(): int
    {
        return $this->jobs()->query()
            ->where('kind', FactoryJobModel::KIND_NEW)
            ->where('job_status', FactoryJobModel::STATUS_DELIVERED)
            ->where('publish_status', FactoryJobModel::PUBLISH_PENDING)
            ->count();
    }

    /** VPS'ten yanıt beklenen işler (kategori dengesi için). @return array<int,array<string,mixed>> */
    public function pendingRows(): array
    {
        return $this->jobs()->query()
            ->where('kind', FactoryJobModel::KIND_NEW)
            ->where('job_status', FactoryJobModel::STATUS_PENDING)
            ->rows();
    }

    /**
     * Teslim edilmiş ve yayına çıkmamış (bekleyen/eksik) işlerin yazı kimlikleri (içerik türüne göre).
     * @return int[]
     */
    public function unpublishedPostIds(string $contentType): array
    {
        $rows = $this->jobs()->query()
            ->select('post_id')
            ->where('kind', FactoryJobModel::KIND_NEW)
            ->where('content_type', $contentType)
            ->where('job_status', FactoryJobModel::STATUS_DELIVERED)
            ->whereIn('publish_status', [FactoryJobModel::PUBLISH_PENDING, FactoryJobModel::PUBLISH_INCOMPLETE])
            ->whereNotNull('post_id')
            ->rows();
        return array_map('intval', array_column($rows, 'post_id'));
    }

    /**
     * Yeni yazı işiyle (kind=new) oluşmuş yazıların kimlikleri: bunlar yenileme adayı değildir.
     * @return int[]
     */
    public function newPostIds(string $contentType): array
    {
        $rows = $this->jobs()->query()
            ->select('post_id')
            ->where('kind', FactoryJobModel::KIND_NEW)
            ->where('content_type', $contentType)
            ->whereNotNull('post_id')
            ->rows();
        return array_map('intval', array_column($rows, 'post_id'));
    }

    /** Taslak için iş sunumu kaydı: varsa günceller (yeniden sunum/deneme), yoksa ekler. */
    public function request(int $draftId, array $row): void
    {
        $values = $row + [
            'error_message' => null,
            'failed_at'     => null,
            'job_status'    => FactoryJobModel::STATUS_PENDING,
            'requested_at'  => $this->now(),
        ];
        $existing = $this->jobs()->query()->where('draft_id', $draftId)->limit(1)->rows();
        if ($existing !== []) {
            $this->jobs()->query()->where('id', (int) $existing[0]['id'])->update($values);
            return;
        }
        $this->jobs()->create($values + ['draft_id' => $draftId]);
    }

    /** Yanıt beklenen işi başarısız yazar; deneme hakkı bittiyse kalıcı hata. @return bool kalıcı hata mı */
    public function markFailed(string $jobNo, string $reason, int $maxRetries): bool
    {
        $job = $this->jobs()->query()
            ->where('job_no', $jobNo)
            ->where('job_status', FactoryJobModel::STATUS_PENDING)
            ->limit(1)->rows();
        if ($job === []) {
            return false;
        }
        $permanent = (int) $job[0]['retry_count'] >= $maxRetries;
        $this->jobs()->query()
            ->where('job_no', $jobNo)
            ->where('job_status', FactoryJobModel::STATUS_PENDING)
            ->update([
                'job_status'    => $permanent ? FactoryJobModel::STATUS_PERMANENT_FAILURE : FactoryJobModel::STATUS_FAILED,
                'error_message' => mb_substr($reason, 0, 500),
                'failed_at'     => $this->now(),
            ]);
        return $permanent;
    }

    /** Başka işe atanmış, gelecekteki yayın zamanları. @return string[] */
    public function takenSlots(): array
    {
        $rows = $this->jobs()->query()
            ->select('scheduled_at')
            ->where('scheduled_at', '>', $this->now())
            ->rows();
        return array_column($rows, 'scheduled_at');
    }

    /**
     * Gelen yazıyı PASİF satır olarak ekler ve işi 'delivered' yapar (tek işlem).
     * İş önce `pending` koşuluyla ATOMİK sahiplenilir: eşzamanlı ikinci istek 0 satır etkiler ve yazı eklemez.
     * @param callable $nextSlot yayın zamanı üretici (işlem içinde, güncel doluluğa göre)
     * @return array{post_id:int, scheduled_at:string}|null null = iş zaten başka istek tarafından alınmış
     * @throws \DomainException slug bu arada başka yazıya verilmişse (işlem geri alınır, iş pending kalır)
     */
    public function deliver(array $job, array $post, callable $nextSlot): ?array
    {
        return $this->atomic(function () use ($job, $post, $nextSlot): ?array {
            $claimed = $this->jobs()->query()
                ->where('id', (int) $job['id'])
                ->where('job_status', FactoryJobModel::STATUS_PENDING)
                ->updateAffected(['job_status' => FactoryJobModel::STATUS_DELIVERED, 'delivered_at' => $this->now()]);
            if ($claimed !== 1) {
                return null;
            }
            // İkinci savunma: tabloda slug benzersiz indeksi yok; sahiplenmeden sonra yeniden bakılır
            if ($this->repository('factory.post')->slugExists((string) $job['content_type'], (string) $post['slug'])) {
                throw new \DomainException('slug zaten var');
            }
            $slot = $nextSlot($this->takenSlots());
            $postId = $this->repository('factory.post')->insertPost(
                (string) $job['content_type'],
                $post + ['category_id' => $job['category_id'], 'is_active' => 0]
            );
            $this->jobs()->query()->where('id', (int) $job['id'])->update([
                'post_id'        => $postId,
                'scheduled_at'   => $slot,
                'publish_status' => FactoryJobModel::PUBLISH_PENDING,
                'missing_reason' => null,
            ]);
            return ['post_id' => $postId, 'scheduled_at' => $slot];
        });
    }

    /** Yayın saati gelmiş, teslim edilmiş, henüz yayında olmayan işler. @return array<int,array<string,mixed>> */
    public function due(): array
    {
        return $this->jobs()->query()
            ->where('kind', FactoryJobModel::KIND_NEW)
            ->where('job_status', FactoryJobModel::STATUS_DELIVERED)
            ->whereIn('publish_status', [FactoryJobModel::PUBLISH_PENDING, FactoryJobModel::PUBLISH_INCOMPLETE])
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', $this->now())
            ->orderBy('scheduled_at', 'ASC')
            ->rows();
    }

    /** Yazıyı aktifleştirir ve işi 'published' yapar (tek işlem). */
    public function publish(array $job): void
    {
        $this->atomic(function () use ($job): void {
            $this->repository('factory.post')->setActive((string) $job['content_type'], (int) $job['post_id'], true);
            $now = $this->now();
            $this->jobs()->query()->where('id', (int) $job['id'])->update([
                'publish_status'  => FactoryJobModel::PUBLISH_PUBLISHED,
                'last_checked_at' => $now,
                'published_at'    => $now,
                'missing_reason'  => null,
            ]);
        });
    }

    /** Denetimden geçemeyen yazıyı pasif tutar ve nedeni yazar. */
    public function markIncomplete(array $job, string $reason): void
    {
        $this->atomic(function () use ($job, $reason): void {
            $this->repository('factory.post')->setActive((string) $job['content_type'], (int) $job['post_id'], false);
            $this->jobs()->query()->where('id', (int) $job['id'])->update([
                'publish_status'  => FactoryJobModel::PUBLISH_INCOMPLETE,
                'last_checked_at' => $this->now(),
                'missing_reason'  => $reason,
            ]);
        });
    }

    public function countPermanentFailures(): int
    {
        return $this->jobs()->query()->where('job_status', FactoryJobModel::STATUS_PERMANENT_FAILURE)->count();
    }

    /** @return array<int,array<string,mixed>> */
    public function permanentFailures(): array
    {
        return $this->jobs()->query()
            ->where('job_status', FactoryJobModel::STATUS_PERMANENT_FAILURE)
            ->orderBy('id', 'ASC')
            ->rows();
    }

    /** Yayında ama taslağı silinmemiş işler (geçici DB hatasını sonraki turda tamamlamak için). */
    public function publishedWithDraft(): array
    {
        return $this->jobs()->query()
            ->where('kind', FactoryJobModel::KIND_NEW)
            ->where('job_status', FactoryJobModel::STATUS_DELIVERED)
            ->where('publish_status', FactoryJobModel::PUBLISH_PUBLISHED)
            ->whereNull('draft_deleted_at')
            ->orderBy('published_at', 'ASC')
            ->rows();
    }

    /**
     * Yayınlanmış işe bağlı taslağı atomik siler; özet izini iş satırında bırakır.
     * @return bool silme yapıldı mı (zaten silinmişse / koşul tutmuyorsa false)
     */
    public function deletePublishedDraft(int $jobId): bool
    {
        return (bool) $this->atomic(function () use ($jobId): bool {
            // Sahiplenme: koşullu güncelleme; 0 satır = başka tur zaten silmiş ya da iş yayında değil
            $claimed = $this->jobs()->query()
                ->where('id', $jobId)
                ->where('job_status', FactoryJobModel::STATUS_DELIVERED)
                ->where('publish_status', FactoryJobModel::PUBLISH_PUBLISHED)
                ->whereNotNull('post_id')
                ->whereNull('draft_deleted_at')
                ->updateAffected(['draft_deleted_at' => $this->now()]);
            if ($claimed === 0) {
                return false;
            }
            $job = $this->jobs()->query()->where('id', $jobId)->limit(1)->rows()[0];
            $drafts = $this->repository('factory.draft');
            $draft = $drafts->findDraft((int) $job['draft_id']);
            if ($draft !== null) {
                $drafts->deleteDraft((int) $job['draft_id']);
                $this->jobs()->query()->where('id', $jobId)->update([
                    'draft_title'       => $draft['title'] ?? null,
                    'draft_category_id' => $draft['category_id'] ?? null,
                ]);
            }
            return true;
        });
    }

    /** Yayın saatinden $cutoff'tan fazla geçmiş, hâlâ eksik işler. @return array<int,array<string,mixed>> */
    public function stuck(string $cutoff): array
    {
        return $this->jobs()->query()
            ->where('job_status', FactoryJobModel::STATUS_DELIVERED)
            ->where('publish_status', FactoryJobModel::PUBLISH_INCOMPLETE)
            ->where('scheduled_at', '<=', $cutoff)
            ->orderBy('scheduled_at', 'ASC')
            ->rows();
    }

    // --- Yenileme (renew) işleri: yazı başına tek satır, taslak yok; içerik yayın saatinde yazının yerine yazılır ---

    /**
     * Yenileme işi olan yazılar (her durumda).
     * @return array<int,array<string,mixed>> post_id => iş satırı
     */
    public function renewalsByPost(string $contentType): array
    {
        $out = [];
        foreach ($this->jobs()->query()->where('kind', FactoryJobModel::KIND_RENEW)->where('content_type', $contentType)->rows() as $row) {
            $out[(int) $row['post_id']] = $row;
        }
        return $out;
    }

    /**
     * $since'ten beri sunulan yenileme işi sayısı (günlük sınır: sunulan + teslim alınan).
     * $staleBefore'dan önce sunulup hâlâ yanıtlanmamış işler sayılmaz: yeniden sunulabilirler.
     */
    public function countRenewalsRequestedSince(string $since, string $staleBefore): int
    {
        $requested = fn(): object => $this->jobs()->query()
            ->where('kind', FactoryJobModel::KIND_RENEW)
            ->where('requested_at', '>=', $since);
        return $requested()->count() - $requested()
            ->where('job_status', FactoryJobModel::STATUS_PENDING)
            ->where('requested_at', '<', $staleBefore)
            ->count();
    }

    /**
     * Yazı için yenileme işi sunum kaydı: varsa günceller (yeniden sunum/deneme), yoksa ekler.
     * INSERT `uq_factory_renew_post` indeksine dayanır: eşzamanlı ikinci istek düşer.
     * @return bool false = iş başka istek tarafından açılmış (bu istek sunmamalı)
     */
    public function requestRenewal(string $contentType, int $postId, array $row): bool
    {
        $values = $row + [
            'error_message' => null,
            'failed_at'     => null,
            'job_status'    => FactoryJobModel::STATUS_PENDING,
            'requested_at'  => $this->now(),
        ];
        $existing = $this->jobs()->query()
            ->where('kind', FactoryJobModel::KIND_RENEW)
            ->where('content_type', $contentType)
            ->where('post_id', $postId)
            ->limit(1)->rows();
        if ($existing !== []) {
            $this->jobs()->query()->where('id', (int) $existing[0]['id'])->update($values);
            return true;
        }
        try {
            $this->jobs()->create($values + [
                'kind'          => FactoryJobModel::KIND_RENEW,
                'content_type'  => $contentType,
                'post_id'       => $postId,
                'renew_post_id' => $postId,
            ]);
        } catch (\Throwable $e) {
            if (str_contains($e->getMessage(), '1062') || stripos($e->getMessage(), 'duplicate') !== false) {
                return false;
            }
            throw $e;
        }
        return true;
    }

    /**
     * Gelen yenileme içeriğini işte BEKLETİR (yazıya dokunmaz) ve işi 'delivered' yapar (tek işlem).
     * Yarışı kaybeden istek null alır.
     * @param callable $nextSlot yenileme yayın zamanı üretici
     * @return array{post_id:int, scheduled_at:string}|null
     */
    public function deliverRenewal(array $job, array $payload, callable $nextSlot): ?array
    {
        return $this->atomic(function () use ($job, $payload, $nextSlot): ?array {
            $claimed = $this->jobs()->query()
                ->where('id', (int) $job['id'])
                ->where('job_status', FactoryJobModel::STATUS_PENDING)
                ->updateAffected(['job_status' => FactoryJobModel::STATUS_DELIVERED, 'delivered_at' => $this->now()]);
            if ($claimed !== 1) {
                return null;
            }
            $slot = $nextSlot($this->takenSlots());
            $this->jobs()->query()->where('id', (int) $job['id'])->update([
                'renew_payload'  => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'scheduled_at'   => $slot,
                'publish_status' => FactoryJobModel::PUBLISH_PENDING,
                'missing_reason' => null,
            ]);
            return ['post_id' => (int) $job['post_id'], 'scheduled_at' => $slot];
        });
    }

    /** Yayın saati gelmiş, teslim edilmiş, henüz yazının yerine yazılmamış yenilemeler. @return array<int,array<string,mixed>> */
    public function dueRenewals(): array
    {
        return $this->jobs()->query()
            ->where('kind', FactoryJobModel::KIND_RENEW)
            ->where('job_status', FactoryJobModel::STATUS_DELIVERED)
            ->whereIn('publish_status', [FactoryJobModel::PUBLISH_PENDING, FactoryJobModel::PUBLISH_INCOMPLETE])
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', $this->now())
            ->orderBy('scheduled_at', 'ASC')
            ->rows();
    }

    /** Yenileme yazıya uygulandı: işi 'published' yapar, bekleyen içeriği siler. */
    public function markRenewed(array $job): void
    {
        $now = $this->now();
        $this->jobs()->query()->where('id', (int) $job['id'])->update([
            'publish_status'  => FactoryJobModel::PUBLISH_PUBLISHED,
            'last_checked_at' => $now,
            'published_at'    => $now,
            'missing_reason'  => null,
            'renew_payload'   => null,
        ]);
    }

    /** Denetimden geçemeyen yenilemeyi bekletir (yazı AKTİF kalır, eski içerik yerinde durur) ve nedeni yazar. */
    public function markRenewalIncomplete(array $job, string $reason): void
    {
        $this->jobs()->query()->where('id', (int) $job['id'])->update([
            'publish_status'  => FactoryJobModel::PUBLISH_INCOMPLETE,
            'last_checked_at' => $this->now(),
            'missing_reason'  => $reason,
        ]);
    }

    /**
     * Yenileme sayıları: toplam yenilenen, $day (Y-m-d) gününde yenilenen, yayın saatini bekleyen, yenilenemeyen (kalıcı hata).
     * @return array{renewed:int, today:int, waiting:int, failed:int}
     */
    public function renewalCounts(string $day): array
    {
        $renewals = fn(): object => $this->jobs()->query()->where('kind', FactoryJobModel::KIND_RENEW);
        return [
            'renewed' => $renewals()->where('publish_status', FactoryJobModel::PUBLISH_PUBLISHED)->count(),
            'today'   => $renewals()
                ->where('publish_status', FactoryJobModel::PUBLISH_PUBLISHED)
                ->where('published_at', '>=', $day . ' 00:00:00')
                ->where('published_at', '<=', $day . ' 23:59:59')
                ->count(),
            'failed'  => $renewals()->where('job_status', FactoryJobModel::STATUS_PERMANENT_FAILURE)->count(),
            'waiting' => $renewals()
                ->where('job_status', FactoryJobModel::STATUS_DELIVERED)
                ->whereIn('publish_status', [FactoryJobModel::PUBLISH_PENDING, FactoryJobModel::PUBLISH_INCOMPLETE])
                ->count(),
        ];
    }

    /** Verilen gün (Y-m-d) yayına çıkan işler. @return array<int,array<string,mixed>> */
    public function publishedOn(string $day): array
    {
        return $this->jobs()->query()
            ->where('kind', FactoryJobModel::KIND_NEW)
            ->where('publish_status', FactoryJobModel::PUBLISH_PUBLISHED)
            ->where('published_at', '>=', $day . ' 00:00:00')
            ->where('published_at', '<=', $day . ' 23:59:59')
            ->orderBy('published_at', 'ASC')
            ->rows();
    }
}
