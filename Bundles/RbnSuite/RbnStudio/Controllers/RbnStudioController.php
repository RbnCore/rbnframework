<?php

declare(strict_types=1);

namespace Rbn\Framework\Bundles\RbnSuite\RbnStudio\Controllers;

use Rbn\Framework\Core\Base\Web\BaseController;
use Rbn\Framework\Core\Base\Attributes\Module;
use Rbn\Framework\Bundles\RbnSuite\RbnStudio\Models\ModuleData;
/**
 * RbnStudioController - The RBN Framework Root for RbnStudio 🎨🚀🏛️⚓
 * RBN Framework Standard.
 */
#[Module(
    name: 'studio',
    data: ModuleData::class,
    context: 'backend'
)]
class RbnStudioController extends BaseController
{
    /**
     * Aktif projenin RouteMap yapılandırmasından içerik ayarlarını çeker 🏛️⚡
     */
    protected function getContentConfig(?string $key = null, ?string $projectKey = null): mixed
    {
        $pKey = $projectKey ?: $this->activeProjectKey();
        if (empty($pKey)) {
            return null;
        }

        $contentMap = (array) ($this->getRouteConfig($pKey, 'content') ?? []);
        return $key ? ($contentMap[$key] ?? null) : $contentMap;
    }

    /**
     * Blog Yönetimi Aktif mi? 📝
     */
    protected function hasBlog(?string $projectKey = null): bool
    {
        return !empty($this->getContentConfig('blog', $projectKey));
    }

    /**
     * Haber Yönetimi Aktif mi? 📰
     */
    protected function hasNews(?string $projectKey = null): bool
    {
        return !empty($this->getContentConfig('news', $projectKey));
    }

    /**
     * Kategori Yönetimi Aktif mi? 🏷️
     */
    protected function hasCategory(?string $projectKey = null): bool
    {
        return !empty($this->getContentConfig('category', $projectKey));
    }

    /**
     * Taslak / Fikir (Draft) Yönetimi Aktif mi? 💡
     */
    protected function hasDraft(?string $projectKey = null): bool
    {
        return !empty($this->getContentConfig('draft', $projectKey));
    }

    /**
     * Sosyal Medya Yönetimi ve Kolonları Aktif mi? 📱
     */
    protected function hasSocial(?string $projectKey = null): bool
    {
        return !empty($this->getContentConfig('social', $projectKey));
    }

    /**
     * Aktif projeye ait makale / blog servisi 📰
     */
    protected function resolvePostService(?string $projectKey = null): ?object
    {
        $conf = $this->getContentConfig('blog', $projectKey);
        return !empty($conf['service']) ? $this->service($conf['service']) : null;
    }

    /**
     * Aktif projeye ait makale / blog model aliası 🏺
     */
    protected function resolvePostModel(?string $projectKey = null): ?string
    {
        $conf = $this->getContentConfig('blog', $projectKey);
        return $conf['model'] ?? null;
    }

    /**
     * Aktif projeye ait kategori model aliası (Varsayılan: app.contentCategory) 🏷️
     */
    protected function resolveCategoryModel(?string $projectKey = null): string
    {
        $conf = $this->getContentConfig('category', $projectKey);
        if (is_array($conf) && !empty($conf['model'])) {
            return $conf['model'];
        }
        return 'app.contentCategory';
    }

    /**
     * Aktif projeye ait kategori servisi / repository aliası 🏷️
     */
    protected function resolveCategoryService(?string $projectKey = null): ?object
    {
        $conf = $this->getContentConfig('category', $projectKey);
        if (is_array($conf) && !empty($conf['service'])) {
            return $this->service($conf['service']);
        }
        return $this->repository('app.contentCategory');
    }

    /**
     * Aktif projeye ait taslak / fikir model aliası (Varsayılan: app.contentDraft) 💡
     */
    protected function resolveDraftModel(?string $projectKey = null): string
    {
        $conf = $this->getContentConfig('draft', $projectKey);
        if (is_array($conf) && !empty($conf['model'])) {
            return $conf['model'];
        }
        return 'app.contentDraft';
    }

    /**
     * Aktif projeye ait taslak / fikir servisi / repository aliası 💡
     */
    protected function resolveDraftService(?string $projectKey = null): ?object
    {
        $conf = $this->getContentConfig('draft', $projectKey);
        if (is_array($conf) && !empty($conf['service'])) {
            return $this->service($conf['service']);
        }
        return $this->repository('app.contentDraft');
    }

    /**
     * Aktif projeye ait haber servisi 🛰️
     */
    protected function resolveNewsService(?string $projectKey = null): ?object
    {
        $conf = $this->getContentConfig('news', $projectKey);
        return !empty($conf['service']) ? $this->service($conf['service']) : null;
    }

    /**
     * Aktif projeye ait haber model aliası 🏺
     */
    protected function resolveNewsModel(?string $projectKey = null): ?string
    {
        $conf = $this->getContentConfig('news', $projectKey);
        return $conf['model'] ?? null;
    }

    /**
     * Gemini AI ile Bağımsız Kapak Görseli Üretici (Evrensel Studio Motoru) 🎨🧠🛰️
     */
    protected function executeGenerateImage(string $type = 'blog')
    {
        $id = (int) ($this->request->input('id') ?: $this->request->query('id'));
        $title = (string) $this->request->input('title', '');
        $customPrompt = (string) $this->request->input('custom_prompt', '');
        $projectKey = (string) ($this->request->input('project_key') ?: $this->activeProjectKey());

        $prompt = !empty($customPrompt) ? $customPrompt : $title;

        if (empty($prompt)) {
            return $this->response->json(['success' => false, 'message' => 'Görsel üretmek için lütfen bir başlık veya açıklama belirtin.']);
        }

        $result = $this->service('api')->gemini('image', $prompt, [
            'project_key' => $projectKey,
            'id' => $id
        ]);

        if (!($result['success'] ?? false) || empty($result['base64'])) {
            return $this->response->json([
                'success' => false,
                'message' => $result['message'] ?? 'Görsel üretilemedi.'
            ]);
        }

        // Base64 görseli diske WebP formatında kaydet ve URL üret 🖼️
        $targetFolder = "images/{$type}/" . date('Y/m');
        $uploadRes = $this->service('image')->base64Image(
            $result['base64'],
            $targetFolder,
            'public',
            ['extension' => 'webp']
        );

        if (!($uploadRes['success'] ?? false)) {
            return $this->response->json([
                'success' => false,
                'message' => 'Görsel sunucuya kaydedilemedi: ' . ($uploadRes['message'] ?? 'Dosya hatası')
            ]);
        }

        $imagePath = '/' . ltrim($uploadRes['path'] ?? '', '/');
        $result['url'] = $imagePath;
        $result['image_url'] = $imagePath;
        $result['image'] = $imagePath;

        if ($id > 0) {
            $service = ($type === 'news') ? $this->resolveNewsService($projectKey) : $this->resolvePostService($projectKey);
            if ($service) {
                $service->savePost([
                    'id' => $id,
                    'image' => $imagePath,
                    'image_prompt' => $result['prompt'] ?? $prompt
                ]);
            }
        }

        return $this->response->json($result);
    }

    /**
     * Gemini AI ile Yazı / Haber İçeriğini Yeniden Üretici (Evrensel Studio Motoru) 🧠🖋️🛰️
     */
    protected function executeRewrite(string $type = 'blog')
    {
        $id = (int) ($this->request->input('id') ?: $this->request->query('id'));

        if (empty($id)) {
            return $this->response->json(['success' => false, 'message' => 'Geçersiz veya eksik içerik ID\'si.']);
        }

        $projectKey = $this->activeProjectKey();

        $localModel = ($type === 'news') ? $this->resolveNewsModel($projectKey) : $this->resolvePostModel($projectKey);
        $categoryModel = $this->resolveCategoryModel($projectKey);

        $result = $this->builder('task.content_rewrite')->autopilot([
            'id'             => $id,
            'target_id'      => $id,
            'project_key'    => $projectKey,
            'preset'         => $type,
            'type'           => $type,
            'local_model'    => $localModel,
            'category_model' => $categoryModel,
            'force'          => true
        ]);

        if (is_array($result) && isset($result['success']) && !$result['success']) {
            return $this->response->json([
                'success' => false,
                'message' => $result['message'] ?? 'İçerik yeniden yazılırken hata oluştu.'
            ]);
        }

        $label = ($type === 'news') ? 'Haber' : 'Makale';
        return $this->response->json([
            'success' => true,
            'message' => "{$label} yapay zeka ile E-E-A-T uyumlu olarak yeniden yazıldı ve güncellendi!",
            'reload'  => true
        ]);
    }

    /**
     * Projeye tanımlı ve geçerli sosyal medya platformlarını çözer 📱🌐
     */
    protected function getActiveSocialPlatforms(?string $projectKey = null): array
    {
        $projectKey = $projectKey ?: $this->activeProjectKey();

        if (!$this->hasSocial($projectKey)) {
            return [];
        }

        $apiManager = $this->manager('api');
        $socialProvider = $this->provider('socialmedia');
        $apiKeys = $this->resolveProjectData('api_keys', $projectKey) ?: [];
        $platforms = [];

        // 1. Instagram kontrolü (Access Token + Account ID)
        $igData = $apiManager ? $apiManager->resolveApiKey('instagram', $projectKey) : null;
        $igToken = (!empty($igData['access_token'])) ? $igData['access_token'] : ($apiKeys['INSTAGRAM_ACCESS_TOKEN'] ?? null);
        $igAccountId = (!empty($igData['account_id'])) ? $igData['account_id'] : ($apiKeys['INSTAGRAM_ACCOUNT_ID'] ?? null);

        if (!empty($igToken) && !empty($igAccountId)) {
            $meta = $socialProvider ? $socialProvider->getPlatform('instagram') : null;
            $platforms['instagram'] = [
                'name' => $meta && $meta->exists() ? $meta->title() : 'Instagram',
                'icon' => $meta && $meta->exists() ? $meta->riIcon() : 'ri-instagram-line',
                'color' => $meta && $meta->exists() ? $meta->color() : '#E4405F',
                'badge' => 'Business Media',
                'account_id' => $igAccountId
            ];
        }

        // 2. Facebook kontrolü (Access Token + Page ID)
        $fbData = $apiManager ? $apiManager->resolveApiKey('facebook', $projectKey) : null;
        $fbToken = (!empty($fbData['access_token'])) ? $fbData['access_token'] : ($apiKeys['FACEBOOK_ACCESS_TOKEN'] ?? null);
        $fbPageId = (!empty($fbData['page_id'])) ? $fbData['page_id'] : ($apiKeys['FACEBOOK_PAGE_ID'] ?? null);

        if (!empty($fbToken) && !empty($fbPageId)) {
            $meta = $socialProvider ? $socialProvider->getPlatform('facebook') : null;
            $platforms['facebook'] = [
                'name' => $meta && $meta->exists() ? $meta->title() : 'Facebook',
                'icon' => $meta && $meta->exists() ? $meta->riIcon() : 'ri-facebook-fill',
                'color' => $meta && $meta->exists() ? $meta->color() : '#1877F2',
                'badge' => 'Sayfa Yayını',
                'page_id' => $fbPageId
            ];
        }

        return $platforms;
    }

    /**
     * Sosyal Medya Paylaşım Modalı İçeriğini Döndürür 🎨📱
     */
    protected function executeSocialModal(string $type, int|string|null $id = null): mixed
    {
        $id = (int) ($id ?: ($this->request->input('id') ?: $this->request->query('id')));
        $projectKey = $this->activeProjectKey();

        if (!$this->hasSocial($projectKey)) {
            return $this->response->json(['error' => 'Bu projede sosyal medya entegrasyonu aktif değildir.'], 403);
        }

        $service = ($type === 'news') ? $this->resolveNewsService($projectKey) : $this->resolvePostService($projectKey);
        $post = ($service && $id > 0) ? $service->find($id) : null;

        if (!$post) {
            return $this->response->json(['error' => 'Kayıt bulunamadı.'], 404);
        }

        $platforms = $this->getActiveSocialPlatforms($projectKey);

        // Kategori slug'ını bul
        $categorySlug = '';
        $catId = $post['category_id'] ?? null;
        if ($catId) {
            try {
                $catRepo = $this->repository('app.contentCategory');
                $cat = $catRepo ? $catRepo->find($catId) : null;
                $categorySlug = $cat['slug'] ?? '';
            } catch (\Throwable $e) {
            }
        }

        $typePrefix = ($type === 'news') ? 'haber' : 'blog';
        $postPath = !empty($categorySlug) ? "{$typePrefix}/{$categorySlug}/{$post['slug']}" : "{$typePrefix}/{$post['slug']}";
        $publishedUrl = $this->targetProjectUrl($projectKey, $postPath);

        // Görsel URL çöz: Hem dış API (public) hem yerel modal önizleme (preview) için
        $rawImage = !empty($post['image']) ? (string) $post['image'] : '';
        $imageUrl = $rawImage;
        $previewImageUrl = $rawImage;

        if (!empty($rawImage)) {
            if (!str_starts_with($rawImage, 'http://') && !str_starts_with($rawImage, 'https://')) {
                $imageUrl = $this->targetProjectUrl($projectKey, ltrim($rawImage, '/'));
                // Tarayıcı modal önizlemesi için doğrudan panel kökünden veya relative path'ten eriş
                $previewImageUrl = '/' . ltrim($rawImage, '/');
            }
        }

        $this->render('Posts/Partials/social_modal', [
            'post' => $post,
            'id' => $id,
            'type' => $type,
            'platforms' => $platforms,
            'publishedUrl' => $publishedUrl,
            'imageUrl' => $imageUrl,
            'previewImageUrl' => $previewImageUrl,
            'projectKey' => $projectKey,
            'ajax' => true
        ]);
        return null;
    }

    /**
     * Sosyal Medyaya Paylaşım Gönderici (AJAX Dispatcher) 🚀📱
     */
    protected function executeSocialShare(string $type = 'blog')
    {
        $id = (int) $this->request->input('id');
        $platforms = (array) $this->request->input('platforms', []);
        $caption = (string) $this->request->input('caption', '');
        $hashtags = (string) $this->request->input('hashtags', '');
        $projectKey = $this->activeProjectKey();

        if (!$this->hasSocial($projectKey)) {
            return $this->response->json(['success' => false, 'message' => 'Bu projede sosyal medya entegrasyonu aktif değildir.']);
        }

        if (empty($id)) {
            return $this->response->json(['success' => false, 'message' => 'Geçersiz içerik ID\'si.']);
        }

        if (empty($platforms)) {
            return $this->response->json(['success' => false, 'message' => 'Lütfen paylaşım yapılacak en az bir platform seçin.']);
        }

        $service = ($type === 'news') ? $this->resolveNewsService($projectKey) : $this->resolvePostService($projectKey);
        $post = ($service && $id > 0) ? $service->find($id) : null;

        if (empty($post)) {
            return $this->response->json(['success' => false, 'message' => 'İçerik bulunamadı.']);
        }

        // Kategori ve URL çöz
        $catId = $post['category_id'] ?? null;
        $categorySlug = '';
        if ($catId) {
            try {
                $cat = $this->repository('app.contentCategory')->find($catId);
                $categorySlug = $cat['slug'] ?? '';
            } catch (\Throwable $e) {
            }
        }

        $typePrefix = ($type === 'news') ? 'haber' : 'blog';
        $postPath = !empty($categorySlug) ? "{$typePrefix}/{$categorySlug}/{$post['slug']}" : "{$typePrefix}/{$post['slug']}";
        $publishedUrl = $this->targetProjectUrl($projectKey, $postPath);

        // Görsel URL
        $imageUrl = !empty($post['image']) ? (string) $post['image'] : null;
        if (!empty($imageUrl) && !str_starts_with($imageUrl, 'http://') && !str_starts_with($imageUrl, 'https://')) {
            $imageUrl = $this->targetProjectUrl($projectKey, ltrim($imageUrl, '/'));
        }

        // Metin birleşimi
        $captionParts = array_filter([trim($caption), trim($hashtags), $publishedUrl ? "Detaylar: " . $publishedUrl : '']);
        $fullCaption = implode("\n\n", $captionParts);

        $results = [];
        $hasError = false;

        // 1. Facebook Paylaşımı
        if (in_array('facebook', $platforms, true)) {
            try {
                $fbService = $this->service('facebook');
                $fbData = $this->manager('api')->resolveApiKey('facebook', $projectKey);
                $pageId = is_array($fbData) ? ($fbData['page_id'] ?? null) : null;

                if (!empty($fbService) && !empty($pageId)) {
                    if (!empty($imageUrl)) {
                        $fbRes = $fbService->postPhoto((string) $pageId, $imageUrl, $fullCaption, $projectKey);
                    } else {
                        $fbRes = $fbService->postFeed((string) $pageId, $fullCaption, $publishedUrl, $projectKey);
                    }

                    if (($fbRes['status'] ?? '') === 'success' || !empty($fbRes['data']['id'])) {
                        $results['facebook'] = ['success' => true, 'message' => 'Facebook gönderisi paylaşıldı.'];
                    } else {
                        $hasError = true;
                        $results['facebook'] = ['success' => false, 'message' => $fbRes['message'] ?? 'Facebook hatası.'];
                    }
                } else {
                    $hasError = true;
                    $results['facebook'] = ['success' => false, 'message' => 'Facebook Page ID veya Access Token bulunamadı.'];
                }
            } catch (\Throwable $e) {
                $hasError = true;
                $results['facebook'] = ['success' => false, 'message' => $e->getMessage()];
            }
        }

        // 2. Instagram Paylaşımı
        if (in_array('instagram', $platforms, true)) {
            try {
                $igService = $this->service('instagram');
                $igData = $this->manager('api')->resolveApiKey('instagram', $projectKey);
                $igAccountId = is_array($igData) ? ($igData['account_id'] ?? null) : null;

                if (!empty($igService) && !empty($igAccountId) && !empty($imageUrl)) {
                    $igRes = $igService->publishPhoto((string) $igAccountId, $imageUrl, $fullCaption, $projectKey);

                    if (($igRes['status'] ?? '') === 'success' || !empty($igRes['data']['id'])) {
                        $results['instagram'] = ['success' => true, 'message' => 'Instagram gönderisi paylaşıldı.'];
                    } else {
                        $hasError = true;
                        $results['instagram'] = ['success' => false, 'message' => $igRes['message'] ?? 'Instagram hatası.'];
                    }
                } else {
                    $hasError = true;
                    $reason = empty($imageUrl) ? 'Kapak görseli olmadan Instagram paylaşımı yapılamaz.' : 'Instagram Hesap ID veya Access Token bulunamadı.';
                    $results['instagram'] = ['success' => false, 'message' => $reason];
                }
            } catch (\Throwable $e) {
                $hasError = true;
                $results['instagram'] = ['success' => false, 'message' => $e->getMessage()];
            }
        }

        // Başarı / hata mesajını topla
        $messages = [];
        foreach ($results as $plat => $r) {
            $platName = ucfirst($plat);
            $messages[] = "{$platName}: " . ($r['success'] ? 'Başarılı ✅' : ('Hata ❌ (' . $r['message'] . ')'));
        }

        return $this->response->json([
            'success' => !$hasError,
            'message' => implode('<br>', $messages),
            'results' => $results
        ]);
    }
}
