<?php

namespace Rbn\Framework\Bundles\RbnSuite\RbnAuth\Controllers;

use Rbn\Framework\Core\Base\Web\BaseController;
use Rbn\Framework\Core\Base\Attributes\Module;
use Rbn\Framework\Core\Http\Engine\RequestEnvironment;
use Rbn\Framework\Bundles\RbnSuite\RbnAuth\Models\ModuleData;

/**
 * AuthController
 * RBN 3.5 Sovereign Gateway Controller. 🏹🛡️⚓
 * 
 * Orchestrates the entry and exit points of the system.
 * Delegated to AuthService for logic and security.
 * 
 * @property \Rbn\Framework\Bundles\RbnSuite\RbnAuth\Services\AuthService $authService
 */
#[Module(name: 'rbnauth', context: 'auth', data: ModuleData::class)]
class AuthController extends BaseController
{
    /**
     * POST: Kimlik Doğrulama Alias'ı 🔑
     */
    public function authenticate(): void
    {
        $this->loginSubmit();
    }

    /**
     * POST: Giriş İşlemi 🚥
     */
    public function loginSubmit(): void
    {
        try {
            $data = $this->request->form([
                'email' => 'required|email',
                'password' => 'required'
            ], [
                'rateLimitEnabled' => true,
                'action' => 'login'
            ]);

            $remember = $this->request->input('remember_me') == '1';

            // 🎼 RBN 3.5 Masterpiece: Orchestrated via AuthService 🎻🏹
            $result = $this->authService->login($data['email'], $data['password'], $remember);

            $this->Route->handleResult($result, [
                'success_message' => $result['message'],
                'success_path' => $result['redirect'] ?? '/',
                'error_path' => 'login'
            ]);
        } catch (\Throwable $e) {
            // [YA-8 · DÜZELTME] İstisna mesajı KULLANICIYA GÖNDERİLMEZ.
            //
            // ÖNCEKİ HALİ: `$this->Route->alert('error', $e->getMessage(), 'login')`
            // → A-01/A-04 canlı kanıtlarında kullanıcıya tam olarak
            // `SQLSTATE[42S22]: Unknown column 'type' in 'where clause'`
            // (şema/tablo/kolon adı) ve dosya yolu/stack trace sızmıştı.
            //
            // KURAL: ayrıntı **yalnız sunucu logunda** (Sınıf: mesaj), kullanıcıya
            // sabit, eyleme uygun mesaj. Giriş yine BAŞARISIZ olur (fail-closed).
            error_log(sprintf(
                'RBN Guvenlik: loginSubmit istisnasi [%s]: %s',
                get_class($e),
                $e->getMessage()
            ));
            $this->Route->alert('error', 'Giriş şu anda gerçekleştirilemiyor. Lütfen tekrar deneyin.', 'login');
        }
    }

    /**
     * POST: Çıkış Yapma (CSRF korumalı, ÖNERİLEN yol) 🔐🚪
     *
     * [R-16 · 2026-10-04 · zeki-6eb7f5] GET `/logout` bir CSRF yüzeyiydi:
     * saldırgan `<img src="https://site/logout">` ile kurbanın oturumunu
     * kapatabiliyordu. ASIL çıkış yolu artık **POST + CSRF token**'dır
     * (`POST /auth/logout`).
     *
     * `rawAll()` çağrısı `csrf => true` ile tüm koruma zincirini çalıştırır
     * (CSRF fail-closed; `FormGuardHandler`). `logOnlySayac => false` yalnız
     * F-10 gözlem sayacını dışlar: çıkış bir "form gönderimi" değil, hız
     * sınırı beklenmeyen bir uçtur — ölçümü kirletmemelidir.
     *
     * CSRF doğrulaması BAŞARISIZ olursa `rawAll()` isteği buraya UĞRAMAZ
     * (kullanıcı hata mesajı + yönlendirme ile döner) → çıkış yapılmaz.
     */
    public function logoutSubmit(): void
    {
        // FW-ALTYAPI-2 / B (adım 2): `form([])` → `rawAll()`. Dönüş değeri
        // kullanılmıyor; amaç YALNIZ kalkan zinciri (CSRF) — `rawAll()` method
        // kontrolü + shield'i `form()` ile birebir aynı çalıştırır.
        $this->request->rawAll([
            'csrf' => true,
            'logOnlySayac' => false,
        ]);

        $this->logout();
    }

    /**
     * Çıkış Yapma İşlemi 🛡️🚪
     *
     * [R-16 · 2026-10-04 · zeki-6eb7f5] GERİYE UYUMLU GET YOLU.
     *
     * - GET `/logout` ve `/cikis` **KALDIRILMAZ** (18 sitenin meşru çıkış
     *   bağlantıları ve duman testi bu yolu kullanıyor).
     * - Her GET kullanımı `security` kanalına **LOG-ONLY** "deprecated" sayacı
     *   yazar (`FormGuardHandler::SAYAC_GET_CIKIS`): hangi bağlantıların hâlâ
     *   GET kullandığı ölçülebilir olur.
     * - **Çapraz-site GET kapatılmaz**: `Sec-Fetch-Site: cross-site` ya da
     *   başka alan adından gelen `Referer` varsa çıkış YAPILMAZ (403).
     *   Başlıkların ikisi de yoksa (curl, PowerShell, eski istemci) çıkış
     *   YAPILIR — aksi hâlde meşru bağlantılar kırılırdı.
     *
     * Aşağıdaki çıkış mantığı (A-14 kimlik eşleşmesi, master token koruması)
     * **AYNEN** korunmuştur.
     */
    public function logout(): void
    {
        // [R-16] Çıkış kapısı: POST değilse (yani GET ise) önce kaynak denetimi.
        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
            // LOG-ONLY: geriye uyumlu yolun kullanım sayacı (gürültü sınırlı).
            try {
                $this->handler('formGuard')->artirLogOnlySayac(
                    \Rbn\Framework\Core\Http\Security\Handlers\FormGuardHandler::SAYAC_GET_CIKIS
                );
            } catch (\Throwable $e) {
                // Sayaç yazılamazsa çıkış yine de çalışır.
            }

            // Tarayıcı bildirimi (Fetch Metadata): 'cross-site' ise BAŞKA SİTE.
            $fetchSite = strtolower(trim((string) ($_SERVER['HTTP_SEC_FETCH_SITE'] ?? '')));
            $caprazSite = ($fetchSite === 'cross-site');

            // Referer yedeği: eski tarayıcılar Fetch Metadata göndermez.
            if (!$caprazSite) {
                $referer = trim((string) ($_SERVER['HTTP_REFERER'] ?? ''));
                if ($referer !== '') {
                    $refererHost = parse_url($referer, PHP_URL_HOST);
                    $refererHost = is_string($refererHost) ? strtolower($refererHost) : '';
                    $istekHost = strtolower(
                        str_contains((string) ($_SERVER['HTTP_HOST'] ?? ''), ':')
                            ? explode(':', (string) $_SERVER['HTTP_HOST'])[0]
                            : (string) ($_SERVER['HTTP_HOST'] ?? '')
                    );
                    // Ayrıştırılamayan Referer güvenli tarafta değerlendirilir.
                    $caprazSite = ($refererHost !== '' && $istekHost !== '' && $refererHost !== $istekHost);
                }
            }

            if ($caprazSite) {
                error_log('RBN Guvenlik: capraz-site GET cikis istegi REDDEDILDI (R-16)');

                if (!headers_sent()) {
                    http_response_code(403);
                    header('Content-Type: text/plain; charset=utf-8');
                }
                echo 'Oturum kapatma isteği reddedildi (güvenlik: çapraz-site bağlantı).';
                return;
            }
        }

        // [A-14 · KİMLİK EŞLEŞMESİ] Çıkış YALNIZ kendi oturumunun kimliğine
        // ait token'ı geçersiz kılar. Kimlik OTURUMDAN (güvenilir) okunur,
        // istek girdisinden DEĞİL.
        $role = $this->storage->sessions()->get('user_role') ?? ($_SESSION['user_role'] ?? 'user');
        $userId = $this->storage->sessions()->get('user_id') ?? ($_SESSION['user_id'] ?? null);
        $isMaster = (bool) ($this->storage->sessions()->get('is_master_developer')
            ?? ($_SESSION['is_master_developer'] ?? false));
        $isAdmin = in_array($role, \Rbn\Framework\Bundles\RbnSuite\RbnAuth\Models\AuthRole::ADMIN_ROLES);

        // 🎼 Clear Remember Token in DB if user is logged out 🔐
        if ($userId) {
            // [A-14 · DÜZELTME] `master.developer` token'ı ARTIK `user_id` ile
            // koşulsuz temizlenmiyor. ÖNCEKİ HALİ: master OLMAYAN bir kullanıcı
            // (ör. `z_users.id = 1`) çıkış yaptığında `rbn_master.developers`
            // tablosunda `id = 1` olan Hükümdar Geliştiricinin remember-me
            // token'ı da siliniyordu → BAŞKA KİMLİĞİN oturumu düşüyordu (DoS).
            // `z_users` ve `developers` AYRI kimlik alanlarıdır; sayısal `id`
            // çakışması tesadüf değildir. Artık master tokenı yalnız oturumun
            // KENDİSİ master geliştirici kimliğiyle açıldıysa temizlenir.
            if ($isMaster) {
                try {
                    $this->model('master.developer')->update((int) $userId, ['dev_token_hash' => null]);
                } catch (\Throwable $e) {
                }
            }
            // Normal kullanıcı tokenı: `user_id` KİLİTİ — başka bir satır
            // hedeflenemez (kendi oturumunun kimliği).
            try {
                $this->model('userSecurity')->where('user_id', (int) $userId)->update(['remember_token' => null]);
            } catch (\Throwable $e) {
            }
        }

        // 🎼 Secure termination via AuthService 🗝️🛡️
        $this->authService->logout();
        $this->service('sidebar')->clearSidebarCache();

        // 🔔 Çıkış Başarılı Toast Bildirimi (RbnAlert Flash)
        $alertPayload = json_encode([
            'type' => 'info',
            'message' => 'Güle Güle!',
            'message2' => 'Oturumunuz güvenli bir şekilde sonlandırıldı.',
            'display' => 'toast',
        ], JSON_UNESCAPED_UNICODE);

        // [http #11] Aynı bayraklar, aynı karar kaynağı (yerel kopya kaldırıldı).
        setcookie('rbn_alert', $alertPayload, [
            'expires'  => time() + 60,
            'path'     => '/',
            'domain'   => '',
            'secure'   => RequestEnvironment::isHttpsRequest(),
            'httponly' => false, // rbnAlert.js document.cookie ile okuyor
            'samesite' => 'Lax'
        ]);

        if ($isAdmin) {
            // Admin Panel Logout: Redirects to Admin Login Page
            $this->Route->redirect('login');
        } else {
            // User Panel Logout: Redirects to Frontend Home Page (/)
            response()->redirect('/');
        }
    }
}
