<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Database\Repositories\Project;

use Rbn\Framework\Core\Base\Data\BaseRepository;

/**
 * ContentRedirectRepository - Eski içerik adresi (301) kayıtlarının veri deposu 🧭📦
 * Tablo (`app_content_redirects`) projenin migration'ı ile kurulur; tablo olmayan projede okuma boş döner,
 * yazma sessizce atlanır (içerik kaydı hiçbir koşulda bu yüzden bozulmaz).
 */
class ContentRedirectRepository extends BaseRepository
{
    /** @var string Hedef Model Bağlantısı 🏺 */
    protected $targetModel = 'app.contentRedirect';

    /**
     * Eski adresi yazıya bağlar (aynı eski adres varsa yeni yazıya taşınır).
     *
     * @return bool kayıt yazıldı mı
     */
    public function record(string $contentType, int $postId, string $oldSlug, string $oldCategorySlug): bool
    {
        if ($oldSlug === '') {
            return false;
        }
        try {
            $model = $this->model($this->targetModel);
            $existing = $this->match($model, $contentType, $oldCategorySlug, $oldSlug)->first();
            if ($existing) {
                $row = is_object($existing) && method_exists($existing, 'toArray') ? $existing->toArray() : (array) $existing;
                return (bool) $model->query()->where('id', (int) $row['id'])->update(['post_id' => $postId]);
            }
            return (bool) $model->create([
                'content_type'      => $contentType,
                'post_id'           => $postId,
                'old_slug'          => $oldSlug,
                'old_category_slug' => $oldCategorySlug,
                'created_at'        => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            return $this->missingTable($e) ? false : throw $e;
        }
    }

    /** Yazının bu adrese geri dönmesi halinde adres artık eski değildir: kaydı siler. */
    public function forget(string $contentType, string $categorySlug, string $slug): void
    {
        try {
            $this->match($this->model($this->targetModel), $contentType, $categorySlug, $slug)->delete();
        } catch (\Throwable $e) {
            if (!$this->missingTable($e)) {
                throw $e;
            }
        }
    }

    /**
     * Kesin eşleşen eski adresin yazı kimliği (zincir yok: hedef her zaman yazının güncel adresidir).
     */
    public function findPostId(string $contentType, string $categorySlug, string $slug): ?int
    {
        try {
            $row = $this->match($this->model($this->targetModel), $contentType, $categorySlug, $slug)->first();
        } catch (\Throwable $e) {
            return $this->missingTable($e) ? null : throw $e;
        }
        if (!$row) {
            return null;
        }
        $row = is_object($row) && method_exists($row, 'toArray') ? $row->toArray() : (array) $row;
        return (int) $row['post_id'] ?: null;
    }

    private function match(object $model, string $contentType, string $categorySlug, string $slug): object
    {
        return $model->query()
            ->where('content_type', $contentType)
            ->where('old_category_slug', $categorySlug)
            ->where('old_slug', $slug);
    }

    private function missingTable(\Throwable $e): bool
    {
        return str_contains($e->getMessage(), '42S02') || str_contains(strtolower($e->getMessage()), "doesn't exist");
    }
}
