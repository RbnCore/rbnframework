<?php

declare(strict_types=1);

namespace Rbn\Framework\Bundles\RbnSuite\RbnAuth\Services;

use Rbn\Framework\Core\Base\Services\BaseService;

/**
 * RecoveryService - The Identity Lifecycle Orchestrator 🧶📧⚓
 * RBN 3.5 Masterpiece Standard.
 * 
 * Orchestrates account creation, password recovery, and verification.
 * 
 * [A0-6] Token zorunluluğu: `verify()` ve `reset()` artık **ham token'ı olmadan
 * çalışmaz**. `userId` oturumdan/istekten değil, **doğrulanmış token satırından**
 * gelir. Ölçülen açık: eski `verify(int $userId)` imzası token'ı hiç okumuyordu
 * (A-01: `?uid=N&token=yanlış` herhangi bir hesabı aktifleştiriyordu) ve
 * `reset(int $userId, …)` kullanıcıyı **yazılmayan** `reset_user_id` oturum
 * değişkeninden alıyordu (A-02).
 * 
 * @property \Rbn\Framework\Bundles\RbnSuite\RbnAuth\Handlers\LifecycleHandler $lifecycleHandler
 * @property \Rbn\Framework\Bundles\RbnSuite\RbnAuth\Handlers\CredentialHandler $credentialHandler
 */
class RecoveryService extends BaseService
{
    /** E-posta doğrulama token'ı ömrü: 24 saat. */
    private const EMAIL_VERIFICATION_HOURS = 24;

    /** Parola sıfırlama token'ı ömrü: 60 dakika. */
    private const PASSWORD_RECOVERY_HOURS = 1;

    /**
     * Kullanıcı numaralandırmasını (enumeration) engelleyen TEK mesaj.
     * Hem "hesap yok" hem "hesap var" dalı **bu satırı** döndürür (A-26).
     */
    private const RECOVERY_NEUTRAL_MESSAGE = 'Eğer bu e-posta adresi kayıtlıysa, şifre sıfırlama bağlantısı gönderilmiştir.';

    /**
     * Orchestrates the secure identity registration process 🧬🚀
     */
    public function register(array $data): array
    {
        $lifecycle = $this->lifecycleHandler ?? $this->handler('lifecycle');

        // 🎼 Step 1: Execute Creation (Worker Logic) 🏹🧬
        $attempt = $lifecycle ? $lifecycle->createIdentity($data) : ['success' => false, 'error' => 'lifecycle_error', 'message' => 'Servise ulaşılamadı.'];

        if (!$attempt['success']) {
            return $this->sendError($attempt['message'] ?? 'Kayıt oluşturulamadı.', ['reason' => $attempt['error'] ?? '']);
        }

        // 🎼 Step 2: Create Verification Token via CredentialHandler 🔑🏗️⚓
        //          [A0-6] Token üretilemezse kullanıcı "doğrula" denmemeli:
        //          YA-6'nın "sonsuza kadar is_active=2" sessizliği yerine hata.
        $user = $attempt['user'];
        $credential = $this->credentialHandler ?? $this->handler('credential');
        if (!$credential) {
            return $this->sendError('Doğrulama bağlantısı oluşturulamadı. Lütfen tekrar deneyin.', ['reason' => 'credential_unavailable']);
        }

        try {
            $token = $credential->issueVaultToken((int)$user['id'], 'email_verification', self::EMAIL_VERIFICATION_HOURS);
        } catch (\Throwable $e) {
            return $this->sendError('Doğrulama bağlantısı oluşturulamadı. Lütfen tekrar deneyin.', ['reason' => 'token_issue_failed']);
        }

        if ($token === '') {
            return $this->sendError('Doğrulama bağlantısı oluşturulamadı. Lütfen tekrar deneyin.', ['reason' => 'empty_token']);
        }

        // 🎼 Step 3: Bağlantıyı e-postayla gönder (şablon yoksa "gönderildi" denmez)
        $delivered = $this->deliverLink($user['email'], 'E-posta Doğrulama', 'email_verification', $user['username'] ?? 'User', $token, (int) $user['id']);

        if (!$delivered) {
            return $this->sendError(
                'Kaydınız oluşturuldu ancak doğrulama e-postası gönderilemedi. Lütfen destek ile iletişime geçin.',
                ['reason' => 'email_not_delivered', 'user_id' => $user['id']]
            );
        }

        return $this->sendSuccess('Kayıt başarılı. Doğrulama bağlantısı e-posta adresinize gönderildi (24 saat geçerli).', ['user_id' => $user['id']]);
    }

    /**
     * Orchestrates the password recovery workflow 🗝️🛡️
     *
     * [A0-6] Kullanıcı numaralandırması yok: **her iki dal da aynı mesajı ve aynı
     * maliyeti üretir.** "Hesap yok" dalı bile sabit maliyetli `password_verify`
     * yapar (A-08 zamanlama sızıntısı düzeltmesi).
     */
    public function initiateRecovery(string $email): array
    {
        $userRepo = $this->repository('project.user');
        // [A0-6 / 500 kökü] Kullanıcı araması DB'ye dokunur. Şema/erişim hatası
        // **nötr** yanıtı bozmaz: istek "gönderildi" görünür, 500 olmaz, hesap
        // varlığı sızmaz (A-26) — yalnız token üretilmez.
        try {
            $user = $userRepo ? $userRepo->findByIdentity($email) : null;
        } catch (\Throwable $e) {
            $user = null;
        }

        if (!$user) {
            // 🛡️ Sabit maliyet: varlık sızıntısını zamanlama üzerinden de kapat
            password_verify($email, '$2y$10$usesomesillystringforsaltusesomesillystringforuSALTxxxx');

            return $this->sendSuccess(self::RECOVERY_NEUTRAL_MESSAGE);
        }

        // 🎼 Step 2: Create Recovery Token via CredentialHandler 🔑🏗️⚓ (60 dk)
        $credential = $this->credentialHandler ?? $this->handler('credential');
        $token = '';
        if ($credential) {
            try {
                $token = $credential->issueVaultToken((int)$user['id'], 'password_recovery', self::PASSWORD_RECOVERY_HOURS);
            } catch (\Throwable $e) {
                $token = '';
            }
        }

        if ($token !== '') {
            $this->deliverLink($user['email'], 'Şifre Sıfırlama İsteği', 'password_recovery', $user['username'] ?? 'User', $token, (int) $user['id']);
        }

        // ⚠️ Aynı mesaj — "hesap var mı yok mu" bilgisi ASLA dönmez (A-26)
        return $this->sendSuccess(self::RECOVERY_NEUTRAL_MESSAGE);
    }

    /**
     * Finalizes the password reset lifecycle ✅🔐
     *
     * [A0-6] İmza değişti: `reset(string $rawToken, string $newPassword)`.
     * `userId` **token satırından** gelir; oturum değişkeni (`reset_user_id`)
     * tamamen kaldırıldı ve framework'ta onu yazan kod yoktu (ölü yol, A-02).
     *
     * @return array{success:bool,message:string,error?:string}
     */
    public function reset(string $rawToken, string $newPassword): array
    {
        // [A0-6 / 500 kökü] Şema-uyumsuz bir sorgu (ör. eksik kolon) buradan kaçarsa
        // `SQLSTATE[42S22]` framework'ün geliştirme hata sayfasına düşer ve istek
        // **HTTP 500** + sunucu yolları/stack trace ile döner. Token akışı
        // **fail-closed** olmalı: yakalanmayan Throwable → jenerik hata + 302.
        try {
            return $this->resetFlow($rawToken, $newPassword);
        } catch (\Throwable $e) {
            return $this->sendError($this->tokenErrorMessage('password_recovery'), ['error' => 'reset_failed']);
        }
    }

    private function resetFlow(string $rawToken, string $newPassword): array
    {
        // 🎼 Step 0: Token zorunluluğu — oturum/parametre ile kullanıcı seçilmez 🔐
        $claim = $this->claimToken($rawToken, 'password_recovery');
        if (isset($claim['error'])) {
            return $this->sendError($this->tokenErrorMessage('password_recovery'), ['error' => $claim['error']]);
        }
        $userId = (int) $claim['user_id'];

        $credential = $this->credentialHandler ?? $this->handler('credential');

        // 🎼 Step 1: Security Barrier 🔐🛡️⚓
        if ($credential) {
            $passCheck = $credential->validatePassword($newPassword);
            if ($passCheck !== true) {
                return $this->sendError($passCheck['message'] ?? 'Geçersiz şifre.', ['error' => $passCheck['error'] ?? 'weak_password']);
            }
        }

        // 🎼 Step 2: Persistence
        $lifecycle = $this->lifecycleHandler ?? $this->handler('lifecycle');
        $success = $lifecycle ? $lifecycle->completeReset($userId, $newPassword) : false;

        if (!$success) {
            return $this->sendError('Şifre güncellenemedi.', ['error' => 'reset_failed']);
        }

        return $this->sendSuccess('Şifreniz başarıyla güncellendi. Yeni şifrenizle giriş yapabilirsiniz.');
    }

    /**
     * Finalizes the email verification lifecycle ✅🔐
     *
     * [A0-6] İmza değişti: `verify(string $rawToken, int $expectedUserId = 0)`.
     * `$expectedUserId` yalnız **çapraz kullanıcı kontrolü** içindir: e-postanın
     * bağlantısındaki `uid` ile token'ın sahibi örtüşmezse aktivasyon olmaz.
     * Token doğrulanmadan `confirmIdentity()` **hiç** çağrılmaz.
     *
     * @return array{success:bool,message:string,error?:string}
     */
    public function verify(string $rawToken, int $expectedUserId = 0): array
    {
        // [A0-6 / 500 kökü] `verify()` de aynı fail-closed kuralı uygular: token
        // tüketimi/aktivasyon sırasında çıkan **her** Throwable kullanıcıya sızmaz,
        // jenerik mesaja çevrilir → `handleResult` 302 yönlendirmesi yapar.
        try {
            return $this->verifyFlow($rawToken, $expectedUserId);
        } catch (\Throwable $e) {
            return $this->sendError($this->tokenErrorMessage('email_verification'), ['error' => 'verify_failed']);
        }
    }

    private function verifyFlow(string $rawToken, int $expectedUserId = 0): array
    {
        // 🎼 Step 0: Token zorunluluğu 🔐
        $claim = $this->claimToken($rawToken, 'email_verification');
        if (isset($claim['error'])) {
            return $this->sendError($this->tokenErrorMessage('email_verification'), ['error' => $claim['error']]);
        }

        $userId = (int) $claim['user_id'];
        if ($expectedUserId > 0 && $expectedUserId !== $userId) {
            return $this->sendError('Doğrulama bağlantısı geçersiz.', ['error' => 'user_mismatch']);
        }

        $lifecycle = $this->lifecycleHandler ?? $this->handler('lifecycle');
        $ok = $lifecycle ? $lifecycle->confirmIdentity($userId) : false;

        if (!$ok) {
            return $this->sendError('E-posta doğrulanamadı.', ['error' => 'confirm_failed']);
        }

        return $this->sendSuccess('E-posta adresiniz doğrulandı. Giriş yapabilirsiniz.');
    }

    /**
     * Parola sıfırlama sayfasının **tüketmeden** token kontrolü (GET görünümü için).
     */
    public function canShowResetForm(string $rawToken): bool
    {
        $tokenRepo = $this->repository('project.userToken');
        if (!$tokenRepo) {
            return false;
        }

        // [A0-6 / 500 kökü] `peek()` DB'ye dokunur: şema uyuşmazlığı 500 üretmesin.
        try {
            return (bool) $tokenRepo->peek($rawToken, 'password_recovery');
        } catch (\Throwable $e) {
            return false;
        }
    }

    /* ==========================================================================
       [ PRIVATE HELPERS ] 🔧
       ========================================================================== */

    /**
     * Token'ı tek seferlik tüketir ve sahibi kullanıcıyı döndürür.
     *
     * @return array{user_id:int}|array{error:string}
     */
    private function claimToken(string $rawToken, string $purpose): array
    {
        $tokenRepo = $this->repository('project.userToken');
        if (!$tokenRepo) {
            return ['error' => 'token_store_unavailable'];
        }
        if ($rawToken === '') {
            return ['error' => 'not_found'];
        }

        // [A0-6 / 500 kökü] `consume()` SELECT + UPDATE yapar. Eksik kolon / erişim
        // hatası **fail-closed** bir hata döner; istisna framework'e kaçmaz.
        try {
            return $tokenRepo->consume($rawToken, $purpose);
        } catch (\Throwable $e) {
            return ['error' => 'token_store_unavailable'];
        }
    }

    /**
     * Token hatasını kullanıcıya **tek ve nötr** bir mesajla çevirir.
     * (Ayrıntı sızdırmak yok; ayrıntı `error` alanında sunucu içi kalır.)
     */
    private function tokenErrorMessage(string $purpose): string
    {
        if ($purpose === 'password_recovery') {
            return 'Şifre sıfırlama bağlantısı geçersiz veya süresi dolmuş. Lütfen yeni bir bağlantı isteyin.';
        }

        return 'Doğrulama bağlantısı geçersiz veya süresi dolmuş. Lütfen yeni bir bağlantı isteyin.';
    }

    /**
     * E-posta bağlantısını gönderir.
     *
     * [A0-6] Şablon yoksa / SMTP kapalıysa akış **sessizce başarılı sayılmaz**:
     * çağıran taraf `$sent` bayrağını görüp uyarı üretebilir. Gerçek e-posta
     * gönderimi yerelde kapalıdır (test kayıtları silinir).
     */
    private function deliverLink(string $to, string $subject, string $view, string $name, string $token, int $userId): bool
    {
        $emailService = $this->service('email');
        if (!$emailService || !method_exists($emailService, 'send')) {
            return false;
        }

        try {
            $result = $emailService->send($to, $subject, $view, [
                'token'      => $token,
                'name'       => $name,
                'user_id'    => $userId,
                // Hazır bağlantı: şablon ziyaretçinin `uid` parametresini unutsa
                // bile token tek başına yeter (user_id token'dan türetilir).
                'link'       => $view === 'email_verification'
                    ? url('verify-email?uid=' . $userId . '&token=' . $token)
                    : url('reset-password?token=' . $token),
                'expires_in' => $view === 'email_verification' ? '24 saat' : '60 dakika',
            ]);

            return is_array($result) ? (bool) ($result['success'] ?? true) : true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
