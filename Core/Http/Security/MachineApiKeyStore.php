<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Http\Security;

/**
 * MachineApiKeyStore - Anahtar basina kimlik + kapsam + iptal/sure 🔑🗝️
 *
 * [FW-APIGUARD · TASARIM GOREV 2 · 2026-10-03 · team member]
 *
 * TABAN (A-10): `external-api` ayarinda tek bir `api_key` vardir, duz metindir,
 * iptali/suresi/kapsami YOKTUR; `enabled=false` tum uclari ayni anda dusurur
 * (tek anahtar = tek blast radius).
 *
 * BU SINIF NE YAPAR
 *   - `keys` bolumunu okur (geri uyumlu: `api_key` dalı ACIK kalir),
 *   - anahtari `hash_equals` ile KARSILASTIRIR (A-1 zamanlama acigi kapandi),
 *   - eslesen anahtarin KIMLIGINI dondurur (`key_id`, kapsam, iptal, sure),
 *   - `key_id` uretimi/iptali icin CLI'ye veri saglar.
 *
 * DEPOLAMA (PATRON KARARI 2): `sha256:<hex>`.
 *   - `hash_alan` dolu anahtar: `hash_equals($stored, hash('sha256', $given))`,
 *   - duz metin anahtar (geri uyum): `hash_equals($plain, $given)`.
 *   Kisa devre YOK: iki dal da calisirsa `hash_equals` SAYISI sabittir, boylece
 *   "bu anahtar hangi daldan geldi" bilgisi zamanlamadan sizmaz.
 *
 * GERI UYUM KURALI (PATRON KARARI 3): duz metin `api_key` dalı KALDIRILMAZ.
 * `keys` bolumu hic yoksa eski davranis bit-bit ayni kalir.
 *
 * [PATRON KARARI 6] Bu sinif HICBIR karari zorlamaz (enforce yoktur): yalniz
 * kimlik KIMDIR ve denetlenebilir.
 */
final class MachineApiKeyStore
{
    /**
     * Hash oneki: `sha256:<hex>`. (PATRON KARARI 2)
     */
    public const HASH_PREFIX = 'sha256:';

    /**
     * Anahtar tanimlarini normalize eder (dongu sayisi sabit kalsin diye
     * dizi KENDISI dondurulur; turler kararlari etkilemesin).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function normalizeDefinitions(mixed $raw): array
    {
        if (!is_array($raw)) {
            return [];
        }
        $liste = [];
        foreach ($raw as $tanim) {
            if (!is_array($tanim)) {
                continue;
            }
            $id = trim((string) ($tanim['id'] ?? ''));
            $hash = trim((string) ($tanim['hash'] ?? ''));
            $plain = trim((string) ($tanim['key'] ?? ''));
            if ($id === '' || ($hash === '' && $plain === '')) {
                continue;   // kimliksiz veya degeri olmayan tanim GECERSIZDIR
            }
            $liste[] = [
                'id'         => $id,
                'hash'       => $hash,
                'key'        => $plain,
                'label'      => (string) ($tanim['label'] ?? ''),
                'scopes'     => self::normalizeScopes($tanim['scopes'] ?? null),
                'rate'       => self::normalizeRate($tanim['rate'] ?? null),
                'body_max'   => isset($tanim['body_max']) ? (int) $tanim['body_max'] : null,
                'expires_at' => self::normalizeTime($tanim['expires_at'] ?? null),
                'revoked_at' => self::normalizeTime($tanim['revoked_at'] ?? null),
            ];
        }
        return $liste;
    }

    /**
     * Kapsam listesini normalize eder. Bos liste = "kapsam denetimi YOK"
     * (geri uyum: eski anahtarlar tum eylemleri gorur).
     *
     * @return array<int, string>
     */
    public static function normalizeScopes(mixed $raw): array
    {
        if (is_string($raw)) {
            $raw = preg_split('/[\s,]+/', $raw) ?: [];
        }
        if (!is_array($raw)) {
            return [];
        }
        $sonuc = [];
        foreach ($raw as $s) {
            $s = trim((string) $s);
            if ($s !== '') {
                $sonuc[] = $s;
            }
        }
        return array_values(array_unique($sonuc));
    }

    /**
     * Anahtar basina hiz siniri. Bos = uygulanmaz.
     *
     * @return array{max:int, per:int}|null
     */
    public static function normalizeRate(mixed $raw): ?array
    {
        if (!is_array($raw)) {
            return null;
        }
        $max = (int) ($raw['max'] ?? 0);
        $per = (int) ($raw['per'] ?? 60);
        if ($max <= 0) {
            return null;
        }
        return ['max' => $max, 'per' => $per > 0 ? $per : 60];
    }

    /**
     * Zaman damgasini normalize eder (null | unix ts).
     */
    public static function normalizeTime(mixed $raw): ?int
    {
        if ($raw === null || $raw === '' || $raw === false) {
            return null;
        }
        if (is_int($raw)) {
            return $raw > 0 ? $raw : null;
        }
        $s = trim((string) $raw);
        if ($s === '') {
            return null;
        }
        if (ctype_digit($s)) {
            return (int) $s;
        }
        $ts = strtotime($s);
        return $ts === false ? null : $ts;
    }

    /**
     * Verilen anahtari butun tanimlara karsilastirir ve kimligi dondurur. 🔎
     *
     * @param array<int, array<string, mixed>> $tanimlar `normalizeDefinitions()` ciktisi
     * @return array{key_id:string, scopes:array<int,string>, rate:?array, body_max:?int, source:string}|null
     *         Eslesme yoksa null. Deger (anahtarin kendisi) ASLA dondurulmez.
     */
    public static function resolve(array $tanimlar, string $given): ?array
    {
        if ($given === '') {
            return null;
        }
        // SABIT ZAMANLAMA: butun tanimlar KARSILASTIRILIR, ilk eslesmede
        // `break` YOK. Aksi halde "dogru anahtar 3. sirada" bilgisi zamanlama
        // kanali olurdu (A-1'in kapatilmis hali).
        $bulunan = null;
        $givenHash = hash('sha256', $given);

        foreach ($tanimlar as $t) {
            $eslesti = false;
            $hash = (string) ($t['hash'] ?? '');
            $plain = (string) ($t['key'] ?? '');

            if ($hash !== '') {
                if (str_starts_with($hash, self::HASH_PREFIX)) {
                    $eslesti = hash_equals($hash, self::HASH_PREFIX . $givenHash);
                } else {
                    // oneksiz hash: yine de hex karsilastirmasi (geri uyum)
                    $eslesti = hash_equals($hash, $givenHash);
                }
            } elseif ($plain !== '') {
                $eslesti = hash_equals($plain, $given);
            }

            if ($eslesti && $bulunan === null) {
                $bulunan = $t;
            }
        }

        if ($bulunan === null) {
            return null;
        }

        $scopes = is_array($bulunan['scopes'] ?? null) ? array_values($bulunan['scopes']) : [];
        $rate = is_array($bulunan['rate'] ?? null) ? $bulunan['rate'] : null;
        $bodyMax = isset($bulunan['body_max']) ? (int) $bulunan['body_max'] : null;
        $source = ((string) ($bulunan['hash'] ?? '')) !== '' ? 'hashed' : 'plain';

        return [
            'key_id'   => (string) $bulunan['id'],
            'scopes'   => $scopes,
            'rate'     => $rate,
            'body_max' => ($bodyMax !== null && $bodyMax > 0) ? $bodyMax : null,
            'source'   => $source,
        ];
    }

    /**
     * Anahtar iptal edilmis mi (iptal zamani gecmis)?
     */
    public static function isRevoked(array $tanim, ?int $now = null): bool
    {
        $revoked = self::normalizeTime($tanim['revoked_at'] ?? null);
        if ($revoked === null) {
            return false;
        }
        return $revoked <= ($now ?? time());
    }

    /**
     * Anahtar suresi dolmus mu?
     */
    public static function isExpired(array $tanim, ?int $now = null): bool
    {
        $expires = self::normalizeTime($tanim['expires_at'] ?? null);
        if ($expires === null) {
            return false;
        }
        return $expires <= ($now ?? time());
    }

    /**
     * Anahtari hash'li bicimde uretir (saklama bicimi: `sha256:<hex>`).
     *
     * @return array{plain:string, hash:string} `plain` YALNIZ bir kez
     *         gosterilir (CLI `create`/`rotate` ciktisinda); sonra saklanmaz.
     */
    public static function generate(int $bytes = 32): array
    {
        $plain = 'rbn_' . bin2hex(random_bytes(max(16, $bytes)));
        return ['plain' => $plain, 'hash' => self::HASH_PREFIX . hash('sha256', $plain)];
    }

    /**
     * Anahtar kimligi uretir (okunabilir, sirali, benzersiz).
     */
    public static function generateId(string $scope = 'default', array $mevcut = []): string
    {
        $temel = 'k_' . preg_replace('/[^a-z0-9]+/i', '_', $scope) . '_';
        $no = 1;
        do {
            $id = $temel . str_pad((string) $no, 2, '0', STR_PAD_LEFT);
            $no++;
        } while (in_array($id, $mevcut, true));
        return $id;
    }

    /**
     * Bu istek icin cozulen anahtar kimligi (istek omru boyunca tekil).
     *
     * [FW-APIGUARD · TASARIM GOREV 2] `ApiGuard` middleware'i kimligi cozer ve
     * burada BIRAKIR; `ExternalApiController` `actions` kontrolunden ONCE
     * `assertScope()` ile ikinci bir kez dener (derinlikte savunma).
     * BOS liste = kapsam denetimi YOK (geri uyum).
     *
     * @var array{key_id:string, scopes:array}|null
     */
    private static ?array $current = null;

    public static function rememberIdentity(array $kimlik): void
    {
        self::$current = [
            'key_id' => (string) ($kimlik['key_id'] ?? ''),
            'scopes' => is_array($kimlik['scopes'] ?? null) ? array_values($kimlik['scopes']) : [],
        ];
    }

    public static function currentIdentity(): ?array
    {
        return self::$current;
    }

    public static function forgetIdentity(): void
    {
        self::$current = null;
    }

    /**
     * Kapsam denetimi. `true` = gecerli; `false` = kapsam disi.
     *
     * Bos kapsam listesi `true` doner (geri uyum: eski anahtarlar tum eylemleri
     * gorur). `*` kapsami tum eylemleri kapsar.
     */
    public static function assertScope(?string $action): bool
    {
        $kimlik = self::$current;
        if ($kimlik === null) {
            return true;   // ApiGuard hic calismadi (dogrusal koruma yok) -> dokunma
        }
        $scopes = $kimlik['scopes'];
        if ($scopes === []) {
            return true;
        }
        $action = (string) $action;
        if ($action === '') {
            return true;   // eylem yok: controller'in 400 dondurmesi beklenir
        }
        return in_array($action, $scopes, true) || in_array('*', $scopes, true);
    }
}