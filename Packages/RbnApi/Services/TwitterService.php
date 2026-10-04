<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnApi\Services;

use Rbn\Framework\Core\Base\Services\BaseService;

/**
 * TwitterService - The Sovereign X Service 🐦🛰️⚓
 * 
 * RBN 3.5 Masterpiece: High-level service for all Twitter/X operations.
 * @property \Rbn\Framework\Packages\RbnApi\Providers\TwitterProvider $twitter
 */
class TwitterService extends BaseService
{
    /**
     * Standard X (Twitter) API Proxy (Gemini Symmetry) 🤖
     */
    public function call(string $method, array $params = [], string $httpMethod = 'POST', bool $isUpload = false, ?string $projectKey = null): array
    {
        return $this->provider('apiTwitter')->call($method, $params, $httpMethod, $isUpload, $projectKey);
    }
    /**
     * Twitter'a Tweet gönderir. 🚀
     */
    public function send(string $text, ?string $imagePath = null, ?string $projectKey = null): array
    {
        $mediaIds = [];

        // 🖼️ Handle Image Upload if exists
        if ($imagePath && file_exists($imagePath)) {
            $fileData = base64_encode(file_get_contents($imagePath));

            $uploadResponse = $this->provider('apiTwitter')->call('media/upload', [
                'media_data' => $fileData
            ], 'POST', true, $projectKey);

            if (!empty($uploadResponse['media_id_string'])) {
                $mediaIds[] = $uploadResponse['media_id_string'];
            }
        }

        // 📝 Post the Tweet
        $payload = ['text' => $text];
        if (!empty($mediaIds)) {
            $payload['media'] = ['media_ids' => $mediaIds];
        }

        return $this->provider('apiTwitter')->call('tweets', $payload, 'POST', false, $projectKey);
    }

    /**
     * Specialized: Share a movie/series object directly. 🎬
     */
    public function shareContent(string $title, string $url, ?string $imagePath = null, string $suffix = '', ?string $projectKey = null): array
    {
        $text = "🎬 {$title}\n\n🍿 Hemen izlemek için tıkla:\n{$url}\n\n{$suffix}";
        return $this->send($text, $imagePath, $projectKey);
    }
}
