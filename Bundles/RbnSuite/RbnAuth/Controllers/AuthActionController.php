<?php

namespace Rbn\Framework\Bundles\RbnSuite\RbnAuth\Controllers;

use Rbn\Framework\Core\Support\Blueprints\Validations\PasswordValidations;
use Rbn\Framework\Core\Support\Definitions\Route\RouteBlueprint;

/**
 * AuthActionController
 * Kayıt, şifre sıfırlama, e-posta doğrulama gibi tekil kullanıcı aksiyonlarını yönetir.
 * 
 * [A0-6] Token zorunluluğu: `verifyEmail()` artık `$token`'ı **kullanır** (eskiden
 * okuyup atıyordu) ve `resetPasswordSubmit()` kullanıcıyı oturum değişkeninden
 * değil **form token'ından** alır. `reset_user_id` oturum değişkeni **kaldırıldı**
 * (framework'ta onu yazan kod yoktu — ölü yol, A-02).
 * 
 * @property \Rbn\Framework\Core\Base\Storage\StorageManager $storage
 * @property \Rbn\Framework\Core\Support\Definitions\Route\RouteBlueprint $Route
 * @property \Rbn\Framework\Core\Http\Engine\Request $request
 * @property \Rbn\Framework\Bundles\RbnSuite\RbnAuth\Services\AuthService $authService
 * @property \Rbn\Framework\Bundles\RbnSuite\RbnAuth\Services\RecoveryService $recoveryService
 */
class AuthActionController extends AuthController
{
    /**
     * E-posta Doğrulama 🔐
     *
     * GET `/verify-email?uid=N&token=<token>`
     *
     * Ölçülen açık (A-01): `?uid=N&token=yanlış` isteği veritabanına ulaşıyor ve
     * `is_active=1` yapıyordu — token hiç okunmuyordu. Artık:
     *  - token boşsa: **aktivasyon yapılmaz**, kullanıcı "yeni bağlantı iste" yönlendirmesine düşer
     *    (eski token'sız bağlantıların geriye dönük riski, ONARIM-PLANI §5 satırı);
     *  - token yanlış / süresi dolmuş / kullanılmışsa: aktivasyon olmaz, kullanıcı
     *    hatası döner (500 değil);
     *  - `uid` ile token sahibi örtüşmezse: aktivasyon olmaz (çapraz kullanıcı).
     */
    public function verifyEmail(): void
    {
        $token = trim((string) $this->request->input('token', ''));
        $uid   = (int) $this->request->input('uid', 0);

        if ($token === '') {
            // ⚠️ Token'sız/eskiden kalma bağlantı: sessizce aktivasyon YAPMA
            $this->Route->alert(
                'error',
                'Doğrulama bağlantısı eksik veya geçersiz. Lütfen yeni bir doğrulama bağlantısı isteyin.',
                '/forgot-password'
            );
            return;
        }

        // 🎼 RBN Framework: Verification logic via Chef 🧶✅ (token zorunlu)
        $result = $this->recoveryService->verify($token, $uid);

        $this->Route->handleResult($result, [
            'success_path' => '/' . RouteBlueprint::LOGIN_PATH,
            'error_path'   => '/forgot-password'
        ]);
    }

    /**
     * POST: Kayıt İşlemi
     */
    public function registerSubmit(): void
    {
        $data = $this->request->form([
            'username' => 'required',
            'email' => 'required|email',
            // [A-12] Dagink `min:6` yerine merkezi politika sabiti.
            'password' => 'required|min:' . PasswordValidations::MIN_PASSWORD_LENGTH,
            // [FW-ALTYAPI-3 / B · QA §4.3] Parola tekrarı artık SUNUCUDA zorunlu.
            // Önceden kayıt kurallarında hiç yoktu; doğrulama yalnız istemcideydi
            // (ve görünümde alan bile bulunmuyordu). `nullable|string` DEĞİL:
            // boş veya hiç gönderilmemiş tekrar kabul edilmemeli (sıfırlama
            // akışındaki aynı açık kapatıldı). Güvenlik kontrolü girdisi olduğu
            // için `rawAll()`'a taşınmaz, kural tabanlı gelir.
            'confirm_password' => 'required|string'
        ], [
            'rateLimitEnabled' => true,
            'action' => 'register'
        ]);

        // 🔐 Şifre tekrarı: parolayla EŞLEŞMELİ (kayıt akışında da).
        if (!$this->confirmPasswordMatches($data)) {
            $this->Route->alert(
                'error',
                'Şifreler birbiriyle eşleşmiyor.',
                '/register'
            );
            return;
        }

        // 🎼 RBN Framework: Delegated to the Identity Lifecycle orchestrator 🧬🎻
        $result = $this->recoveryService->register($data);

        $this->Route->handleResult($result, [
            'success_message' => $result['message'],
            'success_path' => '/' . RouteBlueprint::LOGIN_PATH,
            'error_path' => '/register'
        ]);
    }

    /**
     * POST: Şifre Sıfırlama İsteği
     *
     * [A0-6] `action` değeri `password_reset` olarak düzeltildi. Eski değer `recovery`
     * idi ve `RateLimitValidations::LIMITS` içinde **yoktu** → hız sınırı hiç
     * tetiklenmiyordu (A-06 / team member http.md #61-62). Kalan eylem adları eşlemesi
     * (login → frontend_login) A0-3 kalemiyle Baran'ın sahipliğindedir, dokunulmadı.
     */
    public function forgotPasswordSubmit(): void
    {
        $data = $this->request->form([
            'email' => 'required|email'
        ], [
            'csrf' => true,
            'rateLimitEnabled' => true,
            'action' => 'password_reset'
        ]);

        // 🎼 RBN Framework: Autonomous Recovery 🗝️🛡️
        $result = $this->recoveryService->initiateRecovery($data['email']);

        $this->Route->handleResult($result, [
            'success_message' => $result['message'],
            'success_path' => '/' . RouteBlueprint::LOGIN_PATH,
            'error_path' => '/forgot-password'
        ]);
    }

    /**
     * POST: Yeni Şifre Kaydetme (Kurtarma Tamamlama) 🗝️🛡️🔐
     *
     * [A0-6] Üç düzeltme:
     *  1. `token` **zorunlu** alan; kullanıcı kimliği token'dan türetilir
     *     (`reset_user_id` oturum değişkeni kaldırıldı — A-02 ölü yol).
     *  2. `options` dizisi eklendi: `csrf` + `rateLimitEnabled` + **geçerli**
     *     `action` (`password_reset`). Önceki sürüm `options` vermiyordu →
     *     CSRF ve hız sınırı yoktu (YA-2 / team member http.md #74).
     *  3. Boş/geçersiz token → **500 değil**, kullanıcı hatası (kabul ölçütü).
     */
    public function resetPasswordSubmit(): void
    {
        $data = $this->request->form([
            'token' => 'required|string',
            // [A-12] Dagink `min:6` yerine merkezi politika sabiti.
            'password' => 'required|min:' . PasswordValidations::MIN_PASSWORD_LENGTH,
            // [FW-ALTYAPI-2 / B · adım 3 · B1] Parola tekrar alani BEYAZ LISTEDE.
            // Bu alan bir GUVENLIK kontrolunun girdisidir; `rawAll()`'a tasinmaz
            // (ham yol guvenlik kontrolunu baypas ederdi).
            // [FW-ALTYAPI-3 / B · QA §4.3-4] `nullable` KALDIRILDI: `nullable`
            // olmak boş tekrarı kabul ediyordu, yani alan hiç gönderilmeden de
            // şifre değiştirilebiliyordu. Artık `required` → boş/eksik tekrar
            // doğrulamada reddedilir; görünüm (`auth.rbn.php`, `reset-password`
            // bloğu) alanı zaten `required` ile gönderiyor.
            'confirm_password' => 'required|string'
        ], [
            'csrf' => true,
            'rateLimitEnabled' => true,
            'action' => 'password_reset'
        ]);

        // 🔐 Şifre tekrarı: form `confirm_password` gönderiyorsa eşleşmeli
        // [FW-ALTYAPI-3 / B] `$confirm !== ''` kaçışı KALDIRILDI — boş tekrar
        // artık `required` kuralında doğrulamada reddediliyor; burada koşul
        // koşulsuzdur (sıfırma yolu açık değil).
        if (!$this->confirmPasswordMatches($data)) {
            $this->Route->alert(
                'error',
                'Şifreler birbiriyle eşleşmiyor.',
                '/reset-password?token=' . urlencode((string) $data['token'])
            );
            return;
        }

        // 🎼 RBN Framework: Atomic reset via Recovery Orchestrator ✅ (token zorunlu)
        $result = $this->recoveryService->reset((string) $data['token'], (string) $data['password']);

        $this->Route->handleResult($result, [
            'success_path' => '/' . RouteBlueprint::LOGIN_PATH,
            'error_path'   => '/forgot-password'
        ]);
    }

    /**
     * Parola tekrarı (`confirm_password`) parolayla eşleşiyor mu?
     *
     * [FW-ALTYAPI-3 / B · QA §4.3] Kayıt ve sıfırlama akışlarında ortak kullanılır.
     * `hash_equals()` zamanlama sabit karşılaştırma yapar; ayrıca iki alanın
     * uzunluğu farklıysa PHP `hash_equals()` öncesinde reddeder (bilinen davranış).
     *
     * Boş tekrar BURADA "eşleşmiş" sayılmaz: her iki akış da `required` kuralı
     * taşıdığı için boş/eksik tekrar doğrulamada zaten elenir; bu metot ek bir
     * güvenlik katmanıdır.
     *
     * @param array<string,mixed> $data `form()` çıktısı
     */
    private function confirmPasswordMatches(array $data): bool
    {
        $confirm = (string) ($data['confirm_password'] ?? '');
        $password = (string) ($data['password'] ?? '');

        if ($confirm === '' || $password === '') {
            return false;
        }

        return hash_equals($confirm, $password);
    }
}
