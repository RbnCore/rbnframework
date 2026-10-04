<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Render\Handlers;

use Rbn\Framework\Core\Base\BaseComponent;

/**
 * SchemaPreset - Pure declarative single-entry preset orchestrator 🎨🛡️
 */
class SchemaPreset extends BaseComponent
{
    /**
     * Dışarıya açık TEK metot. Tüm şema istekleri buraya gelir.
     */
    public function execute(object $manager, string $type, array $data = []): void
    {
        $resolver = $this->resolver('schema');
        $meta = $resolver ? $resolver->meta() : [];
        $builder = $this->handler('schemaBuilder')->reset();

        // 1. Liste sayfalarında sadece breadcrumbs yeterlidir
        if ($type === 'blogList' || $type === 'blogCategory') {
            $builder->breadcrumbs($resolver ? $resolver->breadcrumbs($meta['title'] ?? '') : []);
            $manager->schemas = array_merge($manager->schemas, $builder->getSchemas());
            return;
        }

        // Auto-resolve missing or empty image parameter using SchemaResolver 🖼️
        if (empty($data['image'])) {
            $data['image'] = $resolver ? $resolver->resolveDefaultImage() : url('images/og-image.png');
        }

        // 2. Tipe göre şemayı akıcı (fluent) olarak inşa et
        match ($type) {
            'contact' => $builder->contact([
                'name' => $meta['title'] ?? 'İletişim',
                'description' => ($meta['description'] ?? '') ?: (($meta['appName'] ?? '') . ' İletişim.'),
                'url' => $meta['url'] ?? ''
            ]),
            'blogPost' => $builder->blogPost([
                'headline' => $meta['title'] ?? '',
                'description' => $meta['description'] ?? '',
                'image' => $data['image'] ?? '',
                'datePublished' => $data['created_at'] ?? date('Y-m-d'),
                'authorName' => $meta['appName'] ?? ''
            ]),
            'seriesDetail' => $builder->seriesDetail($data),
            'movieDetail' => $builder->movieDetail($data),
            'localBusiness' => $builder->localBusiness($data),
            'product' => $builder->product($data),
            
            default => $builder->schema($data['type'] ?? 'WebPage', array_merge([
                'name' => $meta['title'] ?? '',
                'description' => $meta['description'] ?? '',
                'url' => $meta['url'] ?? ''
            ], array_diff_key($data, array_flip(['type', 'faqs', 'pros_cons']))))
        };

        // 3. Ortak Adım: Her sayfa için breadcrumbs ekle
        $breadcrumbsTitle = $data['name'] ?? ($meta['title'] ?? '');
        $builder->breadcrumbs($resolver ? $resolver->breadcrumbs($breadcrumbsTitle) : []);

        // 4. Ortak Adım: Varsa SSS'leri ekle
        if (!empty($data['faqs'])) {
            $builder->faq($data['faqs']);
        }

        // 4.5. Ortak Adım: Varsa Kıyaslama (Pros & Cons) Şemasını ekle 📊🚀
        if (!empty($data['pros_cons'])) {
            $builder->prosCons($data, $meta);
        }

        // 5. Transfer built schemas to the manager container
        $manager->schemas = array_merge($manager->schemas, $builder->getSchemas());
    }
}
