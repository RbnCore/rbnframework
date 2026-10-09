<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Services\Hosting\Providers;

use Rbn\Framework\Core\Base\BaseComponent;
use Rbn\Framework\Core\Services\Hosting\Data\CpanelData;
use Rbn\Framework\Core\System\Config\Secrets;

/**
 * CPanelProvider - Core Data & Execution Provider for cPanel 🏗️🛰️⚓
 * 
 * RBN Framework Standard.
 * Handles API communication bridge.
 */
class CPanelProvider extends BaseComponent
{
    private ?string $host  = null;
    private ?string $user  = null;
    private ?string $token = null;

    /**
     * Initialize connection settings 🧬🗝️
     *
     * [GÜVENLİK / B-1] Kimlik bilgileri artık kaynak kodda sabit DEĞİLDİR; `Secrets`
     * okuyucusundan **tembel (lazy)** gelir. Bu yüzden `afterBoot()` HİÇBİR sırı
     * okumaz: sır dosyası olmayan siteler/komutlar bu provider'ı boot edebilir,
     * çünkü cPanel işlevi çağrılana kadar dosya zorunlu değildir.
     * Değerler ilk gerçek kullanımda (aşağıdaki `credentials()`) çözülür.
     */
    protected function afterBoot(): void
    {
        // Sır YOK: tembel yükleme bilinçli olarak burada yapılmaz.
    }

    /**
     * cPanel kimlik bilgilerini tembel çözer (fail-closed).
     *
     * treturn array{host:string,user:string,token:string}
     * @throws \RuntimeException `secrets.php` `cpanel` bölümü yoksa / eksikse (sessiz fallback YOK).
     */
    private function credentials(): array
    {
        if ($this->host !== null && $this->user !== null && $this->token !== null) {
            return ['host' => $this->host, 'user' => $this->user, 'token' => $this->token];
        }

        // Fail-closed: dosya yoksa/eksikse burada RuntimeException fırlatılır.
        $c = Secrets::section('cpanel');

        $this->host  = $c['host'];
        $this->user  = $c['user'];
        $this->token = $c['token'];

        return $c;
    }

    /**
     * cPanel sunucu adı (domain; port YOK). Mail/SSO adresleri bu değerle kurulur.
     *
     * @throws \RuntimeException `secrets.php` `cpanel` bölümü yoksa / eksikse (fail-closed).
     */
    public function host(): string
    {
        return $this->credentials()['host'];
    }

    /**
     * Checks if credentials are valid 🛡️
     */
    public function isConfigured(): bool
    {
        return !empty($this->host) && !str_contains($this->host, 'your-cpanel-host');
    }

    /**
     * Core cPanel UAPI Call Execution 🛰️🗝️⚓
     */
    public function call(string $module, string $function, array $params = []): array
    {
        // [B-1] Fail-closed: kimlik bilgileri sır dosyasından çözülür. Dosya yoksa/
        // eksikse RuntimeException fırlatılır — sessiz "yapılandırılmamış" dönüşü YOK.
        $credentials = $this->credentials();

        if (!$this->isConfigured()) {
            return ['status' => 0, 'errors' => ['cPanel configuration is missing or invalid.']];
        }

        $url = "https://{$credentials['host']}:2083/execute/{$module}/{$function}";

        $response = $this->remote->request('GET', $url, $params, [], false, [
            'connect_timeout' => 5,
            'timeout' => 15,
            'curl' => [
                CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
                CURLOPT_USERPWD => "{$credentials['user']}:{$credentials['token']}",
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false
            ]
        ]);

        if (($response['status'] ?? 'error') === 'error' || ($response['status'] ?? '') === 'remote_error') {
            return ['status' => 0, 'errors' => [$response['message'] ?? 'cPanel Connection Error']];
        }

        return $response['data'] ?? [];
    }

    /**
     * Generate SSO auto-login session URL 🔗⚡
     */
    public function getSsoUrl(string $target, ?string $email = null): array
    {
        $target = strtolower($target);
        if (!isset(CpanelData::DESTINATIONS[$target])) {
            return [
                'success' => false,
                'message' => "Geçersiz hedef: '{$target}'"
            ];
        }

        $dest = CpanelData::DESTINATIONS[$target];

        if ($target === 'webmail') {
            if (!$email) {
                return [
                    'success' => false,
                    'message' => 'Webmail oturumu için e-posta adresi gereklidir.'
                ];
            }
            
            $sessionResult = $this->service('cpanel')->mail()->getWebmailSession($email);
            if ($sessionResult['success'] ?? false) {
                // Compile the final webmail login URL
                $url = rtrim($sessionResult['url'], '/') . '/?' . http_build_query([
                    'session'  => $sessionResult['session'],
                    'goto_uri' => $dest['path']
                ]);
                return [
                    'success' => true,
                    'url'     => $url
                ];
            }
            return $sessionResult;
        }

        // [B-1] Fail-closed: sır dosyası yoksa burada açık hata fırlatılır.
        $credentials = $this->credentials();

        $user = $credentials['user'];
        $pass = $credentials['token'];

        $url = "https://{$credentials['host']}:{$dest['port']}/login/?" . http_build_query([
            'user' => $user,
            'pass' => $pass,
            'goto_uri' => $dest['path']
        ]);

        return [
            'success' => true,
            'url' => $url
        ];
    }

    /**
     * Unified redirection gateway for all cPanel destinations 🔗⚡
     * Handles JSON session URL retrieval and HTML loading screen rendering.
     */
    public function redirect(string $target, ?string $email = null): void
    {
        // Release session lock early to prevent blocking concurrent requests
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $target = strtolower($target);
        if (!isset(CpanelData::DESTINATIONS[$target])) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => "Geçersiz hedef: '{$target}'"
            ]);
            exit;
        }

        $dest = CpanelData::DESTINATIONS[$target];

        // 1. If requesting the SSO session via AJAX
        if ($this->request->input('get_session')) {
            $ssoResult = $this->getSsoUrl($target, $email);
            header('Content-Type: application/json; charset=utf-8');
            if ($ssoResult['success'] ?? false) {
                echo json_encode([
                    'success' => true,
                    'url'     => $ssoResult['url']
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => $ssoResult['message'] ?? 'Yönlendirme adresi oluşturulamadı.'
                ]);
            }
            exit;
        }

        // 2. Otherwise render the HTML loading/redirect page using dynamic configuration texts
        $title       = $dest['title'] ?? 'Yönlendiriliyor...';
        $message     = $dest['message'] ?? 'Bağlantı Kuruluyor';
        $subMessage  = $dest['sub_message'] ?? 'Lütfen bekleyin, güvenli bağlantı kuruluyor...';
        $redirectUrl = null;
        $delay       = 0;

        $viewPath = \Rbn\Framework\Core\System\Paths\Paths::frameworkRoot() . '/Resources/Views/System/redirect_loading.rbn.php';
        if (file_exists($viewPath)) {
            require $viewPath;
            exit;
        }

        // Ultimate fallback
        $ssoResult = $this->getSsoUrl($target, $email);
        if ($ssoResult['success'] ?? false) {
            header('Location: ' . $ssoResult['url']);
        } else {
            echo "Error: " . ($ssoResult['message'] ?? 'Redirection failed.');
        }
        exit;
    }
}
