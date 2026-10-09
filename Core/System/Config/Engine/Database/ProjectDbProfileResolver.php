<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\System\Config\Engine\Database;

use Rbn\Framework\Core\System\Config\Definitions\DbProfiles\ProjectDbData;

/**
 * ProjectDbProfileResolver - Proje veritabanı profil SEÇİCİSİ 🎚️🗃️
 *
 * [FW-DB-PROFIL · team member] `project-settings.php` dosyasında proje DB
 * bilgisi TEK dosyada iki profille durabilir:
 *
 *     'DB_PROFILES' => [
 *         'local'      => ['DB_HOST' => …, 'DB_NAME' => …, 'DB_USER' => …, 'DB_PASS' => …, 'DB_CHARSET' => …],
 *         'production' => ['DB_HOST' => …, 'DB_NAME' => …, 'DB_USER' => …, 'DB_PASS' => …, 'DB_CHARSET' => …],
 *     ]
 *
 * Dosya sunucuya OLDUĞU GİBİ atılabilir; motor çalıştığı ortama göre profili
 * seçer. Motor geneldir: proje/veritabanı ADI burada YOKTUR (Anayasa §9).
 *
 * GERİYE UYUMLULUK: `DB_PROFILES` YOKSA eski düz `DB_*` anahtarları AYNEN
 * çalışır (dosya olduğu gibi döner, hiçbir anahtar yeniden adlandırılmaz).
 * Kırıcı değişiklik YOKTUR.
 *
 * FAIL-CLOSED (sessiz düşme YOK):
 *   - seçilen profil yoksa            -> RuntimeException
 *   - profil içinde zorunlu anahtar
 *     eksikse (örn. `DB_NAME`)       -> RuntimeException
 *   - değer `__DOLDUR__` yer tutucusuysa -> RuntimeException
 * Hiçbir durumda "diğer profile" ya da "root / boş parola" varsayılanına
 * düşülmez. Hata metni yalnız ANAHTAR/PROFIL ADI yazar, sır DEĞERİ yazmaz.
 *
 * TEK MERKEZ: `DatabaseConfig::fromRaw()`,
 * `DatabaseGuardProvider::loadCredentials()`,
 * `ProjectDataMapper::buildProjectPdo()` ve
 * `ProjectCleanupJob::cleanProjectTables()` BU SINIFI çağırır; başka hiçbir
 * yerde profil seçme mantığı yazılmaz.
 *
 * @see \Rbn\Framework\Core\System\Config\Definitions\DbProfiles\ProjectDbData
 */
final class ProjectDbProfileResolver
{
    /** Profil bloğunun `project-settings.php` içindeki anahtarı. */
    public const PROFILES_KEY = 'DB_PROFILES';

    /** Yerel geliştirme profili. */
    public const PROFILE_LOCAL = 'local';

    /** Canlı (üretim) profili. */
    public const PROFILE_PRODUCTION = 'production';

    /**
     * Doldurulmamış yer tutucusu. Seçilen profilde bu değer varsa motor
     * AÇIK hata verir; sessizce bağlanmaz (fail-closed).
     */
    public const PLACEHOLDER = '__DOLDUR__';

    /**
     * Zorunlu anahtarlar (proje DB kimlik haritası = TEK kaynak).
     *
     * @return string[]
     */
    public static function requiredKeys(): array
    {
        return array_values(ProjectDbData::KEYS_MAP);
    }

    /**
     * Ortam kararı: 'local' mı 'production' mı? 🎚️
     *
     * ⚠️ KARAR NEDEN İSTEMCİYE BAĞLI DEĞİL (ölçülmüş tasarım gerekçesi):
     * `is_local()` bir **istemci tarafı** yardımcısıdır; robots.txt muafiyeti
     * ve IndexNow atlama gibi TARAYICIYA dönük kararlar için `REMOTE_ADDR` +
     * `Host` başlığına bakar (gerekçesi: `Host` saldırgan kontrollüdür). **Veri
     * tabanı seçimi böyle bir karara bağlanamaz:** karar SUNUCUNUN KİMLİĞİ
     * olmalı, isteğin kimden geldiği değil. Aksi halde aynı sunucuda istek
     * kimliğine bağlı olarak FARKLI veritabanlarına bağlanılır — yerel makinede
     * dış ağdan gelen istek `production` seçip `__DOLDUR__` üzerinde patlar,
     * canlıda `local` seçilirse yerel veritabanına bağlanmayı dener.
     * ÖLÇÜM: kabul harness'i `REMOTE_ADDR=203.0.113.9` (doküman IP'si) ile
     * 8 dosyada hata üretti — bu bir test ölçümüdür, canlı bir hatadır.
     *
     * SIRA (ilk sinyal kazanır):
     *   1) `secrets.php` `app.db_profile` = 'local' | 'production'
     *      (sunucu cron'ı için AÇIK operatör kararı; TEK okuyucu `Secrets::app()`).
     *      Boş/tanımsız -> karar YOK sayılır, sıradakine geçilir; bilinmeyen
     *      değeri şema zaten varsayılana (`''`) düşürür (yazım hatası profili
     *      çalıştırmaz).
     *   2) **Framework KÖKÜNDE** tam `localhost` yol segmenti -> `local`.
     *      Bu SUNUCU kimliğidir: HTTP'de de CLI'de de aynıdır, istekten
     *      bağımsızdır, erken boot'ta da okunabilir (`Paths` gerekmez).
     *      Çalışılan DİZİN (`getcwd()`) ve alan adı BİLEREK kullanılmaz:
     *      cron'un çalışma dizini sunucuda `/root` da `/home/...` da olabilir.
     *   3) Hiçbir sinyal okunamazsa/belirsizlik -> `production` (fail-closed).
     *
     * @return string `self::PROFILE_LOCAL` | `self::PROFILE_PRODUCTION`
     */
    public static function activeProfile(): string
    {
        $declared = self::declaredProfile();
        if ($declared !== null) {
            return $declared;
        }

        return self::frameworkRootIsLocal() ? self::PROFILE_LOCAL : self::PROFILE_PRODUCTION;
    }

    /**
     * Ham ayar dizisinden seçilmiş profilin DÜZ anahtar dizisini döner. 🎚️
     *
     * @param array       $data    `project-settings.php` dosyasının döndürdüğü dizi.
     * @param string|null $profile Açık profil (`local`/`production`); null ise
     *                              ortam kararı (`activeProfile()`) kullanılır.
     *
     * @return array<string,mixed> `DB_HOST`/`DB_NAME`/`DB_USER`/`DB_PASS`/`DB_CHARSET`
     *
     * @throws \RuntimeException Seçilen profil yoksa, profil içinde zorunlu
     *                           anahtar eksikse ya da yer tutucu değer varsa.
     */
    public static function resolve(array $data, ?string $profile = null): array
    {
        $profiller = $data[self::PROFILES_KEY] ?? null;

        // 🔙 GERİYE UYUM: profil bloğu yok -> dosya AYNEN döner.
        if (!is_array($profiller) || $profiller === []) {
            return $data;
        }

        $secilen = $profile ?? self::activeProfile();

        if (!is_array($profiller[$secilen] ?? null)) {
            throw new \RuntimeException(
                'RBN Yapılandırma: seçilen DB profili eksik ('
                . self::PROFILES_KEY . '.' . $secilen . '). '
                . 'Ortam: ' . $secilen . '. Sıradaki profilere sessizce DÜŞÜLMEZ; '
                . 'projenin Core/Config/project-settings.php dosyasında '
                . self::PROFILES_KEY . '.' . $secilen . ' bloğunu tanımlayın.'
            );
        }

        $blok = $profiller[$secilen];
        $cozulmus = [];

        foreach (self::requiredKeys() as $anahtar) {
            if (!array_key_exists($anahtar, $blok)) {
                throw new \RuntimeException(
                    'RBN Yapılandırma: DB profilinde zorunlu anahtar eksik ('
                    . self::PROFILES_KEY . '.' . $secilen . '.' . $anahtar . '). '
                    . 'Eksik anahtar için varsayılan DEĞER (root/boş parola) '
                    . 'kullanılmaz.'
                );
            }

            $deger = $blok[$anahtar];

            if (is_string($deger) && trim($deger) === self::PLACEHOLDER) {
                throw new \RuntimeException(
                    'RBN Yapılandırma: DB profili henüz doldurulmamış ('
                    . self::PROFILES_KEY . '.' . $secilen . '.' . $anahtar
                    . ' = ' . self::PLACEHOLDER . '). '
                    . 'Yer tutucu değerle bağlantı DENEMEZ; '
                    . 'değeri güvenli yoldan yazın.'
                );
            }

            $cozulmus[$anahtar] = $deger;
        }

        return $cozulmus;
    }

    /**
     * `secrets.php` `app.db_profile` ile AÇIK profil kararı (varsa).
     *
     * TEK okuyucu `Secrets::app()`; izinli değer listesi şemadadır
     * (`SecretsSchema::APP_ALLOWED`), burada ikinci bir liste yazılmaz.
     */
    private static function declaredProfile(): ?string
    {
        $v = \Rbn\Framework\Core\System\Config\Secrets::app()['db_profile'];

        return match ($v) {
            self::PROFILE_LOCAL => self::PROFILE_LOCAL,
            self::PROFILE_PRODUCTION => self::PROFILE_PRODUCTION,
            default => null,
        };
    }

    /**
     * Framework KÖKÜ yerel makine işareti taşıyor mu? (CLI kararı)
     *
     * Bu dosya `rbnframework/Core/System/Config/Engine/Database/` altındadır;
     * beş dizin yukarı framework köküdür. `Paths` henüz ayakta olmayan erken
     * boot'ta da çalışabilmesi için YOL bu dosyanın konumundan türetilir.
     */
    private static function frameworkRootIsLocal(): bool
    {
        $kok = dirname(__DIR__, 5);

        foreach (preg_split('#[\\\\/]#', $kok) ?: [] as $segment) {
            if (strtolower($segment) === 'localhost') {
                return true;
            }
        }

        return false;
    }
}
