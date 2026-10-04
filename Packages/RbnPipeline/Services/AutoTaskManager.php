<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnPipeline\Services;

use Rbn\Framework\Core\Base\Attributes\Component;
use Rbn\Framework\Core\Base\Services\BaseManager;
use Rbn\Framework\Packages\RbnPipeline\Concerns\AutoTaskTrait;

/**
 * AutoTaskManager - Master Orchestrator for All Sovereign Automated Tasks (Blog, News, Product, etc.) 🤖🛰️⚓
 * RBN 3.5 Masterpiece Standard.
 */
#[Component(alias: 'autoTask', type: 'manager')]
class AutoTaskManager extends BaseManager
{
    use AutoTaskTrait;

    /**
     * Ortak AI İçerik Üretimi, Yanıt Düzleştirme ve SSS Temizleme İşleyişi 🧠🛰️⚓
     */
    public function generateText(
        string $type,
        string $title,
        array $context = [],
        $taskLog = null
    ): ?array {
        $projectKey = $this->getProjectKey($context['project_key'] ?? null);
        $context['project_key'] = $projectKey;

        $geminiApp = $this->service('app.gemini');
        if (!$geminiApp) {
            if ($taskLog) {
                $taskLog->step('AI Servis Hatası', "[{$projectKey}] GeminiAppService (app.gemini) yüklenemedi.");
            }
            return null;
        }

        // 1. SEO İç Linkleme için mevcut yazıları doğrudan Trait Metotları (Model) üzerinden çek ve bağlama ekle 🔗
        if (empty($context['published_articles'])) {
            $pModelName = $context['local_model'] ?? ($context['post_model'] ?? (str_contains(strtolower($type), 'news') ? 'news' : 'blog'));
            $hasCategories = $context['has_categories'] ?? true;
            $catModelAlias = $hasCategories ? ($context['category_model'] ?? (str_contains((string) $pModelName, '.') ? $pModelName . '.category' : $pModelName . 'Category')) : false;

            $rawPosts = $this->resolveRecentPosts($pModelName, 50);
            $categories = $hasCategories ? $this->resolveCategories($catModelAlias) : [];

            $categoryMap = [];
            if (is_array($categories)) {
                foreach ($categories as $c) {
                    if (isset($c['id'], $c['slug'])) {
                        $categoryMap[$c['id']] = (string) $c['slug'];
                    }
                }
            }

            if (!empty($rawPosts)) {
                $context['published_articles'] = $this->formatExistingLinksForAi($rawPosts, $categoryMap, $type);
            }
        }

        // 2. Gemini AI Çağrısı
        $aiResult = $geminiApp->generateText($type, $title, $context);
        if (!($aiResult['success'] ?? false)) {
            if ($taskLog) {
                $taskLog->step('AI Üretim Hatası', "Gemini API üretimi başarısız: " . ($aiResult['message'] ?? 'Bilinmeyen Hata'));
            }
            return null;
        }

        // 2. Yanıt Düzleştirme (Normalize)
        $res = $this->normalizeAiResponse($aiResult);

        // 3. SSS Temizleme ve Formatlama
        $res['faqs'] = $this->formatFaqs($res['faqs'] ?? null);

        if ($taskLog) {
            $availableKeys = is_array($res) ? implode(', ', array_keys($res)) : 'Düz Metin';
            $taskLog->step('2A. Adım: AI Yanıtı Başarılı', "İçerik başarıyla üretildi. Dönen Alanlar: [{$availableKeys}]");
        }

        return $res;
    }

    /**
     * Üretilen metin içeriğini pasif (is_active = 0) olarak kaydeder ve bağlı taslağı günceller 💾
     */
    public function saveDraftPost(
        string $modelAlias,
        array $postData,
        ?array $draft = null,
        $taskLog = null,
        array $taskParams = []
    ): int {
        $targetId = (int) ($postData['id'] ?? 0);

        // Veritabanı tablosunda bulunmayan geçici yapay zeka ve taslak metadatalarını doğrudan temizle 🧹
        unset(
            $postData['id'],
            $postData['selected_key'],
            $postData['processed_keys'],
            $postData['target_category_hint'],
            $postData['candidates'],
            $postData['draft']
        );

        $postData['is_active'] = 0;

        if (isset($postData['project_key']) || !empty($taskParams['has_project_key'])) {
            $postData['project_key'] = $this->getProjectKey($postData['project_key'] ?? ($taskParams['project_key'] ?? null));
        }

        // Otomatik Kategori ID Çözümleme ve Doğrulama (Yalnızca kategorili modeller için) 🏷️
        $hasCategories = !isset($taskParams['has_categories']) || $taskParams['has_categories'] !== false;
        $catTarget = $hasCategories ? ($taskParams['category_model'] ?? 'app.contentCategory') : null;
        if (!empty($catTarget) && $hasCategories) {
            $this->taskParams = array_merge($this->taskParams ?? [], $taskParams);
            $postData['category_id'] = $this->resolveCategories($catTarget, $postData['category_id'] ?? null, $draft);
        } else {
            unset($postData['category_id']);
        }

        if (!empty($postData['title'])) {
            $currentSlug = (string) ($postData['slug'] ?? '');
            if (empty($currentSlug) || str_contains($currentSlug, ' ') || $currentSlug === (string) $postData['title']) {
                $seoHelper = $this->helper('meta.seo');
                if ($seoHelper && method_exists($seoHelper, 'seoSlug')) {
                    $targetForSlug = (!empty($currentSlug) && !str_contains($currentSlug, ' ')) ? $currentSlug : (string) $postData['title'];
                    $postData['slug'] = $seoHelper->seoSlug($targetForSlug);
                }
            }
        }

        $model = $this->model($modelAlias);

        // 🛡️ Çift Emniyet Kalkanı: Eğer ID belirtilmemiş ancak GUID tabloda zaten varsa, yeni kayıt yerine mevcut kaydı güncelle (1062 Duplicate Önleme)
        if ($targetId <= 0 && !empty($postData['guid']) && $model && method_exists($model, 'query')) {
            try {
                $guidQuery = $model->query()->where('guid', (string) $postData['guid']);
                if (!empty($postData['project_key'])) {
                    try {
                        $guidQuery->where('project_key', $postData['project_key']);
                    } catch (\Throwable $e) {
                    }
                }
                $existingRow = $guidQuery->first();
                if (!empty($existingRow['id'])) {
                    $targetId = (int) $existingRow['id'];
                }
            } catch (\Throwable $e) {
            }
        }

        if ($targetId > 0 && $model) {
            if (method_exists($model, 'query')) {
                $model->query()->where('id', $targetId)->update($postData);
            } else {
                $model->save(array_merge(['id' => $targetId], $postData));
            }
            $postId = $targetId;
        } else {
            $saved = $model ? $model->save($postData) : 0;
            $postId = is_array($saved) || $saved instanceof \ArrayAccess
                ? (int) ($saved['id'] ?? ($saved['post_id'] ?? 0))
                : (int) $saved;
        }

        // Taslak işlendi ve yayınlandı: Taslağı kuyruktan sil 🗑️📝
        $draftData = $draft ?? ($postData['draft'] ?? ($taskParams['draft'] ?? null));
        $draftModelAlias = $taskParams['draft_model'] ?? ($params['draft_model'] ?? 'app.contentDraft');

        if (!empty($draftData['id']) && $postId > 0 && !empty($draftModelAlias)) {
            try {
                $draftModel = $this->model($draftModelAlias);
                if ($draftModel && method_exists($draftModel, 'query')) {
                    $draftModel->query()->where('id', $draftData['id'])->delete();
                }
            } catch (\Throwable $e) {
                // Ignore draft delete errors
            }
        }

        if ($taskLog && $postId > 0) {
            $taskLog->step('3A. Adım: DB Kaydı Başarılı', "İçerik veritabanına başarıyla kaydedildi (ID: {$postId}).");
        }

        return $postId;
    }

    /**
     * Gemini Imagen AI ile kapak görseli üretir, WebP olarak kaydeder ve DB kaydını günceller 🎨🖼️
     */
    public function generateAndSavePostImage(
        string $prompt,
        string $type,
        $taskLog = null,
        array $context = [],
        ?int $postId = null,
        ?string $modelAlias = null
    ): ?string {
        $finalPrompt = !empty($prompt) ? $prompt : (!empty($context['image_prompt']) ? $context['image_prompt'] : '');
        if (empty($finalPrompt)) {
            $t = $context['title'] ?? ($context['topic'] ?? '');
            $s = $context['summary'] ?? ($context['short_desc'] ?? '');
            $finalPrompt = trim("{$t}. {$s}");
        }
        if (empty($finalPrompt)) {
            $finalPrompt = 'Professional high quality cover photo';
        }

        $projectKey = !empty($context['project_key']) ? (string) $context['project_key'] : (function_exists('project_key') ? project_key() : '');

        $geminiApp = $this->service('app.gemini');
        if (!$geminiApp) {
            return null;
        }

        usleep(1500000); // 1.5 sn bekleme

        $imgRes = $geminiApp->generateImage($finalPrompt, $projectKey, $type, $context);
        $imagePath = null;

        if ($imgRes['success'] ?? false) {
            $imagePath = $imgRes['url'] ?? ($imgRes['image_url'] ?? null);
            if ($taskLog && $imagePath) {
                $taskLog->step('4A. Adım: Görsel Yükleme', "Kapak görseli WebP olarak yüklendi: {$imagePath}");
            }
        } else if ($taskLog) {
            $taskLog->step('Görsel Üretim Hatası', "Görsel üretilemedi: " . ($imgRes['message'] ?? 'API Hatası'));
        }

        if (!empty($imagePath) && $postId > 0 && !empty($modelAlias)) {
            $model = $this->model($modelAlias);
            if ($model) {
                $model->query()->where('id', $postId)->update(['image' => $imagePath, 'image_prompt' => $finalPrompt]);
            }
            if ($taskLog) {
                $taskLog->step('4B. Adım: Görsel DB Kaydı', "Görsel yolu veritabanına kaydedildi: {$imagePath}");
            }
        }

        return $imagePath;
    }


    /**
     * Makale/Haber Yayına Alma, Önbellek Temizleme ve IndexNow Ping Sürecini Sonlandırır 🚀
     */
    public function finalizeAndPublishPost(
        int $postId,
        string $modelAlias,
        string $categorySlug,
        string $postSlug,
        string $typePrefix, // Örn: 'blog' veya 'haber'
        $taskLog = null,
        $guids = null,
        ?string $projectKey = null
    ): string|bool {
        $projectKey = $this->getProjectKey($projectKey, true);

        // 1. Postu Yayına Al (is_active = 1)
        $published = $this->publishPost($postId, $modelAlias);
        if (!$published) {
            if ($taskLog) {
                $taskLog->failed("İçerik üretildi ancak yayına alınamadı. Post ID: {$postId}");
            }
            return false;
        }

        // Kategori slug ve post slug boş ise veritabanı kaydından çözümler 🔍
        if (empty($postSlug) || $categorySlug === '') {
            $model = $this->model($modelAlias);
            $postObj = $model ? $model->query()->where('id', $postId)->first() : null;
            $pData = is_array($postObj) ? $postObj : (method_exists($postObj, 'toArray') ? $postObj->toArray() : (array) $postObj);
            if (empty($postSlug) && !empty($pData['slug'])) {
                $postSlug = (string) $pData['slug'];
            }
            if ($categorySlug === '' && !empty($pData['category_slug'])) {
                $categorySlug = (string) $pData['category_slug'];
            } elseif ($categorySlug === '' && !empty($pData['category_id'])) {
                $catId = (int) $pData['category_id'];
                
                // 1. Evrensel RBN İçerik Kategorisi Modeli (app.contentCategory -> app_content_categories) 🏛️
                try {
                    $catModel = $this->model('app.contentCategory');
                    if ($catModel) {
                        $catObj = $catModel->query()->where('id', $catId)->first();
                        if (!empty($catObj['slug'])) {
                            $categorySlug = (string) $catObj['slug'];
                        }
                    }
                } catch (\Throwable $e) {
                    // Fallback
                }

                // 2. Özel Modül Kategori Modeli Fallback 🔍
                if (empty($categorySlug)) {
                    $catModelAlias = str_contains($modelAlias, '.post') ? str_replace('.post', '.category', $modelAlias) : (str_contains($modelAlias, '.') ? explode('.', $modelAlias)[0] . '.category' : $modelAlias . 'Category');
                    try {
                        $catModel = $this->model($catModelAlias);
                        if ($catModel) {
                            $catObj = $catModel->query()->where('id', $catId)->first();
                            if (!empty($catObj['slug'])) {
                                $categorySlug = (string) $catObj['slug'];
                            }
                        }
                    } catch (\Throwable $e) {
                        // Fallback
                    }
                }
            }
        }

        // 2. Trait üzerinden sitemap, RSS feed ve içerik (content_data) önbelleğini temizle 🧹
        $this->clearSitemapCache();
        $this->clearFeedCache();
        $this->clearContentCache();
        if ($taskLog) {
            $taskLog->step('5A. Adım: Önbellek Temizliği', "Sitemap, RSS Feed ve Proje İçerik (content_data) önbelleği temizlendi.");
        }
        $this->db()->resetBootQueryCount();

        // 3. RSS / Trend GUID veya GUID listesi varsa ortak rss_blacklist tablosuna kaydet 🛡️
        $sourceType = is_array($guids) ? ($guids['source_type'] ?? ($guids['source'] ?? '')) : '';
        $isDraft = ($sourceType === 'draft') || (isset($guids['id']) && !isset($guids['guid']) && !isset($guids['candidates_for_ai']));

        if (!empty($guids) && !$isDraft && in_array($sourceType, ['rss', 'trends', 'external'], true)) {
            $rssParser = $this->service('rssParser');
            if ($rssParser) {
                $guidList = [];
                if (is_array($guids) && (isset($guids['guid']) || isset($guids['candidates_for_ai']))) {
                    $guidList = $this->resolveGuidsForBlacklist($guids, []);
                } elseif (is_string($guids)) {
                    $guidList = [$guids];
                } elseif (is_array($guids) && !isset($guids['id'])) {
                    $guidList = $guids;
                }

                $addedCount = 0;
                foreach ($guidList as $g) {
                    if (!empty($g) && is_string($g)) {
                        $rssParser->blacklistCandidate($projectKey, (string) $g);
                        $addedCount++;
                    }
                }
                if ($taskLog && $addedCount > 0) {
                    $taskLog->step('Kara Liste Kaydı', "{$addedCount} adet RSS GUID ortak rss_blacklist tablosuna eklendi.");
                }
            }
        }

        // 4. IndexNow Ping Gönderimini Yap 🚀
        $postPath = !empty($categorySlug) ? "{$typePrefix}/{$categorySlug}/{$postSlug}" : "{$typePrefix}/{$postSlug}";

        if (!function_exists('is_local') || !is_local()) {
            try {
                if ($taskLog) {
                    $taskLog->step('5B. Adım: IndexNow Gönderimi', "IndexNow pingi başarıyla gönderildi.");
                }
                $this->service('indexNow')->pingSingleUrl($projectKey, $postPath);
            } catch (\Throwable $e) {
                if ($taskLog) {
                    $taskLog->step('IndexNow Uyarısı', "Ping gönderimi esnasında uyarı: " . $e->getMessage());
                }
            }
        } else if ($taskLog) {
            $taskLog->step('5B. Adım: IndexNow Gönderimi', "Yerel ortam (is_local) olduğu için IndexNow ping atlandı.");
        }

        $publishedUrl = $this->resolvePublishedPostLink($projectKey, $typePrefix, $postSlug, $categorySlug, false);

        // 5. Otonom Sosyal Medya Paylaşım Dağıtımı (Sadece social_share tanımlıysa çalışır) 📱🌐🚀
        $socialTargets = $this->taskParams['social_share'] ?? ($this->taskParams['options']['social_share'] ?? []);
        if (!empty($socialTargets)) {
            $this->dispatchSocialShares($postId, $modelAlias, $publishedUrl, $taskLog, $projectKey);
        }

        return $publishedUrl;
    }

    /**
     * Sosyal Medya Otonom Paylaşım Dağıtıcısı (Instagram & Facebook) 📱📘🌐🚀
     * Yalnızca görev parametrelerinde social_share tanımlıysa tetiklenir.
     */
    public function dispatchSocialShares(
        int $postId,
        string $modelAlias,
        string $publishedUrl,
        $taskLog = null,
        ?string $projectKey = null
    ): void {
        $socialTargets = $this->taskParams['social_share'] ?? ($this->taskParams['options']['social_share'] ?? []);
        if (empty($socialTargets)) {
            return;
        }

        if (is_string($socialTargets)) {
            $socialTargets = array_map('trim', explode(',', $socialTargets));
        }

        if (!is_array($socialTargets) || empty($socialTargets)) {
            return;
        }

        $socialTargets = array_map('strtolower', $socialTargets);
        $projectKey = $this->getProjectKey($projectKey);

        // 1. Post Verilerini Modelden Çek 💾
        $postRow = null;
        if ($postId > 0 && !empty($modelAlias)) {
            try {
                $model = $this->model($modelAlias);
                if ($model) {
                    $postRow = $model->query()->where('id', $postId)->first();
                }
            } catch (\Throwable $e) {
                // Ignore model read error
            }
        }

        if (empty($postRow)) {
            return;
        }

        // 2. Paylaşım Metni & Hashtag Hazırlığı ✍️
        $socialSummary = !empty($postRow['social_summary']) 
            ? trim((string) $postRow['social_summary']) 
            : (!empty($postRow['summary']) ? trim((string) $postRow['summary']) : trim((string) ($postRow['title'] ?? '')));

        $hashtags = !empty($postRow['social_hashtags']) ? trim((string) $postRow['social_hashtags']) : '';

        $captionParts = array_filter([$socialSummary, $hashtags, $publishedUrl ? "Detaylar: " . $publishedUrl : '']);
        $fullCaption = implode("\n\n", $captionParts);

        // 3. Görsel URL'ini Çöz 🖼️
        $imageUrl = !empty($postRow['image']) ? (string) $postRow['image'] : null;
        if (!empty($imageUrl) && !str_starts_with($imageUrl, 'http://') && !str_starts_with($imageUrl, 'https://')) {
            $imageUrl = $this->targetProjectUrl($projectKey, ltrim($imageUrl, '/'));
        }

        // --- [ FACEBOOK PAYLAŞIMI ] --- 📘
        if (in_array('facebook', $socialTargets, true)) {
            try {
                $fbService = $this->service('facebook');
                $fbTokenData = $this->manager('api')->resolveApiKey('facebook', $projectKey);
                $pageId = is_array($fbTokenData) ? ($fbTokenData['page_id'] ?? null) : null;

                if (!empty($fbService) && !empty($pageId)) {
                    if ($taskLog) {
                        $taskLog->step('5C. Adım: Facebook Paylaşımı', "Facebook Sayfasında ({$pageId}) otonom paylaşım yapılıyor...");
                    }

                    if (!empty($imageUrl)) {
                        $fbRes = $fbService->postPhoto((string) $pageId, $imageUrl, $fullCaption, $projectKey);
                    } else {
                        $fbRes = $fbService->postFeed((string) $pageId, $fullCaption, $publishedUrl, $projectKey);
                    }

                    if (($fbRes['status'] ?? '') === 'success' || !empty($fbRes['data']['id'])) {
                        if ($taskLog) {
                            $taskLog->step('Facebook Başarılı', "Facebook Sayfasında içerik yayınlandı. ID: " . ($fbRes['data']['id'] ?? 'OK'));
                        }
                    } else {
                        if ($taskLog) {
                            $taskLog->step('Facebook Uyarısı', "Facebook paylaşımında hata: " . ($fbRes['message'] ?? 'Bilinmeyen Hata'));
                        }
                    }
                } elseif ($taskLog) {
                    $taskLog->step('Facebook Uyarısı', "Facebook Page ID veya Access Token bulunamadı ({$projectKey}).");
                }
            } catch (\Throwable $e) {
                if ($taskLog) {
                    $taskLog->step('Facebook Hatası', "Facebook paylaşım hatası: " . $e->getMessage());
                }
            }
        }

        // --- [ INSTAGRAM PAYLAŞIMI ] --- 📸
        if (in_array('instagram', $socialTargets, true)) {
            try {
                $igService = $this->service('instagram');
                $igTokenData = $this->manager('api')->resolveApiKey('instagram', $projectKey);
                $igAccountId = is_array($igTokenData) ? ($igTokenData['account_id'] ?? null) : null;

                if (!empty($igService) && !empty($igAccountId) && !empty($imageUrl)) {
                    if ($taskLog) {
                        $taskLog->step('5D. Adım: Instagram Paylaşımı', "Instagram Business Hesabında ({$igAccountId}) görsel paylaşılıyor...");
                    }

                    $igRes = $igService->publishPhoto((string) $igAccountId, $imageUrl, $fullCaption, $projectKey);

                    if (($igRes['status'] ?? '') === 'success' || !empty($igRes['data']['id'])) {
                        if ($taskLog) {
                            $taskLog->step('Instagram Başarılı', "Instagram gönderisi yayınlandı. ID: " . ($igRes['data']['id'] ?? 'OK'));
                        }
                    } else {
                        if ($taskLog) {
                            $taskLog->step('Instagram Uyarısı', "Instagram paylaşımında hata: " . ($igRes['message'] ?? 'Bilinmeyen Hata'));
                        }
                    }
                } elseif ($taskLog) {
                    if (empty($imageUrl)) {
                        $taskLog->step('Instagram Uyarısı', "Instagram görsel olmadan paylaşılamaz. Kapak görseli bulunamadı.");
                    } else {
                        $taskLog->step('Instagram Uyarısı', "Instagram Account ID bulunamadı ({$projectKey}).");
                    }
                }
            } catch (\Throwable $e) {
                if ($taskLog) {
                    $taskLog->step('Instagram Hatası', "Instagram paylaşım hatası: " . $e->getMessage());
                }
            }
        }
    }
}
