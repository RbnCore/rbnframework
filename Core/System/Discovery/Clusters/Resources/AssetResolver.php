<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\System\Discovery\Clusters\Resources;

use Rbn\Framework\Core\System\Paths\Paths;
use Rbn\Framework\Core\System\Discovery\Base\BaseResolver;
use Rbn\Framework\Core\Render\Configs\AssetConfig;

/**
 * AssetResolver - Physical Asset Discovery Hub (Zemin Katmanı) 🪐🛰️⚓
 * 
 * RBN 3.5 Masterpiece: Sadece dosyanın fiziksel varlığıyla ilgilenir. 
 */
class AssetResolver extends BaseResolver
{
    /**
     * Resolve a logical path into a physical metadata object. 🕵️‍♂️⚓
     */
    public function resolve(string $name, ?string $context = null): ?object
    {
        // 1. Simetri Hazırlığı ⚖️
        $path = path_symmetric($name);
        $cleanPath = ltrim($path, '/\\');
        $registry = AssetConfig::CLUSTER_REGISTRY;

        // 2. PROJECT DISCOVERY (Project First! 🏠)
        if ($context === null || $context === 'project') {
            $projectRoot = Paths::project()->public();
            $physicalPath = $projectRoot . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $cleanPath);

            if (file_exists($physicalPath)) {
                return (object) [
                    'path'   => $physicalPath,
                    'source' => 'project',
                    'label'  => $registry['project']['label'] ?? 'Project'
                ];
            }
        }

        // 3. FRAMEWORK DISCOVERY (Framework Fallback 🏛️)
        if ($context === null || $context === 'framework') {
            $fwRoot = Paths::framework()->assets();
            $cleanPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $cleanPath);
            $physicalPath = rtrim($fwRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $cleanPath;

            // 3.1 Exact Match Check in Assets 🎯
            if (file_exists($physicalPath)) {
                return (object) [
                    'path'   => $physicalPath,
                    'source' => 'framework',
                    'label'  => $registry['core']['label'] ?? 'Core'
                ];
            }

            // 3.1.b Match Check in Resources Root (e.g. images/) 🎯
            $resourcesRoot = Paths::framework()->resources();
            $resourcePhysicalPath = rtrim($resourcesRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $cleanPath;
            if (file_exists($resourcePhysicalPath)) {
                return (object) [
                    'path'   => $resourcePhysicalPath,
                    'source' => 'framework',
                    'label'  => $registry['core']['label'] ?? 'Core'
                ];
            }

            // 3.2 Deep Discovery for Flattened Paths 🛰️🔍
            // rbnkit/rbnalert.css -> Searches inside RbnKit/... for rbnalert.css
            if (str_contains($cleanPath, DIRECTORY_SEPARATOR)) {
                $parts = explode(DIRECTORY_SEPARATOR, $cleanPath);
                $clusterCandidate = $parts[0];
                $fileName = end($parts);
                
                // Find actual cluster folder (Case Insensitive)
                $directories = glob($fwRoot . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR);
                foreach ($directories as $dir) {
                    if (strtolower(basename($dir)) === strtolower($clusterCandidate)) {
                        $foundPath = $this->deepSearch($dir, $fileName);
                        if ($foundPath) {
                            return (object) [
                                'path'   => $foundPath,
                                'source' => 'framework',
                                'label'  => $registry['core']['label'] ?? 'Core'
                            ];
                        }
                    }
                }
            }

            // 4. SMART PATTERN DISCOVERY: RbnCommon Components & Tools (Fallback ⚓)
            if (preg_match('/^rbn([A-Z][a-zA-Z0-9]+)\.(css|js)$/', $cleanPath, $matches)) {
                $ext = $matches[2];
                $patternPath = "RbnCommon/{$ext}/components/{$cleanPath}";
                
                $physicalPath = $fwRoot . DIRECTORY_SEPARATOR . $patternPath;
                if (file_exists($physicalPath)) {
                    return (object) [
                        'path'   => $physicalPath,
                        'source' => 'framework',
                        'label'  => $registry['core']['label'] ?? 'Core'
                    ];
                }
            }
        }

        return null; // Dosya hiçbir yerde yok 🚫
    }

    /**
     * Internal: Recursive search for a file within a cluster directory. 🕵️‍♂️🛰️
     */
    private function deepSearch(string $dir, string $fileName): ?string
    {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
        $fileNameLower = strtolower($fileName);

        foreach ($iterator as $file) {
            if ($file->isFile() && strtolower($file->getBasename()) === $fileNameLower) {
                return $file->getRealPath();
            }
        }

        return null;
    }

    /**
     * Physical discovery tool for URL Generation layer.
     */
    public function findPhysicalPath(string $path, &$detectedModule = null): ?string
    {
        $detected = $this->resolve($path);

        if ($detected) {
            $detectedModule = $detected->label;
            return $detected->path;
        }

        return null;
    }
}
