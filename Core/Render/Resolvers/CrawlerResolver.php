<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Render\Resolvers;

use Rbn\Framework\Core\Base\Web\BaseRender;

/**
 * CrawlerResolver - Search Engine Intelligence & Discovery Hub Orchestrator 🧬🤖⚓
 * 
 * RBN Framework Framework Standards.
 * Orchestrates dedicated sub-resolvers for Robots, Sitemaps, Feeds and LLMs.
 */
class CrawlerResolver extends BaseRender
{

    /**
     * Resolves CrawlerMap configuration class for active project 🚀
     */
    public function resolveCrawlerMap(): ?array
    {
        $projectBundles = (array) $this->getRouteConfig(project_key(), 'bundles');
        foreach ($projectBundles as $pBundle) {
            $crawlerMapClass = "Rbn\\Project\\Modules\\Backend\\" . ucfirst($pBundle) . "\\Models\\CrawlerMap";
            if (class_exists($crawlerMapClass) && defined("{$crawlerMapClass}::MAP")) {
                return $crawlerMapClass::MAP;
            }
        }
        return null;
    }

    /**
     * Resolves project sources 🗺️
     */
    public function resolveProjectSources(): array
    {
        $map = $this->resolveCrawlerMap();
        if ($map !== null && !empty($map['sources'])) {
            return $map['sources'];
        }
        return [];
    }

    /**
     * Model name Direct-Hit resolver 🎯
     */
    public function resolveDirectModel(string $identifier, string $moduleNamespace): string
    {
        if (str_contains($identifier, '\\')) {
            return $identifier;
        }

        $modelName = ucfirst($identifier) . 'Model';

        $internalClass = $moduleNamespace . '\\' . $modelName;
        if (class_exists($internalClass)) {
            return $internalClass;
        }

        $appModelClass = "Rbn\Project\App\Models\\{$modelName}";
        if (class_exists($appModelClass)) {
            return $appModelClass;
        }

        return $identifier;
    }

    /**
     * Dynamic service/model method invoker 🧠⚓
     */
    public function callServiceMethod(array $config, string $methodKey, string $defaultMethod, ...$args)
    {
        $targetClass = $config['service'] ?? ($config['model'] ?? null);
        if (!$targetClass) {
            $targetClass = \Rbn\Framework\Core\Database\Models\Project\ContentCategoryModel::class;
        }

        if ($targetClass && class_exists($targetClass)) {
            $service = new $targetClass();
            $method = $config['method'] ?? ($config[$methodKey] ?? $defaultMethod);

            // 1. Özel tanımlanmış metod varsa öncelikle onu çalıştır 🎯
            if ($method !== $defaultMethod && method_exists($service, $method)) {
                return $service->$method(...$args);
            }

            if ($methodKey === 'raw_feed_method') {
                if (method_exists($service, 'getFeedEntries')) {
                    return $service->getFeedEntries(...$args);
                }
            } else {
                if (method_exists($service, 'getSitemapEntries') && $defaultMethod === 'getEntries') {
                    return $service->getSitemapEntries(...$args);
                }
            }

            if (!($service instanceof \Rbn\Framework\Core\Base\Data\BaseModel)) {
                $dynamicMethod = 'get' . ucfirst($defaultMethod);
                if (method_exists($service, $dynamicMethod)) {
                    return $service->$dynamicMethod(['is_active' => 1, 'limit' => 30]);
                }
            }

            if ($service instanceof \Rbn\Framework\Core\Base\Data\BaseModel) {
                $where = $config['where'] ?? [];
                $query = $service->query();

                if (!empty($where)) {
                    foreach ($where as $field => $condition) {
                        if (is_array($condition) && count($condition) >= 2) {
                            $op = $condition[0];
                            $val = $condition[1] === '{project_key}' ? project_key() : $condition[1];
                            $query->where($field, $op, $val);
                        } else {
                            $val = $condition === '{project_key}' ? project_key() : $condition;
                            $query->where($field, $val);
                        }
                    }
                }

                if ($method === 'getCount') {
                    return $query->count();
                }

                $page = $args[0] ?? 1;
                $limit = 50000;
                $offset = ($page - 1) * $limit;

                $result = $query->orderBy('created_at', 'DESC')->limit($limit)->offset($offset)->get();

                if (is_array($result))
                    return $result;
                if (is_object($result)) {
                    if (method_exists($result, 'all'))
                        return $result->all();
                    if (method_exists($result, 'toArray'))
                        return $result->toArray();
                    return (array) $result;
                }
                return [];
            }
        }
        return null;
    }
}
