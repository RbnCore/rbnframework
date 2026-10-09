<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Support\Bridges\Helpers\Library;

/**
 * RenderFieldHelper - The RBN Framework UI Engine 🏛️🏗️⚓
 * 
 * RBN Framework: Strategic decoupling of module business logic from UI rendering.
 * [MOTOR]: renderField() - Generic HTML generation engine.
 * [BRIDGE]: renderSetting() - Specific normalization for Admin Settings.
 */
class RenderFieldHelper
{
    /**
     * [BRIDGE] Renders a field specifically for the Admin Settings module.
     * Maps setting-specific keys to generic motor-understandable data. 🧬⚙️
     */
    public function renderSetting(array|object $item, array $config = []): string
    {
        if (is_object($item)) {
            $item = method_exists($item, 'toArray') ? $item->toArray() : (array) $item;
        }

        // Settings normalization logic 🛡️⚓
        $normalized = [
            'type'        => $item['field_type'] ?? 'text',
            'key'         => $item['setting_key'] ?? '',
            'value'       => $item['setting_value'] ?? '',
            'placeholder' => $item['label_tr'] ?? $item['setting_key'] ?? '',
            'required'    => (bool)($item['is_required'] ?? false),
            'options'     => $item['field_options'] ?? $item['setting_options'] ?? '',
            'select_html' => $item['select_html'] ?? ''
        ];

        // Default name_prefix for settings module
        $config['name_prefix'] = $config['name_prefix'] ?? 'settings';

        return $this->renderField($normalized, $config);
    }

    /**
     * [MOTOR] Pure rendering engine for generic UI fields. 🎭✨
     * Expects normalized data: type, key, value, placeholder, required.
     */
    public function renderField(array $data, array $config = []): string
    {
        $type       = $data['type'] ?? 'text';
        $key        = $data['key'] ?? '';
        $namePrefix = $config['name_prefix'] ?? null;
        $name       = $namePrefix ? "{$namePrefix}[{$key}]" : $key;
        $value      = $data['value'] ?? '';

        // Standardize Config
        $config = array_merge([
            'id'          => $key,
            'required'    => (bool)($data['required'] ?? false),
            'placeholder' => $data['placeholder'] ?? $key
        ], $config);

        // 🎼 Style Intelligence 🎨✨
        $userClass  = $config['class'] ?? 'form-control';
        $isSelect   = ($type === 'select');
        $finalClass = str_replace(['form-control', 'form-select'], '', $userClass);
        $finalClass = ($isSelect ? 'form-select' : 'form-control') . ' ' . $finalClass;

        if (str_contains($finalClass, '-lg')) {
            // Native LG handles the size correctly.
        }

        $config['class'] = trim($finalClass);

        return match ($type) {
            'select'                    => $this->renderSelect($data, $name, $value, $config),
            'textarea'                  => $this->renderTextarea($name, $value, $config),
            'checkbox', 'toggle', 'switch' => $this->renderToggle($name, $value, $config),
            'color'                     => $this->buildInput('color', $name, $value, array_merge($config, ['class' => $config['class'] . ' form-control-color w-100'])),
            'number', 'email', 'url'    => $this->buildInput($type, $name, $value, $config),
            'tel'                       => $this->buildInput('tel', $name, $value, array_merge($config, ['data-mask' => 'phone'])),
            default                     => $this->buildInput('text', $name, $value, $config),
        };
    }

    /**
     * Unified Select Renderer 🎭⚓
     */
    protected function renderSelect(array $data, string $name, $value, array $config): string
    {
        $optionsHtml = $data['select_html'] ?? '';
        
        if (empty($optionsHtml)) {
            $optionsHtml = $this->parseOptions($data['options'] ?? '', $value);
        }

        $config['name'] = $name;
        return $this->buildTag('select', $optionsHtml, $config);
    }

    /**
     * Options Parser (JSON/Array to HTML) 🧬
     */
    public function parseOptions($data, $selectedValue = ''): string
    {
        if (empty($data)) return '<option value="">Seçenek bulunamadı</option>';
        
        $options = is_string($data) ? json_decode($data, true) : $data;
        if (!is_array($options)) return '<option value="">Geçersiz format</option>';

        $html = '';
        foreach ($options as $key => $option) {
            $val   = is_array($option) ? ($option['value'] ?? $key) : $key;
            $label = is_array($option) ? ($option['label'] ?? $val) : $option;
            $icon  = is_array($option) ? ($option['icon'] ?? '') : '';
            
            $selected = ((string)$val === (string)$selectedValue) ? 'selected' : '';
            $html .= sprintf('<option value="%s" %s>%s %s</option>', htmlspecialchars((string)$val), $selected, $icon, htmlspecialchars((string)$label));
        }
        return $html;
    }

    /**
     * Unified Toggle/Switch Renderer ⚡
     */
    protected function renderToggle(string $name, $value, array $config): string
    {
        $checked = in_array($value, ['1', 1, 'on', true], true) ? 'checked' : '';
        $label   = $checked ? 'Aktif' : 'Pasif';
        $color   = $checked ? 'text-success' : 'text-danger';

        return sprintf(
            '<div class="form-check form-switch ps-0 d-flex align-items-center gap-3">
                <input type="hidden" name="%s" value="0">
                <input class="form-check-input ms-0 shadow-none" type="checkbox" name="%s" id="%s" value="1" %s style="width:40px;height:20px;">
                <span class="small text-muted">Durum: <b class="%s">%s</b></span>
            </div>',
            $name, $name, $config['id'], $checked, $color, $label
        );
    }

    /**
     * Textarea Renderer 📝
     */
    protected function renderTextarea(string $name, $value, array $config): string
    {
        $text = (string)$value;
        $config['rows'] = min(8, max(2, (int)ceil(mb_strlen($text) / 80), substr_count($text, "\n") + 1));
        $config['name'] = $name;
        
        return $this->buildTag('textarea', htmlspecialchars($text), $config);
    }

    /**
     * Standard Input Builder 🛰️
     */
    public function buildInput(string $type, string $name, $value = '', array $config = []): string
    {
        $config['type']  = $type;
        $config['name']  = $name;
        $config['value'] = htmlspecialchars((string)$value);
        
        return $this->buildTag('input', null, $config);
    }

    /**
     * MASTER TAG BUILDER 🏛️⚓🔱
     */
    protected function buildTag(string $tag, ?string $content = null, array $config = []): string
    {
        $attributes = [];
        $map = ['id', 'name', 'class', 'type', 'value', 'placeholder', 'rows', 'required', 'readonly'];
        
        foreach ($map as $attr) {
            if (isset($config[$attr]) && $config[$attr] !== false) {
                if ($config[$attr] === true) {
                    $attributes[] = $attr;
                } else {
                    $attributes[] = sprintf('%s="%s"', $attr, $config[$attr]);
                }
            }
        }

        if (isset($config['attributes']) && is_array($config['attributes'])) {
            foreach ($config['attributes'] as $attr => $val) {
                $attributes[] = sprintf('%s="%s"', $attr, htmlspecialchars((string)$val));
            }
        }

        $attrString = implode(' ', $attributes);
        return ($content === null && $tag === 'input') 
            ? sprintf('<input %s>', $attrString)
            : sprintf('<%s %s>%s</%s>', $tag, $attrString, $content, $tag);
    }
}
