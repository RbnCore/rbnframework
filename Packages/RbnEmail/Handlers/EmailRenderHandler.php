<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnEmail\Handlers;

use Rbn\Framework\Core\Base\BaseComponent;
use Rbn\Framework\Packages\RbnEmail\Models\EmailConstant;

/**
 * EmailRenderHandler - The Designer (Worker) 🎨🎻📧
 * 
 * RBN Framework: Standard (BaseComponent Actor).
 * Handles template resolution and premium HTML wrapping.
 */
class EmailRenderHandler extends BaseComponent
{
    private string $layoutPath;

    public function __construct()
    {
        $this->layoutPath = __DIR__ . '/../Views/layout.php';
    }

    /**
     * Render a project view file into the premium layout 🏗️
     */
    public function view(string $path, array $data = [], string $subject = ''): string
    {
        $content = $this->loadFile($path);
        return $this->render($content, $data, $subject);
    }

    /**
     * Render raw content into the premium layout 🏗️
     */
    public function render(string $content, array $data = [], string $subject = ''): string
    {
        // 1. Resolve Config for UI DNA
        $useMaster = !empty($data['use_master']);
        $config = $this->handler('emailConfig')->resolve($useMaster);

        // 2. Prepare visual mapping
        $templateData = array_merge($config, $data, [
            'subject' => $subject,
            'email_content' => $this->parse($content, $data), // Parse inner content first
            'year' => date('Y'),
            'logo_html' => (!empty($config['site_logo']) && $config['site_logo'] !== EmailConstant::DEFAULT_LOGO_SVG) ? '<img src="' . htmlspecialchars((string) $config['site_logo']) . '" alt="' . htmlspecialchars((string) $config['site_name']) . '" style="max-height: 50px; width: auto; margin-bottom: 12px;">' : ''
        ]);

        // 3. Fetch Skeleton (RBN Framework Layout)
        if (!file_exists($this->layoutPath)) {
            return "<html><body><h1>{$subject}</h1><div>{$templateData['email_content']}</div></body></html>";
        }

        $skeleton = file_get_contents($this->layoutPath);

        // 4. Parse DNA (Variables)
        return $this->parse($skeleton, $templateData);
    }

    /**
     * Parse variables in template string 🧼
     */
    public function parse(string $content, array $data = []): string
    {
        foreach ($data as $key => $value) {
            if (is_scalar($value)) {
                $pattern = '/\{\{\s*' . preg_quote((string) $key, '/') . '\s*\}\}/';
                $content = preg_replace($pattern, (string) $value, $content);
            }
        }
        return $content;
    }

    /**
     * Load view file content otonomous 🔎
     */
    private function loadFile(string $path): string
    {
        $viewPath = \Rbn\Framework\Core\System\Paths\Paths::project()->views($path . '.rbn.php');

        if (!file_exists($viewPath)) {
            $viewPath = \Rbn\Framework\Core\System\Paths\Paths::project()->views($path . '.php');
        }

        return file_exists($viewPath) ? file_get_contents($viewPath) : "Template not found: {$path}";
    }
}
