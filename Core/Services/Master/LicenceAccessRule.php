<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Services\Master;

use Rbn\Framework\Core\Services\Master\Data\MasterLicenceConfig;

/**
 * LicenceAccessRule - Lisans "kısıtsız mı?" kuralının TEK merkezi 🔑
 *
 * NEDEN AYRI SINIF (B-05): "geçerli lisans" sorusu iki katmanda soruluyordu
 * ve iki yerde FARKLI yazılmıştı (`DatabaseGuardHandler::isUnrestricted()` ve
 * `MasterLicencesService::verify()`). Kural burada bir kez yazılır; kapı ve
 * servis aynı cevabı alır.
 *
 * KURAL (tek tanım, iki katmana ortak):
 *   Kısıtsız = tier FREE **VE** status active **VE**
 *              (expires_at NULL/boş → SÜRESİZ)
 *              (expires_at DOLU **VE** `strtotime` ile ÇÖZÜLEMEYEN → KISITLI = fail-closed)
 *              (expires_at DOLU **VE** çözülüp geçmiş → KISITLI)
 *
 * FAIL-CLOSED: NULL / boş / beklenmeyen (`status`, `tier`) değerler geçerli
 * sayılmaz. "Bilinmiyorsa kısıtlı" esastır.
 *
 * SINIFIN NEREDE KULLANILACAĞI:
 *   - `DatabaseGuardHandler::isUnrestricted()` → `isUnrestricted()`
 *   - `MasterLicencesService::verify()` → `isRecordUsable()` + `isExpired()`
 *
 * Bu sınıf SAF bir kural sınıfıdır: durum tutmaz, veritabanına dokunmaz,
 * `new` gerektirmez (yalnız statik metot). Bu yüzden keşif/IoC kaydı gerekmez
 * (Anayasa §1 `new` yasağına ve §5 "yalnız bileşen kaydı" ilkesine uyar).
 *
 * @see \Rbn\Framework\Core\Services\Gatekeepers\Handlers\DatabaseGuardHandler
 * @see \Rbn\Framework\Core\Services\Master\MasterLicencesService
 */
final class LicenceAccessRule
{
    /**
     * Lisans kapısı kararı: kayıt kısıtsız mı?
     *
     * @param array<int,mixed>|null $record `licences` satırı (null = kayıt yok/okunamadı)
     */
    public static function isUnrestricted(?array $record): bool
    {
        if (!self::isRecordUsable($record)) {
            return false;
        }

        /** @var array<int,mixed> $record */
        return (string) $record['tier'] === MasterLicenceConfig::TIER_FREE
            && (string) $record['status'] === MasterLicenceConfig::STATUS_ACTIVE
            && !self::isExpired($record['expires_at'] ?? null);
    }

    /**
     * Kısıtlama RUTİN mi, ANORMAL mi? (günlük gürültüsü kararı — B-04)
     *
     * RUTİN  = kayıt okundu, alanları tanınabilir, tarih çözülebiliyor; yalnız
     *          kademe/durum/süre kuralı kısıtladı (PRO/LIFETIME, status≠active,
     *          süresi dolmuş). Bu bir İŞ KURALI sonucudur → günlüğe yazılmaz.
     * ANORMAL = kayıt yok/okunamadı/boş, alan NULL ya da şemada yok, tarih
     *          bozuk. Bu bir VERİ/KARAR HATASIDIR → günlüğe yazılır.
     *
     * @param array<int,mixed>|null $record
     */
    public static function isAnomalous(?array $record): bool
    {
        if (!is_array($record) || $record === []) {
            return true; // Kayıt yok / okunamadı / boş döndü.
        }

        if (!self::isRecordUsable($record)) {
            return true; // NULL, boş veya şemada tanımsız alan.
        }

        return self::hasValue($record['expires_at'] ?? null)
            && strtotime((string) $record['expires_at']) === false;
    }

    /**
     * Bu `status` lisansı TARİHTEN BAĞIMSIZ olarak geçersiz kılar mı?
     *
     * KURAL: `revoked` (iptal), `suspended` (dondurma) ve `expired` (süresi dolmuş)
     * durumlarının ÜÇÜ de geçersizdir — `expires_at` NULL ya da gelecekte olsa bile.
     * Kalan tek durum `active`'tir. "Bilinmiyorsa geçersiz" (fail-closed) esastır;
     * `isRecordUsable()` tanınmayan durumları zaten eler.
     */
    public static function isStatusBlocking(mixed $status): bool
    {
        return self::blockingReason($status) !== null;
    }

    /**
     * Engelleyici `status` için `verify()` sebebi; engel yoksa `null`.
     * Neden tek metottadır: sebep ile karar AYRI yazılırsa ikisi zamanla ayrışır.
     */
    public static function blockingReason(mixed $status): ?string
    {
        return match (trim((string) self::text($status))) {
            MasterLicenceConfig::STATUS_REVOKED   => MasterLicenceConfig::REASON_REVOKED,
            MasterLicenceConfig::STATUS_SUSPENDED => MasterLicenceConfig::REASON_SUSPENDED,
            MasterLicenceConfig::STATUS_EXPIRED   => MasterLicenceConfig::REASON_EXPIRED,
            default => null,
        };
    }

    /**
     * Kayıt "tanınabilir" mi? NULL/boş/beklenmeyen `status` ya da `tier`
     * varsa HAYIR (fail-closed). `tier` değerinin PRO olması bu kontrolü
     * bozmaz: kademe geçerliliği `isUnrestricted()` içindeki karara aittir.
     *
     * @param array<int,mixed>|null $record
     */
    public static function isRecordUsable(?array $record): bool
    {
        if (!is_array($record) || $record === []) {
            return false;
        }

        return self::isKnownStatus($record['status'] ?? null)
            && self::isKnownTier($record['tier'] ?? null);
    }

    /** `status` dolu ve şemada tanımlı mı? (active/suspended/revoked/expired) */
    public static function isKnownStatus(mixed $status): bool
    {
        return in_array(trim((string) self::text($status)), self::statuses(), true);
    }

    /** `tier` dolu ve şemada tanımlı mı? (FREE/LIFETIME/PRO) */
    public static function isKnownTier(mixed $tier): bool
    {
        return in_array(trim((string) self::text($tier)), self::tiers(), true);
    }

    /**
     * Süresi dolmuş mu? DOLU ama `strtotime` ile ÇÖZÜLEMEYEN tarih de
     * "dolmuş" sayılır (Y-1 fail-closed). NULL/boş = süresiz (false).
     */
    public static function isExpired(mixed $expiresAt): bool
    {
        if (!self::hasValue($expiresAt)) {
            return false; // NULL veya boş metin = süresiz lisans.
        }

        $zaman = strtotime((string) $expiresAt);

        return $zaman === false || $zaman <= time();
    }

    /** Değer dolu mu? NULL, boş metin ve yalnız boşluktan oluşan metin = boş sayılır. */
    private static function hasValue(mixed $value): bool
    {
        return is_scalar($value) && trim((string) $value) !== '';
    }

    /** NULL olmayan değeri metne çevirir (NULL = boş string). */
    private static function text(mixed $value): string
    {
        return self::hasValue($value) ? trim((string) $value) : '';
    }

    /** @return array<int,string> Şemada tanımlı lisan durumları */
    private static function statuses(): array
    {
        return [
            MasterLicenceConfig::STATUS_ACTIVE,
            MasterLicenceConfig::STATUS_SUSPENDED,
            MasterLicenceConfig::STATUS_REVOKED,
            MasterLicenceConfig::STATUS_EXPIRED,
        ];
    }

    /** @return array<int,string> Şemada tanımlı lisans kademeleri */
    private static function tiers(): array
    {
        return [
            MasterLicenceConfig::TIER_FREE,
            MasterLicenceConfig::TIER_LIFETIME,
            MasterLicenceConfig::TIER_PRO,
        ];
    }
}