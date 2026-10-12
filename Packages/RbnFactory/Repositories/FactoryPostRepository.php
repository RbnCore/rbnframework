<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnFactory\Repositories;

use Rbn\Framework\Core\Base\Attributes\Component;
use Rbn\Framework\Core\Base\Data\BaseRepository;

/**
 * FactoryPostRepository - Sitenin yazı tablosu erişimi (içerik türüne göre tablo) 📰📦
 */
#[Component(alias: 'factory.post', type: 'repository')]
class FactoryPostRepository extends BaseRepository
{
    /** @var string Hedef model takma adı */
    protected $targetModel = 'factory.post';

    private function posts(string $contentType): object
    {
        return $this->model('factory.post')
            ->withProjectScope($this->handler('factoryConfig')->projectKey())
            ->forContentType($contentType);
    }

    /** İçerik türünün yazı tablosunda bu sütun var mı? (isteğe bağlı sütunlar: ör. `images`) */
    public function hasColumn(string $contentType, string $column): bool
    {
        return $this->posts($contentType)->hasColumn($column);
    }

    /**
     * Görsel yolu (başındaki `/` olmadan) başka bir yazının kapağında, içeriğinde ya da `images` listesinde geçiyor mu?
     * `_` ve `%` LIKE joker sayıldığından eşleşme fazla geniş olabilir; silme kararı için güvenli yöndedir.
     */
    public function imageInUse(string $contentType, string $path, int $exceptId): bool
    {
        $needle = '%' . ltrim($path, '/') . '%';
        $columns = ['image', 'content'];
        if ($this->hasColumn($contentType, 'images')) {
            $columns[] = 'images';
        }
        foreach ($columns as $column) {
            if ($this->posts($contentType)->query()->where('id', '<>', $exceptId)->where($column, 'LIKE', $needle)->exists()) {
                return true;
            }
        }
        return false;
    }

    /** @return string[] */
    public function slugs(string $contentType): array
    {
        return array_column($this->posts($contentType)->query()->select('slug')->rows(), 'slug');
    }

    public function slugExists(string $contentType, string $slug): bool
    {
        return $this->posts($contentType)->query()->where('slug', $slug)->exists();
    }

    /** @return array<string,mixed>|null */
    public function findPost(string $contentType, int $id): ?array
    {
        $rows = $this->posts($contentType)->query()->where('id', $id)->limit(1)->rows();
        return $rows[0] ?? null;
    }

    /**
     * @param int[] $ids
     * @return array<int,array<string,mixed>> id => satır
     */
    public function findMany(string $contentType, array $ids): array
    {
        if ($ids === []) {
            return [];
        }
        $out = [];
        foreach ($this->posts($contentType)->query()->whereIn('id', $ids)->rows() as $row) {
            $out[(int) $row['id']] = $row;
        }
        return $out;
    }

    /** Yeni yazıyı ekler; kiracı anahtarı model kapsamından yazılır. */
    public function insertPost(string $contentType, array $data): int
    {
        return (int) $this->posts($contentType)->create($data);
    }

    public function setActive(string $contentType, int $id, bool $active): void
    {
        $this->posts($contentType)->query()->where('id', $id)->update(['is_active' => $active ? 1 : 0]);
    }

    /**
     * İç bağlantı adayı olabilecek AKTİF yazılar (taslak slug'ı hariç).
     * @return array<int,array<string,mixed>>
     */
    public function activeForLinks(string $contentType, string $excludeSlug): array
    {
        return $this->posts($contentType)->query()
            ->select('title, slug, summary, seo_keywords, category_id')
            ->where('is_active', 1)
            ->where('slug', '<>', $excludeSlug)
            ->where('slug', '<>', '')
            ->rows();
    }

    /**
     * Yenilenmeye aday AKTİF ve henüz yenilenmemiş (`is_rewritten` = 0) yazılar, yayın tarihine göre (varsayılan en eski önce).
     * Yeni yazı işiyle (kind=new) oluşmuş yazılar aday değildir: yenilenmeleri istenmez.
     * @return array<int,array<string,mixed>> id, title, slug, image, category_id, created_at
     */
    public function activeByAge(string $contentType, bool $oldestFirst = true): array
    {
        return $this->posts($contentType)->query()
            ->select('id, title, slug, image, category_id, created_at')
            ->where('is_active', 1)
            ->where('is_rewritten', 0)
            ->where('slug', '<>', '')
            ->whereNotIn('id', $this->repository('factory.job')->newPostIds($contentType))
            ->orderBy('created_at', $oldestFirst ? 'ASC' : 'DESC')
            ->orderBy('id', $oldestFirst ? 'ASC' : 'DESC')
            ->rows();
    }

    /** @return array<string,mixed>|null aktif yazı (slug ile) */
    public function findActiveBySlug(string $contentType, string $slug): ?array
    {
        $rows = $this->posts($contentType)->query()->where('slug', $slug)->where('is_active', 1)->limit(1)->rows();
        return $rows[0] ?? null;
    }

    /**
     * Benzersizlik kapısı için en yeni yazılar (aktif + pasif), en yeni önce.
     * @return array<int,array<string,mixed>> slug, seo_title, title
     */
    public function recent(string $contentType, int $limit): array
    {
        return $this->posts($contentType)->query()
            ->select('slug, seo_title, title')
            ->orderBy('id', 'DESC')
            ->limit($limit)
            ->rows();
    }

    /**
     * Kategori dengesi için yazı özeti (id, category_id, is_active).
     * @return array<int,array<string,mixed>>
     */
    public function balanceRows(string $contentType): array
    {
        return $this->posts($contentType)->query()->select('id, category_id, is_active')->rows();
    }
}
