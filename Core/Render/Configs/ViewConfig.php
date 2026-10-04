<?php

namespace Rbn\Framework\Core\Render\Configs;


use Rbn\Framework\Core\Base\Data\BaseConfig;

/**
 * ViewConfig - Unified View Engine Configuration Registry 🎨
 * 
 * Part of the RBN 3.0 Render Hub.
 * Centralizes template directives and engine-specific settings.
 */
class ViewConfig extends BaseConfig
{
    /**
     * Map of custom Blade-like directives to their PHP equivalent.
     * 
     * @return array
     */
    public static function directives(): array
    {
        return [
            // Structural & Loops are core (handled in engine), 
            // but custom ones go here.

            // Authorization 🛡️
            '/@hasrole\s*\((.*?)\)/' => '<?php if(($this->handler("access") ?? \Rbn\Framework\Core\Base\Services\BaseService::get()?->handler("access"))?->can($1)): ?>',
            '/@endhasrole/' => '<?php endif; ?>',

            // View Helpers (Powered by ViewHelperTrait DNA) 🧬✨
            '/@csrfToken/i' => '<?php echo $this->csrfToken(); ?>',
            '/@csrf/i' => '<?php echo $this->csrfField(); ?>',
            '/@method\s*\(\s*\'(.+?)\'\s*\)/i' => '<?php echo $this->methodField("$1"); ?>',

            // System Info
            '/@sys\s*\(\s*\'(.+?)\'\s*\)/i' => '<?php echo \Rbn\Framework\Core\System\Registries\RbnSystemInfo::get(\'$1\'); ?>',

            // Paths & Imports
            // [A0-9 / R-02] @import -> ViewEngine::import() -> path() + compile().
            // Iki katman da kok siniri kontrolu yapar; `@import('../../evil.php')`
            // bu nedenle require edilemeden reddedilir.
            '/@path\s*\((.*?)\)/i' => '<?php echo $this->path($1); ?>',
            '/@import\s*\((.*?)\)/i' => '<?php require $this->import($1); ?>',

            // Code Blocks
            '/@php/' => '<?php ',
            '/@endphp/' => ' ?>',
        ];
    }
}
