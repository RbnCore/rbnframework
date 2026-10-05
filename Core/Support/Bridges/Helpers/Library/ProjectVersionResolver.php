<?php
declare(strict_types=1);

namespace Rbn\Framework\Core\Support\Bridges\Helpers\Library;

use Rbn\Framework\Core\System\Kernel\Bootstrap;

/**
 * ProjectVersionResolver - PROJE SURUMUNUN TEK COZUCUSU 🧬
 *
 * TEK KAYNAK (patron karari 2026-10-05): proje surumu **master veritabani**
 * `projects.version` kolonudur. Deger acilista proje veri onbellegine yazilir
 * (`ProjectDataMapper` -> `project_data('version')`) ve HER YER buradan okur:
 *   - `app_version()`            (global yardimci)
 *   - `APP_VERSION` sabiti       (`PreBoot::defineAppVersion()`)
 *   - `module-version` SEO metası (`SeoResolver`)
 * Proje surumu `project-settings.php` DENOKUNUR ve hicbir yerel dosyaya
 * yazilmaz ( versioning.md §4 "tek kaynak").
 *
 * GUVENLI DUSUS: deger yoksa (`null`/bos) veya bozuksa (4 parca, `v` oneki,
 * iki parcali `1.0` vb.) `Version::initial()` (`0.1.1`) doner. Boylece
 * `{{APP_VERSION}}` gibi sabit yazan sablonlar ASLA gecersiz surum basmaz.
 * Bozuk kayit goruldugunde `LogThrottle::once()` ile **saatte bir** uyari
 * yazilir (gunluk gurultusu olusmaz); bu yolu cagirmak hicbir istegi
 * bozmaz ve istisna firlatmaz.
 *
 * Sinif durumsuzdur (statik): hicbir ozellik tutmaz, `new` gerektirmez
 * (Anayasa §1). Proje/müşteri/kişi adi taşımaz (Anayusa §9).
 */
final class ProjectVersionResolver
{
    /** Gunluk kapinin anahtari (saatte bir uyari; deger/PII anahtara girmez). */
    public const THROTTLE_KEY = 'project_version_invalid';

    /**
     * Ham tek kaynak degerini cozer.
     *
     * @param mixed $raw `projects.version` degeri (string|null|bool|int|array).
     * @return string Her zaman `Version::isValid()` sağlayan bir surum.
     */
    public static function resolve(mixed $raw): string
    {
        // Yalnizca tek satirlik (skaler) metin kabul edilir: dizi/bool gibi
        // girdiler CAST EDILMEZ, guvenli baslangica dusulur.
        $metin = is_string($raw) ? trim($raw) : '';

        if ($metin !== '' && Version::isValid($metin)) {
            return $metin;
        }

        if ($metin !== '') {
            self::warnOnce($metin);
        }

        return Version::initial();
    }

    /**
     * Proje veri onbelleginden (`project_data('version')`) okuyup cozer.
     *
     * Onbellek henuz hazir degilse (cok erken boot, proje bulunamadi) de
     * guvenli sekilde baslangic surumune dusulur.
     */
    public static function resolveFromCache(mixed $projectData = null): string
    {
        if ($projectData === null) {
            try {
                $projectData = Bootstrap::getAppContext('project_data');
            } catch (\Throwable) {
                $projectData = null;
            }
        }

        return self::resolve(is_array($projectData) ? ($projectData['version'] ?? null) : null);
    }

    /**
     * Bozuk kayit varsa saatte bir uyari yazar.
     *
     * `LogThrottle` hicbir istisna firlatmaz ve depolama kullanilamazsa
     * sessizce yazmaz (fail-closed): bu yol yalnizca GORUNURLUK icindir,
     * karar hicbir zaman degismez.
     */
    private static function warnOnce(string $metin): void
    {
        try {
            if (LogThrottle::once(self::THROTTLE_KEY)) {
                error_log('[RBN] projects.version gecersiz deger bulundu, guvenli baslangic surumu kullanildi.');
            }
        } catch (\Throwable) {
            // Loglama altyapisi yoksa karar degismez (Version::initial() doner).
        }
    }
}
