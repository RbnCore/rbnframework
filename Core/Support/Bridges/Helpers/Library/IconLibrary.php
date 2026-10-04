<?php

namespace Rbn\Framework\Core\Support\Bridges\Helpers\Library;

use Rbn\Framework\Core\Support\Bridges\Helpers\Library\Icons\SmartIconCategories;
use Rbn\Framework\Core\Support\Bridges\Helpers\Library\Icons\IconCollection;

/**
 * IconLibrary - Merkezi İkon Yönetim Kütüphanesi
 * 
 * Bu kütüphane, projede kullanılan tüm ikonları (Tabler, FontAwesome, SVG) 
 * tek bir noktadan, tutarlı ve mühürlü bir şekilde yönetmeyi sağlar.
 */
class IconLibrary
{
    /**
     * Icon library types
     */
    const BOOTSTRAP = 'bootstrap';
    const FONTAWESOME = 'fontawesome';
    const ALL = 'all';

    // ====================================================================
    // FLUENT DATA PROVIDERS (Returns IconCollection)
    // ====================================================================

    /**
     * Start a fluent query for ALL icons
     * 
     * @return IconCollection
     */
    public function all(): IconCollection
    {
        $bootstrap = require __DIR__ . '/Icons/Internal/bootstrap_icons.php';
        $fontAwesome = require __DIR__ . '/Icons/Internal/font_awesome.php';

        return new IconCollection(array_merge(
            $bootstrap,
            $fontAwesome['solid'],
            $fontAwesome['regular'],
            $fontAwesome['brands']
        ));
    }

    /**
     * Start a fluent query for specific smart categories
     * 
     * @param string $categoryName (e.g. 'admin', 'form', 'status', 'sidebar')
     * @return IconCollection
     */
    public function category(string $categoryName): IconCollection
    {
        $icons = [];
        switch (strtolower($categoryName)) {
            case 'admin':
                $icons = SmartIconCategories::getAdminIcons();
                break;
            case 'developer':
                $icons = SmartIconCategories::getDeveloperIcons();
                break;
            case 'popular':
                $icons = SmartIconCategories::getPopularIcons();
                break;
            case 'sidebar':
                $icons = SmartIconCategories::getSidebarCategoryIcons();
                break;
            case 'menu':
                $icons = SmartIconCategories::getSidebarMenuIcons();
                break;
            case 'form':
                $icons = SmartIconCategories::getFormActionIcons();
                break;
            case 'status':
                $icons = SmartIconCategories::getStatusIcons();
                break;
        }
        return new IconCollection($icons);
    }

    /**
     * Fast access wrapper: search and start a query
     */
    public function search(string $keyword): IconCollection
    {
        return current($this->all()->search($keyword)); // returns IconCollection
    }

    // ====================================================================
    // THE AI MATCHER
    // ====================================================================

    /**
     * Smart Context Matcher: Takes a keyword and returns the best icon class
     * 
     * @param string $keyword
     * @param string $default
     * @return string
     */
    public function match(string $keyword, string $default = 'bi-circle'): string
    {
        return SmartIconCategories::matchKeyword($keyword) ?? $default;
    }


}
