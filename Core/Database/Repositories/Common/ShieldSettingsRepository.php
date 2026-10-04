<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Database\Repositories\Common;

use Rbn\Framework\Core\Base\Data\BaseRepository;

/**
 * ShieldSettingsRepository - The DB-Backed Security Configuration Repository 🛡️🏛️⚓
 * 
 * RBN 3.5 Masterpiece Standard.
 * Centralizes retrieval and management of security and system-wide flags from rbncore_common.
 * 
 * @property \Rbn\Framework\Core\Database\Models\Common\CmSysSettingsShieldModel $shieldSettingModel
 */
class ShieldSettingsRepository extends BaseRepository
{
    /** @var string Target primary model alias */
    protected $targetModel = 'common.shieldSetting';

    /* ========================================================================
       [ TEK MERKEZ: AYAR ANAHTARLARI + TIP NORMALIZASYONU ]  FW-110
       ========================================================================
       Anayasa §8: `cm_sys_settings_shield` tablosuna dokunan TEK sinif budur.
       Anahtar sabitleri ve boolean/anahtar normalizasyonu da burada yasar; cagiran
       her katman ayni metodu cagirir (ikinci bir normalizasyon YAZILMAZ).
       ======================================================================== */

    /** @var string IP katmanı çalışma modu anahtarı. */
    public const KEY_IP_GUARD_MODE = 'shield_ip_guard_mode';

    /** @var string IP katmanı ana kill-switch'i. */
    public const KEY_IP_GUARD = 'shield_ip_guard';

    /** @var string Hız sınırı kill-switch'i. */
    public const KEY_RATE_LIMIT = 'shield_rate_limit';

    /** @var string GeoIP anahtarı. */
    public const KEY_GEOIP_STATUS = 'shield_geoip_status';

    /** @var string Bakım modu anahtarı. */
    public const KEY_MAINTENANCE_MODE = 'maintenance_mode';

    /** @var string Bakım modu yerel IP beyaz listesi. */
    public const KEY_MAINTENANCE_IPS = 'maintenance_ips';

    /** @var string Bakım modu mesajı. */
    public const KEY_MAINTENANCE_MESSAGE = 'maintenance_message';

    /** IP katmanı: karar üret, engelleme (varsayılan). */
    public const MODE_LOG_ONLY = 'log_only';

    /** IP katmanı: gerçek engelleme. */
    public const MODE_ENFORCE = 'enforce';

    /**
     * YALNIZCA AÇIKÇA KAPALI sayılan değerler (fail-closed, FW-110 / 97-2).
     *
     * Kural: "kapalı" listesine BAKILIR, "açık" listesine değil. Tablo değerleri
     * string olarak saklandığı için `'on'`, `'yes'`, `'2'`, `''` (boş satır),
     * `null` ve yazım hataları **AÇIK** sayılır. 97'deki ters normalizasyon
     * (`'1'|'true'` dışındaki her şeyi kapat) bu yüzden bir yazım hatasıyla
     * 18 sitenin hız sınırını sessizce düşürüyordu.
     *
     * [B-10 / FW-BANU-BULGULAR-2] `no` ve `hayir` de AÇIKÇA kapatma
     * değeridir. A0-2'nin `SystemGuardHandler::FAILCLOSED_OFF_VALUES` listesi
     * zaten bu ikisini içeriyordu; iki farklı "kapalı" listesi aynı değeri
     * iki yerde farklı yorumluyordu (bir shield ayarında `AÇIK`, ortam
     * değişkeninde `KAPALI`). Artık ikisi de TEK yerden okur.
     *
     * @var string[]
     */
    public const KAPALI_DEGERLER = ['0', 'false', 'off', 'no', 'hayir'];

    /**
     * Boolean ayar normalizasyonu - TEK MERKEZ.
     *
     * Yalnız AÇIKÇA kapatma değerleri (`0`, `false`, `off`, `no`, `hayir`;
     * büyük/küçük harf + boşluk varyantları) sınırı KAPATIR. Diğer her şey
     * AÇIKTIR (fail-closed): belirsizlik "güvenli taraf" olan AÇIK tarafa düşer.
     *
     * [B-10] Okunamayan/eksik/bozuk değerde (`null`, boş string, dizi/nesne)
     * `$default` DEĞERİ korunur — çağıranın A0-1 (`log_only`) veya A0-2
     * (fail-closed) varsayılanı bozulmaz. Bu metot ASLA yeni bir yorum
     * üretmez; ikinci bir parser yoktur.
     *
     * @param mixed  $raw     Ayar tablosundan gelen ham değer.
     * @param bool   $default Okunamayan/eksik değerde kullanılacak karar.
     */
    public static function normalizeSwitch(mixed $raw, bool $default = true): bool
    {
        if ($raw === null) {
            return $default;
        }
        if (is_bool($raw)) {
            return $raw;
        }
        if (is_int($raw) || is_float($raw)) {
            return $raw != 0; // 0 kapalı, kalan her şey açık
        }
        if (is_string($raw)) {
            $v = strtolower(trim($raw));
            if ($v === '') {
                return $default;
            }
            return !in_array($v, self::KAPALI_DEGERLER, true);
        }

        // Bilinmeyen tip (dizi/nesne) -> güvenli taraf.
        return $default;
    }

    /**
     * IP katmanı modu normalizasyonu (FW-110 / A0-1a, güvenlik incelemesi 104).
     *
     * YALNIZ **tam** `'enforce'` metni zorlama sayılır. `true`, `1`, `'1'`,
     * `'on'`, `'yes'`, boş, `null` ve yazım hatası **zorlama DEĞİLDİR**;
     * hepsi `log_only`'ya düşer. Gerekçe: ayar tablosunda mevcut değerler
     * `value_type='boolean'` olup **string** saklanıyor (`shield_ip_guard='1'`);
     * operatör `1` yazdığında 18 site tek hamlede gerçek engellemeye geçerdi.
     * Varsayılan daima `log_only`.
     */
    public static function normalizeIpGuardMode(mixed $raw): string
    {
        if (is_string($raw)) {
            $raw = strtolower(trim($raw));
        }

        return $raw === self::MODE_ENFORCE ? self::MODE_ENFORCE : self::MODE_LOG_ONLY;
    }

    /**
     * Güvenlik ayarı önbellek anahtarı (D-50): proje bazlıdır. Okuma da silme de
     * AYNI anahtarı kullanır; sabit `settings_shield` anahtarı bir projenin ayarını
     * başka projenin okumasına yol açabiliyordu (sorgu `active_project_key()`,
     * önbellek öneki `project_key()` ile ayrışabilir).
     */
    public static function settingsCacheKey(?string $projectKey = null): string
    {
        if ($projectKey === null || $projectKey === '') {
            $projectKey = function_exists('active_project_key') ? active_project_key() : (function_exists('project_key') ? project_key() : 'default');
        }
        $safe = preg_replace('/[^a-zA-Z0-9_.-]/', '_', (string) $projectKey);

        return 'settings_shield_' . ($safe === '' ? 'default' : $safe);
    }

    /**
     * Projenin güvenlik ayarı önbelleğini düşürür (yazma sonrası çağrılır).
     */
    public function forgetSettingsCache(?string $projectKey = null): void
    {
        try {
            $this->cache()->delete(self::settingsCacheKey($projectKey));
        } catch (\Throwable $e) {
            // Önbellek düşürülemezse ayar bir sonraki TTL'de yenilenir; güvenlik açığı değil.
        }
    }

    /**
     * Retrieve a specific shield setting by its key. ⚓🛰️
     */
    public function getSetting(string $key, mixed $default = null): mixed
    {
        $projectKey = function_exists('active_project_key') ? active_project_key() : (function_exists('project_key') ? project_key() : 'default');
        $cacheKey = self::settingsCacheKey($projectKey);
        $model = $this->model('common.shieldSetting');

        $allSettings = $this->cache()->remember($cacheKey, function () use ($model, $projectKey) {
            $settings = $model->withoutProjectScope()->whereIn('project_key', [$projectKey, 'GLOBAL', 'common'])->get();
            $mapped = [];
            foreach ($settings as $setting) {
                $pKey = is_array($setting) ? ($setting['project_key'] ?? '') : ($setting->project_key ?? '');
                $sKey = is_array($setting) ? ($setting['setting_key'] ?? '') : ($setting->setting_key ?? '');
                $sVal = is_array($setting) ? ($setting['setting_value'] ?? '') : ($setting->setting_value ?? '');

                // Projeye özel ayar varsa GLOBAL/common varsayılanı onun üstüne yazmasın
                if (($pKey === 'GLOBAL' || $pKey === 'common') && isset($mapped[$sKey])) {
                    continue;
                }
                $mapped[$sKey] = $sVal;
            }
            return $mapped;
        });

        return $allSettings[$key] ?? $default;
    }

    /**
     * Fetch a group of shield settings by group key. 📂🛰️
     */
    public function getGroupSettings(string $groupKey): array
    {
        $projectKey = function_exists('active_project_key') ? active_project_key() : (function_exists('project_key') ? project_key() : 'default');
        $model = $this->model('common.shieldSetting');

        $settings = $model->withoutProjectScope()
            ->whereIn('project_key', [$projectKey, 'GLOBAL', 'common'])
            ->where('group_key', '=', $groupKey)
            ->get();

        $filtered = [];
        foreach ($settings as $setting) {
            $key = is_array($setting) ? ($setting['setting_key'] ?? '') : ($setting->setting_key ?? '');
            $pKey = is_array($setting) ? ($setting['project_key'] ?? '') : ($setting->project_key ?? '');

            if (isset($filtered[$key])) {
                if ($pKey === 'GLOBAL' || $pKey === 'common') {
                    continue;
                }
            }
            $filtered[$key] = $setting;
        }
        return array_values($filtered);
    }

    /**
     * Save/Create/Update a shield setting in DB 💾
     */
    public function saveSetting(string $key, $value, string $groupKey = 'general', string $valueType = 'boolean', ?string $projectKey = null): bool
    {
        $model = $this->model('common.shieldSetting');

        if ($projectKey === null) {
            $projectKey = function_exists('active_project_key') ? active_project_key() : 'GLOBAL';
        }

        $exists = $model->withoutProjectScope()
            ->where('project_key', '=', $projectKey)
            ->where('setting_key', '=', $key)
            ->first();
        $formattedValue = is_array($value) ? json_encode($value) : (string) $value;

        // 🛡️ FW-BASE-1 T3 (B-20): bu metot bilerek GLOBAL'a yazabilir; scoped
        // modelde `project_key` daima sunucu baglamindan yazildigi icin
        // ACIK ve LOGLANAN tek kacis kapisindan gecer: `writeAsProject()`.
        $writer = $model->writeAsProject($projectKey);

        if ($exists) {
            $existsId = is_array($exists) ? ($exists['id'] ?? 0) : ($exists->id ?? 0);
            $ok = (bool) $writer->update((int) $existsId, ['setting_value' => $formattedValue]);
        } else {
            $ok = (bool) $writer->save([
                'group_key' => $groupKey,
                'setting_key' => $key,
                'setting_value' => $formattedValue,
                'value_type' => $valueType
            ]);
        }

        if ($ok) {
            $this->forgetSettingsCache($projectKey);
        }

        return $ok;
    }
}
