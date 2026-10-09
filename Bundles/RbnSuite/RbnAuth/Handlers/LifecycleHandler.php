<?php

declare(strict_types=1);

namespace Rbn\Framework\Bundles\RbnSuite\RbnAuth\Handlers;

use Rbn\Framework\Core\Base\BaseComponent;

/**
 * LifecycleHandler - Unified User Identity Lifecycle Worker 🧬🏹✅
 * RBN Framework Standard.
 * 
 * Sorumluluk: Kullanıcı kaydı (Registration), e-posta doğrulama (Verification)
 * ve şifre sıfırlama (Recovery) gibi tüm yaşam döngüsü işlerini tek merkezde yürütür.
 * 
 * @property \Rbn\Framework\Core\Database\Repositories\Project\UserRepository $userRepository
 * @property \Rbn\Framework\Core\Database\Repositories\Project\UserSecurityRepository $userSecurityRepository
 * @property \Rbn\Framework\Bundles\RbnSuite\RbnAuth\Handlers\CredentialHandler $credentialHandler
 */
class LifecycleHandler extends BaseComponent
{
    /** `z_users.name` kolon uzunlugu (olculdü: SHOW COLUMNS -> varchar(200)). */
    public const NAME_MAX_LENGTH = 200;

    /** `z_users.username` kolon uzunlugu (olculdu: varchar(100)). */
    public const USERNAME_MAX_LENGTH = 100;

    /**
     * Yeni kullanıcı icin `name` / `username` degerlerini turetir (YA-10).
     *
     * [YA-10 · DÜZELTME] ÖNCEKİ HALİ:
     *   'name'     => $data['username'] ?? explode('@', $data['email'])[0],
     *   'username' => $data['username'] ?? $data['email'],
     * Iki sorun: (a) e-posta yerel parçası 100+ karakter oldugunda
     * `z_users.username` (varchar 100) ve `z_users.name` (varchar 200) ASILIR,
     * (b) `explode()` sonucu oldugu gibi yaziliyordu.
     *
     * KURAL: once istenen deger, yoksa e-posta yerel parcası; her ikisi de
     * kolon sinirlarina **kirpilir** (cok baytli karakterde mb_* kullanilir).
     * Metot saf fonksiyondur (DB/oturum yok) -> birim testlenebilir.
     *
     * @return array{name:string,username:string}
     */
    public static function deriveNameAndUsername(array $data): array
    {
        $email = trim((string) ($data['email'] ?? ''));
        $username = trim((string) ($data['username'] ?? ''));
        $local = $email !== '' ? (explode('@', $email, 2)[0] ?? '') : '';

        $name = $username !== '' ? $username : $local;
        $name = trim($name) !== '' ? $name : 'user';

        // Kullanici adi verilmediyse e-posta adresinin tamami kullanilir
        // (proje davranisi degistirilmedi), ama kolon sinirina kirpilir.
        $finalUsername = $username !== '' ? $username : $email;
        if (trim($finalUsername) === '') {
            $finalUsername = 'user';
        }

        return [
            'name'     => mb_substr($name, 0, self::NAME_MAX_LENGTH),
            'username' => mb_substr($finalUsername, 0, self::USERNAME_MAX_LENGTH),
        ];
    }

    /**
     * Yeni kullanıcı kimliği oluşturur (Registration) 🆕🧬
     */
    public function createIdentity(array $data): array
    {
        $credential = $this->credentialHandler ?? $this->handler('credential');

        // 🎼 Step 1: Identity Validation (Email) 📧🛡️
        if ($credential) {
            $emailCheck = $credential->validateEmail($data['email'] ?? '');
            if ($emailCheck !== true) {
                return ['success' => false, 'error' => $emailCheck['error'] ?? 'invalid_email', 'message' => $emailCheck['message'] ?? 'Geçersiz e-posta.'];
            }

            // 🎼 Step 2: Password Complexity Validation 🔐🛡️
            $passCheck = $credential->validatePassword($data['password'] ?? '');
            if ($passCheck !== true) {
                return ['success' => false, 'error' => $passCheck['error'] ?? 'weak_password', 'message' => $passCheck['message'] ?? 'Zayıf şifre.'];
            }
        }

        $userRepo = $this->repository('project.user');
        $userModel = $this->model('project.user');

        // 🎼 Step 3: Pre-flight uniqueness check
        // Kayıtlı adres dalı da yeni kayıttaki parola özetini hesaplar (sabit
        // maliyet); süre farkı hesap varlığını sızdırmaz. Taban: RecoveryService.
        if ($userRepo && $userRepo->findByIdentity($data['email'] ?? '')) {
            $this->helper('crypto')->hash($data['password'] ?? '');

            return ['success' => false, 'error' => 'email_exists', 'message' => 'Bu e-posta adresi zaten kullanılıyor.'];
        }

        // 🎼 Step 4: Password Score & Hashing
        $score = $credential ? $credential->calculateScore($data['password'] ?? '') : 50;
        $hashedPassword = $this->helper('crypto')->hash($data['password'] ?? '');

        // 🎼 Step 5: Create User in Project DB
        // [A0-6] `password_hash` buraya YAZILMAZ. Ölçülen şema kanıtı: `z_users`
        // kolonları id, name, email, username, email_verified, password_score,
        // is_active, role, is_online, last_activity, created_at, updated_at —
        // `password_hash` YOK. Parola kasası `z_users_security.password_hash`
        // sütunudur (giriş `UserRepository::findByIdentity` JOIN'i ile oradan okur).
        // Önceki kod buraya yazınca `Unknown column 'password_hash'` 500'ü veriyordu.
        // [YA-10] `name`/`username` tek noktadan, kolon sinirlarina kirpilmis
        // degerlerle gelir (eski hali: `explode('@',$email)[0]` oldugu gibi).
        $kimlik = self::deriveNameAndUsername($data);

        $userId = $userModel ? $userModel->insert([
            'name' => $kimlik['name'],
            'email' => $data['email'],
            'username' => $kimlik['username'],
            'email_verified' => 0,
            'password_score' => $score,
            'role' => 'user',
            'is_active' => 2, // 2 = Pending (Awaiting email confirmation) ⏳
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]) : false;

        if (!$userId) {
            return ['success' => false, 'error' => 'creation_failed', 'message' => 'Kullanıcı kaydı oluşturulamadı.'];
        }

        // 🎼 Step 6: Initialize Security Vault (z_users_security)
        $secRepo = $this->repository('project.userSecurity');
        if ($secRepo) {
            $secRepo->saveSecurityData((int) $userId, [
                'password_hash' => $hashedPassword,
                'last_ip' => $this->request->ip(),
            ]);
        }

        return [
            'success' => true,
            'user' => [
                'id' => $userId,
                'email' => $data['email'],
                'username' => $kimlik['username']
            ]
        ];
    }

    /**
     * E-posta ve kullanıcı kimliğini doğrular (Verification) ✅
     *
     * [A0-6] İki düzeltme:
     *  1. `z_users_security.type` kolonu **yok** (ölçüldü: SHOW COLUMNS, 7 şema).
     *     Eski kod `where('type','email_verification')->delete()` çağırınca
     *     `SQLSTATE[42S22] Unknown column 'type' in 'where clause'` → 500 (A-04).
     *     Artık `user_id` üzerinden tek kolon (`verification_token`) temizlenir.
     *  2. Metot artık **gerçekte güncelleme yapmışsa** `true` döner; kullanıcı
     *     modeli yoksa sessizce "doğrulandı" denmez.
     *
     * @param int $userId Token satırından türetilmiş kullanıcı (istemekten GELMEZ)
     */
    public function confirmIdentity(int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }

        $userModel = $this->model('project.user');
        if (!$userModel) {
            return false;
        }

        $updated = (bool) $userModel->where('id', $userId)->update([
            'is_active' => 1,
            'email_verified' => 1,
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        if (!$updated) {
            return false;
        }

        // Eski tek kolonlu kasadan da temizle (geri dönüş/kalıntı için zararsız)
        $secModel = $this->model('project.userSecurity');
        if ($secModel) {
            $secModel->where('user_id', $userId)
                ->where('verification_token', '!=', '')
                ->update(['verification_token' => null, 'updated_at' => date('Y-m-d H:i:s')]);
        }

        return true;
    }

    /**
     * Şifre sıfırlama işlemini tamamlar (Password Reset) 🔑
     *
     * [A0-6] Üç düzeltme:
     *  1. Parola özeti `z_users.password_hash`'e değil **`z_users_security.password_hash`**
     *     sütununa yazılır (o kolon `z_users`'ta yok — ölçülen şema kanıtı).
     *  2. `z_users_security.type` kolonu yok → temizlik `reset_token` üzerinden.
     *  3. [A0-6 görev kalemi / rbnauth.md A-18] Parola değişince o kullanıcının
     *     **remember-me token'ı düşürülür**; oturum yenileme (A0-4) ayrı kalemdir
     *     ve burada dokunulmaz.
     *
     * @param int $userId Token satırından türetilmiş kullanıcı (istemekten GELMEZ)
     */
    public function completeReset(int $userId, string $newPassword): bool
    {
        if ($userId <= 0) {
            return false;
        }

        $hashedPassword = $this->helper('crypto')->hash($newPassword);
        $now = date('Y-m-d H:i:s');

        // 🔐 1) Parola kasası (z_users_security) — giriş buradan okuyor
        $secRepo = $this->repository('project.userSecurity');
        if (!$secRepo) {
            return false;
        }

        $saved = $secRepo->saveSecurityData($userId, [
            'password_hash' => $hashedPassword,
            // ♻️ 2) Parola değişti → mevcut "beni hatırla" geçersiz
            'remember_token' => null,
            'reset_token' => null,
            'updated_at' => $now,
        ]);

        if (!$saved) {
            return false;
        }

        // 🧹 3) Süresi dolmuş/iptal edilmiş token satırlarını şişmesin diye temizle
        $tokenRepo = $this->repository('project.userToken');
        if ($tokenRepo) {
            $tokenRepo->revokePending($userId, 'password_recovery');
            $tokenRepo->revokePending($userId, 'email_verification');
        }

        return true;
    }
}
