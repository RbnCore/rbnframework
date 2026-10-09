<?php

namespace Rbn\Framework\Core\Support\Bridges\Helpers\Library;

/**
 * CryptoHelper - Merkezi Şifreleme ve Güvenlik Yardımcısı
 * AES-256-CBC algoritmasını kullanarak veri şifreleme/çözme ve şifre özetleme (hashing) işlemlerini yönetir.
 */
class CryptoHelper
{
    private static string $method = 'aes-256-cbc';

    /**
     * Veriyi şifreler (Reversible)
     */
    public static function encrypt(string $data, ?string $key = null): string
    {
        $key = self::resolveKey($key);
        $ivLength = openssl_cipher_iv_length(self::$method);
        $iv = openssl_random_pseudo_bytes($ivLength);

        $encrypted = openssl_encrypt($data, self::$method, $key, 0, $iv);

        return base64_encode($iv . $encrypted);
    }

    /**
     * Şifrelenmiş veriyi çözür (Reversible)
     */
    public static function decrypt(string $data, ?string $key = null): ?string
    {
        try {
            if (empty($data))
                return null;

            $key = self::resolveKey($key);
            $decoded = base64_decode($data);
            $ivLength = openssl_cipher_iv_length(self::$method);

            if (strlen($decoded) <= $ivLength)
                return null;

            $iv = substr($decoded, 0, $ivLength);
            $encrypted = substr($decoded, $ivLength);

            $decrypted = openssl_decrypt($encrypted, self::$method, $key, 0, $iv);

            return $decrypted !== false ? $decrypted : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Doğrulamalı şifreleme (AES-256-GCM). Bozulan/başka kayda taşınan şifreli metin
     * çözülmez (CBC'deki bit çevirme ve dolgu kehaneti burada yoktur).
     *
     * Anahtar: `hash_hkdf('sha256', <uygulama anahtarı>, 32, $info)` — her kullanım
     * (`$info`) kendi alt anahtarını alır, uygulama anahtarının tamamı (256 bit) kullanılır.
     * `$aad` şifrelenmez ama doğrulanır (ör. kaydın kimliği): farklı AAD ile çözme başarısız.
     *
     * Biçim: base64( nonce[12] || tag[16] || şifreli metin ).
     *
     * @throws \RuntimeException Anahtar hiçbir kaynaktan bulunamazsa (fail-closed).
     */
    public static function encryptAead(string $data, string $info, string $aad = '', ?string $key = null): string
    {
        $nonce = random_bytes(12);
        $tag = '';
        $cipher = openssl_encrypt($data, 'aes-256-gcm', self::deriveKey($info, $key), OPENSSL_RAW_DATA, $nonce, $tag, $aad, 16);
        if ($cipher === false) {
            throw new \RuntimeException('RBN Guvenlik: AES-256-GCM sifreleme basarisiz.');
        }

        return base64_encode($nonce . $tag . $cipher);
    }

    /**
     * `encryptAead()` çözücüsü. Etiket/AAD tutmazsa ya da biçim bozuksa `null`.
     *
     * @throws \RuntimeException Anahtar hiçbir kaynaktan bulunamazsa (fail-closed).
     */
    public static function decryptAead(string $data, string $info, string $aad = '', ?string $key = null): ?string
    {
        $raw = base64_decode($data, true);
        if ($raw === false || strlen($raw) < 28) {
            return null;
        }

        $plain = openssl_decrypt(substr($raw, 28), 'aes-256-gcm', self::deriveKey($info, $key), OPENSSL_RAW_DATA, substr($raw, 0, 12), substr($raw, 12, 16), $aad);

        return $plain === false ? null : $plain;
    }

    /**
     * Uygulama anahtarından kullanım başına 32 baytlık alt anahtar (HKDF-SHA256).
     *
     * @throws \RuntimeException Anahtar hiçbir kaynaktan bulunamazsa (fail-closed).
     */
    private static function deriveKey(string $info, ?string $key = null): string
    {
        return hash_hkdf('sha256', self::keyMaterial($key), 32, $info);
    }

    /**
     * Şifre özetler (One-way Hashing)
     * Kullanıcı şifreleri için bunu kullanın.
     */
    public static function hash(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    /**
     * Şifre özetini doğrular
     */
    public static function verify(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * A-17: Özet güncel algoritma/maliyetten eskiyse true (başarılı girişte sessiz yeniden hash için).
     * `hash()` ile aynı algoritmayı ölçer; maliyet sonradan yükseltilirse kendiliğinden devreye girer.
     */
    public static function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, PASSWORD_BCRYPT);
    }

    public static function blindIndex(string $data): string
    {
        $key = self::resolveKey();
        return hash_hmac('sha256', strtolower(trim($data)), $key);
    }

    /**
     * Generates a cryptographically secure random token
     */
    public static function generateToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }

    /**
     * Hashes a token for secure database storage (using SHA-256)
     */
    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    /**
     * Şifreleme anahtarını belirler — FAIL-CLOSED.
     *
     * ÖNCELİK (ilk bulan kazanır):
     *   1. Çağırana açıkça verilen `$key`.
     *   2. Sır dosyası: `Secrets::optional('app_key')` (`Secrets/secrets.php`).
     *      [FW-096-D8] Ortam değişkeni (`APP_KEY`/`ENCRYPTION_KEY`) yolu YOKTUR.
     *
     * HİBİRİ YOKSA **HATA FIRLATILIR** — şifreleme/çözme sessizce
     * "bütün kurulumlarda bilinen" bir anahtarla devam ETMEZ. Anahtarı
     * göremeyen bir saldırganın bile bileceği sabit bir tuz, şifrelemenin
     * güvenliğini tamamen ortadan kaldırır (şifreli veri "şifreli" görünür
     * ama istenildiği an açılır); bu yüzden gömülü tuz kaldırıldı.
     *
     * GERİYE UYUMLULUK (kill-switch — operatör kararı):
     *   Daha önce gömülü tuzla şifrelenmiş veri varsa, tuzun kendisi depoda
     *   BULUNMAZ; operatör eski tuzu kendi kayıtlarından `secrets.php`
     *   üst düzey `legacy_salt` olarak verir ve `app.allow_legacy_salt = true`
     *   ile açıkça onaylar.
     *   Anahtar değişmeden üretilir → eski şifreli veri okunur.
     *   Onay verilmezse FAIL-CLOSED (veri okunamaz, ama sessizce yanlış
     *   anahtarla çözülmez).
     *
     * @throws \RuntimeException Anahtar hiçbir kaynaktan bulunamazsa.
     */
    private static function resolveKey(?string $key = null): string
    {
        // v1 (CBC) türetmesi: SHA-256 özetinin ilk 32 HEX karakteri. Mevcut şifreli
        // veriyi okumak için DEĞİŞTİRİLMEZ; yeni veri `encryptAead()` (HKDF) kullanır.
        return substr(hash('sha256', self::keyMaterial($key)), 0, 32);
    }

    /**
     * Anahtar kaynağı (ham değer); öncelik sırası `resolveKey()` belgesindeki gibidir.
     *
     * @throws \RuntimeException Anahtar hiçbir kaynaktan bulunamazsa.
     */
    private static function keyMaterial(?string $key = null): string
    {
        if ($key !== null && $key !== '') {
            return $key;
        }

        $sirDosyasi = self::sirDosyasindanAnahtar();
        if ($sirDosyasi !== null && $sirDosyasi !== '') {
            return $sirDosyasi;
        }

        $legacy = self::legacySaltApproval();
        if ($legacy !== null) {
            return $legacy;
        }

        throw new \RuntimeException(
            'RBN Guvenlik: sifreleme anahtari bulunamadi (Secrets/secrets.php icindeki '
            . '`app_key` anahtari tanimli degil). Sifreleme icin gomulu/sabit bir tuz KULLANILMAZ. '
            . 'Cozum: Core/System/Config/Secrets/secrets.php dosyasina `app_key` anahtarini ekleyin. '
            . 'ESKI GOMULU TUZLA sifrelenmis veriniz varsa ayni dosyada gecici olarak '
            . '`app.allow_legacy_salt = true` ve ust duzey `legacy_salt` tanimlayin; veriyi '
            . 'yeniden sifreledikten sonra bu iki alani kaldirin.'
        );
    }

    /**
     * Sır dosyasından uygulama anahtarı (`Secrets::optional('app_key')`).
     *
     * `optional()` zaten "anahtar TANIMLI DEĞİLSE `null`" diye TASARLANMIŞTIR;
     * dolayısıyla anahtar tanımlı değilken bu dal mevcut fail-closed hatasına
     * düşer (sessiz varsayılan yok).
     *
     * [FW-BANU-B4-BARAN] BURADA `try/catch` **KALDIRILDI**: Sır dosyası
     * bozuk/okunamaz/izin fazla geniş olduğunda `optional()` artık
     * fail-closed `RuntimeException` fırlatır ve o **ASIL NEDEN** yukarı
     * çıkmalıdır. Önceden buradaki `catch` onu yutup "şifreleme anahtarı
     * bulunamadı" diye yanlış teşhis üretiyordu.
     */
    private static function sirDosyasindanAnahtar(): ?string
    {
        $secrets = 'Rbn\\Framework\\Core\\System\\Config\\Secrets';
        if (!class_exists($secrets)) {
            return null;
        }

        // Sır dosyası tanımlı değilse `null` (fail-closed aşağıda devreye girer);
        // dosya BOZUK/okunamaz ise `optional()` fırlatır ve sebep korunur.
        $v = $secrets::optional('app_key');

        return is_string($v) ? trim($v) : null;
    }

    /**
     * Kill-switch: eski gömülü tuzla şifrelenmiş veriyi okumak için
     * operatörün AÇIKÇA onayı + tuzun kendisi (depo dışından).
     *
     * [FW-096-D8] TEK kaynak `secrets.php`: onay `app.allow_legacy_salt`
     * (PHP `true` olmalı; metin kabul edilmez), tuz üst düzey `legacy_salt`.
     * Onay YOKSA `null` döner → çağıran fail-closed hatasını fırlatır.
     * Onay VARSA tuz da tanımlı olmalıdır; o da yoksa yine `null`.
     */
    private static function legacySaltApproval(): ?string
    {
        $secrets = 'Rbn\\Framework\\Core\\System\\Config\\Secrets';
        if (!class_exists($secrets) || $secrets::app()['allow_legacy_salt'] !== true) {
            return null;
        }

        $tuz = $secrets::legacySalt();
        if ($tuz === null || $tuz === '') {
            return null;
        }

        self::legacyUyari();

        return self::eskiTuzdanTuretilenGirdi() . $tuz;
    }

    /**
     * Eski türetmenin girdi kısmı: çalışma alanı yolu.
     * (Eski kod `$workspace . <gömülü tuz>` öğesini hash'liyordu.)
     */
    private static function eskiTuzdanTuretilenGirdi(): string
    {
        if (defined('WORKSPACE_PATH')) {
            return (string) WORKSPACE_PATH;
        }

        $paths = 'Rbn\\Framework\\Core\\System\\Paths\\Paths';
        if (class_exists($paths)) {
            return (string) $paths::workspace();
        }

        return '';
    }

    /** Kill-switch kullanımı görünür olsun: istek başına bir kez uyarı. */
    private static function legacyUyari(): void
    {
        static $uyarildi = false;
        if ($uyarildi) {
            return;
        }
        $uyarildi = true;

        $mesaj = 'RBN Guvenlik: CryptoHelper GOMULU TUZ (legacy) modunda calisiyor '
            . '(secrets.php app.allow_legacy_salt). Bu mod gecicidir; eski veriyi sifreleyip '
            . 'app.allow_legacy_salt / legacy_salt alanlarini kaldirin.';

        if (function_exists('error_log')) {
            @error_log($mesaj);
        }
        @trigger_error($mesaj, E_USER_WARNING);
    }
}
