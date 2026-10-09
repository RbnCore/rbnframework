<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Render\Providers;

use Rbn\Framework\Core\Base\Web\BaseRender;
use Rbn\Framework\Core\Support\Contracts\Base\BaseRenderInterface;
use Rbn\Framework\Core\Support\Definitions\System\FrameworkIdentity;

/**
 * AssetProvider - HTML Asset Painter 🎨🛰️⚓
 * 
 * RBN Framework: Sunumsal sorumluluk katmanı.
 * Varlık listelerini HTML etiketlerine dönüştürür.
 */
class AssetProvider extends BaseRender implements BaseRenderInterface
{
    /**
     * Unified Render Entry Point 🏹
     */
    public function render(?string $view, array $data = []): string
    {
        $html = [];

        // 1. Render CSS Styles 🎨
        if (!empty($data['styles'])) {
            // 🌐 CDN-First Sort: External libraries load before local assets
            $styles = $this->cdnFirst($data['styles']);
            foreach ($styles as $style) {
                $html[] = $this->renderStyle($style);
            }
        }

        // 2. Render JS Scripts ⚡
        if (!empty($data['scripts'])) {
            // 🌐 CDN-First Sort: External libraries load before local assets
            $scripts = $this->cdnFirst($data['scripts']);
            foreach ($scripts as $script) {
                $html[] = $this->renderScript($script);
            }
        }

        return implode("", $html);
    }

    /**
     * CDN-First Sort: Moves https:// assets before local assets. 🌐⚓
     * CDN assets (external) always take priority in load order.
     * Local assets preserve their relative definition order.
     */
    protected function cdnFirst(array $assets): array
    {
        $cdn = [];
        $local = [];

        foreach ($assets as $asset) {
            $path = $asset['path'] ?? '';
            if (str_starts_with($path, 'https://') || str_starts_with($path, 'http://')) {
                $cdn[] = $asset;
            } else {
                $local[] = $asset;
            }
        }

        return array_merge($cdn, $local);
    }

    /**
     * Render a standard CSS link tag. 🏷️
     */
    protected function renderStyle(array $style): string
    {
        $path = $style['path'] ?? '';
        if (!$path)
            return '';

        // 🛡️ Font linkleri zaten SEO RESOURCE LINKS altında basıldığı için App Assets içinde mükerrer basılmasını önle
        if (str_contains($path, '/framework-assets/fonts/')) {
            return '';
        }

        $attrString = '';
        foreach ($style['attributes'] ?? [] as $k => $v) {
            $attrString .= ' ' . htmlspecialchars((string) $k) . '="' . htmlspecialchars((string) $v) . '"';
        }

        return '    <link href="' . htmlspecialchars($path) . '" rel="stylesheet" type="text/css"' . $attrString . ' />' . PHP_EOL;
    }

    /**
     * Render a standard JS script tag with attributes. 📜
     */
    protected function renderScript(array $script): string
    {
        $path = $script['path'] ?? '';
        if (!$path)
            return '';

        $attributes = $script['attributes'] ?? [];
        $attrString = '';

        foreach ($attributes as $k => $v) {
            $attrString .= ' ' . htmlspecialchars((string) $k) . '="' . htmlspecialchars((string) $v) . '"';
        }

        return '    <script src="' . htmlspecialchars($path) . '"' . $attrString . '></script>' . PHP_EOL;
    }

    /**
     * Decorate asset content with a signature banner. 🔱🎨📝
     */
    public function decorate(string $content, string $physicalPath, ?string $label = 'RBN Framework'): string
    {
        $extension = strtolower(pathinfo($physicalPath, PATHINFO_EXTENSION));

        if (in_array($extension, ['css', 'js'])) {
            $fwName = FrameworkIdentity::FRAMEWORK_NAME;
            $fwVersion = FrameworkIdentity::FRAMEWORK_VERSION;

            $kitName = \Rbn\Framework\Core\Render\Configs\AssetConfig::NAME;
            $kitVersion = \Rbn\Framework\Core\Render\Configs\AssetConfig::VERSION;
            $fileName = basename($physicalPath);

            $banner = "/**\n";
            $banner .= " * 🔱 {$fwName} [v{$fwVersion}]\n";
            $banner .= " * 🏛️ ARCHITECTURE  : RBN Asset Engine\n";
            $banner .= " * ---------------------------------------\n";
            $banner .= " * 🛰️ COMPONENT     : {$kitName} v{$kitVersion}\n";
            $banner .= " * ⚓ RESOURCE      : {$fileName} [{$label}]\n";
            $banner .= " * 🛡️ STATUS        : Verified & Proxied\n";
            $banner .= " * ---------------------------------------\n";
            $banner .= " */\n\n";

            return $banner . ltrim($content);
        }

        return $content;
    }
}
