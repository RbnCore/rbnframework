<?php

namespace Rbn\Framework\Bundles\RbnSuite\RbnAuth\Handlers;

use Rbn\Framework\Core\Base\BaseComponent;
use Rbn\Framework\Core\Support\Blueprints\Validations\EmailValidations;
use Rbn\Framework\Core\Support\Blueprints\Validations\PasswordValidations;

/**
 * CredentialHandler - The Central Security Karargah 🛡️🏛️⚓
 * RBN Framework Standard.
 * 
 * Specialized orchestrator for identity credential validation:
 * - Email Domain/Pattern filtering
 * - Password Blacklist & Strength analysis
 * - Password Scoring (Modern Entropy logic)
 */
class CredentialHandler extends BaseComponent
{
    /**
     * Validates Email Identity against Core Blueprints 📧🛡️⚓
     */
    public function validateEmail(string $email): array|true
    {
        $domain = substr(strrchr($email, "@"), 1);

        // 1. Temp Mail Check 🛡️
        if (in_array($domain, EmailValidations::TEMP_DOMAINS)) {
            return ['error' => 'disposable_email', 'message' => 'Geçici e-posta servisleri ile kayıt yapılamaz.'];
        }

        // 2. Spam Domain Check 🚫
        if (in_array($domain, EmailValidations::SPAM_DOMAINS)) {
            return ['error' => 'spam_domain', 'message' => 'Bu e-posta servisine şu an izin verilmiyor.'];
        }

        // 3. Suspicious Pattern Check (Regex) 🕵️‍♂️
        foreach (EmailValidations::SUSPICIOUS_PATTERNS as $pattern) {
            if (preg_match($pattern, $email)) {
                return ['error' => 'suspicious_identity', 'message' => 'Bu tip e-posta adresleri sistem tarafından reddedilmiştir.'];
            }
        }

        return true;
    }

    /**
     * Validates Password against Blacklists and Patterns 🔐🛡️🏗️
     *
     * [A-12] TABAN KURAL: bu metot artik minimum uzunlugu **kendi icinde**
     * denetler (`PasswordValidations::MIN_PASSWORD_LENGTH`). Onceden yalniz
     * form katmaninda `min:6` vardi ve 6 karakterlik zayif parola bu yoldan
     * geciyordu. Bos/yalniz bosluk da ayni kuralla reddedilir.
     *
     * KAPSAM DIYARI (BILINCLI): bu kural YALNIZ yeni parola belirleyen
     * akislarda (kayit, sifre sifirlama) cagrilir. GIRIS yolunda
     * (`AccessHandler::authenticate` -> `crypto->verify`) UZUNLUK DENETLENMEZ;
     * mevcut kullanicilarin kisa parolalari bozulmaz.
     */
    public function validatePassword(string $password): array|true
    {
        $payload = strtolower($password);

        // 0. Base Rule: Bosluk ve Minimum Uzunluk 📏🛡️ (onceki davranis: YOK)
        if (trim($password) === '') {
            return ['error' => 'password_too_short', 'message' => 'Şifre boş olamaz.'];
        }
        if (strlen($password) < PasswordValidations::MIN_PASSWORD_LENGTH) {
            return [
                'error'   => 'password_too_short',
                'message' => 'Şifre en az ' . PasswordValidations::MIN_PASSWORD_LENGTH . ' karakter olmalıdır.'
            ];
        }

        // 1. Blacklist Check (Common Passwords) 🚫
        if (in_array($payload, PasswordValidations::COMMON_PASSWORDS)) {
            return ['error' => 'common_password', 'message' => 'Daha güvenli bir şifre seçmelisiniz. (Yaygın kullanılan şifre)'];
        }

        // 2. Sequential/Keyboard Pattern Check 🧬🚥
        // [A-13 · DÜZELTME] KONTROL YÖNÜ TERS'İDİ.
        //
        // ÖNCEKİ HALİ: `str_contains($pattern, $payload)` — yani "taslağın İÇİNDE
        // parolayı ara". Sonuç ölçüldü: '9876' reddediliyordu, '0987' ve 'aaaa'
        // GEÇİYORDU. Sebep: `SEQUENTIAL_PATTERNS` birer tam dizi ('123456789',
        // 'abcdefghijklmnopqrstuvwxyz', …); parolada bu dizinin TAMAMI bulunamaz.
        // Doğru kontrol: parolanın İÇİNDE 4+ haritli ardisik alt dizi aranır.
        if ($this->containsSequentialRun($payload)) {
            return ['error' => 'sequential_password', 'message' => 'Şifreniz sıralı karakterler içeremez.'];
        }

        return true;
    }

    /**
     * Parolanın içinde ardisik karakter dizisi (klavye/alfabe) ve tekrar var mı?
     *
     * [A-13] Saf yardımcı (DB/oturum bağımlılığı yok → birim testlenebilir).
     *
     * KURALLAR (bilinçli seçim):
     *  - HARF: 4+ ardisik harf reddedilir ('qwer', 'zxcv', 'abcd').
     *  - RAKAM: 5+ ardisik rakam reddedilir; **4 haneli 19xx/20xx istisna**
     *    (yıl — 'Ankara2024!' meşru bir paroladır, reddedilmemeli).
     *  - TEKRAR: 4+ aynı karakter reddedilir ('aaaa').
     *  '1234'/'9876' gibi 4 haneli diziler bugün de reddediliyordu (yön hatalı
     *  olsa da `str_contains('123456789','1234')` doğruydu); bu davranış korunur.
     */
    private function containsSequentialRun(string $payload): bool
    {
        // 4+ aynı karakter (aaa…): yaygın zayıf parola göstergesi
        if (preg_match('/(\w)\1{3,}/u', $payload)) {
            return true;
        }

        // Rakam dizileri: 5+ ardisik reddedilir, 4 haneli yıl (19xx/20xx) değilse
        if (preg_match_all('/\d{4,}/', $payload, $matches)) {
            foreach ($matches[0] as $run) {
                if (strlen($run) >= 5) {
                    return true;
                }
                if (strlen($run) === 4 && !preg_match('/^(19|20)\d{2}$/', $run)) {
                    return true;
                }
            }
        }

        // Harf dizileri: `SEQUENTIAL_PATTERNS` her dizisinden 4 karakterlik
        // ALT DIZI üretilip parolanın içinde aranır (her iki yön de kapsanır:
        // '123456789' ve '987654321' tanımda birlikte tanımlıdır).
        foreach (PasswordValidations::SEQUENTIAL_PATTERNS as $pattern) {
            $length = strlen($pattern);
            for ($i = 0; $i + 4 <= $length; $i++) {
                if (str_contains($payload, substr($pattern, $i, 4))) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Calculates password strength score (0-100) 🛡️📊
     * RBN Framework High-Performance Entropy Analysis.
     */
    public function calculateScore(string $password): int
    {
        $score = 0;
        $length = strlen($password);
        
        // 🎼 Layer 1: Length contribution (Max 25)
        $score += min(25, $length * 2);

        // 🎼 Layer 2: Complexity factors (+10 each)
        if (preg_match('/[a-z]/', $password)) $score += 10;
        if (preg_match('/[A-Z]/', $password)) $score += 10;
        if (preg_match('/[0-9]/', $password)) $score += 10;
        if (preg_match('/[^A-Za-z0-9]/', $password)) $score += 10;

        // 🎼 Layer 3: Bonus for extra length
        if ($length >= 12) $score += 5;
        if ($length >= 16) $score += 5;

        // 🎼 Layer 4: Entropy factor (Unique characters ratio)
        $uniqueChars = count(array_unique(str_split($password)));
        if ($length > 0 && $uniqueChars >= $length * 0.8) $score += 15;

        return (int) max(0, min(100, $score));
    }

    /**
     * Issues a secure, single-use, time-limited Vault Token 🔑🕐🏗️⚓
     *
     * [A0-6] Değişiklik:
     *  - Token artık `z_users_security` tek kolonuna değil, **tek kullanımlık token
     *    kasasına** (`UserTokenRepository` -> `z_user_tokens`) yazılır. Eski yol
     *    üç açık bırakıyordu: (a) `$columnMap[$type] ?? 'verification_token'`
     *    bilinmeyen tipi sessizce yanlış kolona düşürüyordu (YA-5), (b) token
     *    `expires_at`/`used_at` taşımadığı için **süresiz ve sınırsız kez**
     *    geçerliydi (YA-4), (c) `$hours` parametresi hiç kullanılmıyordu (YA-4).
     *  - `$hours` **artık gerçekten uygulanır** ve TTL üst sınırla kırpılır.
     *  - DB'ye **yalnız sha256 özeti** yazılır; ham token yalnız çağırana döner.
     *
     * @param string $type email_verification | password_recovery
     * @throws \InvalidArgumentException Bilinmeyen token tipi (sessiz yanlış kolon olmaz)
     * @throws \RuntimeException Token kasası yazamazsa (sessiz "başarılı" olmaz)
     */
    public function issueVaultToken(int $userId, string $type, int $hours = 2): string
    {
        /** @var \Rbn\Framework\Core\Database\Repositories\Project\UserTokenRepository $tokenRepo */
        $tokenRepo = $this->repository('project.userToken');
        if (!$tokenRepo) {
            throw new \RuntimeException('Token üretilemedi (token kasası kayıtlı değil).');
        }

        // 🎼 Step 1: TTL — saat cinsinden dakikaya çevrilir, makul aralığa kırpılır 🕐
        $hours = max(1, min($hours, 24));
        $ttlMinutes = $hours * 60;

        // 🎼 Step 2: Tek kullanımlık token üret (random_bytes(32) + sha256 özeti DB'de) 🧬⚓
        return $tokenRepo->issue($userId, $type, $ttlMinutes, $this->request->ip());
    }
}
