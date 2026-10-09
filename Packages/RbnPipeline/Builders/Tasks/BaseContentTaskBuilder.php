<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnPipeline\Builders\Tasks;

use Rbn\Framework\Packages\RbnPipeline\Builders\AbstractTaskBuilder;
use Rbn\Framework\Core\Support\Exceptions\CronTaskException;

/**
 * BaseContentTaskBuilder - Standardized Content Autopilot Task Builder Base 🤖📰📈
 * 
 * Location: RbnPipeline/Builders/Tasks/BaseContentTaskBuilder.php
 * RBN Framework Standard.
 * Dedicated base class for all text/content publishing task builders (Blog, News, Trends, etc.).
 * Encapsulates standard 6-step content pipeline execution to keep concrete builders lightweight and clean.
 */
abstract class BaseContentTaskBuilder extends AbstractTaskBuilder
{
    /**
     * Standart Otonom İçerik Üretim ve Yayınlama Şablonu 🤖🎨🚀
     */
    protected function runStandardPipeline(
        array $params,
        callable $candidateFetcher,
        string $presetName = 'blog',
        string $taskType = 'blog_publish'
    ): array {
        $this->taskParams['preset'] = $presetName;
        if (empty($this->taskParams['category_type'])) {
            $this->taskParams['category_type'] = (strtolower($presetName) === 'news') ? 'news' : 'blog';
        }
        $this->taskParams['content_type'] = $this->taskParams['content_type'] ?? $this->taskParams['category_type'];

        $projectKey = $this->getProjectKey();
        $postModelName = $params['local_model'] ?? ($params['post_model'] ?? '');

        // 1. Sıradaki Dengeli Adayı Çözümle 🎲
        $draft = $candidateFetcher($params);
        if (empty($draft) || empty($draft['title'])) {
            throw CronTaskException::candidateNotFound();
        }

        $title = (string) ($draft['title'] ?? 'Otonom İçerik');

        // 2. Yapay Zeka ile Metni Üret 🧠
        $generationContext = $this->buildContext([
            'task_type' => $taskType,
            'topic' => $title,
            'draft' => $draft,
            'category_name' => $draft['category_name'] ?? ($draft['category_title'] ?? 'Genel')
        ]);

        $res = $this->generateText($presetName, $title, $generationContext);

        // 3. Adım: Veritabanına Makale Kaydı (Pasif / Draft) 💾
        $options = $params['generation_options'] ?? [];
        $draftCatId = (int) ($draft['category_id'] ?? 0);

        $summary = $res['summary'] ?? ($res['short_description'] ?? ($res['short_desc'] ?? ''));
        if (empty($summary) && !empty($res['content'])) {
            $summary = mb_substr(trim(strip_tags($res['content'])), 0, 160) . '...';
        }

        $faqs = $res['faqs'] ?? ($res['faq'] ?? null);
        $prosCons = $res['pros_cons'] ?? ($res['pros_and_cons'] ?? null);
        $slugCandidate = !empty($res['slug']) ? (string) $res['slug'] : (string) ($res['title'] ?? $title);
        $seoHelper = $this->helper('meta.seo');
        $rawSlug = $seoHelper ? $seoHelper->seoSlug($slugCandidate) : '';

        $fallbackCatId = $draft['fallback_category_id'] ?? ($params['fallback_category_id'] ?? null);
        $postData = [
            'project_key' => $draft['project_key'] ?? $projectKey,
            'category_id' => $draftCatId > 0 ? $draftCatId : (!empty($res['category_id']) ? (int) $res['category_id'] : ($fallbackCatId ? (int) $fallbackCatId : null)),
            'title' => $res['title'] ?? $title,
            'slug' => $rawSlug,
            'summary' => $summary,
            'content' => $res['content'] ?? '',
            'faqs' => is_array($faqs) ? json_encode($faqs, JSON_UNESCAPED_UNICODE) : $faqs,
            'seo_title' => $res['seo_title'] ?? $title,
            'seo_description' => $res['seo_description'] ?? ($summary ?: $title),
            'seo_keywords' => $res['seo_keywords'] ?? '',
            'image_prompt' => $res['image_prompt'] ?? ''
        ];

        if (!empty($options['has_author_comment']) || !empty($params['has_author_comment'])) {
            $postData['author_comment'] = $res['author_comment'] ?? '';
        }
        if (!empty($options['has_cta']) || !empty($params['has_cta'])) {
            $postData['cta'] = $res['cta'] ?? '';
        }
        if (!empty($options['has_pros_cons']) || !empty($params['has_pros_cons'])) {
            $postData['pros_cons'] = is_array($prosCons) ? json_encode($prosCons, JSON_UNESCAPED_UNICODE) : $prosCons;
        }

        // 🪝 Alt sınıfların özel kolon/veri enjeksiyonu yapabilmesi için kanca (Hook)
        $postData = $this->preparePostData($res, $draft, $params, $postData);

        $postId = $this->savePost($postModelName, $postData, $draft);

        // 4. Adım: Imagen ile Kapak Resmi Üretimi & Veritabanı Kaydı 🎨
        $this->generateImage($res['image_prompt'] ?? '', $presetName, $res, $postId, $postModelName);

        // 5. Adım: Yayına Alma (is_active = 1, Sitemap & IndexNow) 🚀
        $typePrefix = $params['type_prefix'] ?? ($params['url_prefix'] ?? ($params['category_type'] ?? null));
        if (empty($typePrefix)) {
            if (str_contains($postModelName, '.blog.') || str_ends_with($postModelName, '.blog') || str_contains($postModelName, 'blog')) {
                $typePrefix = 'blog';
            } elseif (str_contains($postModelName, '.news.') || str_ends_with($postModelName, '.news') || str_contains($postModelName, 'news')) {
                $typePrefix = 'haber';
            } else {
                $typePrefix = (strtolower($presetName) === 'news' ? 'haber' : 'blog');
            }
        }
        $publishedUrl = $this->finalizePost($postId, $postModelName, '', $rawSlug, $typePrefix, $draft);

        // 6. Adım: Final (Görev Başarılı Sonlandırma) 🏁
        return $this->finishTask($postId, "İçerik başarıyla yayınlandı: " . $title, $publishedUrl);
    }

    /**
     * Evrensel Veri ve Kolon Enjeksiyon Kancası (Hook) 🪝📱🌐
     * Sosyal medya alanlarını (social_summary, social_hashtags) ve kaynak bilgilerini (source_url, source_name)
     * tüm içerik türlerinde (Blog, News, Trends vb.) veritabanı dizisine enjekte eder.
     */
    protected function preparePostData(array $res, array $draft, array $params, array $postData): array
    {
        $options = $params['generation_options'] ?? ($params['options'] ?? []);

        // 1. Kaynak URL, İsim ve GUID Enjeksiyonu 🔗
        if (!empty($draft['guid'])) {
            $postData['guid'] = (string) $draft['guid'];
        }
        if (!empty($draft['source_url'])) {
            $postData['source_url'] = (string) $draft['source_url'];
        }
        if (!empty($draft['source_name'])) {
            $postData['source_name'] = (string) $draft['source_name'];
        }

        // 2. Evrensel Sosyal Medya Kolonları (Social Summary & Hashtags) 📱🌐
        if (!empty($res['social_summary'])) {
            $postData['social_summary'] = (string) $res['social_summary'];
        }

        if (!empty($res['social_hashtags']) || !empty($params['fixed_hashtags']) || !empty($options['fixed_hashtags'])) {
            $hashtags = [];
            if (!empty($res['social_hashtags'])) {
                $rawTags = is_array($res['social_hashtags']) ? $res['social_hashtags'] : explode(' ', (string) $res['social_hashtags']);
                foreach ($rawTags as $tag) {
                    $tag = trim($tag);
                    if ($tag !== '') {
                        $hashtags[mb_strtolower($tag)] = str_starts_with($tag, '#') ? $tag : ('#' . $tag);
                    }
                }
            }

            // Proje/Görev Düzeyinde Zorunlu Sabit Hashtag'ler 🏷️
            $fixedTags = $params['fixed_hashtags'] ?? ($options['fixed_hashtags'] ?? ($params['default_hashtags'] ?? []));
            if (!empty($fixedTags)) {
                $fixedList = is_array($fixedTags) ? $fixedTags : explode(' ', (string) $fixedTags);
                foreach ($fixedList as $fTag) {
                    $fTag = trim($fTag);
                    if ($fTag !== '') {
                        $fTagFormatted = str_starts_with($fTag, '#') ? $fTag : ('#' . $fTag);
                        $hashtags[mb_strtolower($fTagFormatted)] = $fTagFormatted;
                    }
                }
            }

            $postData['social_hashtags'] = implode(' ', array_values($hashtags));
        }

        return $postData;
    }
}


