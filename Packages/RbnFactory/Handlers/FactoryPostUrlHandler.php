<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnFactory\Handlers;

use Rbn\Framework\Core\Base\BaseComponent;

/**
 * FactoryPostUrlHandler - Yazı adresinin TEK üreticisi 🔗
 * Kural içerik türünün `url_prefix` ayarından gelir: /{ön ek}/{kategori_slug}/{yazı_slug};
 * kategorisi olmayan yazıda /{ön ek}/{yazı_slug}. Adres başka yerde elle kurulmaz;
 * Telegram, iç bağlantı adayları ve kırık bağlantı denetimi buradan geçer.
 */
class FactoryPostUrlHandler extends BaseComponent
{
    /** @var array<int,string>|null kategori id => slug (süreç başına bir sorgu) */
    private static ?array $categorySlugs = null;

    /** Ön ek (kenar eğik çizgisiz): /blog/... için "blog" */
    public function prefix(string $contentType): string
    {
        return trim((string) $this->handler('factoryConfig')->get('content_types.' . $contentType . '.url_prefix', ''), '/');
    }

    /** Site köküne göre yol: /ön-ek/kategori/yazi */
    public function path(string $contentType, string $slug, int $categoryId): string
    {
        $prefix = $this->prefix($contentType);
        $category = trim($this->categorySlugs()[$categoryId] ?? '', '/');
        return '/' . ($prefix !== '' ? $prefix . '/' : '')
            . ($category !== '' ? rawurlencode($category) . '/' : '')
            . rawurlencode(ltrim($slug, '/'));
    }

    /** Mutlak adres; taban adres ayarsızsa boş döner. */
    public function absolute(string $contentType, string $slug, int $categoryId, string $baseUrl): string
    {
        $baseUrl = rtrim($baseUrl, '/');
        return $baseUrl === '' ? '' : $baseUrl . $this->path($contentType, $slug, $categoryId);
    }

    /** Kategori kimliğinden ad (bildirimler için). */
    public function categoryName(int $categoryId): ?string
    {
        return $this->categoryRows()[$categoryId]['name'] ?? null;
    }

    /** Kategori kimliğinden slug (kategori yoksa boş metin). */
    public function categorySlug(int $categoryId): string
    {
        return $this->categorySlugs()[$categoryId] ?? '';
    }

    /** Uzun süreli süreçler için önbelleği boşaltır. */
    public function reset(): void
    {
        self::$categorySlugs = null;
    }

    /** @return array<int,string> */
    private function categorySlugs(): array
    {
        if (self::$categorySlugs === null) {
            self::$categorySlugs = [];
            foreach ($this->categoryRows() as $id => $row) {
                self::$categorySlugs[$id] = (string) ($row['slug'] ?? '');
            }
        }
        return self::$categorySlugs;
    }

    /** @return array<int,array<string,mixed>> kategori id => satır */
    private function categoryRows(): array
    {
        $rows = $this->repository('app.contentCategory')->getCategories([
            'project_key' => $this->handler('factoryConfig')->projectKey(),
            'is_active'   => 'all',
        ]) ?? [];
        $out = [];
        foreach ($rows as $row) {
            $row = (array) $row;
            $out[(int) ($row['id'] ?? 0)] = $row;
        }
        return $out;
    }
}
