<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Base\Data\Traits\Config\Engine;

use Rbn\Framework\Core\Support\Bridges\Traits\NormalizationTrait;
use Rbn\Framework\Core\System\Discovery\Clusters\Logic\Definition\Definition;
use Rbn\Framework\Core\System\Discovery\Engine\DiscoveryEngine;

/**
 * DataConfigTrait - Persistent Static Cache and Deep Data Access 🛰️⚙️
 * 
 * RBN Framework: Manages configuration state and dot-notation navigation.
 * Isolated as an Engine trait for architectural symmetry.
 */
trait DataConfigTrait
{
    use NormalizationTrait;

    /**
     * Standard Repository for active configuration state 🧠
     */
    protected array $repository = [];

    /**
     * Local Data Cache (Shared across all configs) 🧠
     */
    private static array $dataCache = [];

    /**
     * SMART ENTRY POINT: Resolve Path (Relative or Global Alias) 🛰️
     */
    public function at(string $path): static
    {
        $this->resolvedPrefix = $this->resolveConfigPath($path);

        return $this;
    }

    /**
     * B-35: `at()` yolunu hesaplar. Onceki kod bu degeri hesaplayip ATIYORDU
     * (hicbir alana yazmiyordu), bu yuzden `at()` ETKISIZDI.
     */
    protected function resolveConfigPath(string $path): string
    {
        if (!str_contains($path, '.')) {
            $resolved = DiscoveryEngine::instance()->namespace()->resolveLayer($path);
            $frameworkPrefix = Definition::get('namespace', 'FRAMEWORK_PREFIX') ?? 'Rbn\Framework\\';
            $projectPrefix = Definition::get('namespace', 'PROJECT_PREFIX') ?? 'Rbn\Project\\';

            if (str_starts_with($resolved, $frameworkPrefix)) {
                $path = 'FRAMEWORK.' . str_replace('\\', '.', trim(substr($resolved, strlen($frameworkPrefix)), '\\'));
            } elseif (str_starts_with($resolved, $projectPrefix)) {
                $path = 'PROJECT.' . str_replace('\\', '.', trim(substr($resolved, strlen($projectPrefix)), '\\'));
            }
        } elseif (property_exists($this, 'rootContext') && $this->rootContext && !str_starts_with(strtoupper($path), 'PROJECT') && !str_starts_with(strtoupper($path), 'FRAMEWORK')
            && !str_starts_with($path, $this->rootContext . '.')) {
            // `at()` artık gerçekten etkili olduğu için aynı bağlam ikinci kez
            // `at()` ile verilirse `CTX.CTX.` gibi çift önek ÜRETİLMEZ.
            $path = $this->rootContext . '.' . $path;
        }

        return $path;
    }

    /**
     * B-35: `at()` ile belirlenen cozulmus yol oneki ("son yazan kazanir").
     */
    protected ?string $resolvedPrefix = null;

    /**
     * B-35: `at()` oneki mutlak degilse `get()` anahtarina uygulanir.
     */
    protected function applyResolvedPrefix(string $key): string
    {
        $onek = $this->resolvedPrefix;

        if ($onek === null || $onek === '' || $key === '') {
            return $key;
        }

        // Mutlak kutular (FRAMEWORK.* / PROJECT.*) asla cift oneklenmez.
        if (str_starts_with(strtoupper($key), 'PROJECT') || str_starts_with(strtoupper($key), 'FRAMEWORK')) {
            return $key;
        }

        if (str_starts_with($key, $onek . '.')) {
            return $key;
        }

        return $onek . '.' . $key;
    }

    /**
     * Get a configuration value (Dot-Notation Support) 🎯⚓
     *
     * ⚠️ [B-23 / FW-ALTYAPI-1] **BU GÖVDE ÇALIŞMA ZAMANINDA ÇAĞRILMAZ.**
     *
     * ÖLÇÜM: `BaseConfig` bu trait'i `use` eden SINIFTIR ve PHP'de **sınıfın
     * kendi metotları trait metotlarını ezer**. `BaseConfig::get()` bir
     * `static` metottur, oysa buradaki `get()` bir `instance` metottur; PHP
     * imza uyumsuzluğunda bile sınıf metodu kazanır. Yani `set()` repository'ye
     * yazıyor, `get()` **hiç bunu okumuyor** — çalışma zamanı etkisi YOK,
     * ama kod okuyan yanlış varsayıyor.
     *
     * NEDEN DÜZELTİLMEDİ (gerekçe): B-23 bir "imza daraltması" DEĞİL, bir
     * gölgelenmedir; FW-ALTYAPI-1 imza politikası ("alt sınıf daralttıysa yeni
     * ad ekle") bu kaleme UYGULANMAZ. Ayrıca Anayusa §10 mevcut adları
     * yeniden adlandırmayı yasaklar ve trait'ten çıkarmak API yüzeyini
     * değiştirirdi. Doğru çözüm ayrı bir kalemdir (ölçüm + karar gerekir).
     * Bu not, sonraki turda "imzasız görünen" bu metodu silen birini uyarır.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        // 🎼 Sync with static CONFIG constant if exists (Satellite Integrity) 🧬⚓
        $config = defined('static::CONFIG') ? static::CONFIG : [];

        // 🎼 RBN Framework: [RBN Framework PRIORITIZATION]
        // Check Registry (Repository) first, then Static Config.
        if (isset($this->repository[$key])) {
            return $this->repository[$key];
        }

        if (array_key_exists($key, $config)) {
            return $config[$key];
        }

        if (str_contains($key, '.')) {
            $value = array_merge($config, $this->repository);
            foreach (explode('.', $key) as $segment) {
                if (is_array($value) && isset($value[$segment])) {
                    $value = $value[$segment];
                } else {
                    $value = null;
                    break;
                }
            }
            if ($value !== null)
                return $value;
        }

        return static::data($this->applyResolvedPrefix($key)) ?? $default;
    }

    /**
     * Set a configuration value 🖊️⚓
     */
    public function set(string $key, mixed $value): self
    {
        if (str_contains($key, '.')) {
            $repository = &$this->repository;
            foreach (explode('.', $key) as $segment) {
                if (!isset($repository[$segment]) || !is_array($repository[$segment])) {
                    $repository[$segment] = [];
                }
                $repository = &$repository[$segment];
            }
            $repository = $value;
        } else {
            $this->repository[$key] = $value;
        }

        return $this;
    }

    /**
     * Check if a configuration key exists ✅⚓
     */
    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    /**
     * DIRECT DATA ACCESS: Deep Discovery & Performance Path ⚡
     */
    public static function data(string $key): mixed
    {
        $class = static::class;
        $cacheKey = $class . '::' . $key;

        if (array_key_exists($cacheKey, self::$dataCache)) {
            return self::$dataCache[$cacheKey];
        }

        if (str_contains($key, '.')) {
            [$file, $target] = explode('.', $key, 2);
            $instance = new static();
            $resolvedClass = $instance->at($file)->data('');

            if ($resolvedClass && class_exists($resolvedClass)) {
                $targetConst = strtoupper($target);
                if (defined("$resolvedClass::$targetConst")) {
                    return self::$dataCache[$cacheKey] = constant("$resolvedClass::$targetConst");
                }
            }
        }

        $upperKey = strtoupper($key);
        if (defined("$class::$upperKey")) {
            return self::$dataCache[$cacheKey] = constant("$class::$upperKey");
        }

        $method = static::toCamelCase($key);
        if (method_exists($class, $method)) {
            $instance = new static();
            return self::$dataCache[$cacheKey] = $instance->$method();
        }

        return self::$dataCache[$cacheKey] = null;
    }
}
