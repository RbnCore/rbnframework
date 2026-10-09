<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnApi\Services;

use Rbn\Framework\Core\Base\Services\BaseService;

/**
 * FacebookService - RBN Framework Facebook Page & Graph API Orchestrator 🌐📘⚓
 * 
 * RBN Framework: High-level interface for Facebook Page publishing & operations.
 */
class FacebookService extends BaseService
{
    /**
     * Facebook API'sine ham çağrı yapar. 🚀
     */
    public function call(string $method, array $params = [], string $httpMethod = 'GET', ?string $projectKey = null): array
    {
        return $this->provider('apiFacebook')->call($method, $params, $httpMethod, $projectKey);
    }

    /**
     * Facebook Sayfa Profil/Detay bilgilerini getirir. 👤
     */
    public function getPageInfo(string $pageId = 'me', array $fields = ['id', 'name', 'instagram_business_account'], ?string $projectKey = null): array
    {
        return $this->call($pageId, ['fields' => implode(',', $fields)], 'GET', $projectKey);
    }

    /**
     * Facebook Sayfa Duvarına Yazı/Link paylaşır (Feed Post). 📰
     * 
     * @param string $pageId
     * @param string $message Paylaşım metni / caption
     * @param string|null $link Paylaşılacak link (opsiyonel)
     * @param string|null $projectKey
     * @return array
     */
    public function postFeed(string $pageId, string $message, ?string $link = null, ?string $projectKey = null): array
    {
        $params = ['message' => $message];
        if (!empty($link)) {
            $params['link'] = $link;
        }

        return $this->call($pageId . '/feed', $params, 'POST', $projectKey);
    }

    /**
     * Facebook Sayfasına Görselli Post paylaşır. 🖼️
     * 
     * @param string $pageId
     * @param string $imageUrl Herkese açık görsel URL'i
     * @param string $caption Görsel altı açıklama/yazı
     * @param string|null $projectKey
     * @return array
     */
    public function postPhoto(string $pageId, string $imageUrl, string $caption = '', ?string $projectKey = null): array
    {
        $params = [
            'url' => $imageUrl,
            'caption' => $caption
        ];

        return $this->call($pageId . '/photos', $params, 'POST', $projectKey);
    }
}
