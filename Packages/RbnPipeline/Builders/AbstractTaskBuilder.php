<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnPipeline\Builders;

use Rbn\Framework\Core\Base\BaseComponent;
use Rbn\Framework\Core\Support\Exceptions\CronTaskException;
use Rbn\Framework\Packages\RbnPipeline\Concerns\AutoTaskTrait;

/**
 * AbstractTaskBuilder - Universal RBN Framework Template for All Automated Task Builders 🤖🛰️⚓
 * Enforces standardized lifecycle validation, cron timing matching, daily publishing quota checks,
 * and provides direct shortcut methods for AI response normalization, image generation, FAQ formatting, and publishing.
 */
abstract class AbstractTaskBuilder extends BaseComponent
{
    use AutoTaskTrait;

    /** @var array Current task parameters 🛡️ */
    public array $taskParams = [];

    /** @var string|null Last published full URL state 🔗 */
    protected ?string $lastPublishedUrl = null;

    /**
     * Alt sınıfların kendi özel iş mantığını (AI haber üretimi, taslak çekme vb.) yazacağı mecburi soyut metot 🎯
     */
    abstract protected function executeAutopilot(array $params): array;

    /**
     * Autopilot şablon metodu: Yaşam döngüsü kapı kontrolünü mecburi kılar. 🛡️
     * 
     * @param array $params
     * @return array ['success' => bool, 'message' => string]
     */
    public function autopilot(array $params = []): array
    {
        $this->taskParams = $params;
        $taskLog = $this->service('base.taskLog');

        // Kategori Tipi Belirleme Hiyerarşisi:
        // 1. Task içinde açıkça 'category_type' belirtilmişse (Özel/Custom tasklar için) doğrudan onu kullanır.
        // 2. Belirtilmemişse çalışan preset adından (news -> news, blog -> blog, trends -> trends vs.) doğrudan çözer.
        if (empty($this->taskParams['category_type'])) {
            $preset = strtolower((string) ($params['preset'] ?? ($params['type'] ?? '')));
            if (!empty($preset)) {
                $this->taskParams['category_type'] = $preset;
                $this->taskParams['content_type'] = $this->taskParams['content_type'] ?? $preset;
            }
        }

        // STANDART KURAL: Tek ve gerçek model parametresi local_model'dir 🛡️
        if (empty($params['project_key'])) {
            throw CronTaskException::missingParameter('project_key');
        }
        if (empty($params['local_model'])) {
            throw CronTaskException::missingParameter('local_model', (string) $params['project_key']);
        }

        try {
            return $this->executeAutopilot($this->taskParams);
        } catch (CronTaskException $e) {
            return [
                'success' => true,
                'status' => $e->getCronStatus(),
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Cron veritabanından veya parametrelerden gelen geçerli project_key değerini döner 🔑🛡️
     */
    protected function getProjectKey(): string
    {
        $key = (string) ($this->taskParams['project_key'] ?? project_key());
        if (empty($key)) {
            throw CronTaskException::missingParameter('project_key');
        }
        return $key;
    }

    /**
     * 1. Adım: Sıradaki Dengeli İçeriği/Taslağı Çözümle ve Logla (Draft veya RSS) 🎲
     */
    protected function resolveCandidate(array $params = []): array
    {
        $taskLog = $this->service('base.taskLog');
        $fetchManager = $this->manager('externalFetch');

        if (!$fetchManager) {
            throw CronTaskException::skipped("ExternalFetchManager servisi yüklenemedi.");
        }

        if ($taskLog) {
            $taskLog->step('1. Adım: Aday Çözümleme', 'Bekleyen güncel haber veya blog taslağı aranıyor...');
        }

        $rawSource = strtolower(trim((string) ($params['source'] ?? 'draft')));
        $draft = null;

        $fetchManager->taskParams = array_merge($fetchManager->taskParams ?? [], $this->taskParams);

        if (str_contains($rawSource, 'trends')) {
            $draft = $fetchManager->fetchTrendsCandidate($params);
        } elseif (str_contains($rawSource, 'rss')) {
            $draft = $fetchManager->fetchRssCandidate($params);
        }

        if (empty($draft)) {
            $draft = $fetchManager->fetchDraftCandidate($params);
        }

        if (empty($draft)) {
            $msg = "İşlenecek yeni içerik adayı bulunamadı (Gündemdeki tüm trendler/haberler sitede zaten yayınlanmış). 🛡️";
            if ($taskLog) {
                $taskLog->skipped($msg);
            }
            throw CronTaskException::candidateNotFound($msg);
        }

        if ($taskLog) {
            $draftId = (int) ($draft['id'] ?? 0);
            $title = (string) ($draft['title'] ?? ($draft['name'] ?? 'İçerik Adayı'));
            $sourceType = $draft['source_type'] ?? ($draft['source'] ?? 'draft');

            if ($draftId > 0) {
                $taskLog->step('1A. Adım: Taslak Çözümleme', "Source: {$sourceType} | Taslak ID: {$draftId}, Başlık: '{$title}'");
            } else {
                $taskLog->step('1A. Adım: İçerik Adayı Çözümleme', "Source: {$sourceType} | Başlık: '{$title}'");
            }
        }

        return $draft;
    }

    /**
     * Görev bağlamını, generation_options'ı, task_key ve project_key'i otomatik harmanlar,
     * kategorileri ve geçmiş haberleri veri dizisi içinde otomatik hazırlar 🛡️
     */
    protected function buildContext(array $extraContext = []): array
    {
        $options = $this->taskParams['generation_options'] ?? [];
        $merged = array_merge($this->taskParams, $extraContext, is_array($options) ? $options : []);

        if (empty($merged['task_key']) && !empty($this->taskParams['task_key'])) {
            $merged['task_key'] = $this->taskParams['task_key'];
        }

        if (empty($merged['project_key'])) {
            $merged['project_key'] = $this->getProjectKey();
        }

        if (empty($merged['target_language'])) {
            $merged['target_language'] = 'Türkçe';
        }

        if (!isset($merged['has_author_comment'])) {
            $merged['has_author_comment'] = false;
        }

        if (!isset($merged['has_cta'])) {
            $merged['has_cta'] = false;
        }

        if (!isset($merged['has_pros_cons'])) {
            $merged['has_pros_cons'] = false;
        }

        if (!empty($merged['candidate']) && is_array($merged['candidate'])) {
            $cand = $merged['candidate'];
            if (empty($merged['candidates']) && !empty($cand['candidates_for_ai'])) {
                $merged['candidates'] = $cand['candidates_for_ai'];
            }
            if (empty($merged['target_category_hint']) && !empty($cand['target_category_hint'])) {
                $merged['target_category_hint'] = $cand['target_category_hint'];
            }
        }

        $autoManager = $this->manager('autoTask');
        if ($autoManager) {
            $hasCategories = $this->taskParams['has_categories'] ?? true;
            $postTarget = $this->taskParams['local_model'] ?? ($this->taskParams['post_model'] ?? null);

            // 1. Kural: has_categories false ise kesinlikle DB'de kategori aranmaz
            // 2. Kural: has_categories true/belirtilmemişse; önce taskParams['category_model'], yoksa evrensel 'app.contentCategory' kullanılır
            if ($hasCategories && empty($merged['categories'])) {
                $catTarget = $this->taskParams['category_model'] ?? 'app.contentCategory';
                if (property_exists($autoManager, 'taskParams')) {
                    $autoManager->taskParams = array_merge($autoManager->taskParams ?? [], $this->taskParams);
                }
                $merged['categories'] = $autoManager->resolveCategories($catTarget);
            }

            // 🏛️ TEK MÜKEMMEL STANDART: Yayınlanmış İçerikler (published_articles)
            // Haber, blog, rehber veya ürün ne olursa olsun tüm geçmiş yayınlar tek bir standart isimde birleşir!
            if (empty($merged['published_articles']) && !empty($postTarget)) {
                $rawArticles = $autoManager->resolveRecentPosts($postTarget, 50);
                
                $categoryMap = [];
                if ($hasCategories) {
                    $catModelAlias = $this->taskParams['category_model'] ?? (str_contains((string) $postTarget, '.') ? $postTarget . '.category' : $postTarget . 'Category');
                    $categories = $autoManager->resolveCategories($catModelAlias);

                    if (is_array($categories)) {
                        foreach ($categories as $c) {
                            if (isset($c['id'], $c['slug'])) {
                                $categoryMap[$c['id']] = (string) $c['slug'];
                            }
                        }
                    }
                }

                $type = $this->taskParams['preset'] ?? ($this->taskParams['type'] ?? 'blog');
                $formattedLinks = $autoManager->formatExistingLinksForAi($rawArticles, $categoryMap, $type);

                $articleMap = [];
                foreach ($formattedLinks as $item) {
                    if (!empty($item['title']) && !empty($item['url'])) {
                        $articleMap[(string) $item['title']] = (string) $item['url'];
                    }
                }
                $merged['published_articles'] = $articleMap;
            } elseif (!empty($merged['published_articles']) && is_array($merged['published_articles'])) {
                // Dışarıdan ham dizi/obje geldiyse [Başlık => URL] haritasına otomatik dönüştür
                $articleMap = [];
                foreach ($merged['published_articles'] as $key => $val) {
                    if (is_numeric($key) && (is_array($val) || is_object($val))) {
                        $pTitle = is_object($val) ? ($val->title ?? '') : ($val['title'] ?? '');
                        $pUrl = is_object($val) ? ($val->url ?? ($val->slug ?? '')) : ($val['url'] ?? ($val['slug'] ?? ''));
                        if (!empty($pTitle) && !empty($pUrl)) {
                            $articleMap[(string) $pTitle] = (string) $pUrl;
                        }
                    } else {
                        $articleMap[(string) $key] = (string) $val;
                    }
                }
                $merged['published_articles'] = $articleMap;
            }
        }

        return $merged;
    }

    /**
     * 2. Adım: Gemini AI ile metin içeriği üretir, yanıtı düzleştirir ve SSS formatlar 🧠
     */
    protected function generateText(string $type, string $title, array $extraContext = []): ?array
    {
        $taskLog = $this->service('base.taskLog');
        if ($taskLog) {
            $taskLog->step('2. Adım: Metin Üretimi', "Yapay zeka ile metin içeriği üretiliyor: '{$title}'");
        }

        $autoManager = $this->manager('autoTask');
        $context = $this->buildContext($extraContext);
        $res = $autoManager ? $autoManager->generateText($type, $title, $context, $taskLog) : null;

        if (empty($res) || !is_array($res)) {
            $msg = "Yapay zeka metin üretimi yanıt vermedi veya zaman aşımına uğradı.";
            if ($taskLog) {
                $taskLog->skipped($msg);
            }
            throw CronTaskException::skipped($msg);
        }

        return $res;
    }

    /**
     * 3. Adım: Üretilen metin içeriğini pasif (is_active = 0) olarak veritabanına kaydeder ve bağlı taslağı günceller 💾
     */
    protected function savePost(string $modelAlias, array $postData, ?array $draft = null): int
    {
        $taskLog = $this->service('base.taskLog');
        if ($taskLog) {
            $taskLog->step('3. Adım: Veritabanı Kaydı', "Üretilen içerik pasif (is_active = 0) olarak veritabanına kaydediliyor...");
        }

        $autoManager = $this->manager('autoTask');
        return $autoManager ? $autoManager->saveDraftPost($modelAlias, $postData, $draft, $taskLog, $this->taskParams) : 0;
    }

    /**
     * 4. Adım: Gemini Imagen AI ile kapak görseli üretir, WebP olarak kaydeder ve DB kaydını günceller 🎨🖼️
     */
    protected function generateImage(
        string $prompt,
        string $type,
        ?array $context = [],
        ?int $postId = null,
        ?string $modelAlias = null
    ): ?string {
        $taskLog = $this->service('base.taskLog');
        if ($taskLog) {
            $taskLog->step('4. Adım: Görsel Üretimi', "Imagen AI ile kapak görseli üretiliyor...");
        }

        $autoManager = $this->manager('autoTask');
        $mergedContext = $this->buildContext($context ?? []);
        return $autoManager ? $autoManager->generateAndSavePostImage($prompt, $type, $taskLog, $mergedContext, $postId, $modelAlias) : null;
    }

    /**
     * 5. Adım: İçeriği yayına alma (is_active = 1), Sitemap temizleme ve IndexNow ping işlemlerini sonlandırır 🚀📡
     */
    protected function finalizePost(
        int $postId,
        string $modelAlias,
        string $categorySlug = '',
        string $postSlug = '',
        string $typePrefix = 'post',
        $guids = null
    ): string {
        $taskLog = $this->service('base.taskLog');
        if ($taskLog) {
            $taskLog->step('5. Adım: Yayına Alma', "İçerik aktifleştiriliyor (is_active = 1), önbellek temizleniyor ve ping gönderiliyor...");
        }

        $autoManager = $this->manager('autoTask');

        if ($autoManager) {
            $pubRes = $autoManager->finalizeAndPublishPost(
                $postId,
                $modelAlias,
                $categorySlug,
                $postSlug,
                $typePrefix,
                $taskLog,
                $guids,
                $this->getProjectKey()
            );

            if (is_string($pubRes) && !empty($pubRes)) {
                $this->lastPublishedUrl = $pubRes;
            } else {
                $this->lastPublishedUrl = $this->resolvePublishedPostLink(null, $typePrefix, $postSlug, $categorySlug, false);
            }
        } else {
            $this->lastPublishedUrl = $this->resolvePublishedPostLink(null, $typePrefix, $postSlug, $categorySlug, false);
        }

        return $this->lastPublishedUrl ?: '';
    }

    /**
     * 6. Adım: Final ve Görev Başarılı Sonlandırma 🏁
     */
    protected function finishTask(int $postId, string $message = '', ?string $publishedUrl = null): array
    {
        $taskLog = $this->service('base.taskLog');

        $url = $publishedUrl ?: ($this->lastPublishedUrl ?: '');
        $plainUrl = !empty($url) ? " " . (str_starts_with($url, 'http') ? $url : $this->resolvePublishedPostLink(null, $url, '', '', false)) : '';

        $msg = !empty($message) ? $message . $plainUrl : "Otonom görev başarıyla tamamlandı! (Post ID: {$postId}){$plainUrl}";

        if ($taskLog) {
            $taskLog->step('6. Adım: Final', "Görev başarıyla işlendi: {$msg}");
        }

        return [
            'success' => true,
            'status' => 'success',
            'id' => $postId,
            'post_id' => $postId,
            'message' => $msg
        ];
    }
}
