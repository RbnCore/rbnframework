<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnApi\Services;

use Rbn\Framework\Core\Base\Services\BaseService;

/**
 * InstagramService - RBN Framework Instagram Orchestrator 📸🛰️⚓
 * 
 * RBN Framework: High-level interface for Meta Graph API operations.
 * @property \Rbn\Framework\Packages\RbnApi\Providers\InstagramProvider $instagram
 */
class InstagramService extends BaseService
{
    /**
     * Instagram API'sine ham çağrı yapar. 🚀
     */
    public function call(string $method, array $params = [], string $httpMethod = 'GET', ?string $projectKey = null): array
    {
        return $this->provider('apiInstagram')->call($method, $params, $httpMethod, $projectKey);
    }

    /**
     * Instagram kullanıcı profilini getirir. 👤
     */
    public function getProfile(string $userId = 'me', array $fields = ['id', 'username', 'account_type'], ?string $projectKey = null): array
    {
        return $this->call($userId, ['fields' => implode(',', $fields)], 'GET', $projectKey);
    }

    /**
     * Medya paylaşımı (Image/Video) için ilk adımı atar. 🎬
     */
    public function createMediaContainer(string $instagramAccountId, array $params, ?string $projectKey = null): array
    {
        return $this->call($instagramAccountId . '/media', $params, 'POST', $projectKey);
    }

    /**
     * Üretilen medya container'ını yayınlar. 🚀
     */
    public function publishMedia(string $instagramAccountId, string $creationId, ?string $projectKey = null): array
    {
        return $this->call($instagramAccountId . '/media_publish', ['creation_id' => $creationId], 'POST', $projectKey);
    }

    /**
     * Tek adımda Instagram Görseli Paylaşır (Container Create + Publish). 📸🚀
     */
    public function publishPhoto(string $instagramAccountId, string $imageUrl, string $caption = '', ?string $projectKey = null): array
    {
        // 1. Container oluştur
        $containerRes = $this->createMediaContainer($instagramAccountId, [
            'image_url' => $imageUrl,
            'caption'   => $caption
        ], $projectKey);

        $creationId = $containerRes['data']['id'] ?? null;
        if (empty($creationId)) {
            return [
                'status' => 'error',
                'message' => 'Instagram medya container oluşturulamadı: ' . ($containerRes['message'] ?? 'Bilinmeyen Hata'),
                'details' => $containerRes
            ];
        }

        // 2. Medyayı yayınla
        return $this->publishMedia($instagramAccountId, (string) $creationId, $projectKey);
    }
}
