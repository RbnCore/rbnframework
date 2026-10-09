<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\System\Storage\Providers;

use Rbn\Framework\Core\System\Storage\Base\BaseStorageProvider;
use Rbn\Framework\Core\System\Paths\Paths;
use SessionHandlerInterface;
use SessionUpdateTimestampHandlerInterface;

/**
 * SessionProvider - Framework Oturum Birimi 🔑
 * 
 * PHP session dosyalarını (sess_*) güvenli ve şifreli bir şekilde yönetir.
 * RBN Framework: Çift şapkalı mimari (Native SessionHandlerInterface + Yüksek Seviye Key-Value API).
 */
class SessionProvider extends BaseStorageProvider implements SessionHandlerInterface, SessionUpdateTimestampHandlerInterface
{
    protected string $storageName = 'sessions';
    protected bool $encrypted = false;
    protected string $format = 'raw';
    protected string $prefix = 'sess_';

    protected function getStorageDir(): string
    {
        return Paths::project()->sessions();
    }

    /**
     * RBN Framework: [RBN Framework SESSION IDENTITY] 🏙️🛰️⚓
     * Projenin otonom kimliğini baz alarak benzersiz bir session cookie ismi üretir.
     */
    public function getName(): string
    {
        $projectKey = function_exists('project_key') && project_key() ? project_key() : 'rbn';
        $workspaceHash = substr(hash('sha256', Paths::workspace()), 0, 8);
        return 'RBN_' . strtoupper($projectKey) . '_' . strtoupper($workspaceHash) . '_SESS';
    }

    /* ==========================================================================
       [ NATIVE SESSION HANDLER METHODS ] - PHP Core Compatibility 🛠️
       ========================================================================== */

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string|false
    {
        $path = $this->resolvePath($this->prefix . $id);
        $data = $this->driver->read($path, $this->format, $this->encrypted);
        return is_string($data) ? $data : '';
    }

    public function write(string $id, string $data): bool
    {
        $path = $this->resolvePath($this->prefix . $id);
        return $this->driver->write($path, $data, $this->format, false, $this->encrypted);
    }

    /**
     * [A0-4 · DÜZELTME] Oturum dosyasını siler — **idempotent**.
     *
     * NEDEN? PHP'nin `session_regenerate_id(true)` çağrısı, özel save handler'ın
     * `destroy()` metodu `false` döndürürse **uyarı üretip yenilemeyi tamamen
     * reddediyor**:
     *   "session_regenerate_id(): Session object destruction failed. ID: ..."
     * Ölçüldü: kullanıcı ilk kez giriş yaptığında (henüz `sess_<id>` dosyası
     * YOK) `destroy()` `is_file()` kontrolünden geçemediği için `false`
     * dönüyordu → oturum sabitleme koruması **ilk girişte hiç çalışmıyor**,
     * ikinci girişte çalışıyordu (o an dosya zaten vardı).
     *
     * `SessionHandlerInterface::destroy()` sözleşmesi zaten "silme başarılı ya
     * da dosya yok" anlamına gelir; **VAR OLMAYAN dosya başarıdır** (aynı
     * hedefe iki kez gitmek idempotent olmalıdır).
     */
    public function destroy(string $id): bool
    {
        $path = $this->resolvePath($this->prefix . $id);

        if (!is_file($path)) {
            // Zaten silinmiş: başarı sayılır (yoksa regenerate_id iptal edilir).
            return true;
        }

        if (@unlink($path)) {
            return true;
        }

        // Dosya silinemiyse (izin/kilit): "bulunamadı" say ama YANILTMA.
        // PHP yine de uyarı üretmeye devam eder; fail-closed tarafta
        // oturum düşürme kararı çağırana bırakılır.
        return !is_file($path);
    }

    /**
     * `session.use_strict_mode` icin kimlik denetimi: istemcinin gonderdigi ID
     * YALNIZ sunucuda bu ID ile bir oturum dosyasi varsa kabul edilir. Yoksa
     * PHP yeni bir ID uretir (istemcinin sectigi ID kullanilmaz).
     *
     * Bu arayuz uygulanmadan ozel save handler'da strict mode etkisizdi.
     */
    public function validateId(string $id): bool
    {
        if (preg_match('/^[a-zA-Z0-9,\-]{22,256}$/', $id) !== 1) {
            return false;
        }

        return is_file($this->resolvePath($this->prefix . $id));
    }

    /**
     * Veri degismediginde (`session.lazy_write`) dosya zamani tazelenir; GC
     * etkin oturumu silmez.
     */
    public function updateTimestamp(string $id, string $data): bool
    {
        $path = $this->resolvePath($this->prefix . $id);
        if (is_file($path)) {
            return @touch($path);
        }

        return $this->write($id, $data);
    }

    public function gc(int $max_lifetime): int|false
    {
        $dir = $this->getStorageDir();
        $count = 0;
        // 🧹 Timezone & Early Expiry Guard: Keep sessions for at least 24 hours (86400 seconds)
        $safetyLifetime = max($max_lifetime, 86400);

        // [FW-ALTYAPI-3 / B · A-12 / S-04] `glob()` + `filemtime()` TÜM oturum
        // dosyalarını belleğe alıyordu. `FilesystemIterator` tek geçişte gezinir:
        // dizin listesi bellekte tutulmaz ve alt dizinlere (`SKIP_DOTS` yalnızca
        // . / ..'u atlar) inilmez.
        //
        // DAVRANIŞ DEĞİŞMEDİ: eskiden `glob($dir.'/*')` + `is_file()` filtresi
        // uygulanıyordu; burada da yalnızca GERÇEK dosyalar işlenir, üstelik
        // `.`/`..` için `is_file()` zaten false dönüyordu. Kırıcı etki yok.
        //
        // Ölçüm: `glob()` her çağrıda dizini tarayıp tam dizi döndürür; oturum
        // sayısı büyüdükçe GC'nin bellek ve syscall maliyeti O(n) yerine
        // `FilesystemIterator` ile de O(n) kalır ama TEK geçişte, dizi tahsisi
        // olmadan yapılır. Davranışsal fark yoktur, kazanç büyük dizinlerde
        // belirginleşir.
        try {
            $ite = new \FilesystemIterator($dir, \FilesystemIterator::SKIP_DOTS);
        } catch (\UnexpectedValueException $e) {
            // Dizin yoksa/erişilemiyorsa eski `glob()` da boş dizi dönerdi:
            // "silinen 0 oturum" ile aynı sonuç, istisna YAYILMAZ.
            return 0;
        }

        foreach ($ite as $file) {
            // Dizinler/dizin bağlantıları atlanır (eski `is_file()` filtresiyle aynı).
            if (!$file->isFile()) {
                continue;
            }
            if ($file->getMTime() + $safetyLifetime < time()) {
                if (@unlink($file->getPathname())) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /* ==========================================================================
       [ DEVELOPER KEY-VALUE & LIFECYCLE API ] - RBN Application Layer 🧠
       ========================================================================== */

    /**
     * Oturumun aktif ve açık olduğundan emin olur 🛡️
     */
    protected function ensureStarted(): void
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }
    }

    /**
     * Session Verisi Oku ($_SESSION ile Entegre) 🔑
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $this->ensureStarted();
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Session Verisi Var mı? 🔍
     */
    public function has(string $key): bool
    {
        $this->ensureStarted();
        return isset($_SESSION[$key]);
    }

    /**
     * Session Verisi Yaz ($_SESSION ile Entegre) 📝
     */
    public function set(string $key, mixed $value, array $options = []): bool
    {
        $this->ensureStarted();
        $_SESSION[$key] = $value;
        return true;
    }

    /**
     * Session Verisi Sil ($_SESSION ile Entegre) 🗑️
     */
    public function delete(string $key): bool
    {
        $this->ensureStarted();
        unset($_SESSION[$key]);
        return true;
    }

    /**
     * Aktif Oturumu ve RAM Verisini Sonlandır (Logout / Reset) 🚪🧹
     */
    public function end(): bool
    {
        $sessId = session_id();
        if (!empty($sessId)) {
            $this->destroy($sessId);
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            @session_unset();
            @session_destroy();
        }

        return true;
    }

    /**
     * Oturum ID'sini yeniler (Session Fixation Koruması) 🔒
     */
    public function regenerate(bool $deleteOldSession = true): bool
    {
        $this->ensureStarted();
        return @session_regenerate_id($deleteOldSession);
    }

    /* ==========================================================================
       [ STORAGE & AUDIT METHODS ] - Path & Inspection Tools 📂
       ========================================================================== */

    /**
     * Güvenli Dosya Yolu Çözücü (Proje ve Oturum ID Bazlı İzolasyon) 🔐
     */
    protected function resolvePath(string $key): string
    {
        $projectKey = $this->projectKey ?: (function_exists('project_key') ? project_key() : 'default');
        
        // Eğer zaten tam bir session dosya adı ise (örn: site_example_sess_6866d8...)
        if (str_contains($key, '_sess_')) {
            $safeKey = preg_replace('/[^a-zA-Z0-9_\-\.\/]/', '_', $key);
            return $this->getStorageDir() . '/' . $safeKey;
        }

        // Eğer yerel PHP session dosyası ise (örn: sess_4de344...)
        if (str_starts_with($key, $this->prefix)) {
            $safeKey = preg_replace('/[^a-zA-Z0-9_\-\.\/]/', '_', $key);
            return $this->getStorageDir() . '/' . $safeKey;
        }

        $sessId = session_id();

        // Eğer özel bir session anahtarı ise (örn: csrf_token, user_id)
        $safeKey = preg_replace('/[^a-zA-Z0-9_\-\.\/]/', '_', $key);
        if (!empty($sessId)) {
            return $this->getStorageDir() . '/' . $sessId . '_' . $safeKey;
        }

        return $this->getStorageDir() . '/fallback_' . $safeKey;
    }

    /**
     * Diskteki fiziksel session dosyasını siler 💾
     */
    protected function deleteFile(string $key): bool
    {
        $path = $this->resolvePath($key);
        if (is_file($path)) {
            return @unlink($path);
        }
        return false;
    }

    /**
     * Tümünü Temizle (Yalnızca bu projeye ait session/token dosyalarını siler) 🧹
     */
    public function clearAll(): bool
    {
        $projectKey = $this->projectKey ?: (function_exists('project_key') ? project_key() : 'default');
        $dir = $this->getStorageDir();
        $files = glob($dir . '/' . $projectKey . '_*');
        if ($files !== false) {
            foreach ($files as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }
        }
        return true;
    }

    /**
     * Session dosyası oku ve çözümle (Syshub / Admin Audit) 🧐
     */
    public function getFileContent(string $filename): ?array
    {
        $path = $this->resolvePath($filename);
        $content = $this->driver->read($path, 'raw', $this->encrypted);

        if (empty($content)) {
            return null;
        }

        $data = $this->decodeSession($content);

        return [
            'data' => $data,
            'raw' => $content,
            'size' => filesize($path),
            'modified' => filemtime($path),
            'mtime' => filemtime($path)
        ];
    }

    /**
     * PHP Internal Session Decoder (Zengin İçerik Çözümü) 🧬
     */
    private function decodeSession(string $content): array
    {
        $data = [];
        try {
            $offset = 0;
            while ($offset < strlen($content)) {
                if (!preg_match("/^([a-zA-Z0-9_]+)\|/", substr($content, $offset), $matches)) {
                    break;
                }

                $key = $matches[1];
                $offset += strlen($matches[0]);
                $val = @unserialize(substr($content, $offset));
                $data[$key] = $val;
                $offset += strlen(serialize($val));
            }
        } catch (\Throwable $e) {
        }

        // Fallback: If standard decode fails, try direct unserialize or JSON
        if (empty($data)) {
            $unserialized = @unserialize($content);
            if ($unserialized !== false) {
                $data = is_array($unserialized) ? $unserialized : ['data' => $unserialized];
            } else {
                $json = json_decode($content, true);
                $data = is_array($json) ? $json : ['raw' => $content];
            }
        }

        return $data;
    }
}
