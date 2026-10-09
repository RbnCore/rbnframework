<?php
declare(strict_types=1);

namespace Rbn\Framework\Core\Services\Gatekeepers\Handlers;

use Rbn\Framework\Core\Base\BaseComponent;
use Rbn\Framework\Core\Database\Models\Master\MasterDevelopersModel;
use Rbn\Framework\Core\Database\Models\Master\MasterIpWhitelistModel;
use Rbn\Framework\Core\Database\Repositories\Common\ShieldSettingsRepository;
use Rbn\Framework\Core\Render\Configs\AssetConfig;
use Rbn\Framework\Core\System\Config\Secrets;
use Rbn\Framework\Core\Support\Definitions\Route\RouteBlueprint;

/**
 * SystemGuardHandler - The Master Access Evaluator 🛡️⚖️
 * 
 * RBN Framework: Logic - Prioritized Maintenance and VIP validation.
 */
class SystemGuardHandler extends BaseComponent
{
    /**
     * Evaluates if the current request should be blocked due to Maintenance Mode.
     *
     * [STEP-BY-STEP LOGIC] 🛰️🪐
     * 1. Check Maintenance Mode status (Single DB hit).
     * 2. If ON, validate the client IP against local and global whitelists.
     *
     * @return bool True if access is DENIED.
     */
    public function isAccessDenied(): bool
    {
        try {
            $uri = $_SERVER['REQUEST_URI'] ?? '/';
            $currentIp = (string) ($_SERVER['REMOTE_ADDR'] ?? '');

            // [S-1] KALIP AssetDoctor ile AYNIDIR: once YOL ayristirilir
            // (`parse_url(..., PHP_URL_PATH)`), sonra istisna oneki BAŞA
            // eslestirilir. Ham `REQUEST_URI` uzerinde `str_contains` ile
            // `/?x=/framework-assets/` gibi istekler bakim modu + tum VIP
            // kontrolunu ATLIYORDU.
            $uriPath = '/' . trim((string) parse_url($uri, PHP_URL_PATH), '/');
            if ($this->isStaticAssetPath($uriPath)) {
                return false;
            }

            // 2. Critical Path Bypass (Admin, Auth, API)
            // [A0-2] `config()` anahtar yoksa `LogicException` attigi icin `safeConfig()` kullanilir.
            // [FW-ROUTEMAP-SSOT-2] Panel on eki TEK KAYNAK: `project-routemap.php`
            // (`ProjectDataMapper` -> BootCache -> `project_data()`, kapisiz).
            // Ayar dosyasindaki `dashboard_prefix` OKUNMAZ (YOK SAYILIR), varsayilan
            // `RouteBlueprint::DASHBOARD_PREFIX`.
            // [S-1] Eslesme YOL (`$uriPath`) uzerinde: `/login?x=/rbn-admin` ATLANMAZ.
            $routePrefix = function_exists('project_data') ? trim((string) project_data('dashboard_prefix', ''), '/') : '';
            $dashboardPrefix = '/' . ($routePrefix !== '' ? $routePrefix : RouteBlueprint::DASHBOARD_PREFIX);

            $allowedPaths = [$dashboardPrefix];
            foreach (RouteBlueprint::SYSTEM_ALLOWED_PATHS as $path) {
                $allowedPaths[] = '/' . ltrim($path, '/');
            }
            foreach ($allowedPaths as $path) {
                if (str_starts_with($uriPath, $path)) {
                    return false;
                }
            }

            // [S-2] BOS `REMOTE_ADDR` = VIP DEGILDIR. Eski hali bos IP'de
            // istegi REDDETMEYIP bakim + VIP kontrolunu ATLIYORDU (fail-OPEN).
            // Karar: CLI yolu AYRI (bkz. isCliRequest()); WEB'de bos IP artik
            // VIP sayilmaz -> bakim acikken ENGELLI, kapaliyken serbest.
            if ($currentIp === '' && $this->isCliRequest()) {
                return false;
            }

            // 1. Fetch Maintenance Mode Status via Shield Repository 🛰️
            // [FW-110 / 97-1 + 97-5] Anayasa §8: DB okuyan sınıf Repository'dir.
            $maintenanceMode = $this->repository('common.shieldSetting')
                ->getSetting(ShieldSettingsRepository::KEY_MAINTENANCE_MODE, '0');

            // If maintenance mode is OFF or setting missing, allow access (DB Saving) ✨
            if (!ShieldSettingsRepository::normalizeSwitch($maintenanceMode, false)) {
                return false;
            }

            // 2. MAINTENANCE IS ON: Check Developer Token Bypass (Highest Priority) 🔑✨
            $devToken = $this->request->header('X-RBN-DEV-TOKEN');
            if (!empty($devToken)) {
                $tokenHash = $this->helper('crypto')->hashToken($devToken);
                
                /** @var MasterDevelopersModel $devModel */
                $devModel = $this->model(MasterDevelopersModel::class);
                $isDeveloper = $devModel->where('dev_token_hash', '=', $tokenHash)
                    ->exists();

                if ($isDeveloper) {
                    return false; // Access Granted (Developer Bypass) 🏅
                }
            }

            // 3. MAINTENANCE IS ON: Check Whitelists 🛡️🕊️
            
            // A. Local IP Whitelist. [S-2] Bos IP bir beyaz liste kaydiyla ASLA
            // eslesmez; listedeki bos kayitlar ("1.2.3.4," -> "") filtrelenir.
            $localIps = $this->repository('common.shieldSetting')
                ->getSetting(ShieldSettingsRepository::KEY_MAINTENANCE_IPS, '');
            if ($currentIp !== '' && !empty($localIps)) {
                $ips = array_values(array_filter(
                    array_map('trim', explode(',', (string) $localIps)),
                    static fn(string $ip): bool => $ip !== ''
                ));
                if (in_array($currentIp, $ips, true)) {
                    return false; // Access Granted (Local VIP)
                }
            }

            // B. Check Global Master VIP List (MASTER DB connection) 📡
            if ($currentIp !== '') {
                /** @var MasterIpWhitelistModel $vipModel */
                $vipModel = $this->model(MasterIpWhitelistModel::class);
                $isGlobalVip = $vipModel->where('ip_address', '=', $currentIp)->exists();

                if ($isGlobalVip) {
                    return false; // Access Granted (Global VIP)
                }
            }

            // [BLOCK ACCESS]: Maintenance is ON and user is NOT a VIP ⛔
            return true;

        } catch (\Throwable $e) {
            // [A0-2 / G-03] FAIL-CLOSED: karar VERILEMIYORSA engelle. Can
            // kilidi olmamasi icin hata LOGLANIR ve `RBN_GUARD_FAILCLOSED`
            // kill-switch'i acikca kapatilabilir (bkz. resolveFailClosed).
            $failClosed = $this->resolveFailClosed();
            $this->logGuardWarning('maintenance_check_failed', [
                'rule'          => 'maintenance_failclosed',
                'error'         => $e->getMessage(),
                'decision'      => $failClosed ? 'DENY' : 'ALLOW',
                'fail_closed'   => $failClosed,
                'revert_hint'   => 'acilirsa RBN_GUARD_FAILCLOSED=false ile eski davranisa donulur',
            ]);

            return $failClosed;
        }
    }

    /* [S-1] STATIK VARLIK ISTISNASI: URL YOLU, HAM ADRES DEGIL.
       Kural `AssetDoctor::check()` ile AYNI: once YOL ayristirilir, sonra
       istisna oneki BAŞA eslestirilir; onedir AssetConfig'ten okunur. */

    /**
     * İstek yolu bir framework/proje statik varlık yolu mu?
     *
     * @param string $uriPath Başına eşleştirilmiş, `/` ile normalize edilmiş yol.
     */
    private function isStaticAssetPath(string $uriPath): bool
    {
        $setup = AssetConfig::PROXY_SETUP;
        $prefixes = [
            (string) ($setup['framework']['path'] ?? 'framework-assets'),
            (string) ($setup['project']['path'] ?? 'project-assets'),
        ];

        foreach ($prefixes as $prefix) {
            $prefix = trim($prefix, '/');
            if ($prefix !== '' && str_starts_with(ltrim($uriPath, '/'), $prefix . '/')) {
                return true;
            }
        }

        return false;
    }

    /**
     * [S-2] Bu karar CLI (konsol/cron) yolunda mi?
     *
     * `MaintenanceGuard` -> `SystemGuardService` zinciri KERNEL'in her boot'unda
     * (web + CLI) calisir; `KernelFactory` komutu `master:migrate` gibi bakim
     * modunda calismasi gerekenleri de icerir. CLI'de `REMOTE_ADDR` dogal
     * olarak YOKTUR, dolayisiyla bos IP'yi fail-closed saymak bakim
     * sirasinda tum komutlari kilitlerdi (migration calismaz, kurtarma yok).
     * Bu yuzden bos IP kisayolu yalnizca CLI'da korunur; WEB'de bos IP VIP
     * DEGILDIR. Metod `protected` ki birim testi iki yolu da sınayabilsin.
     */
    protected function isCliRequest(): bool
    {
        return PHP_SAPI === 'cli';
    }

    /* [A0-2] KILL-SWITCH: TEK MERKEZ + TIP NORMALIZASYONU.
       Varsayilan FAIL-CLOSED'dir; geri almak TEK ortam degiskeniyle mumkundur.
       Deger normalizasyonu `ShieldSettingsRepository::normalizeSwitch()` ile TEK
       MERKEZDEN gelir (ikinci bir normalizasyon YAZILMAZ): `false`/`0`/`'0'/`
       `'false'`/`'off'` -> ACIKCA KAPALI; diger her sey -> FAIL-CLOSED. */

    /** @var string[] [B-10] LISTE TEK YERDE: `ShieldSettingsRepository::KAPALI_DEGERLER` (sabit geriye donuk uyum icin korunur). */
    private const FAILCLOSED_OFF_VALUES = ShieldSettingsRepository::KAPALI_DEGERLER;

    /**
     * Ayar okurken istisna firlatmayan `config()` sarmalayicisi (A0-2):
     * `config()` anahtar yoksa `LogicException` attigi icin makul varsayilan
     * kullanilir.
     *
     * @return mixed Ayar degeri; okunamazsa `$default`.
     */
    protected function safeConfig(string $key, $default = null)
    {
        try {
            return $this->config($key, $default);
        } catch (\Throwable) {
            return $default;
        }
    }

    /**
     * @return bool true = ENGELLE (fail-closed), false = GECIR (geri alinmis)
     */
    protected function resolveFailClosed(): bool
    {
        // [FW-096-D8] `secrets.php` `app.guard_failclosed` (bool; yok/okunamaz -> true).
        try { return Secrets::app()['guard_failclosed']; } catch (\Throwable) { return true; }
    }

    /**
     * Koruma katmani uyarisini sessizce yutmaz (A0-2).
     *
     * @param array<string,mixed> $context
     */
    protected function logGuardWarning(string $rule, array $context): void
    {
        try {
            $this->logs()?->channel('systemguard')->warning('Sistem koruma kontrolu tamamlanamadi.', $context);
        } catch (\Throwable) {
            // Log yazımı güvenlik kararını değiştirmez.
        }
    }

    /** Bakim modu mesajini, yerellestirilmis olarak dondurur. */
    public function getMaintenanceMessage(): string
    {
        try {
            return $this->repository('common.shieldSetting')->getSetting(ShieldSettingsRepository::KEY_MAINTENANCE_MESSAGE)
                ?: 'Sistem şu an bakımdadır. Lütfen daha sonra tekrar deneyiniz.';
        } catch (\Throwable $e) {
            return 'Sistem şu an bakımdadır.';
        }
    }
}
