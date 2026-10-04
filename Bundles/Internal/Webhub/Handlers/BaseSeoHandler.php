<?php

declare(strict_types=1);

namespace Rbn\Framework\Bundles\Internal\Webhub\Handlers;

use Rbn\Framework\Core\Base\BaseComponent;

/**
 * BaseSeoHandler - Abstract Base Class for SEO Scanner Pipeline
 * RBN 3.5 Handler Pipeline
 */
abstract class BaseSeoHandler extends BaseComponent
{
    protected array $report = [];
    protected int $totalScore = 0;

    /**
     * Merkezi Puanlama ve Raporlama Metodu
     */
    protected function award(string $criteria, bool $condition, int $successPoints, int $failPoints = 0): void
    {
        $earned = $condition ? $successPoints : $failPoints;
        $this->totalScore += $earned;
        
        $this->report[] = [
            'criteria' => $criteria,
            'passed' => $condition,
            'earned' => $earned,
            'max' => $successPoints
        ];
    }

    /**
     * Merkezi Karakter Uzunluğu Puanlama ve Raporlama Metodu
     */
    protected function awardLength(string $criteria, string $text, int $min, int $max, int $successPoints, int $failPoints = 0): void
    {
        if (empty($text)) {
            $this->award($criteria, false, $successPoints, 0);
            return;
        }
        $len = mb_strlen(trim($text));
        $condition = ($len >= $min && $len <= $max);
        $this->award($criteria, $condition, $successPoints, $failPoints);
    }
}
