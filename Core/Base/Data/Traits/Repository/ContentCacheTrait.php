<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Base\Data\Traits\Repository;

/**
 * ContentCacheTrait - Single-File Isolated Project Cache Engine 📦⚡
 * 
 * RBN Framework Standard.
 * Manages 1 isolated `content_data.cache` file inside each project's own directory.
 */
trait ContentCacheTrait
{
    /**
     * Aktif proje anahtarını InteractsWithProjectContextTrait üzerinden çözer 🛰️
     */
    protected function resolveActiveProjectKey(?string $explicitKey = null): string
    {
        // B-69: `BaseRepository` `InteractsWithProjectContextTrait` KULLANMIYOR,
        // yani gerçek bir repository'de `activeProjectKey()` YOKTUR ve bu çağrı
        // `Error: Call to undefined method` veriyordu. Metot yoksa 'global'.
        if ($explicitKey !== null && $explicitKey !== '') {
            return $explicitKey;
        }

        if (!method_exists($this, 'activeProjectKey')) {
            return 'global';
        }

        return ($this->activeProjectKey() ?: 'global');
    }

    /**
     * Önbellekten belirli bir anahtarı projenin tek `content_data` dosyasından getirir 🔍
     */
    public function getCacheItem(string $key): mixed
    {
        if (method_exists($this, 'cache') && ($cache = $this->cache())) {
            $masterPayload = $cache->get('content_data');
            if (is_array($masterPayload) && array_key_exists($key, $masterPayload)) {
                return $masterPayload[$key];
            }
        }

        return null;
    }

    /**
     * Önbelleğe belirli bir anahtarı projenin tek `content_data` dosyası içine kaydeder 💾
     */
    public function setCacheItem(string $key, mixed $value, int $ttl = 3600): void
    {
        $hasCache = method_exists($this, 'cache');
        $cache = $hasCache ? $this->cache() : null;

        if ($cache) {
            $masterPayload = $cache->get('content_data');
            if (!is_array($masterPayload)) {
                $masterPayload = [];
            }

            // Güvenli Depolama: Sadece bu cache yazımında veriyi saf array formatına normalize et 🛡️
            //
            // B-68: Önbelleğe JSON metnine ÇEVRİLEMEYEN değer yazılıyordu.
            // `json_encode(Closure)` hata vermez — `{}` (boş nesne) döner ve
            // decode edilen değer BOŞ DİZİ olur; gerçek veri sessizce yok
            // olurdu. Aynı şey kaynak (resource) için de geçerli.
            // Yazılamayan değer depoya YAZILMAZ; hata da fırlatmıyoruz —
            // önbellek yazımı asla isteği patlatmamalı.
            if ($value instanceof \Closure || is_resource($value)) {
                return;
            }

            $encoded = json_encode($value, JSON_UNESCAPED_UNICODE);
            if ($encoded === false) {
                return;
            }

            $masterPayload[$key] = json_decode($encoded, true);
            $cache->set('content_data', $masterPayload, ['ttl' => $ttl]);
        }
    }

    /**
     * Projenin tek önbellek dosyasını sıfırlar/temizler 🗑️
     */
    public function flushProjectCache(?string $key = null): void
    {
        if (method_exists($this, 'cache') && ($cache = $this->cache())) {
            if ($key !== null) {
                $masterPayload = $cache->get('content_data');
                if (is_array($masterPayload) && isset($masterPayload[$key])) {
                    unset($masterPayload[$key]);
                    $cache->set('content_data', $masterPayload);
                }
            } else {
                if (method_exists($cache, 'delete')) {
                    $cache->delete('content_data');
                } elseif (method_exists($cache, 'forget')) {
                    $cache->forget('content_data');
                }
            }
        }
    }
}
