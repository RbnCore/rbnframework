<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnPipeline\Builders\Tasks;

use Rbn\Framework\Core\Base\Attributes\Component;
use Rbn\Framework\Core\Support\Exceptions\CronTaskException;

/**
 * ContentRewriteTaskBuilder - Universal Content Rewriter / Humanizer Task Builder 🤖📝🛡️
 * 
 * Location: RbnPipeline/Builders/Tasks/ContentRewriteTaskBuilder.php
 * RBN Framework Framework Standards.
 * Universal task builder for rewriting existing published articles with humanized AI prompts,
 * preserving existing images, IDs, and slugs.
 */
#[Component(alias: 'task.content_rewrite', type: 'builder')]
class ContentRewriteTaskBuilder extends BaseContentTaskBuilder
{
    /**
     * Evrensel Mevcut İçerik Yenileme & İnsanlaştırma Akışı 🤖📝🛡️
     */
    protected function executeAutopilot(array $params): array
    {
        $preset = (string) ($params['preset'] ?? ($params['type'] ?? 'blog'));
        $taskType = (string) ($params['task_type'] ?? ($preset . '_publish'));
        $postModelName = $params['local_model'] ?? ($params['post_model'] ?? '');

        if (empty($postModelName)) {
            throw CronTaskException::configuration("Yenilenecek içerik modeli (local_model) belirtilmemiş.");
        }

        $modelInstance = $this->model($postModelName);
        if (!$modelInstance) {
            throw CronTaskException::configuration("Model [{$postModelName}] yüklenemedi.");
        }

        // 1. Adım: Sıradaki Henüz Yenilenmemiş (is_rewritten = 0) 1 Aktif İçeriği Seç 🎯
        $targetPost = null;

        // Özel ID verilmişse onu çek
        $targetId = (int) ($params['target_id'] ?? ($params['id'] ?? 0));
        if ($targetId > 0) {
            $targetPost = $modelInstance->query()->where('id', $targetId)->first();
        } else {
            // Önce henüz yenilenmemiş (is_rewritten = 0) aktif yayınları sırayla çek
            try {
                $targetPost = $modelInstance->query()
                    ->where('is_active', 1)
                    ->where('is_rewritten', 0)
                    ->orderBy('id', 'asc')
                    ->first();
            } catch (\Throwable $e) {
                $targetPost = null;
            }

            // Eğer is_rewritten kolonu yoksa veya tüm içerikler yenilenmişse updated_at ASC sırasına düş
            if (empty($targetPost)) {
                $targetPost = $modelInstance->query()
                    ->where('is_active', 1)
                    ->orderBy('updated_at', 'asc')
                    ->first();
            }
        }

        if (empty($targetPost)) {
            return ['status' => 'skipped', 'message' => 'Yenilenecek yayınlanmış içerik bulunamadı.'];
        }

        $postArr = is_object($targetPost) ? (array) $targetPost : $targetPost;
        $postId = (int) ($postArr['id'] ?? 0);
        $title = (string) ($postArr['title'] ?? ($postArr['name'] ?? 'İçerik'));
        $existingSlug = (string) ($postArr['slug'] ?? '');

        $taskLog = $this->service('base.taskLog');
        if ($taskLog) {
            $taskLog->step('1. Adım: Yenilenecek İçerik Seçildi', "ID: {$postId} | Başlık: '{$title}' | Mevcut Slug: '{$existingSlug}'");
        }

        // 2. Adım: İnsanlaştırılmış AI Metni Üret 🧠
        $generationContext = $this->buildContext([
            'task_type' => $taskType,
            'topic' => $title,
            'existing_content' => $postArr['content'] ?? '',
            'category_name' => $postArr['category_name'] ?? 'Genel'
        ]);

        $res = $this->generateText($preset, $title, $generationContext);

        if (empty($res) || empty($res['content'])) {
            $msg = "'{$title}' içeriği için AI yeniden yazım metni üretilemedi.";
            if ($taskLog) {
                $taskLog->failed($msg);
            }
            return ['success' => false, 'message' => $msg];
        }

        // 3. Adım: Veritabanı Güncellemesi (Görsel ve Slug KESİNLİKLE Değiştirilmez!) 💾
        $summary = $res['summary'] ?? ($res['short_description'] ?? ($res['short_desc'] ?? ''));
        if (empty($summary) && !empty($res['content'])) {
            $summary = mb_substr(trim(strip_tags($res['content'])), 0, 160) . '...';
        }

        $faqs = $res['faqs'] ?? ($res['faq'] ?? null);
        $prosCons = $res['pros_cons'] ?? ($res['pros_and_cons'] ?? null);

        $updateData = [
            'title' => $res['title'] ?? $title,
            'summary' => $summary,
            'content' => $res['content'] ?? '',
            'faqs' => is_array($faqs) ? json_encode($faqs, JSON_UNESCAPED_UNICODE) : $faqs,
            'seo_title' => $res['seo_title'] ?? $title,
            'seo_description' => $res['seo_description'] ?? ($summary ?: $title),
            'seo_keywords' => $res['seo_keywords'] ?? '',
            'is_rewritten' => 1,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $options = $params['generation_options'] ?? [];
        if (!empty($options['has_author_comment']) || !empty($params['has_author_comment'])) {
            $updateData['author_comment'] = $res['author_comment'] ?? ($postArr['author_comment'] ?? '');
        }
        if (!empty($options['has_pros_cons']) || !empty($params['has_pros_cons'])) {
            $updateData['pros_cons'] = is_array($prosCons) ? json_encode($prosCons, JSON_UNESCAPED_UNICODE) : $prosCons;
        }

        // 📱 Evrensel Sosyal Medya Açıklaması ve Etiketleri (Social Summary & Hashtags)
        if (!empty($res['social_summary'])) {
            $updateData['social_summary'] = (string) $res['social_summary'];
        }
        if (!empty($res['social_hashtags'])) {
            $updateData['social_hashtags'] = is_array($res['social_hashtags']) ? implode(' ', $res['social_hashtags']) : (string) $res['social_hashtags'];
        }

        $modelInstance->query()->where('id', $postId)->update($updateData);

        if ($taskLog) {
            $taskLog->step('2. Adım: Veritabanı Yenilendi', "Görsel ve Slug korundu, içerik %100 insansı prompt ile güncellendi.");
        }

        // 4. Adım: Kategori Slug Çözümleme ve Yayına Alma (is_active = 1, Önbellek & URL) 🚀
        $categorySlug = '';
        $catId = (int) ($postArr['category_id'] ?? 0);
        $catModelAlias = $params['category_model'] ?? 'blog.category';

        if ($catId > 0 && !empty($catModelAlias)) {
            try {
                $catModel = $this->model($catModelAlias);
                if ($catModel) {
                    $catObj = $catModel->query()->where('id', $catId)->first();
                    if ($catObj) {
                        $categorySlug = (string) (is_object($catObj) ? ($catObj->slug ?? '') : ($catObj['slug'] ?? ''));
                    }
                }
            } catch (\Throwable $e) {
                // Fallback
            }
        }

        $publishedUrl = $this->finalizePost($postId, $postModelName, $categorySlug, $existingSlug, 'blog');
        return $this->finishTask($postId, "İçerik İnsanlaştırıldı ve Güncellendi: [{$title}] (ID: {$postId})", $publishedUrl);
    }
}
