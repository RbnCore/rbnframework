<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnFactory\Services;

use Rbn\Framework\Core\Base\Attributes\Component;
use Rbn\Framework\Core\Base\Services\BaseService;
use Rbn\Framework\Core\System\Paths\Paths;

/**
 * FactoryAuditService - Yayın denetçisi 🔍🎻
 * scheduled_at'i gelmiş, pasif teslim edilmiş yazıları denetler. Geçerse aktifleştirir
 * ve (ayarda açıksa) sitenin kendi önbellek temizleme yolunu çağırır; geçmezse pasif tutar + neden yazar.
 */
#[Component(alias: 'factoryAudit', type: 'service')]
class FactoryAuditService extends BaseService
{
    /**
     * @param callable|null $flush site önbellek temizleyici (başarılıysa true)
     * @return array{due:int, published:array, renewed:array, missing:array, drafts_deleted:int, delete_failures:array, permanent_errors:int, renewal:array, images_deleted:int}
     */
    public function run(?callable $flush = null): array
    {
        $this->handler('factorySchema')->ensure();
        $jobs = $this->repository('factory.job');
        $result = ['due' => 0, 'published' => [], 'renewed' => [], 'missing' => [], 'drafts_deleted' => 0, 'delete_failures' => [], 'permanent_errors' => 0, 'renewal' => [], 'images_deleted' => 0];

        $this->cleanupPublishedDrafts($result);
        $due = $jobs->due();
        $result['due'] = count($due);

        foreach ($due as $job) {
            $post = $this->repository('factory.post')->findPost((string) $job['content_type'], (int) $job['post_id']);
            $problems = $post ? $this->check($post, (string) $job['content_type']) : ['yazı satırı yok'];

            if ($problems === []) {
                $jobs->publish($job);
                $cleared = ($flush && $this->handler('factoryConfig')->get('publishing.flush_cache', true)) ? (bool) $flush() : false;
                $this->deleteDraft($job, $result);
                $result['published'][] = ['job_no' => $job['job_no'], 'post_id' => (int) $job['post_id'], 'cache_cleared' => $cleared];
                continue;
            }

            $reason = mb_substr(implode('; ', $problems), 0, 500);
            $jobs->markIncomplete($job, $reason);
            $result['missing'][] = ['job_no' => $job['job_no'], 'reason' => $reason];
        }
        $this->applyRenewals($result, $flush);
        $result['permanent_errors'] = $jobs->countPermanentFailures();
        $result['renewal'] = $this->service('factory')->renewalStatus();
        return $result;
    }

    /**
     * Yayın saati gelmiş yenilemeleri yazının yerine yazar (yazı AKTİF kalır; slug/kategori değişimi 301 kaydını
     * sitenin içerik servisi `savePost()` içinde kendisi yazar). Denetimi geçemeyen yenileme bekletilir, eski içerik yerinde durur.
     * @param callable|null $flush site önbellek temizleyici
     */
    private function applyRenewals(array &$result, ?callable $flush): void
    {
        $jobs = $this->repository('factory.job');
        foreach ($jobs->dueRenewals() as $job) {
            $type = (string) $job['content_type'];
            $postId = (int) $job['post_id'];
            $payload = $this->decode($job['renew_payload'] ?? null);
            $current = $this->repository('factory.post')->findPost($type, $postId);

            if (!is_array($payload)) {
                $problems = ['bekleyen içerik okunamadı'];
            } elseif ($current === null || (int) $current['is_active'] !== 1) {
                $problems = ['yenilenecek yazı yok ya da pasif'];
            } else {
                // Yazının kapağı artık varsa kabul sırasında gelen kapak düşer; yoksa payload'daki kapak denetlenir ve yazılır
                if ($this->hasCover((string) ($current['image'] ?? ''), $type)) {
                    unset($payload['image']);
                }
                $problems = $this->check($payload + ['image' => $current['image'] ?? ''], $type);
            }
            if ($problems === []) {
                try {
                    $this->applyRenewal($type, $postId, $payload, $current);
                    $result['images_deleted'] += $this->deleteStaleImages($type, $postId, $current, $payload);
                } catch (\Throwable $e) {
                    $problems = ['yazıya yazılamadı: ' . mb_substr($e->getMessage(), 0, 200)];
                }
            }
            if ($problems !== []) {
                $reason = mb_substr(implode('; ', $problems), 0, 500);
                $jobs->markRenewalIncomplete($job, $reason);
                $result['missing'][] = ['job_no' => $job['job_no'], 'reason' => $reason];
                continue;
            }

            $jobs->markRenewed($job);
            $cleared = ($flush && $this->handler('factoryConfig')->get('publishing.flush_cache', true)) ? (bool) $flush() : false;
            $result['renewed'][] = [
                'job_no'        => $job['job_no'],
                'post_id'       => $postId,
                'slug_changed'  => (string) $current['slug'] !== (string) $payload['slug'],
                'cache_cleared' => $cleared,
            ];
        }
    }

    /** Bekleyen yeni içeriği sitenin içerik servisiyle (panelin kullandığı `savePost`) yazıya uygular. */
    private function applyRenewal(string $contentType, int $postId, array $payload, array $current): void
    {
        $name = (string) $this->handler('factoryConfig')->get('publishing.cache_service', '');
        $service = $name !== '' ? $this->service($name) : null;
        if (!$service || !method_exists($service, 'savePost')) {
            throw new \RuntimeException('site içerik servisi yok (publishing.cache_service)');
        }
        // `image` (kapak) yalnız kapaksız yazıda payload'da bulunur (kabulde/uygulamada süzülür); kapaklı yazıda değişmez. `images` yalnız sütun varsa (sonuç kabulünde denetlenir)
        $fields = ['title', 'image', 'slug', 'summary', 'content', 'images', 'seo_title', 'seo_description', 'seo_keywords', 'faqs', 'cta', 'pros_cons', 'author_comment'];
        // is_rewritten: sitenin mevcut "yeniden yazıldı" işareti; içerikle AYNI güncellemede 1 olur
        $service->savePost(['id' => $postId, 'is_active' => 1, 'is_rewritten' => 1] + array_intersect_key($payload, array_flip($fields)));

        // savePost etkilenen satır sayısını güvenilir dönmez; yazının gerçekten güncellendiği okuyarak doğrulanır
        $saved = $this->repository('factory.post')->findPost($contentType, $postId);
        if ($saved === null || (string) $saved['slug'] !== (string) $payload['slug'] || (string) $saved['title'] !== (string) $payload['title']) {
            throw new \RuntimeException('güncelleme doğrulanamadı');
        }
    }

    /**
     * Yenilemeden sonra eski yazı içi görsel dosyalarını siler: eski içerikteki gömülü `<img>`'ler ve eski `images`
     * listesi; yenilemeyle gelen görseller, kapak ve başka yazıda geçen dosyalar HARİÇ. Yalnız görsel dizini altındaki
     * yerel dosyalar silinir; silinemeyen dosya yenilemeyi bozmaz (günlüğe yazılır).
     * @param array<string,mixed> $current yenilemeden ÖNCEKİ yazı satırı
     * @return int silinen dosya sayısı
     */
    private function deleteStaleImages(string $contentType, int $postId, array $current, array $payload): int
    {
        $imageDir = trim((string) $this->handler('factoryConfig')->get('content_types.' . $contentType . '.image_dir', ''), '/');
        if ($imageDir === '') {
            return 0;
        }
        $key = static fn(string $src): string => ltrim((string) preg_replace('#^(?:https?:)?//[^/]+#i', '', trim($src)), '/');
        $keep = array_map($key, array_merge($this->postImageSources($payload['images'] ?? null), [(string) ($current['image'] ?? '')]));
        $old = array_merge($this->contentImages((string) ($current['content'] ?? '')), $this->postImageSources($current['images'] ?? null));

        $posts = $this->repository('factory.post');
        $deleted = 0;
        foreach (array_unique(array_map($key, $old)) as $path) {
            $path = strtok($path, '?#');
            if ($path === '' || in_array($path, $keep, true) || !str_starts_with($path, $imageDir . '/') || $posts->imageInUse($contentType, $path, $postId)) {
                continue;
            }
            $local = $this->localPath($path, $contentType);
            if ($local === null || $local === '' || !is_file($local)) {
                continue;
            }
            if (@unlink($local)) {
                $deleted++;
            } else {
                error_log('[factory] eski görsel silinemedi: ' . $path);
            }
        }
        return $deleted;
    }

    /** @return string[] `images` sütunundaki (JSON ya da dizi) görsel yolları */
    private function postImageSources(mixed $images): array
    {
        $list = $this->decode($images);
        if (!is_array($list)) {
            return [];
        }
        $sources = [];
        foreach ($list as $item) {
            $src = is_array($item) ? trim((string) ($item['src'] ?? '')) : '';
            if ($src !== '') {
                $sources[] = $src;
            }
        }
        return $sources;
    }

    /** @return string[] sorunlar; boş = yayına hazır */
    public function check(array $post, string $contentType): array
    {
        $config = $this->handler('factoryConfig');
        $problems = [];

        foreach ((array) $config->get('rules.required_fields', []) as $field) {
            if ($field === 'faqs' || $field === 'content') {
                continue; // aşağıda ayrıca
            }
            if (trim((string) ($post[$field] ?? '')) === '') {
                $problems[] = "alan boş: {$field}";
            }
        }

        $words = $this->wordCount((string) ($post['content'] ?? ''));
        $minWords = (int) $config->get('rules.min_words', 0);
        if ($words < $minWords) {
            $problems[] = "içerik {$words} kelime (en az {$minWords})";
        }

        $faqs = $this->decode($post['faqs'] ?? null);
        $faqCount = (int) $config->get('rules.faq_count', 0);
        $validFaqs = is_array($faqs) ? array_filter($faqs, static fn($q): bool => is_array($q) && trim((string) ($q['question'] ?? '')) !== '' && trim((string) ($q['answer'] ?? '')) !== '') : [];
        if (count($validFaqs) !== $faqCount || count((array) $faqs) !== $faqCount) {
            $problems[] = 'faqs ' . (is_array($faqs) ? count($faqs) : 0) . " adet (tam {$faqCount} olmalı)";
        }

        $optional = (array) $config->get('rules.optional_fields', []);
        if (!empty($optional['cta'])) {
            $cta = $this->decode($post['cta'] ?? null);
            if (!is_array($cta) || trim((string) ($cta['title'] ?? '')) === '' || trim((string) ($cta['description'] ?? '')) === '' || trim((string) ($cta['url'] ?? '')) === '') {
                $problems[] = 'cta eksik';
            }
        }
        if (!empty($optional['pros_cons'])) {
            $prosCons = $this->decode($post['pros_cons'] ?? null);
            if (!is_array($prosCons) || trim((string) ($prosCons['title'] ?? '')) === '' || empty($prosCons['pros']) || empty($prosCons['cons'])) {
                $problems[] = 'pros_cons eksik';
            }
        }
        if (!empty($optional['author_comment']) && trim((string) ($post['author_comment'] ?? '')) === '') {
            $problems[] = 'author_comment boş';
        }

        foreach ($this->handler('factoryInternalLink')->brokenTargets((string) ($post['content'] ?? '')) as $target) {
            $problems[] = 'kırık iç bağlantı: ' . $target;
        }

        // Görseller: kapak + içerik <img>'leri sunucuda FİZİKSEL olarak var mı
        $cover = trim((string) ($post['image'] ?? ''));
        if ($cover === '') {
            $problems[] = 'kapak görseli tanımsız';
        } elseif (!$this->fileExists($cover, $contentType)) {
            $problems[] = "görsel yok: kapak {$cover}";
        }
        foreach ($this->contentImages((string) ($post['content'] ?? '')) as $src) {
            $local = $this->localPath($src, $contentType);
            if ($local !== null && !is_file($local)) {
                $problems[] = "görsel yok: {$src}";
            }
        }
        // Yazı içi görseller (`images` sütunu): dosya gerçekten var mı
        foreach ($this->postImageSources($post['images'] ?? null) as $src) {
            if (!$this->fileExists($src, $contentType)) {
                $problems[] = "görsel yok: {$src}";
            }
        }
        return $problems;
    }

    public function wordCount(string $html): int
    {
        $text = html_entity_decode(strip_tags(str_replace(['<br>', '</p>', '</li>', '</h2>', '</h3>'], ' ', $html)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return count(preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY) ?: []);
    }

    /** Yayın anında geçici DB hatası olmuşsa sonraki cron turunda taslak silmeyi tamamlar. */
    private function cleanupPublishedDrafts(array &$result): void
    {
        foreach ($this->repository('factory.job')->publishedWithDraft() as $job) {
            $this->deleteDraft($job, $result);
        }
    }

    private function deleteDraft(array $job, array &$result): void
    {
        try {
            if ($this->repository('factory.job')->deletePublishedDraft((int) $job['id'])) {
                $result['drafts_deleted']++;
            }
        } catch (\Throwable $e) {
            $result['delete_failures'][] = ['job_no' => $job['job_no'], 'reason' => $e->getMessage()];
        }
    }

    private function decode(mixed $value): mixed
    {
        if (is_array($value)) {
            return $value;
        }
        return is_string($value) && $value !== '' ? json_decode($value, true) : null;
    }

    /** @return string[] */
    private function contentImages(string $html): array
    {
        preg_match_all('/<img\b[^>]*?\bsrc\s*=\s*(["\'])(.*?)\1/is', $html, $matches);
        return array_values(array_unique($matches[2] ?? []));
    }

    /**
     * Yerel dosya yolu; yerel olmayan (başka sunucu, data:) için null (denetlenmez).
     * '' = güvensiz yol (var sayılmaz). Çözülen gerçek yol (symlink dahil) public kökün altında kalmalıdır;
     * yol ayarlı görsel dizinine düşüyorsa o dizinin gerçek yolu altında da kalmalıdır.
     */
    private function localPath(string $src, string $contentType): ?string
    {
        $src = trim($src);
        if ($src === '' || str_starts_with($src, 'data:')) {
            return null;
        }
        if (preg_match('#^(?:https?:)?//([^/]+)(/.*)?$#i', $src, $matches)) {
            $hosts = array_map('strtolower', (array) $this->handler('factoryConfig')->get('site_hosts', []));
            if (!in_array(strtolower($matches[1]), $hosts, true)) {
                return null;
            }
            $src = $matches[2] ?? '/';
        }
        $path = rawurldecode(strtok($src, '?#'));
        if (str_contains($path, '..')) {
            return '';
        }
        $root = rtrim(Paths::publicRoot(), '/\\');
        $candidate = $root . '/' . ltrim($path, '/');
        $real = realpath($candidate);
        if ($real === false) {
            return $candidate; // yok: is_file() false döner
        }
        $bounds = [$root];
        $imageDir = trim((string) $this->handler('factoryConfig')->get('content_types.' . $contentType . '.image_dir', ''), '/');
        if ($imageDir !== '' && str_starts_with(ltrim($path, '/'), $imageDir . '/')) {
            $bounds[] = $root . '/' . $imageDir;
        }
        foreach ($bounds as $bound) {
            $realBound = realpath($bound);
            if ($realBound === false || !$this->isInside($real, $realBound)) {
                return '';
            }
        }
        return $real;
    }

    private function isInside(string $path, string $root): bool
    {
        $root = rtrim(str_replace('\\', '/', $root), '/') . '/';
        $path = str_replace('\\', '/', $path);
        return PHP_OS_FAMILY === 'Windows' ? stripos($path, $root) === 0 : str_starts_with($path, $root);
    }

    /** Yazının kapağı dolu VE dosyası sunucuda fiziksel olarak var mı (yayın denetimiyle aynı yol). */
    public function hasCover(string $image, string $contentType): bool
    {
        return trim($image) !== '' && $this->fileExists(trim($image), $contentType);
    }

    private function fileExists(string $src, string $contentType): bool
    {
        $local = $this->localPath($src, $contentType);
        return $local !== null && $local !== '' && is_file($local);
    }
}
