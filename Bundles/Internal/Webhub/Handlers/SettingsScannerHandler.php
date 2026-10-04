<?php

declare(strict_types=1);

namespace Rbn\Framework\Bundles\Internal\Webhub\Handlers;

use Rbn\Framework\Core\Base\BaseComponent;

/**
 * SettingsScannerHandler - Global Setting Analysis (Max 25 Points)
 * RBN 3.5 Handler Pipeline
 */
class SettingsScannerHandler extends BaseSeoHandler
{
    public function execute(array $params = []): array
    {
        $this->totalScore = 0;
        $this->report = [];

        $settings = $this->service('settings')
            ->withProject(active_project_key())
            ->all();
        $map = [];
        foreach ($settings as $s) {
            $key = $s['setting_key'] ?? '';
            $val = $s['setting_value'] ?? null;
            $map[$key] = $val;
        }

        // 1. Site Title Check (Max 10)
        $title = $map['meta-title'] ?? ($map['site-title'] ?? '');
        $this->awardLength('Site Başlığı İdeal Uzunluk (10-65 Karakter)', $title, 10, 65, 10, 5);

        // 2. Meta Description Check (Max 10)
        $desc = $map['meta-description'] ?? ($map['site-description'] ?? '');
        $this->awardLength('Meta Description Uzunluğu (50-160 Karakter)', $desc, 50, 160, 10, 5);

        // 3. Keywords Check (Max 5)
        $this->award('Meta Keywords Tanımlaması', !empty($map['meta-keywords'] ?? ''), 5);

        return [
            'score' => $this->totalScore,
            'report' => $this->report
        ];
    }
}
