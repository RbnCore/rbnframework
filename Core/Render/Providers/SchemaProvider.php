<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Render\Providers;

use Rbn\Framework\Core\Base\BaseComponent;

/**
 * SchemaProvider - SEO JSON-LD Schema Rendering Engine 🛰️🎨⚓
 * Part of the RBN Framework Architecture.
 */
class SchemaProvider extends BaseComponent
{
    /**
     * @var array Compiled schemas
     */
    public array $schemas = [];

    /**
     * Build schema configurations using preset templates.
     */
    public function build(string $name, array $data = []): self
    {
        $preset = $this->preset('schema');
        if ($preset && method_exists($preset, 'execute')) {
            $preset->execute($this, $name, $data);
        }
        return $this;
    }

    /**
     * Render all compiled schemas into the final JSON-LD HTML.
     */
    public function render(): string
    {
        if (empty($this->schemas)) {
            return '';
        }

        $graph = [];
        $seen = [];
        $breadcrumbList = null;

        foreach ($this->schemas as $schema) {
            unset($schema['@context']);

            if (($schema['@type'] ?? '') === 'BreadcrumbList') {
                $breadcrumbList = $schema;
                continue;
            }

            $sig = json_encode($schema);
            if (isset($seen[$sig])) {
                continue;
            }
            $seen[$sig] = true;

            $graph[] = $schema;
        }

        if ($breadcrumbList !== null) {
            $graph[] = $breadcrumbList;
        }

        if (empty($graph)) {
            return '';
        }

        $mergedSchema = [
            '@context' => 'https://schema.org',
            '@graph' => $graph
        ];

        $json = json_encode($mergedSchema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $indentedJson = implode("\n", array_map(fn($line) => '    ' . $line, explode("\n", $json)));

        $html = "\n    <!-- [FRAMEWORK SCHEMA ENGINE] -->\n";
        $html .= "    <script type=\"application/ld+json\">\n";
        $html .= $indentedJson;
        $html .= "\n    </script>\n";

        return $html;
    }
}
