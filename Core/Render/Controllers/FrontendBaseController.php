<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Render\Controllers;

use Rbn\Framework\Core\Base\Web\BaseController;
use Rbn\Framework\Core\Base\Services\Traits\Service\Blogcontent\ContentMetaTrait;
use Rbn\Framework\Core\Support\Bridges\Traits\NormalizationTrait;
use Rbn\Framework\Core\Support\Definitions\System\FrameworkIdentity;

/**
 * FrontendBaseController - Core Sovereign Master Base Controller for Frontend Requests 🏛️🛰️⚓
 * RBN 3.5 Masterpiece Standard.
 * 
 * Pre-packages site identity, company, social, and contact settings into controller state
 * with ZERO redundant database queries.
 */
abstract class FrontendBaseController extends BaseController
{
    use NormalizationTrait, ContentMetaTrait;
    /**
     * @var string Class name for SeoData
     */
    protected string $seoData = '';

    /**
     * @var array Holds global localBusiness schema data to be dynamically chained in render
     */
    protected array $localBusinessData = [];

    /**
     * Sovereign After Boot Lifecycle Hook 🚀
     */
    protected function afterBoot(): void
    {
        $settings = $this->service('settings');

        $site = $settings ? ($settings->read('site') ?? []) : [];
        $company = $settings ? ($settings->read('company') ?? []) : [];
        $social = $settings ? ($settings->read('social') ?? []) : [];
        $contact = $settings ? ($settings->read('contact') ?? []) : [];

        $hasFeed = false;
        try {
            $crawlerProvider = $this->provider('crawler');
            if ($crawlerProvider && method_exists($crawlerProvider, 'hasFeed')) {
                $hasFeed = (bool) $crawlerProvider->hasFeed();
            }
        } catch (\Throwable $e) {
            // Fail silently
        }

        $this->set([
            'appName' => $this->appName,
            'projectKey' => $this->projectKey,
            'site' => $site,
            'company' => $company,
            'social' => $social,
            'contact' => $contact,
            'hasFeed' => $hasFeed,
            'siteName' => trim((string) ($site['name'] ?? '')) ?: 'RBN Core',
            'siteSlogan' => trim((string) ($site['slogan'] ?? '')),
            'siteDesc' => trim((string) ($site['description'] ?? '')),
            'siteRoot' => $this->request ? $this->request->root() : '',
            'rbn_url' => FrameworkIdentity::FRAMEWORK_URL,
            'rbn_name' => FrameworkIdentity::FRAMEWORK_NAME,
            'developer_url' => FrameworkIdentity::DEVELOPER_URL,
            'developer_name' => FrameworkIdentity::DEVELOPER_NAME,
        ]);

        $this->onGroupBoot();
        $this->onAfterBoot();
    }

    /**
     * Unified Sovereign Hook Methods for Group Base & Concrete Module Controllers 🚀
     */
    protected function onGroupBoot(): void
    {
    }
    protected function onAfterBoot(): void
    {
    }

    /**
     * Standardized Unified Controller Asset Helper 🎨
     * Accepts single string, array of assets, or variadic arguments.
     * e.g. $this->addAsset('@font/Outfit', '@font/JetBrains Mono', 'js/frontend.js')
     * e.g. $this->addAsset(['@font/Outfit', '@font/JetBrains Mono', 'js/frontend.js'])
     */
    public function addAsset($assets, ...$moreAssets): self
    {
        $items = [];

        if (is_array($assets)) {
            $items = $assets;
        } else {
            $items[] = $assets;
        }

        if (!empty($moreAssets)) {
            foreach ($moreAssets as $item) {
                if (is_array($item)) {
                    $items = array_merge($items, $item);
                } else {
                    $items[] = $item;
                }
            }
        }

        $preparedItems = [];
        foreach ($items as $path) {
            if (!is_string($path) || empty($path)) {
                continue;
            }

            // Eğer tanımlı bir Bundle adı değilse ve @, http, / ile başlamıyorsa @project/ yap
            $isBundle = isset(\Rbn\Framework\Core\Support\Definitions\Render\AssetBundles::BUNDLES[$path]);
            if (!$isBundle && !str_starts_with($path, '@') && !str_starts_with($path, 'http') && !str_starts_with($path, '/')) {
                $path = '@project/' . ltrim($path, '/');
            }

            $preparedItems[] = $path;
        }

        if (!empty($preparedItems)) {
            $this->service('asset')->prepare($preparedItems, 'frontend');
        }

        return $this;
    }

    /**
     * Standardized Controller Footer Categories Helper 🏷️
     */
    public function footerCategories(array $categories, string $showInKey = 'show_in'): array
    {
        $filtered = array_values(array_filter($categories, function ($cat) use ($showInKey) {
            return (int) ($cat[$showInKey] ?? 1) === 1;
        }));

        return !empty($filtered) ? $filtered : $categories;
    }

    /**
     * Standardized Controller Footer Pages Helper 📄
     */
    public function footerPages(array $pages, string $showInKey = 'show_in_footer'): array
    {
        $filtered = array_values(array_filter($pages, function ($page) use ($showInKey) {
            return (int) ($page[$showInKey] ?? 1) === 1;
        }));

        return !empty($filtered) ? $filtered : $pages;
    }

    /**
     * Standardized Controller Share Links Helper 🔗🚀
     * Generates social share links (Facebook, Twitter, WhatsApp, Telegram, Email) for posts/articles.
     */
    public function getShareLinks(string $url, string $title = ''): array
    {
        return $this->service('base.project')->getShareLinks($url, $title);
    }

    /**
     * Standardized Controller AdSettings Helper 🎯
     * Loads and binds AdSense and advertisement slot configurations into view state.
     */
    public function loadAdSettings(): self
    {
        $settings = $this->service('settings');
        $integrations = $settings ? ($settings->read('integrations') ?? []) : [];

        $adSettings = [
            'adsense_status' => (int) ($integrations['adsense_status'] ?? 0),
            'adsense_client_id' => $integrations['adsense_client_id'] ?? '',
            'ads_slot_feed' => $integrations['ads_slot_feed'] ?? '',
            'ads_slot_sidebar' => $integrations['ads_slot_sidebar'] ?? '',
            'ads_slot_content_top' => $integrations['ads_slot_content_top'] ?? '',
            'ads_slot_content_bottom' => $integrations['ads_slot_content_bottom'] ?? '',
            'ads_slot_left_skyscraper' => $integrations['ads_slot_left_skyscraper'] ?? '',
            'ads_slot_right_skyscraper' => $integrations['ads_slot_right_skyscraper'] ?? '',
        ];

        $this->set([
            'integrations' => $integrations,
            'adSettings' => $adSettings
        ]);

        return $this;
    }

    /**
     * Check physical existence of a view file 🔍
     */
    protected function viewExists(string $view): bool
    {
        try {
            $viewProvider = $this->provider('view');
            if ($viewProvider && method_exists($viewProvider, 'viewResolver')) {
                $fullPath = $viewProvider->viewResolver()->resolve($view);
                return !empty($fullPath) && file_exists($fullPath);
            }
        } catch (\Throwable $e) {
            // Fail silently
        }
        return false;
    }

    /**
     * RBN 3.5: [SOVEREIGN VIEW RESOLUTION] 🛰️⚓ (Masterpiece Render Engine)
     */
    public function render(string $view, $data = [], $mergeData = []): \Rbn\Framework\Core\Render\View
    {
        // 🚀 Dynamic placeholder replacement for pages (SSoT)
        if (isset($data['legalPage']['content'])) {
            $data['legalPage']['content'] = str_replace(
                ['{appName}', '{year}'],
                [$this->appName, date('Y')],
                $data['legalPage']['content']
            );
        }

        $viewObj = parent::render($view, (array) $data, (array) $mergeData);

        // 🎼 Automatically bind SEO metadata & Page Header data if defined in the subclass and config exists for the view
        if (!empty($this->seoData) && class_exists($this->seoData)) {
            $seo = $this->seoData::get($view);
            if ($seo) {
                $viewObj->metaseo(
                    $seo['title'] ?? '',
                    $seo['description'] ?? '',
                    $seo['keywords'] ?? ''
                );

                // 🏷️ Otonom Page Header Enjeksiyonu (Controller'da belirtilmediyse SeoData'dan otomatik doldur)
                if (empty($data['pageTitle']) && !empty($seo['header_title'] ?? $seo['title'])) {
                    $viewObj->with('pageTitle', $seo['header_title'] ?? $seo['title']);
                }
                if (empty($data['pageSubtitle']) && !empty($seo['subtitle'] ?? $seo['description'])) {
                    $viewObj->with('pageSubtitle', $seo['subtitle'] ?? $seo['description']);
                }
                if (empty($data['pageEyebrow']) && !empty($seo['eyebrow'])) {
                    $viewObj->with('pageEyebrow', $seo['eyebrow']);
                }
            }
        }


        if (!empty($this->localBusinessData)) {
            $viewObj->schema('localBusiness', $this->localBusinessData);
        }

        return $viewObj;
    }
}
