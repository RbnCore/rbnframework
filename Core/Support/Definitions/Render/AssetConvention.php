<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Support\Definitions\Render;

use Rbn\Framework\Core\System\Paths\Paths;

/**
 * AssetConvention - The Single Naming Authority for Project Favicon & OG Image 🏺🗺️⚓
 *
 * PATRON KURALI (2026-10-05): her projede marka varliklari TEK ada
 * kalibiyla adlandirilir ve TUM istekler bu kalpla cozulur:
 *
 *   - favicon : `favicon-<project_key>.{svg,png,ico}`      -> `images/` altinda
 *   - og image: `og-image-<project_key>.{png,jpg,webp}`    -> `images/` altinda
 *   - apple-touch-icon: `apple-touch-icon-<project_key>.png` -> `images/` altinda (180x180, zeminli)
 *   - web manifest   : `manifest-<project_key>.webmanifest`  -> `images/` altinda
 *
 * MOTOR GENELDIR (Anayusa §9): burada proje/müşteri/ürün adi YAZILMAZ.
 * `<project_key` çalışma anında `SeoResolver::$projectKey` /
 * `project_key()` / sanal kaynak adından gelir.
 *
 * NEDEN TEK SINIF (yinelenen mantik YOK):
 * Dört kapı aynı kararı veriyordu ve birbirinden koptu:
 *   1. `SeoResolver::resolveFaviconRaw()` / `resolveSocial()`  (meta etiketleri)
 *   2. `AssetController::serve()` (sanal `/project-assets/<ad>` servisi)
 *   3. `RedirectManager::selfHealingAssets()` (`/favicon.ico` -> gerçek dosya)
 *   4. `SchemaResolver::resolveDefaultImage()` (JSON-LD `image`)
 * Olcum (05.10, yerel canli): `GET /project-assets/og-image-<project_key>.png`
 * 500 donuyordu ("Asset not found"); `/favicon.ico` -> 301 ->
 * `/images/favicon-<project_key>.png` -> 404 idi (dosya `.svg`).
 * Artik dort kapi da bu sinifi cagirir; ad listesi ve oncelik sirasi
 * TEK YERDEDIR.
 *
 * GERIYE UYUM (kullanımdan kalma notu): canli yedek olcumunde bazi
 * projeler ANAHTARSIZ eski adlar tasiyor (`images/favicon.png`,
 * `images/favicon.svg`, `images/og-image.png`). Bunlar canlida calisiyor;
 * bu nedenle KURAL ONCE, bu adlar YALNIZ "son geri donus" olarak aday
 * listesinin sonunda tutulur. Yeni dosyalar icin KURAL YAZILIR.
 */
class AssetConvention
{
    /** Favicon kural uzantilari (oncelik sirasiyla) */
    public const FAVICON_EXTENSIONS = ['svg', 'png', 'ico'];

    /** OpenGraph gorsel kural uzantilari (oncelik sirasiyla) */
    public const OG_IMAGE_EXTENSIONS = ['png', 'jpg', 'webp'];

    /** apple-touch-icon kural uzantisi (iOS yalniz PNG kabul eder) */
    public const APPLE_TOUCH_EXTENSIONS = ['png'];

    /** Web manifest kural uzantilari (oncelik sirasiyla) */
    public const MANIFEST_EXTENSIONS = ['webmanifest', 'json'];

    /**
     * Sanal og gorsel adinin KABUL edilen sekli:
     * `og-image-<anahtar>.<png|jpg|webp>`. Anahtar yalniz
     * `[A-Za-z0-9_-]+` olabilir: bolu (`/`, `\`), `..`, bosluk, kontrol
     * karakteri ve mutlak yol bu desenle zaten elenir ([R-14] sertligi).
     * Bosluk ve kontrol karakteri icin ek desen kontrolleri asagidadir.
     */
    private const VIRTUAL_OG_PATTERN = '/^og-image-([A-Za-z0-9_-]{1,64})\.(png|jpg|webp)$/';

    /**
     * Favicon aday adlari: once KURAL, sonra ESKI adlar (son geri donus).
     *
     * @return string[] proje public kokune gore goreli yollar
     */
    public static function faviconCandidates(?string $projectKey): array
    {
        $aday = [];

        $key = self::normalizeKey($projectKey);
        if ($key !== null) {
            foreach (self::FAVICON_EXTENSIONS as $ext) {
                $aday[] = "images/favicon-{$key}.{$ext}";
            }
            // ESKI: `images/<key>.svg|png|ico` (anahtarli ama kural disi ad)
            foreach (self::FAVICON_EXTENSIONS as $ext) {
                $aday[] = "images/{$key}.{$ext}";
            }
        }

        // ESKI: anahtarsiz genel adlar (canlida calisiyor, son geri donus)
        foreach (self::FAVICON_EXTENSIONS as $ext) {
            $aday[] = "images/favicon.{$ext}";
        }

        return array_values(array_unique($aday));
    }

    /**
     * Og gorseli aday adlari: once KURAL, sonra ESKI genel ad.
     *
     * @return string[] proje public kokune gore goreli yollar
     */
    public static function ogImageCandidates(?string $projectKey): array
    {
        $aday = [];

        $key = self::normalizeKey($projectKey);
        if ($key !== null) {
            foreach (self::OG_IMAGE_EXTENSIONS as $ext) {
                $aday[] = "images/og-image-{$key}.{$ext}";
            }
        }

        // ESKI: anahtarsiz genel ad (canlida calisiyor, son geri donus)
        foreach (self::OG_IMAGE_EXTENSIONS as $ext) {
            $aday[] = "images/og-image.{$ext}";
        }

        return array_values(array_unique($aday));
    }

    /**
     * apple-touch-icon aday adlari: once KURAL, sonra anahtarsiz genel ad.
     *
     * @return string[] proje public kokune gore goreli yollar
     */
    public static function appleTouchIconCandidates(?string $projectKey): array
    {
        $aday = [];

        $key = self::normalizeKey($projectKey);
        if ($key !== null) {
            foreach (self::APPLE_TOUCH_EXTENSIONS as $ext) {
                $aday[] = "images/apple-touch-icon-{$key}.{$ext}";
            }
        }

        foreach (self::APPLE_TOUCH_EXTENSIONS as $ext) {
            $aday[] = "images/apple-touch-icon.{$ext}";
        }

        return array_values(array_unique($aday));
    }

    /**
     * Web manifest aday adlari: once KURAL, sonra anahtarsiz genel ad.
     *
     * @return string[] proje public kokune gore goreli yollar
     */
    public static function manifestCandidates(?string $projectKey): array
    {
        $aday = [];

        $key = self::normalizeKey($projectKey);
        if ($key !== null) {
            foreach (self::MANIFEST_EXTENSIONS as $ext) {
                $aday[] = "images/manifest-{$key}.{$ext}";
            }
        }

        foreach (self::MANIFEST_EXTENSIONS as $ext) {
            $aday[] = "images/manifest.{$ext}";
        }

        return array_values(array_unique($aday));
    }

    /**
     * Projenin fiziksel apple-touch-icon dosyasini cozer; yoksa `null` (etiket uretilmez).
     */
    public static function findAppleTouchIcon(?string $projectKey): ?string
    {
        return self::firstExisting(self::appleTouchIconCandidates($projectKey));
    }

    /**
     * Projenin fiziksel web manifest dosyasini cozer; yoksa `null` (etiket uretilmez).
     */
    public static function findManifest(?string $projectKey): ?string
    {
        return self::firstExisting(self::manifestCandidates($projectKey));
    }

    /**
     * Projenin fiziksel favicon dosyasini cozer.
     *
     * @return string|null public kokune gore goreli yol; hicbiri yoksa `null`
     *                   (cagiran taraf framework varsayilanina düşer)
     */
    public static function findFavicon(?string $projectKey): ?string
    {
        return self::firstExisting(self::faviconCandidates($projectKey));
    }

    /**
     * Projenin fiziksel og gorselini cozer.
     *
     * @return string|null public kokune gore goreli yol; dosya YOKSA `null`.
     *                   `null` demek "uydurma gorsel uretme" demektir:
     *                   cagiran taraf `og:image` meta etiketini hic uretmez.
     */
    public static function findOgImage(?string $projectKey): ?string
    {
        return self::firstExisting(self::ogImageCandidates($projectKey));
    }

    /**
     * Sanal `/project-assets/og-image-<key>.<png|jpg|webp>` adini fiziksel
     * dosyaya cozer. Cozulemezse `null` doner (cagilan taraf 404 verir).
     *
     * GUVENLIK [R-14]: ad once `VIRTUAL_OG_PATTERN` ile eslesir; sonra
     * ayrica kontrol karakteri/CRLF ve `..` reddi yapilir; son olarak
     * cozulen yolun proje public kokunun `images/` ALTINDA oldugu
     * `realpath` ile DOGRULANIR (sembolik baglantı ve `..` kacisi kapanır).
     *
     * @return string|null mutlak fiziksel yol
     */
    public static function resolveVirtualOgImage(string $name): ?string
    {
        $name = trim($name);

        if ($name === '' || preg_match('/[\x00-\x1F\x7F]/', $name) === 1) {
            return null;
        }

        if (!self::isVirtualOgImageName($name)) {
            return null;
        }

        $publicRoot = self::publicRoot();
        if ($publicRoot === null) {
            return null;
        }

        $goreli = 'images/' . $name;
        $tam = $publicRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $goreli);
        if (!is_file($tam)) {
            return null;
        }

        // KOK SIZDIRMA KONTROLU: cozulen yol `public/images/` altinda mi?
        $real = realpath($tam);
        $rootReal = realpath($publicRoot . DIRECTORY_SEPARATOR . 'images');
        if ($real === false || $rootReal === false) {
            return null;
        }
        $prefix = rtrim(str_replace('\\', '/', $rootReal), '/') . '/';
        if (!str_starts_with(str_replace('\\', '/', $real), $prefix)) {
            return null;
        }

        return $real;
    }

    /**
     * Verilen ad sanal bir og gorsel adi mi? (dosya varligi SORULMAZ)
     */
    public static function isVirtualOgImageName(string $name): bool
    {
        $name = trim($name);

        if ($name === '' || preg_match('/[\x00-\x1F\x7F]/', $name) === 1) {
            return false;
        }

        // Mutlak yol, bolu ve nokta-nokta reddi (desen zaten eler; acik
        // yazmak "bu kapinin neden kapali oldugunu" okunur kilar).
        if (str_contains($name, '/') || str_contains($name, '\\') || str_contains($name, '..')) {
            return false;
        }

        return preg_match(self::VIRTUAL_OG_PATTERN, $name) === 1;
    }

    /**
     * Gorselin MIME tipi (uzantiya gore). Bilinmeyen uzantida `null`.
     */
    public static function ogImageMimeType(string $name): ?string
    {
        $ext = strtolower((string) pathinfo($name, PATHINFO_EXTENSION));

        return match ($ext) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            default => null,
        };
    }

    /**
     * Anahtari normalize eder; kurala uymayan anahtar `null`'dur.
     * (Böylece boş anahtar `og-image-.png` gibi bozuk ad üretmez.)
     */
    private static function normalizeKey(?string $projectKey): ?string
    {
        $key = strtolower(trim((string) $projectKey));

        if ($key === '' || preg_match('/^[a-z0-9_-]{1,64}$/', $key) !== 1) {
            return null;
        }

        return $key;
    }

    /**
     * Aday listesinde public kokunde GERCEKTEN var olan ilk yolu doner.
     *
     * @param string[] $aday
     */
    private static function firstExisting(array $aday): ?string
    {
        $publicRoot = self::publicRoot();
        if ($publicRoot === null) {
            return null;
        }

        foreach ($aday as $goreli) {
            $tam = $publicRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $goreli);
            if (is_file($tam)) {
                return $goreli;
            }
        }

        return null;
    }

    /**
     * Proje public koku (yoksa `null`). `Paths` baslatilmamis ise
     * sessizce `null` doner; cagilan taraf guvenli varsayilana duser.
     */
    private static function publicRoot(): ?string
    {
        if (!Paths::isInitialized()) {
            return null;
        }

        $root = Paths::publicRoot();

        return ($root === '' || !is_dir($root)) ? null : $root;
    }
}