<?php

use Rbn\Framework\Core\Services\Exception\Concerns\Shield;

if (!function_exists('shield')) {
    /**
     * Get the Shield Hub instance for centralized error handling 🏛️🛡️
     * 
     * @return Shield
     */
    function shield(): Shield
    {
        static $shield = null;
        return $shield ??= new Shield();
    }
}

if (!function_exists('now')) {
    /**
     * Get the current date and time string (Laravel-style)
     * @param string $format The date format string
     * @param int|null $timestamp Optional timestamp, defaults to time()
     * @return string
     */
    function now(string $format = 'Y-m-d H:i:s', ?int $timestamp = null): string
    {
        date_default_timezone_set('Europe/Istanbul');
        return date($format, $timestamp ?? time());
    }
}
if (!function_exists('is_local')) {
    /**
     * Check if the current environment is local development 🏠🛰️
     *
     * FW-KARAR-2 / Z-2 (guvenlik duzeltmesi):
     *
     * `is_local()` bir MUAFIYET kapisidir (robots.txt `is_production`,
     * IndexNow ping atlamasi, cron e-posta bildirimi, trafik/AI kullanim
     * muafiyetleri). Once `HTTP_HOST`u ALT DIZGE olarak ariyordu
     * (`str_contains($host, '.test')`). `HTTP_HOST` SALDIRGAN KONTROLLUDUR:
     * uretimde `Host: herhangi.test` gonderilerek tum muafiyetler acilabiliyordu
     * (ayrica `rbncore.tr.test.evil.com` ve `shop.localhost.attacker.com`
     * gibi son-ek/alt-dizge sahte pozitifleri de geciyordu).
     *
     * Artik karar TEK kaynaktan gelir: `PreBoot::isTrustedLocalEnvironment()`
     * (beyaz liste + `*.test` TAM son-ek + loopback/ozel ag istemci IP'si).
     * `PreBoot` yoksa (cok erken boot) karar KAPALI (fail-closed).
     *
     * HTTP basligi OLMAYAN (CLI/cron) ortamda PreBoot karari calisacak istek
     * yoktur; o durumda yalnizca SUNUCU TARAFINDAN URETILEN sinyaller
     * kullanilir (belge koku / calisma dizini yolunda TAM `localhost` segmenti).
     * Bunlar istemci kontrollu DEGILDIR, dolayisiyla yerel gelistirme
     * muafiyeti (cron IndexNow/e-posta atlama) bozulmaz.
     *
     * @return bool
     */
    function is_local(): bool
    {
        static $isLocal = null;
        if ($isLocal !== null) {
            return $isLocal;
        }

        $host = trim((string) ($_SERVER['HTTP_HOST'] ?? ''));

        // --- HTTP istegi: karar TEK kaynaktan (PreBoot), fail-closed ---
        if ($host !== '') {
            if (!class_exists(\Rbn\Framework\Core\System\Kernel\Base\PreBoot::class)) {
                return $isLocal = false;
            }

            return $isLocal = \Rbn\Framework\Core\System\Kernel\Base\PreBoot::isTrustedLocalEnvironment(
                $host,
                (string) ($_SERVER['REMOTE_ADDR'] ?? '')
            );
        }

        // --- CLI/konsol: HTTP basligi yok; yalniz sunucu tarafi sinyalleri ---
        $docRoot = (string) ($_SERVER['DOCUMENT_ROOT'] ?? '');
        $cwd = getcwd() ?: '';

        $localhostSegment = static function (string $path): bool {
            foreach (preg_split('#[\\\\/]#', $path) ?: [] as $segment) {
                if (strtolower($segment) === 'localhost') {
                    return true;
                }
            }

            return false;
        };

        return $isLocal = (
            $localhostSegment($docRoot)
            || $localhostSegment(__DIR__)
            || $localhostSegment($cwd)
        );
    }
}