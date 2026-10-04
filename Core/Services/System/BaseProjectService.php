<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Services\System;

use Rbn\Framework\Core\Base\Services\BaseService;

/**
 * BaseProjectService - The Sovereign base for Project-level business logic 📡🏛️⚓
 * 
 * RBN 3.5 Masterpiece: Centralized authority for common features across all projects,
 * including social media platforms, static pages, settings mapping, and SSS (FAQs).
 */
class BaseProjectService extends BaseService
{
    /**
     * Projeye ait tüm ortak içerikleri (Pages, FAQs) TEK servis çağrısında döner 🚀
     */
    public function getProjectPayload(array $pageFilters = [], array $faqFilters = []): array
    {
        $pageModel = $this->model('project.page');
        $faqModel  = $this->model('project.faq');

        $pKey = $this->projectKey ?? project_key();

        if (!isset($pageFilters['project_key']) && !empty($pKey)) {
            $pageFilters['project_key'] = $pKey;
        }

        if (is_string($faqFilters)) {
            $faqFilters = ['project_key' => $faqFilters];
        } elseif (is_int($faqFilters)) {
            $faqFilters = ['limit' => $faqFilters];
        } elseif (!is_array($faqFilters)) {
            $faqFilters = [];
        }

        if (!isset($faqFilters['project_key']) && !empty($pKey)) {
            $faqFilters['project_key'] = $pKey;
        }

        return [
            'pages' => $pageModel ? $pageModel->getPages($pageFilters) : [],
            'faqs'  => $faqModel  ? $faqModel->getFaqs($faqFilters) : [],
        ];
    }

    /**
     * Projeye ait aktif sosyal medya verilerini (hem detaylı liste hem URL haritası) TEK seferde döner 🌐
     */
    public function getSocialLinks(): array
    {
        $settingsService   = $this->service('settings');
        $socialmediaService = $this->service('socialmedia');

        $settings    = $settingsService ? ($settingsService->read('social') ?? []) : [];
        $socialmedia = $socialmediaService ? $socialmediaService->allSocialPlatforms() : [];

        $links   = [];
        $socials = [];

        foreach ($settings as $key => $url) {
            if (!empty($url) && isset($socialmedia[$key])) {
                $platform = $socialmedia[$key];
                $links[] = [
                    'key'     => $key,
                    'title'   => $platform['title'],
                    'url'     => $url,
                    'color'   => $platform['color'] ?? '',
                    'bi_icon' => $platform['bi_icon'] ?? '',
                    'fa_icon' => $platform['fa_icon'] ?? 'fas fa-link',
                    'ri_icon' => $platform['ri_icon'] ?? 'ri-link'
                ];
                $socials[$key] = $url;
            }
        }

        return [
            'socialLinks' => $links,
            'socials'     => $socials,
        ];
    }

    /**
     * Herhangi bir makale, haber veya içerik için sosyal medya paylaşım butonlarını socialmedia servisinden çeker 🔗🚀
     * SSoT: Kaynağını tamamen social_media_platforms.json ve SocialMediaProvider'dan alır.
     */
    public function getShareLinks(string $url, string $title = ''): array
    {
        $socialmediaService = $this->service('socialmedia');
        if (!$socialmediaService || !method_exists($socialmediaService, 'getShareButtons')) {
            return [];
        }

        $buttons = $socialmediaService->getShareButtons($url, $title);
        $formatted = [];

        foreach ($buttons as $item) {
            $isMailto = str_starts_with((string)($item['url'] ?? ''), 'mailto:');
            $item['target'] = $isMailto ? '_self' : '_blank';
            $item['rel'] = $isMailto ? '' : 'noopener noreferrer nofollow';
            $item['attr'] = $isMailto ? 'target="_self"' : 'target="_blank" rel="noopener noreferrer nofollow"';
            $item['share_class'] = $item['class'] ?? '';
            $item['label'] = ($item['name'] ?? 'Paylaş') . '\'da Paylaş';

            $formatted[] = $item;
        }

        return $formatted;
    }
}
