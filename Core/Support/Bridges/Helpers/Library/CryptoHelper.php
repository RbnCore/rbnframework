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
     *   2. Ortam değişkeni: `APP_KEY`, sonra `ENCRYPTION_KEY`
     *      (`Env::string()` — kaynak sırası `$_ENV` → `$_SERVER` → `getenv`;
     *      CGI/FastCGI'de
     *      `$_ENV` doldurulmaz).
     *   3. Sır dosyası: `Secrets::optional('app_key')` (`Secrets/secrets.php`).
     *
     * HİBİRİ YOKSA **HATA FIRLATILIR** — şifreleme/çözme sessizce
     * "bütün kurulumlarda bilinen" bir anahtarla devam ETMEZ. Anahtarı
     * göremeyen bir saldırganın bile bileceği sabit bir tuz, şifrelemenin
     * güvenliğini tamamen ortadan kaldırır (şifreli veri "şifreli" görünür
     * ama istenildiği an açılır); bu yüzden gömülü tuz kaldırıldı.
     *
     * GERİYE UYUMLULUK (kill-switch — operatör kararı):
     *   Daha önce gömülü tuzla şifrelenmiş veri varsa, tuzun kendisi depoda
     *   BULUNMAZ; operatör eski tuzu kendi kayıtlarından `RBN_LEGACY_SALT`
     *   olarak verir ve `RBN_ALLOW_LEGACY_SALT=1` ile açıkça onaylar.
     *   Anahtar değişmeden üretilir → eski şifreli veri okunur.
     *   Onay verilmezse FAIL-CLOSED (veri okunamaz, ama sessizce yanlış
     *   anahtarla çözülmez).
     *
     * @throws \RuntimeException Anahtar hiçbir kaynaktan bulunamazsa.
     */
    private static function resolveKey(?string $key = null): string
    {
        if ($key !== null && $key !== '') {
            return substr(hash('sha256', $key), 0, 32);
        }

        foreach (['APP_KEY', 'ENCRYPTION_KEY'] as $ad) {
            $v = self::readEnvironment($ad);
            if ($v !== null && $v !== '') {
                return substr(hash('sha256', $v), 0, 32);
            }
        }

        $sirDosyasi = self::sirDosyasindanAnahtar();
        if ($sirDosyasi !== null && $sirDosyasi !== '') {
            return substr(hash('sha256', $sirDosyasi), 0, 32);
        }

        $legacy = self::legacySaltApproval();
        if ($legacy !== null) {
            return substr(hash('sha256', $legacy), 0, 32);
        }

        throw new \RuntimeException(
            'RBN Guvenlik: sifreleme anahtari bulunamadi (APP_KEY / ENCRYPTION_KEY ortam '
            . 'degiskeni ve Secrets/secrets.php icindeki `app_key` anahtari tanimli degil). '
            . 'Sifreleme icin gomulu/sabit bir tuz KULLANILMAZ. Cozum: (a) `APP_KEY` ortam '
            . 'degiskenini tanimlayin, ya da (b) Core/System/Config/Secrets/secrets.php '
            . 'dosyasina `app_key` anahtarini ekleyin. ESKI GOMULU TUZLA sifrelenmis veriniz varsa '
            . 'gecici olarak RBN_ALLOW_LEGACY_SALT=1 ve RBN_LEGACY_SALT=<eski tuz> tanimlayin; '
            . 'veriyi sifreledikten sonra bu iki degiskeni kaldirip yeniden sifreleyin.'
        );
    }

    /**
     * Ortam değişkeni okuma — TEK kapı: `Env::string()`.
     *
     * [FW-ENV-KAYIT-160] Kaynak sırası (`$_ENV` → `$_SERVER` → `getenv`) ve
     * "kayıtlı değilse hata" davranışı `Env`'in KENDİ kuralıdır; burada ikinci
     * bir okuyucu YAZILMAZ. `Env` boş/metin olmayan değerleri geçtiği için
     * eski `trim() === ''` kontrolünün de karşılığı vardır.
     *
     * @param string $name Kayıtlı ortam değişkeni adı.
     */
    private static function readEnvironment(string $name): ?string
    {
        $env = 'Rbn\\Framework\\Core\\System\\Config\\Env';
        if (!class_exists($env)) {
            require_once __DIR__ . '/../../../../System/Config/Env.php';
        }

        return $env::string($name);
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
     * Onay (kill-switch) YOKSA `null` döner → çağıran fail-closed hatasını fırlatır.
     * Onay VARSA eski tuz `RBN_LEGACY_SALT` ile verilmiş olmalıdır; o da yoksa
     * yine `null` (yani: parola tahmin edilmeye çalışılmaz).
     */
    private static function legacySaltApproval(): ?string
    {
        $onay = self::readEnvironment('RBN_ALLOW_LEGACY_SALT');
        if ($onay === null || !in_array(strtolower($onay), ['1', 'true', 'on', 'yes'], true)) {
            return null;
        }

        $tuz = self::readEnvironment('RBN_LEGACY_SALT');
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
            . '(RBN_ALLOW_LEGACY_SALT). Bu mod gecicidir; eski veriyi sifreleyip '
            . 'RBN_ALLOW_LEGACY_SALT / RBN_LEGACY_SALT tanimlarini kaldirin.';

        if (function_exists('error_log')) {
            @error_log($mesaj);
        }
        @trigger_error($mesaj, E_USER_WARNING);
    }
}
