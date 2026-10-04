<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Http\Security;

use Rbn\Framework\Core\Base\BaseComponent;

/**
 * ApiGuard - Security Guard Middleware for Harici API 🛡️🔑
 *
 * [FW-APIGUARD · TASARIM GOREV 2-3 · 2026-10-03 · zeki-6eb7f5]
 *
 * TABAN: 52 satirlik, `!==` karsilastirmali, `?token=` kabul eden, hata
 * mesajinda IP yansitan bir katmandi. Asagidaki maddeler KAPANDI:
 *   - A-1  zamanlama acigi  -> `hash_equals` (MachineApiKeyStore)
 *   - A-2  `?token=` sizinti -> LOG-ONLY + `allow_query_token` (varsayilan true)
 *   - A-5  bos allowed_origins belgelendi, DAVRANIS DEGISMEDI
 *   - A-6  hata mesajinda IP yansitmasi KALDIRILDI
 *   - A-7  govde/icerik tipi siniri (log-only)
 *   - A-8  anahtar basina hiz siniri (log-only)
 *   - A-9  denetim kaydi (key_id, ASLA deger)
 *   - A-10 anahtar basina kimlik/kapsam/iptal/sure
 *   - A-13 hata sozlesmesine `code` alani EKLENDI (`message` metinleri AYNI)
 *
 * [PATRON KARARI 4] `?token=` KAPATILMAZ: yalniz LOG-ONLY uyari + proje
 * ayari `allow_query_token` (varsayilan `true`). REDDETME YAPILMAZ.
 * [PATRON KARARI 6] Yeni korumalar `enforce=false`/log-only; fail-open.
 * Mevcani ajan/entegrasyon davranisi bit-bit AYNI kalmalidir.
 *
 * [ADLANDIRMA NOTU] Hata cikisi ucu `reddet()` olarak adlandirildi: `fail()`
 * adi `BaseComponent` -> `ErrorHandlingTrait::fail()` ile CAKISIYOR ve
 * "Access level ... must be public" fatal'i veriyordu (olculdu).
 */
class ApiGuard extends BaseComponent
{
    /**
     * Handle incoming API security and authentication
     */
    public function handle(): void
    {
        $projectKey = project_key();
        if (empty($projectKey)) {
            $this->reddet('Aktif proje bulunamadı.', 400, 'NO_PROJECT');
        }

        // 1. Proje Dış API Ayarlarını Yükle
        // Kalıtım yoluyla gelen resolveProjectConfig metodunu doğrudan kullanıyoruz 🪐
        $config = $this->resolveProjectConfig('external-api', $projectKey);
        if (!$config) {
            $this->reddet('Bu proje için Dış API yapılandırılmamış.', 403, 'NOT_CONFIGURED');
        }

        if (!($config['enabled'] ?? false)) {
            $this->reddet('Dış API erişimi aktif değil.', 403, 'DISABLED');
        }

        // 2. IP / Origin Sınırlandırma Kontrolü (A-5 · A-6)
        $this->ipKontrol($config);

        // 3. API Key / Token Doğrulaması (A-1 · A-2 · A-10)
        $kimlik = $this->kimlikDogrula($config);

        // 4. Gövde / içerik tipi sınırı (A-7 · LOG-ONLY)
        $this->govdeKontrol($kimlik);

        // 5. Anahtar başına hız sınırı (A-8 · LOG-ONLY)
        $this->hizSiniri($kimlik);

        // 6. Denetim kaydı (A-9) — `key_id` yazılır, anahtar DEĞERİ ASLA.
        $this->denetim('allow', $kimlik);
    }

    /* ======================================================================
     * [2] IP / ORIGIN SINIRLANDIRMA (A-5 · A-6)
     * ====================================================================== */

    /**
     * İstemci IP'sini `allowed_origins` ile karşılaştırır.
     *
     * [A-5 · BELGELENDİ · DAVRANIŞ DEĞİŞMEDİ] `allowed_origins` BOŞ (veya `*`)
     * ise kontrol YAPILMAZ — yani sınırsız erişim. Bu, mevcut tek gerçek ayar
     * dosyasındaki (boş liste) davranışı ve `external-api.php` içindeki yorumu
     * birebir karşılar. PATRON KARARI: geri uyum için BOŞ LİSTENİN DAVRANIŞI
     * DEĞİŞTİRİLMEZ (sıkılaştırma kırıcı olurdu).
     *
     * [A-6 · KAPANDI] Hata metninden istemci IP'si ÇIKARILDI: iç ağ topolojisini
     * sızdırıyor ve log enjeksiyonu yüzeyiydi.
     */
    private function ipKontrol(array $config): void
    {
        $allowedOrigins = $config['allowed_origins'] ?? [];
        if (!empty($allowedOrigins) && !in_array('*', $allowedOrigins)) {
            $clientIp = $_SERVER['REMOTE_ADDR'] ?? '';
            if (!in_array($clientIp, $allowedOrigins)) {
                $this->reddet('Bu IP adresinden erişim izniniz yok.', 403, 'IP_NOT_ALLOWED');
            }
        }
    }

    /* ======================================================================
     * [3] ANAHTAR DOGRULAMA — anahtar basina kimlik 🔑
     * ====================================================================== */

    /**
     * Anahtarı çözer ve KİMLİĞİNİ döndürür. Doğrulanmamış istek 401 ile düşer.
     *
     * @return array{key_id:string, scopes:array, rate:?array, body_max:?int, source:string, query_credential:bool}
     */
    private function kimlikDogrula(array $config): array
    {
        // Anahtar ÖNCE başlıktan: `X-RBN-API-KEY` (Request::header() başlıkları
        // küçük harfe indirir; büyük/küçük yazım ikisi de çalışır).
        $baslikAnahtari = (string) ($this->request->header('X-RBN-API-KEY') ?: '');
        // [A-2] Sorgu/GÖVDE parametresi yalnız LOG-ONLY. Başlık yoksa geri
        // uyum için yine okunur, ama `query_credential` bayrağı denetim
        // kaydına `true` olarak düşer ve uyarı üretilir.
        $sorguAnahtari = $baslikAnahtari === ''
            ? (string) ($this->request->input('token') ?: '')
            : '';
        $given = $baslikAnahtari !== '' ? $baslikAnahtari : $sorguAnahtari;
        $sorgudanGeldi = ($baslikAnahtari === '' && $sorguAnahtari !== '');

        $allowQueryToken = (bool) ($config['allow_query_token'] ?? true);
        if ($sorgudanGeldi && !$allowQueryToken) {
            // PATRON KARARI 4: varsayılan `true`. Proje bunu `false` yaptıysa
            // parametre REDDEDİLİR (Faz 1). Varsayılanda bu dal HİÇ KISLANMAZ.
            $this->reddet('Yetkisiz erişim! Geçersiz API Anahtarı.', 401, 'UNAUTHORIZED');
        }
        if ($sorgudanGeldi) {
            $this->uyari('query_token_kullanildi', [
                'not' => 'A-2 LOG-ONLY: anahtar sorgu/govde parametresinden geldi; '
                    . 'access log / Referer / CDN loglarina dusme riski. '
                    . 'allow_query_token=' . ($allowQueryToken ? 'true' : 'false'),
            ]);
        }

        // Yeni `keys` bölümü (hash'li saklama, kapsam, iptal, süre).
        $tanimlar = MachineApiKeyStore::normalizeDefinitions($config['keys'] ?? null);
        if ($tanimlar !== []) {
            $bulunan = MachineApiKeyStore::resolve($tanimlar, $given);
            if ($bulunan !== null) {
                // İptal / süre kontrolü: biçim olarak geçerli ama iptal edilmiş
                // anahtar da 401'dir (kimlik geçerli, yetki değil -> 403 DEĞİL).
                foreach ($tanimlar as $t) {
                    if ((string) $t['id'] !== $bulunan['key_id']) {
                        continue;
                    }
                    if (MachineApiKeyStore::isRevoked($t)) {
                        $this->denetim('deny:auth', ['key_id' => $bulunan['key_id'], 'sebep' => 'revoked']);
                        $this->reddet('Yetkisiz erişim! Geçersiz API Anahtarı.', 401, 'UNAUTHORIZED');
                    }
                    if (MachineApiKeyStore::isExpired($t)) {
                        $this->denetim('deny:auth', ['key_id' => $bulunan['key_id'], 'sebep' => 'expired']);
                        $this->reddet('Yetkisiz erişim! Geçersiz API Anahtarı.', 401, 'UNAUTHORIZED');
                    }
                    break;
                }

                $kimlik = $bulunan + ['query_credential' => $sorgudanGeldi];
                MachineApiKeyStore::rememberIdentity($kimlik);
                $this->kapsamKontrol($kimlik);
                return $kimlik;
            }
        }

        // [PATRON KARARI 3] Geri uyum düz metin `api_key` dalı AÇIK KALIR.
        // Karşılaştırma `hash_equals` ile yapılır (A-1 kapandı); `!==` KALDIRILDI.
        $plain = (string) ($config['api_key'] ?? '');
        if ($plain === '' || $given === '' || !hash_equals($plain, $given)) {
            $this->denetim('deny:auth', ['sebep' => $given === '' ? 'anahtar_yok' : 'anahtar_gecersiz']);
            $this->reddet('Yetkisiz erişim! Geçersiz API Anahtarı.', 401, 'UNAUTHORIZED');
        }

        // Eski anahtar: kapsam YOK (tüm eylemler) — geri uyum.
        $kimlik = [
            'key_id'            => 'legacy',
            'scopes'            => [],
            'rate'              => null,
            'body_max'          => null,
            'source'            => 'legacy',
            'query_credential'  => $sorgudanGeldi,
        ];
        MachineApiKeyStore::rememberIdentity($kimlik);
        $this->kapsamKontrol($kimlik);
        return $kimlik;
    }

    /**
     * Kapsam (scope) denetimi. Kapsam listesi BOŞ ise denetim YAPILMAZ
     * (geri uyum: eski anahtarlar tüm eylemleri görür).
     *
     * Kapsam dışı istek 403'tür (401 DEĞİL): anahtar geçerli, yetkisi yok.
     */
    private function kapsamKontrol(array $kimlik): void
    {
        $scopes = is_array($kimlik['scopes'] ?? null) ? $kimlik['scopes'] : [];
        if ($scopes === []) {
            return;
        }
        $action = (string) ($this->request->input('action') ?: '');
        if ($action === '') {
            // Eylem yoksa controller'ın 400 döndürmesini bekle (karar değişmez).
            return;
        }
        if (in_array($action, $scopes, true) || in_array('*', $scopes, true)) {
            return;
        }
        $this->denetim('deny:scope', ['key_id' => $kimlik['key_id'], 'action' => $action]);
        $this->reddet('Geçersiz veya yetkisiz eylem: ' . $action, 403, 'FORBIDDEN_SCOPE');
    }

    /* ======================================================================
     * [4] GOVDE / ICERIK TIPI SINIRI (A-7 · LOG-ONLY · enforce=false)
     * ====================================================================== */

    /**
     * Gövde boyutu sınırı (beyan edilen `CONTENT_LENGTH` ölçülür; gövde OKUNMAZ).
     *
     * [PATRON KARARI 6] LOG-ONLY: sınır aşılırsa istek DÜŞÜRÜLMEZ, yalnız
     * denetim kaydı + uyarı. `body_max` tanımlı değilse hiçbir şey olmaz
     * (mevcut davranış bit-bit aynı).
     *
     * `multipart/form-data` bu kontrolden muaftır: yükleme sınırlarıyla
     * (`post_max_size` / servis içi 8 MB) çakışır ve tasarım raporu §3.4'te
     * bu bir ön koşul olarak yazılmıştı.
     */
    private function govdeKontrol(array $kimlik): void
    {
        $bodyMax = isset($kimlik['body_max']) ? (int) $kimlik['body_max'] : 0;
        if ($bodyMax <= 0) {
            return;
        }
        $contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
        if (str_contains($contentType, 'multipart/form-data')) {
            return;   // yükleme yolu: servis içi sınırlar geçerli
        }
        $len = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
        if ($len > $bodyMax) {
            $this->denetim('deny:body', [
                'key_id' => $kimlik['key_id'],
                'declared_bytes' => $len,
                'limit' => $bodyMax,
                'not' => 'LOG-ONLY (enforce=false): istek DUSURULMEDI',
            ]);
            $this->uyari('body_limit_asildi', [
                'key_id' => $kimlik['key_id'],
                'declared_bytes' => $len,
                'limit' => $bodyMax,
            ]);
        }
    }

    /* ======================================================================
     * [5] ANAHTAR BASINA HIZ SINIRI (A-8 · LOG-ONLY · enforce=false)
     * ====================================================================== */

    /**
     * Kimlik = `key_id` (IP DEĞİL). Sayaç dosyada tutulur; yazılamazsa
     * FAIL-OPEN + uyarı (koruma kararı değişmez).
     */
    private function hizSiniri(array $kimlik): void
    {
        $rate = is_array($kimlik['rate'] ?? null) ? $kimlik['rate'] : null;
        if ($rate === null) {
            return;
        }
        $max = (int) ($rate['max'] ?? 0);
        $per = (int) ($rate['per'] ?? 60);
        if ($max <= 0 || $per <= 0) {
            return;
        }

        $sayacDosyasi = $this->sayacDosyasi((string) $kimlik['key_id']);
        if ($sayacDosyasi === null) {
            return;     // sayaç yazılamadı -> fail-open (gizli hata değil)
        }

        $pencere = $per * 60;
        $sayac = 0;
        $pencereBaslangic = time();
        try {
            $kilit = @fopen($sayacDosyasi, 'c+');
            if ($kilit === false) {
                $this->uyari('rate_sayac_yazilamadi', ['not' => 'fail-open: koruma karari degismedi']);
                return;
            }
            try {
                if (!flock($kilit, LOCK_EX)) {
                    $this->uyari('rate_kilit_alinamadi', ['not' => 'fail-open']);
                    return;
                }
                $veri = json_decode((string) stream_get_contents($kilit), true);
                if (is_array($veri) && isset($veri['from'], $veri['n'])) {
                    $pencereBaslangic = (int) $veri['from'];
                    $sayac = (int) $veri['n'];
                }
                if ((time() - $pencereBaslangic) >= $pencere) {
                    $pencereBaslangic = time();
                    $sayac = 0;
                }
                $sayac++;

                ftruncate($kilit, 0);
                rewind($kilit);
                fwrite($kilit, (string) json_encode(['from' => $pencereBaslangic, 'n' => $sayac]));
                fflush($kilit);
                flock($kilit, LOCK_UN);
            } finally {
                @fclose($kilit);
            }

            if ($sayac > $max) {
                $kalan = max(1, $pencere - (time() - $pencereBaslangic));
                $this->denetim('deny:rate', [
                    'key_id' => $kimlik['key_id'],
                    'count' => $sayac,
                    'limit' => $max,
                    'window_seconds' => $pencere,
                    'retry_after' => $kalan,
                    'not' => 'LOG-ONLY (enforce=false): istek DUSURULMEDI',
                ]);
                $this->uyari('rate_limit_asildi', [
                    'key_id' => $kimlik['key_id'],
                    'count' => $sayac,
                    'limit' => $max,
                ]);
            }
        } catch (\Throwable $e) {
            // [A0-2] Sessiz yutma yok: kayıt düşer, karar DEĞİŞMEZ (fail-open).
            $this->uyari('rate_hatasi', ['exception' => get_class($e), 'message' => $e->getMessage()]);
        }
    }

    /**
     * Anahtar başına sayaç dosyası yolu. Yazılamazsa null.
     *
     * `key_id` dosya adına girdiği için SADECE güvenli karakterlere indirilir
     * (yol taşıma / dizin oluşturma vektörü).
     */
    private function sayacDosyasi(string $keyId): ?string
    {
        $guvenli = preg_replace('/[^A-Za-z0-9_.-]/', '_', $keyId) ?: 'anon';
        // Sayaç `Storage/cache` ALTINDA tutulur: bu dizin `.gitignore`'da
        // (`**/Storage/cache/`) ve çalışma anı üretilen geçici veridir.
        // (Başka bir yer seçmek depoya sayaç dosyaları sızdırırdı.)
        $dizin = rtrim((string) (\Rbn\Framework\Core\System\Paths\Paths::project()->storage('cache/machine-api')) ?? '', '/\\');
        if ($dizin === '') {
            return null;
        }
        if (!is_dir($dizin) && !@mkdir($dizin, 0775, true) && !is_dir($dizin)) {
            return null;
        }
        return $dizin . DIRECTORY_SEPARATOR . 'rate_' . $guvenli . '.json';
    }

    /* ======================================================================
     * [6] DENETIM KAYDI (A-9) + HATA SOZLESMESI (A-13)
     * ====================================================================== */

    /**
     * Hata sözleşmesi: `code` alanı EKLENİR, `message` metinleri DEĞİŞTİRİLMEZ.
     *
     * [A-13] Eski istemciler `message`/`success` okur; onlar aynen kalır.
     * Yeni istemci `code` ile karar türünü makine-okunur ayırt eder
     * (401 / 403 / 413 / 429 ayrımı — tasarım raporu §3.7).
     *
     * [NOT] Global `response()` yardımcısı kullanılır: `BaseComponent::__get()`
     * `response` son ekini çözemez (`ComponentTypes::typeMap()`'te yok), yani
     * `$this->response` NULL dönerdi. Taban kod da `response()->error()` ile
     * çalışıyordu; aynı yol.
     */
    private function reddet(string $message, int $status, string $code): void
    {
        response()->json([
            'success'   => false,
            'message'   => $message,
            'code'      => $code,
            'status'    => $status,
            'data'      => null,
            'timestamp' => now(),
        ], $status);
    }

    /**
     * Denetim kaydı: `key_id` + karar. Anahtar DEĞERİ ASLA yazılmaz.
     *
     * Yazma başarısız olursa karar DEĞİŞTİRMEZ (yalnız kayıt kaçar).
     */
    private function denetim(string $karar, array $ek = []): void
    {
        try {
            $baglam = array_merge([
                'key_id'   => (string) ($ek['key_id'] ?? ''),
                'karar'    => $karar,
                'path'     => (string) ($_SERVER['REQUEST_URI'] ?? ''),
                'method'   => (string) ($_SERVER['REQUEST_METHOD'] ?? ''),
                'bytes'    => (int) ($_SERVER['CONTENT_LENGTH'] ?? 0),
                'project'  => (string) (function_exists('project_key') ? project_key() : ''),
            ], $ek);
            // Anahtar değeri kazara girmesin diye son bir savunma:
            $baglam['query_credential_present'] = (bool) ($ek['query_credential'] ?? false);
            unset($baglam['query_credential']);
            $this->logs()?->channel('machine-api')->info('machine-api', $baglam);
        } catch (\Throwable) {
            // Log yazımı güvenlik kararını değiştirmez.
        }
    }

    private function uyari(string $mesaj, array $baglam = []): void
    {
        try {
            $this->logs()?->channel('machine-api')->warning($mesaj, $baglam);
        } catch (\Throwable) {
            // Aynı gerekçe.
        }
    }
}