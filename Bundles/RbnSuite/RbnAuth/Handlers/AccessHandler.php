<?php

namespace Rbn\Framework\Bundles\RbnSuite\RbnAuth\Handlers;

use Rbn\Framework\Core\Base\BaseComponent;
use Rbn\Framework\Bundles\RbnSuite\RbnAuth\Models\AuthRole;

/**
 * AccessHandler - The Consolidated Entry/Exit & Authorization Specialist 🏹🛡️⚓
 * RBN Framework Standard.
 * 
 * Orchestrates authentication, termination, and access level authorization (ACL).
 * 
 * @property \Rbn\Framework\Core\Database\Repositories\Project\UserRepository $projectUserRepository
 * @property \Rbn\Framework\Core\Database\Repositories\Project\UserSecurityRepository $projectUserSecurityRepository
 */
class AccessHandler extends BaseComponent
{
    /**
     * Var olmayan/pasif hesap dallarında harcanan sahte doğrulamanın hash'i
     * (A-08). Rastgele bir dizgenin bcrypt özeti; gerçek hiçbir hesaba ait değil.
     * Maliyet `CryptoHelper::hash()` (PASSWORD_BCRYPT varsayılanı) ile aynı olmalı.
     */
    private const DUMMY_HASH = '$2y$10$wgOK9eDgc5A5mIOPjC069Oof/umWAiPsfyezoYlan7CcAEW7Gf2L2';

    /**
     * Kullanıcı numaralandırmasını zamanlama yoluyla kapatır (A-08): hesap
     * bulunamasa da parola doğrulama maliyeti kadar süre harcanır.
     */
    private function equalizeVerifyTiming(string $password): void
    {
        $this->helper('crypto')->verify($password, self::DUMMY_HASH);
    }

    /**
     * Executes the primitive authentication check 🚀
     * Master Authentication Logic 🔑🛰️⚓
     * RBN Framework: Now with integrated Rate-Limit and Audit layers.
     */
    public function authenticate(string $identity, string $password): array
    {
        $ip = $this->request->ip();

        // 🎼 Step 1: Pre-check Security Blocks (IP Defense) 🛡️🚥
        $block = $this->repository('project.userSecurity')->isIpBlocked($ip);
        if ($block) {
            $this->handler('audit')->logSecurityBlock($ip, "Bloklanmış IP erişimi denemesi. Neden: " . ($block['reason'] ?? 'Tanımsız'));
            return [
                'success' => false,
                'message' => 'IP adresiniz güvenlik politikası nedeniyle engellenmiştir.'
            ];
        }

        // 🎼 Step 1b: [A-19] GLOBAL (master) ban listesi GÖZLEMLENİR — ÜSTÜNE BASILMAZ 🚦👁️
        //
        // İDDİA: giriş banı yalnız `common.ipBlock` okuduğu için global liste
        // giriş akışında hiç dikkate alınmıyordu. Doğru; ancak kör nokta
        // kapatılırken ÖLÇÜM ZORUNLUDUR (brif kısıt 2) ve ölçüm kararı
        // değiştirdi:
        //
        //   rbn_master.ip_blocks (SALT-OKU SELECT): TOPLAM 522, **AKTİF 505**,
        //   ve AKTİF 505 satırın TAMAMI 127.0.0.1 (project_id=0, yani global),
        //   blocked_until 2026-10-03 03:01 → 2026-10-04 02:54. Yani yerel IP
        //   ŞU AN global listede. `database_common.ip_blocks` tablosu yok.
        //
        // → Bu kayıtlar giriş akışına BAĞLANSAYDI 127.0.0.1'den gelen TÜM
        //   yerel site girişleri (duman `8-panel-giris` dahil) anında kapanırdı.
        //   Bu yüzden master listesi burada YALNIZ GÖZLEMLENİR (would-block):
        //   kayıt varsa audit'a yazılır, giriş AKIŞI DEVAM EDER.
        //   Ban kayıtlarının temizliği AYRI karardır (bkz. rapor).
        //
        // NOT: Genel katmanda (`Core/Services/Gatekeepers/Handlers/IpGuardHandler`)
        // master listesi ZATEN `shield_ip_guard_mode` (log_only | enforce)
        // anahtarıyla ele alınıyor; buradaki gözlem, giriş akışına özel iz
        // bırakır ve o modun yerine geçmez.
        $masterBlock = $this->observeMasterIpBan($ip);
        if ($masterBlock) {
            $this->handler('audit')->logSecurityBlock(
                $ip,
                "Global (master) ban listesinde AKTIF kayit — GIRIŞ ENGELLENMEDI (would-block). Neden: "
                . (string) $this->satirDegeri($masterBlock, 'reason')
            );
        }

        // 🎼 Step 2: Identity Discovery (Local) 🧬
        $user = $this->repository('project.user')->findByIdentity($identity);

        if (!$user) {
            $this->equalizeVerifyTiming($password);
            $this->handler('audit')->logFailure($identity, 'Kullanıcı bulunamadı.');
            return [
                'success' => false,
                'message' => 'E-posta veya şifre hatalı.'
            ];
        }

        // 🎼 Step 3: Global Master Developer Check (Priority Bypass) 👨‍💻🌍🏹
        // Handled by Service Orchestrator, but we check Local status here.
        if (!$this->repository('project.user')->isActive((int) $user['id'])) {
            $this->equalizeVerifyTiming($password);
            $this->handler('audit')->logFailure($identity, 'Pasif hesap erişim denemesi.');
            return [
                'success' => false,
                'message' => 'Hesabınız aktif değildir.'
            ];
        }

        // 🎼 Step 4: Password Verification 🗝️🛡️
        if (!$this->helper('crypto')->verify($password, $user['password_hash'] ?? '')) {

            // 🛡️ [HİT RECORDING]: Trigger Autonomous Rate-Limit counter 🚥✅
            $this->service('ipGuard')->recordHit($ip, 'login');

            $this->handler('audit')->logFailure($identity, 'Hatalı şifre denemesi.');
            return [
                'success' => false,
                'message' => 'E-posta veya şifre hatalı.'
            ];
        }

        // A-17: eski özet sessizce güncellenir (başarısızlık girişi etkilemez)
        $this->upgradePasswordHash((int) $user['id'], $password, (string) ($user['password_hash'] ?? ''));

        // 🎼 Step 5: Successful Entry 🏰🗝️⚓
        $this->handler('audit')->logSuccess((int) $user['id'], $identity);

        return [
            'success' => true,
            'user' => $user
        ];
    }

    /**
     * [A-19] GLOBAL (master) ban listesi gözlemi — SADECE OKUMA 👁️
     *
     * `rbn_master.ip_blocks` üzerinde bu IP için SÜRESİ DOLMAMIŞ kayıt arar
     * (global `project_id = 0` veya bu projeye ait kayıtlar; `IpGuardHandler`
     * ile aynı kapsam). Kayıt varsa döner, yoksa `null`.
     *
     * ⚠️ FAIL-OPEN NEDEN? Çünkü bu bir ENGELLEME değil, GÖZLEMDİR: master
     * bağlantısı/tablosu yoksa veya sorgu hata verirse `null` döner ve giriş
     * akışı DEĞİŞMEZ. Burada fail-closed uygulamak (hata durumunda girişi
     * kapatmak) bir gözlem katmanını üretimde tamamen kapatırdı; engelleme
     * kararı zaten `Step 1` (yerel liste) ve genel katmandaki
     * `shield_ip_guard_mode` anahtarına aittir.
     */
    private function observeMasterIpBan(string $ip): mixed
    {
        try {
            $projectId = function_exists('project_id') ? (int) project_id() : 0;

            return $this->model('master.ipBlock')
                ->where('ip_address', '=', $ip)
                ->where('blocked_until', '>', now())
                ->where(function ($q) use ($projectId) {
                    $q->where('project_id', '=', 0)      // Global
                        ->orWhere('project_id', '=', $projectId); // Bu proje
                })
                ->first();
        } catch (\Throwable $e) {
            return null; // gözlem yapılamadı -> giriş akışı olduğu gibi devam eder
        }
    }

    /**
     * Satır dizisi ya da model nesnesi olabilir; tek alan okur.
     *
     * @param mixed $satir
     */
    private function satirDegeri($satir, string $alan): mixed
    {
        if (is_array($satir)) {
            return $satir[$alan] ?? null;
        }

        if (is_object($satir)) {
            return $satir->{$alan} ?? null;
        }

        return null;
    }

    /**
     * Executes the access/role permission check (Supports single role or array of roles) ⚔️🛡️
     * 
     * @param string|array $requiredRole
     */
    public function can(string|array $requiredRole): bool
    {
        $session = $this->session();
        $isLoggedIn = (bool) $session->get('is_logged_in', false);

        if (!$isLoggedIn) {
            return false;
        }

        if ((bool) $session->get('is_master_developer', false)) {
            return true; // Hükümdar Geliştirici her zaman tam yetkilidir
        }

        $roles = AuthRole::ROLES;
        $userRole = (string) $session->get('user_role', 'user');
        $currentLevel = $roles[$userRole]['level'] ?? 0;

        // 🎼 Multiple Roles Array Support (e.g. ['developer', 'admin']) 🎭
        if (is_array($requiredRole)) {
            foreach ($requiredRole as $role) {
                if ($userRole === $role) {
                    return true;
                }
                $requiredLevel = $roles[$role]['level'] ?? 0;
                if ($currentLevel >= $requiredLevel) {
                    return true;
                }
            }
            return false;
        }

        // Single Role Level Check
        $requiredLevel = $roles[$requiredRole]['level'] ?? 0;
        return $currentLevel >= $requiredLevel;
    }

    /**
     * Executes the secure session termination 🚪
     */
    public function terminate(): bool
    {
        $session = $this->session();

        // 🎼 Clear specific auth keys 🏛️
        $session->delete('user_id');
        $session->delete('user_role');
        $session->delete('is_logged_in');
        $session->delete('is_master_developer');
        $session->delete('is_locked');
        $session->delete('auth_fingerprint');

        // 🎼 Purge, unlink session file and clear $_SESSION via Sandbox 🚪🧹
        $session->end();

        // 🎼 Forget Me: Remove the rbn_remember cookie 🛡️
        if (isset($_COOKIE['rbn_remember'])) {
            $this->forgetCookie('rbn_remember');
        }
        if (isset($_COOKIE[session_name()])) {
            $this->forgetCookie(session_name());
        }

        return true;
    }

    /**
     * A-11: Silme çerezi, oluşturulurken kullanılan bayraklarla (secure/httponly/samesite=Lax) gönderilir;
     * bayraksız silme bazı tarayıcılarda eski çerezi bırakır (bkz. SessionHandler::createRememberMe).
     */
    private function forgetCookie(string $name): void
    {
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

        setcookie($name, '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        unset($_COOKIE[$name]);
    }

    /**
     * A-17: Başarılı girişte özet eskiyse (algoritma/maliyet) parola sessizce yeniden özetlenir.
     * Şema değişmez (kasa sütunu 255 karakter). Hata girişi ASLA bozmaz.
     */
    private function upgradePasswordHash(int $userId, string $password, string $currentHash): void
    {
        try {
            $crypto = $this->helper('crypto');
            if ($currentHash === '' || !$crypto->needsRehash($currentHash)) {
                return;
            }
            $secRepo = $this->repository('project.userSecurity');
            if ($secRepo) {
                $secRepo->saveSecurityData($userId, [
                    'password_hash' => $crypto->hash($password),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }
        } catch (\Throwable $e) {
            // yeniden hash opsiyonel: sessizce geç, giriş sürer
        }
    }
}
