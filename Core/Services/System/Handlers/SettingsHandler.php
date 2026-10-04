<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Services\System\Handlers;

use Rbn\Framework\Core\Base\BaseComponent;

/**
 * SettingsHandler - Artisan Presentation & Decoration Logic 🎭🎨
 * 
 * RBN 3.5: Handles visual transformations for settings (Badges, Select options).
 * Relocated to Core\Services\System\Handlers for centralized orchestration.
 * Decoupled from the Service to ensure Single Responsibility.
 */
class SettingsHandler extends BaseComponent
{
    /**
     * Parsing setting options for select/dropdown fields 🎯
     */
    public function decorateSelectOptions(array &$settings): void
    {
        foreach ($settings as &$item) {
            if (!is_array($item) || ($item['field_type'] ?? '') !== 'select') {
                continue;
            }

            $optionsData = $item['field_options'] ?? $item['setting_options'] ?? '';
            if (empty($optionsData)) continue;

            $currentValue = $item['setting_value'] ?? '';

            // 1. JSON Format (Modern RBN)
            if (is_array($optionsData) || str_starts_with(trim((string) $optionsData), '{') || str_starts_with(trim((string) $optionsData), '[')) {
                $jsonString = is_array($optionsData) ? json_encode($optionsData) : $optionsData;
                $item['select_html'] = $this->renderHelper->parseJsonOptions($jsonString, $currentValue);
            }
            // 2. Pipe Separated Format (Legacy)
            else {
                $options = explode('|', $optionsData);
                $html = '';
                foreach ($options as $opt) {
                    $parts = explode(':', $opt);
                    $val = trim($parts[0] ?? '');
                    $lbl = trim($parts[1] ?? $parts[0] ?? '');
                    $selected = ($currentValue == $val) ? 'selected' : '';
                    $html .= "<option value=\"{$val}\" {$selected}>{$lbl}</option>";
                }
                $item['select_html'] = $html;
            }
        }
    }

    /**
     * Converts status values to HTML badges 🎨🛡️
     */
    public function decorateStatusBadges(array &$settings): void
    {
        foreach ($settings as &$item) {
            if (!is_array($item)) continue;

            $fieldType = $item['field_type'] ?? '';
            if ($fieldType === 'toggle' || $fieldType === 'switch') {
                $val = $item['setting_value'] ?? 'off';
                $isActive = ($val === '1' || $val === 1 || $val === 'on' || $val === true);

                $class = $isActive ? 'bg-success' : 'bg-secondary';
                $text = $isActive ? 'Aktif' : 'Pasif';

                $item['status_badge'] = "<span class=\"badge {$class}\">{$text}</span>";
            }
        }
    }
}
