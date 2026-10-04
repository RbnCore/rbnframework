<?php

namespace Rbn\Framework\Core\Support\Bridges\Helpers\Library\Icons;

/**
 * IconCollection - Fluent API Builder for Icons
 * 
 * Provides chainable methods to filter and format icon data for UI components.
 * 
 * @package App\Core\System\Icons
 * @version 1.0
 * @author RBN Bilişim
 */
class IconCollection
{
    /**
     * @var array The raw icon data (Format: [class => ['label' => '...', 'emoji' => '...']])
     */
    protected array $items = [];

    /**
     * Constructor
     * 
     * @param array $items
     */
    public function __construct(array $items = [])
    {
        // Normalize legacy flat arrays [class => label] to rich arrays
        foreach ($items as $class => $data) {
            if (is_string($data)) {
                $this->items[$class] = [
                    'label' => $data,
                    'emoji' => '🔹' // Fallback emoji for legacy items
                ];
            } else {
                $this->items[$class] = $data;
            }
        }
    }

    // ====================================================================
    // FILTERING (Chainable)
    // ====================================================================

    /**
     * Limit the number of returned icons
     * 
     * @param int $count
     * @return self
     */
    public function limit(int $count): self
    {
        if ($count > 0) {
            $this->items = array_slice($this->items, 0, $count, true);
        }
        return $this;
    }

    /**
     * Get random icons from the collection
     * 
     * @param int $count
     * @return self
     */
    public function random(int $count = 1): self
    {
        if (empty($this->items)) {
            return $this;
        }

        $keys = array_keys($this->items);
        shuffle($keys);

        $limitedKeys = array_slice($keys, 0, $count);

        $randomItems = [];
        foreach ($limitedKeys as $key) {
            $randomItems[$key] = $this->items[$key];
        }

        $this->items = $randomItems;
        return $this;
    }

    /**
     * Exclude specific icon classes from the collection
     * 
     * @param array $classes
     * @return self
     */
    public function except(array $classes): self
    {
        foreach ($classes as $class) {
            unset($this->items[$class]);
        }
        return $this;
    }

    /**
     * Search icons by keyword (looks in class, label, and emoji)
     * 
     * @param string $keyword
     * @return self
     */
    public function search(string $keyword): self
    {
        $keyword = mb_strtolower($keyword);

        $filtered = array_filter($this->items, function ($data, $class) use ($keyword) {
            $label = mb_strtolower($data['label'] ?? '');
            return str_contains($class, $keyword) || str_contains($label, $keyword);
        }, ARRAY_FILTER_USE_BOTH);

        $this->items = $filtered;
        return $this;
    }

    // ====================================================================
    // OUTPUT GENERATORS (Terminal methods)
    // ====================================================================

    /**
     * Return raw collection array
     * 
     * @return array
     */
    public function toArray(): array
    {
        return $this->items;
    }

    /**
     * Return array formatted for standard HTML <select> inputs
     * Format: ['bi-gear' => '⚙️ Ayarlar']
     * 
     * @return array
     */
    public function toSelect(): array
    {
        $selectOptions = [];
        foreach ($this->items as $class => $data) {
            $emoji = $data['emoji'] ?? '🔹';
            $label = $data['label'] ?? $class;
            $selectOptions[$class] = "{$emoji} {$label}";
        }
        return $selectOptions;
    }

    /**
     * Return raw HTML <option> tags for direct echoing in a <select>
     * 
     * @param string|null $selectedValue Currently selected class
     * @return string
     */
    public function toOptionsHtml(?string $selectedValue = null): string
    {
        $html = '';
        foreach ($this->toSelect() as $class => $displayText) {
            $selected = ($selectedValue === $class) ? ' selected' : '';
            $html .= "<option value=\"{$class}\"{$selected}>{$displayText}</option>\n";
        }
        return $html;
    }

    /**
     * Return JSON string formatted perfectly for Select2 or TomSelect
     * 
     * @return string
     */
    public function toJson(): string
    {
        $jsonArray = [];
        foreach ($this->items as $class => $data) {
            $jsonArray[] = [
                'id' => $class,
                'text' => $data['label'] ?? $class,
                'emoji' => $data['emoji'] ?? '🔹',
                'icon' => $class
            ];
        }
        return json_encode($jsonArray, JSON_UNESCAPED_UNICODE);
    }
}
