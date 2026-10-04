<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnPipeline\Builders\Tasks;

use Rbn\Framework\Core\Base\Attributes\Component;

/**
 * ContentTaskBuilder - Sovereign Universal Content Autopilot Task Builder 🚀🤖📰📈
 * 
 * Location: RbnPipeline/Builders/Tasks/ContentTaskBuilder.php
 * RBN 3.5 Masterpiece Standard.
 * Universal master task builder for all content publishing workflows (Blog, News, Trends, etc.) across all projects.
 */
#[Component(alias: 'task.content', type: 'builder')]
class ContentTaskBuilder extends BaseContentTaskBuilder
{
    /**
     * Evrensel Otonom İçerik Yayınlama Akışı (Master Autopilot) 🤖🎨🚀
     * 
     * 💡 MİMARİ NOT & PRESET EŞLEME KURALI:
     * Framework seviyesinde (ApiService / PromptBuilder) desteklenen birincil preset adları: ['blog', 'news', 'trends', 'custom'].
     * Eğer bu 3 ana preset dışında özel veya yeni bir görev tipi gelirse (Örn: 'rss_publish' -> 'news', veya 'xxx_publish' -> 'blog'),
     * sistemin ilgili preset sınıfını (BlogPreset, NewsPreset, TrendsPreset) yükleyebilmesi için $preset değişkeni 
     * mutlaka bu birincil preset adlarından birine eşlenmelidir ($preset = 'blog' veya $preset = 'news').
     */
    protected function executeAutopilot(array $params): array
    {
        $preset = (string) ($params['preset'] ?? ($params['type'] ?? 'blog'));
        $taskType = (string) ($params['task_type'] ?? ($preset . '_publish'));

        $rawSource = strtolower(trim((string) ($params['source'] ?? 'draft')));

        // Aday seçimi miras alınan ata sınıfa (AbstractTaskBuilder::resolveCandidate) delege edilir 🎯
        $candidateFetcher = fn(array $p) => $this->resolveCandidate($p);

        return $this->runStandardPipeline(
            $params,
            $candidateFetcher,
            $preset,
            $taskType
        );
    }
}
