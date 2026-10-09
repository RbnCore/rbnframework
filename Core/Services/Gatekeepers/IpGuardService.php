<?php
declare(strict_types=1);

namespace Rbn\Framework\Core\Services\Gatekeepers;

use Rbn\Framework\Core\Base\Services\BaseService;
use Rbn\Framework\Core\Support\Contracts\Base\BaseServiceInterface;

/**
 * IpGuardService - The Master Access Gatekeeper 🛡️🛰️
 * 
 * RBN Framework: Master Orchestrator for all IP-based security layers.
 * Consolidates Blacklisting, Whitelisting, and Rate Limiting.
 * 
 * [SYMMETRIC LAZY DISCOVERY] 🏛️🛰️✨
 * 
 * @property-read \Rbn\Framework\Core\Services\Gatekeepers\Handlers\IpGuardHandler $ipGuardHandler
 * @property-read \Rbn\Framework\Core\Services\Gatekeepers\Handlers\GeoIPHandler $geoIPHandler
 * @property-read \Rbn\Framework\Core\Services\Gatekeepers\Handlers\RateLimitHandler $rateLimitHandler
 * @property-read \Rbn\Framework\Core\Database\Repositories\Master\IpGuardRepository $masterIpGuardRepository
 */
class IpGuardService extends BaseService implements BaseServiceInterface
{

    /* ==========================================================================
       [ PUBLIC API ] - Strategic Gatekeeping 🛡️
       ========================================================================== */

    /**
     * Initial Boot Sequence for Gatekeepers 🎻🛰️
     * Pre-wakes satellites via Symmetric Lazy Discovery.
     */
    public function boot(): void
    {
        // 🛰️ Triggering Lazy Registration via Magic property access 🪄
        $this->ipGuardHandler;
        $this->geoIPHandler;
        $this->rateLimitHandler;
    }

    /**
     * Framework Entry Point: Early Access Check.
     * Called by ShieldSentinel during framework boot.
     */
    public function check(): void
    {
        $this->ipGuardHandler->check();
    }

    /**
     * Frequency Check: Request frequency control.
     */
    public function isLimitReached(string $identifier, string $action): bool
    {
        return $this->rateLimitHandler->isBlocked($identifier, $action);
    }

    /**
     * Get remaining block time in minutes.
     */
    public function remainingTime(string $identifier, string $action): int
    {
        return $this->rateLimitHandler->getRemainingTime($identifier, $action);
    }

    /**
     * Record Hit: Frequency tracking.
     */
    public function recordHit(string $identifier, string $action, array $metadata = []): bool
    {
        return $this->rateLimitHandler->recordAttempt($identifier, $action, $metadata);
    }

    /**
     * Account lock status for a login identity (`remaining` seconds > 0 = locked).
     *
     * @return array{failures:int,lock_seconds:int,remaining:int}
     */
    public function accountLockStatus(string $identity): array
    {
        return $this->rateLimitHandler->accountLockStatus($identity);
    }

    /**
     * Account lock: record a failed login for an identity.
     *
     * @return array{failures:int,lock_seconds:int,remaining:int}
     */
    public function recordAccountFailure(string $identity): array
    {
        return $this->rateLimitHandler->recordAccountFailure($identity);
    }

    /**
     * Account lock: reset the counter after a successful login.
     */
    public function clearAccountFailures(string $identity): void
    {
        $this->rateLimitHandler->clearAccountFailures($identity);
    }

    /**
     * Geo Utility: Flag and Location.
     */
    public function countryCode(string $ip): string
    {
        return $this->geoIPHandler->getCountryCode($ip);
    }

    public function flag(string $countryCode): string
    {
        return $this->geoIPHandler->getFlag($countryCode);
    }

    /**
     * Gelen tarayıcının engelli bir bot olup olmadığını kontrol eder 🤖⛔
     */
    public function isBlockedBot(string $userAgent): bool
    {
        if (empty($userAgent)) {
            return false;
        }

        $ua = strtolower($userAgent);
        // TrafficConstants üzerinden engelli botları çekelim
        $blockedBots = \Rbn\Framework\Core\System\Storage\Constants\TrafficConstants::BLOCKED_BOTS ?? [];

        foreach ($blockedBots as $blockedBot) {
            if (str_contains($ua, $blockedBot)) {
                return true;
            }
        }

        return false;
    }

    /**
     * İstek yolu bir AJAN ucu mu? 🤖🔓
     *
     * [FW-IP-ENFORCE-HAZIRLIK · Ö-2 · 2026-10-03 · team member]
     * Muaf yollar: `/api/agent/*` ve `/api/telegram/webhook` (tam yol).
     * GEREKCE: bu uclarin KENDI kimlik dogrulamasi (`resolveWebhookAuth`,
     * `?key=` / `x-agent-key`) ve KENDI hiz siniri (`AGENT_RATE_MAX`) var.
     * IP ban/WAF onlara ek olarak uygulandiginda ajan susturuluyor; yani iki
     * ayri mekanizma ayni yanlis-pozitif kaynagini uretiyor (B-1/B-4).
     * Muafiyet yalniz IP KATMANI kararlarindadir; ajan ucunun KENDI korumasi
     * yerindedir.
     *
     * GUVENLIK (kapsam daraltma): muafiyet YALNIZ bu iki yol grubuna;
     * IP'ye veya User-Agent'a GUVENILMEZ, yalniz URL yoluna bakilir. Yol
     * normalizasyonu:
     *   - sorgu dizesi (`?`) ve parca (`#`) atilir (hileler etkisiz),
     *   - bir kez kod cozulur (`%2e%2e` -> `..`),
     *   - `..`, `//`, `\` iceren yollar **muaf DEGILDIR** (traversal/cift
     *     slash ile muafiyet BYPASS edilemez),
     *   - kucuk harfe indirilir ve sondaki `/` kirpilir.
     * Boylece `/api/agentx`, `/foo/api/agent/`, `/api/agent/../admin`,
     * `/api/agent//mesaj` muaf DEGILDIR.
     *
     * @return bool true = ajan ucu (IP katmani bloklamaz)
     */
    public function isAgentEndpoint(?string $uri = null): bool
    {
        $yol = (string) ($uri ?? ($_SERVER['REQUEST_URI'] ?? ''));
        $yol = trim($yol);

        // Sorgu dizesi / parca: yol hilesi yerine gecmesin.
        foreach (['?', '#'] as $ayirac) {
            $konum = strpos($yol, $ayirac);
            if ($konum !== false) {
                $yol = substr($yol, 0, $konum);
            }
        }
        if ($yol === '') {
            return false;
        }

        // Kod cozme: yalniz segment/ayirac normalizasyonu icin.
        $yol = rawurldecode($yol);

        // Traversal / bos segment: muafiyata GIREMEZ.
        if (str_contains($yol, '..') || str_contains($yol, '\\') || str_contains($yol, '//')) {
            return false;
        }

        // BUYUK HARF = fail-closed: gercek ajan yolu her zaman kucuk harfle
        // gelir (`/api/agent/...`). Buyuk harfli bir yol MUAF sayilmaz; aksi
        // halde `/API/AGENT/` yazarak korumayi atlatmak mumkun olurdu
        // (muafiyat BYPASS). Esleme kucuk harfe indirilerek yapilir ama
        // once "yol degisti mi" kontrolu yapilir.
        $kucuk = mb_strtolower($yol);
        if ($kucuk !== $yol) {
            return false;
        }

        $yol = rtrim($yol, '/');
        if ($yol === '') {
            return false;
        }

        return $yol === '/api/agent'
            || str_starts_with($yol, '/api/agent/')
            || $yol === '/api/telegram/webhook';
    }

    /**
     * Gövde taraması için byte sınırı (64 KB). 🛡️
     *
     * [FW-IP-ENFORCE-HAZIRLIK · Ö-1] `php://input` SINIRSIZ okunursa tek
     * istek bellek/Regex-CPU (DoS) yaratabilir. Sınırı aşan gövde **taranmaz**
     * (fail-closed değil — tarama gözlem amaçlıdır, `log_only`).
     */
    public const BODY_SCAN_MAX_BYTES = 65536;

    /**
     * Gelen istek parametrelerinde (GET, POST, REQUEST_URI) saldırı örüntülerini tarar 🛡️🔥
     *
     * [FW-IP-ENFORCE-HAZIRLIK · Ö-1 · 2026-10-03 · team member] GÖVDE DE
     * TARANIR. Önceki hâlde yalnız `REQUEST_URI`, `QUERY_STRING` ve `$_POST`
     * vardı: `Content-Type: application/json` ile gelen bir saldırı gövdesi
     * `$_POST`'a DÜŞMEZ, dolayısıyla WAF onu hiç görmüyordu (JSON gövde
     * kör nokta).
     *
     * YANLIŞ POZİTİF KONTROLÜ (ölçüldü, 25 meşru JSON örneği): gövde, URL'den
     * DAHA GENİŞ bir insana metnidir (panel kaydetme, HTML içerik, Türkçe
     * metin, JSON loglar). Bu yüzden gövde için AYRI ve DARALTILMIŞ bir desen
     * seti kullanılır:
     *   - SQLi: yalnız YAPISAL kalıplar (`union select`, `information_schema`,
     *     `drop table`, `insert into`, `delete from`, `sleep(`/`benchmark(`,
     *     `or 1=1`, `having 1=1`).
     *     GÖVDEDE KULLANILMAZ: `select ... from`, `update ... set`,
     *     `order by <sayı>`, `group concat` — bunlar meşru Türkçe metinde de
     *     ("select the best option from the list") ve JSON loglarda geçer.
     *   - Path Traversal: aynen uygulanır (`../` kalıpları metinde nadir).
     *   - XSS (`<script`, `javascript:`, `onerror=` …): gövdede **UYGULANMAZ**.
     *     Gerekçe: gövde meşru HTML/JS içeriği taşır (`<p>`, `<strong>`,
     *     zengin metin); XSS taraması zaten `InjectionHandler::detectXss()`
     *     ile FORM katmanında, içerik bağlamında yapılıyor. IP katmanı ikinci
     *     kez `alert(`/`javascript:` ararsa meşru içerikleri susturur.
     *
     * `php://input` TEK KEZ okunur ama **akış TÜKETİLMEZ**: PHP'te
     * `php://input` `file_get_contents()` ile defalarca okunabilir
     * (multipart/form-data dışında), dolayısıyla controller'ın
     * `Request::getJsonData()` okuması bozulmaz.
     *
     * @param string|null $body Test/çağıran tarafından verilebilir gövde;
     *                           null ise `php://input` okunur.
     */
    public function detectAttack(?string $body = null): ?string
    {
        // [FW-GECE-GATEGUARD · G-07 · 2026-10-03 · team member] YOL AYRI
        // TARAMA ALTINDAN CIKARILDI. Onceki halde `REQUEST_URI` (yol + sorgu
        // dizesi birlikte) TAM deseni geciriyordu; sonuc: meşru URL/slug
        // parcaları saldiri siniflandiriliyordu. OLÇÜLEN 6 yanlis pozitif
        // (`birim\fw_g07_waf_ban_kapsami.php` (c1), fix ONCESI):
        //   yol#2  /kaynaklar/select the best option from the list -> SQLi
        //   yol#3  /blog/order by 1 rows                           -> SQLi
        //   yol#4  /destek/alert(acilir)                           -> XSS
        //   sorgu#2 s=update your settings                        -> SQLi
        //   sorgu#3 q=order by 5 rows                             -> SQLi
        //   sorgu#4 arama=alert(acilir)                           -> XSS
        //
        // GEREKCE (yol icin yalnizca traversal anlamlidir): yol bir kaynak
        // KIMLIGIDIR, kullanici verisi degil. SQLi/XSS yuzeyi sorgu dizesi
        // ve govdedir (govde zaten O-1'de eklendi). YOLDA yalniz traversal
        // kalir: `/dosya/../../etc/passxd` bir sorun yolu yine yakalanir
        // (test (d3)).
        // SORGULARDAKI 4 yanlis pozitif ise desen DARALTILMASIyla gider:
        // `update ... set` artik SET SONRASI ATAMA (`=`) bekler, `order by N`
        // satir sonunda ya da SQL ayiracindan sonra gelirse yakalanir,
        // `alert(` yalniz `document./window./location` gibi bir hedef ya da
        // tirnakli arguman gordugunde yakalanir. (d1)(d2) saldiri korpusu
        // 9/9 YINE yakalanir — tarama kasten gevsetilmedi.
        $inputs = self::attackPatternsForQuery();

        // ---- YOL: yalniz path traversal ----
        $yol = self::requestPath();
        if ($yol !== '') {
            $bulunan = self::matchAny(self::attackPatternsForPath(), $yol);
            if ($bulunan !== null) {
                return $bulunan;
            }
        }

        // ---- SORGU DIZESI + $_POST: SQLi / XSS / traversal ----
        $sorgu = self::requestQueryString();
        if ($sorgu !== '' && ($bulunan = self::matchAny($inputs, $sorgu)) !== null) {
            return $bulunan;
        }
        if (!empty($_POST)) {
            $post = json_encode($_POST);
            if (is_string($post) && ($bulunan = self::matchAny($inputs, $post)) !== null) {
                return $bulunan;
            }
        }

        // ---- GÖVDE (php://input) — DARALTILMIŞ desen seti ----
        // Byte sınırı BURADA da uygulanır (savunma derinliği): gövde dışarıdan
        // verilse bile sınırı aşan içerik taranmaz (tuzak: sınırı aşmakla
        // taramadan kaçınmak).
        $govde = $body ?? self::readRequestBody();
        if ($govde !== null && strlen($govde) > self::BODY_SCAN_MAX_BYTES) {
            $govde = null;
        }
        if ($govde !== null && $govde !== '') {
            return self::matchAny(self::attackPatternsForBody(), $govde);
        }

        return null;
    }

    /* ========================================================================
       [G-07] DESEN KÜTÜĞÜ — TEK MERKEZ
       -----------------------------------------------------------------------
       Önceki hâlde desenler `detectAttack()` gövdesinde iki kez YAZILMIŞTI
       (sorgu seti + gövde seti) ve iki set arasındaki fark yalnızca yorumda
       anlatılıyordu. Desen kü-tüğü artık tek yerde; yeni yüzey eklenirken
       hangi sete girdiği açıkça görülür.
       ======================================================================== */

    /**
     * Traversal deseni (YOL + SORGÜ + GÖVDE için AYNI). 🛡️
     *
     * `(?:\.\.\/){2,}` deseni `../../` ve `../..` biçimlerinin ikisini de
     * yakalar; eski `/\.\.\/\.\.\//` yalnız tam olarak iki kez tekrarını
     * yakalıyordu (`../../../../` içinde yine geçtiği için çoğu durumda
     * yakalanıyordu, ama `..%2f..%2f` tek segmentli varyantta kaçabiliyordu).
     */
    private const PATTERN_TRAVERSAL = '(\.\.[\/\\\\]){2,}|etc\/passwd|boot\.ini|win\.ini|proc\/self\/environ';

    /**
     * Sorgu dizesi / `$_POST` için SQLi deseni (YAPISAL + ATAMA).
     *
     * G-07 daraltmaları:
     *   - `update\s+.*?\s+set` → `update\s+[`\w.]+\s+set\s+[`\w.]+\s*=\s*`
     *     (meşru metin: "update your settings from the panel" artık TEMİZ),
     *   - `order\s+by\s+\d+` → satır sonu / SQL ayıracı gerektirir
     *     (meşru metin: "order by 5 rows returned" artık TEMİZ),
     *   - `select\s+.*?\s+from` **korunur** (SORGU yüzeyinde gerçek SQLi
     *     göstergesidir; `fw_ipguard_beyazliste_sirasi` (4) buna dayanır).
     */
    private const PATTERN_SQLI_QUERY =
        '(union\s+select'
        . '|select\s+[`\w.]+\s*(,|`\w.]+\s*,)*\s*from\s+[`\w]'
        . '|insert\s+into'
        . '|delete\s+from'
        . '|drop\s+table'
        . '|truncate\s+table'
        . '|update\s+[`\w.]+\s+set\s+[`\w.]+\s*=\s*'
        . '|information_schema'
        . '|order\s+by\s+\d+\s*(;|--|#|\/\*|$)'
        . '|group\s+concat'
        . '|benchmark\s*\('
        . '|sleep\s*\('
        . '|or\s+[`\'"]?\d+[`\'"]?\s*=\s*[`\'"]?\d+'
        . '|having\s+\d+\s*=\s*\d+'
        . ')';

    /**
     * Sorgu dizesi / `$_POST` için XSS deseni.
     *
     * G-07 daraltması: `alert\s*\(` YALNIZCA bir hedef/argüman görünce
     * yakalanır (`alert(document.cookie)`, `alert(1)`, `alert('xss')`).
     * Meşru metin/tutor ("alert(acilir) kullanmadan önce…") artık TEMİZ.
     */
    private const PATTERN_XSS_QUERY =
        '(<script[^>]*>'
        . '|<\s*iframe[^>]*>'
        . '|javascript\s*:'
        . '|onload\s*='
        . '|onerror\s*='
        . '|onmouseover\s*='
        . '|document\s*\.\s*(cookie|write)'
        . '|eval\s*\(\s*[`\'"]'
        . '|window\s*\.\s*location\s*=\s*[`\'"]?\s*javascript'
        . '|alert\s*\(\s*(?:`[0-9a-z_$]*`|document\.|window\.|location|[0-9]+[`\'"])'
        . ')';

    /**
     * GÖVDE için SQLi deseni — Ö-1'in DÜŞÜRÜLMÜŞ seti + `select ... from`
     * YAPISAL varyantı.
     *
     * Ö-1'de gövdeden `select ... from` tamamen çıkarılmıştı (meşru Türkçe
     * metinde de geçer). G-07 bu kararı **GÖRÜNMEZCE BOZMAZ**: buradaki
     * desen de yapısaldır — `from` SONRASI gerçek bir tablo/şema adı
     * (`from z_users`, `from\``, `from\s`vb.) ister. Ölçülen meşru cümle
     * "select the best option from the list" → `list` tanıklayıcı değil →
     * TEMİZ kalır. `fw_waf_govde_taramasi` (b) korpusu bunu ölçer.
     */
    private const PATTERN_SQLI_BODY =
        '(union\s+select'
        . '|select\s+[`\w.]+\s*(,|`\w.]+\s*,)*\s*from\s+[`\w]'
        . '|insert\s+into'
        . '|delete\s+from'
        . '|drop\s+table'
        . '|truncate\s+table'
        . '|update\s+[`\w.]+\s+set\s+[`\w.]+\s*=\s*'
        . '|information_schema'
        . '|group\s+concat'
        . '|benchmark\s*\('
        . '|sleep\s*\('
        . '|or\s+[`\'"]?\d+[`\'"]?\s*=\s*[`\'"]?\d+'
        . '|having\s+\d+\s*=\s*\d+'
        . ')';

    /** @return array<string,string> Sorgu/POST yüzeyi için desen kümesi. */
    private static function attackPatternsForQuery(): array
    {
        return [
            'SQL Injection'            => '/' . self::PATTERN_SQLI_QUERY . '/i',
            'Cross-Site Scripting (XSS)' => '/' . self::PATTERN_XSS_QUERY . '/i',
            'Path Traversal / LFI'      => '/' . self::PATTERN_TRAVERSAL . '/i',
        ];
    }

    /** @return array<string,string> Gövde için desen kümesi (XSS YOK — Ö-1). */
    private static function attackPatternsForBody(): array
    {
        return [
            'SQL Injection'       => '/' . self::PATTERN_SQLI_BODY . '/i',
            'Path Traversal / LFI' => '/' . self::PATTERN_TRAVERSAL . '/i',
        ];
    }

    /**
     * YOL için desen kümesi: **yalnız traversal** (G-07 kararı).
     *
     * @return array<string,string>
     */
    private static function attackPatternsForPath(): array
    {
        return [
            'Path Traversal / LFI' => '/' . self::PATTERN_TRAVERSAL . '/i',
        ];
    }

    /**
     * Bir girdi ilk eşleşen saldırı sınıfını döner (`null` = temiz). 🔍
     *
     * Girdi önce `urldecode` edilir; iki kez URL-kodlanmış saldırı
     * (`%2527%2520union...`) bir kez çözmeyle kaçmasın diye **sınırlı**
     * ikinci bir çözme yapılır (3 turda durulur → aşırı girdi patlaması yok).
     */
    private static function matchAny(array $patterns, string $input): ?string
    {
        if ($input === '') {
            return null;
        }

        $decoded = $input;
        for ($tur = 0; $tur < 3; $tur++) {
            $onceki = $decoded;
            foreach ($patterns as $name => $pattern) {
                if (preg_match($pattern, $decoded)) {
                    return (string) $name;
                }
            }
            $decoded = urldecode($decoded);
            if ($decoded === $onceki) {
                break;
            }
        }

        return null;
    }

    /**
     * İsteğin **YOL** kısmı (sorgu dizesi hariç). 🛡️
     *
     * `parse_url` yalnız YOL'u verir: `/p?q=select a from b` → `/p`.
     * Böylece sorgu dizesi YOL taramasına sızmaz (çift tarama ve iki yerden
     * gelen yanlış pozitif biter).
     */
    private static function requestPath(): string
    {
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '');
        if ($uri === '') {
            return '';
        }
        $yol = parse_url($uri, PHP_URL_PATH);
        return is_string($yol) ? $yol : '';
    }

    /**
     * İsteğin SORGU DİZESİ — `QUERY_STRING` yoksa `REQUEST_URI` içinden. 🛡️
     *
     * Neden ikisi de: normal PHP SAPI'si ikisini de doldurur, ama CLI tabanlı
     * test/olçüm kabukları ve bazı SAPI'lar `QUERY_STRING` boş bırakıp
     * sorguyu yalnız `REQUEST_URI` içinde taşıyor. Yalnız `QUERY_STRING`
     * okunsaydı bu istekler **tarama dışı** kalırdı (kör nokta = güvenlik
     * eksiği). İkisi birlikte alınır, ikinci dal yalnız ilki boşsa çalışır
     * (çift tarama yok).
     */
    private static function requestQueryString(): string
    {
        $qs = (string) ($_SERVER['QUERY_STRING'] ?? '');
        if ($qs !== '') {
            return $qs;
        }
        $qs = parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_QUERY);
        return is_string($qs) ? $qs : '';
    }

    /**
     * İstek gövdesini byte sınırıyla okur. 🛡️
     *
     * [FW-IP-ENFORCE-HAZIRLIK · Ö-1] Sınırı aşan gövde okunmaz (`null`) —
     * hem DoS hem de sınırı aşmakla taramadan kaçınmak mümkün değildir.
     * Gövde YOKSA `null` döner (GET istekleri, multipart/form-data).
     *
     * @return string|null gövde ya da null (yok / sınır aşıldı / okunamadı)
     */
    protected static function readRequestBody(): ?string
    {
        // multipart/form-data'da `php://input` güvenilir değildir; $_POST zaten
        // doldurulduğu için gövde taraması gereksiz.
        $contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
        if (str_contains($contentType, 'multipart/form-data')) {
            return null;
        }

        $govde = @file_get_contents('php://input', false, null, 0, self::BODY_SCAN_MAX_BYTES + 1);
        if ($govde === false || $govde === '') {
            return null;
        }
        if (strlen($govde) > self::BODY_SCAN_MAX_BYTES) {
            return null; // sınır aşıldı -> taranmaz (DoS koruması)
        }
        return $govde;
    }

    /**
     * [G-07] Kademeli ceza süresi (dakika). ⏱️
     *
     * DEĞER `FormGuardHandler::KADEMELI_CEZA_DAKIKA` ile **AYNIDIR** ve
     * bilinçli olarak kopyalandı:
     *   - `FormGuardHandler`'ın o sabiti **private**'dır ve oradaki
     *     `kademeliCezaDakika()` **private**'dır; kabul testi
     *     (`kabul\B71-71-kosullari.php` #4) "kademe YALNIZCA
     *     `kademeliCezaDakika()` içinden türemeli" diye ölçüyor → o sınıfın
     *     kapsamı değiştirilemez.
     *   - Bu görev `Core/Services/Gatekeepers/**` + `FormGuardHandler`
     *     dosyalarına yazabilir ama İKİ katmanı tek metota bağlamak, o
     *     kabul ölçümünü kırmak anlamına gelirdi.
     * Bu yüzden kural **iki yerde aynı sayı dizisi** olarak yaşar; test
     * (`fw_g07_waf_ban_kapsami` (b)) dizinin kendisini ölçer, iki sınıfın
     * eşitliği ileride tek noktaya taşınırsa (DURUM.md §5 kararı).
     */
    public const KADEMELI_CEZA_DAKIKA = [15, 60, 1440, 1440];

    /**
     * [G-07] Bir IP'nin WAF cezası kaç dakika sürmeli? ⏱️
     *
     * Kademe, o IP'nin `master.ipBlock` tablosundaki **AKTİF** kayıt sayısından
     * türetilir (`FormGuardHandler::kademeliCezaDakika()` ile aynı mantık):
     *   0 aktif kayıt  → ilk ihlal  → 15 dakika
     *   1 aktif kayıt  → ikinci     → 60 dakika (1 saat)
     *   2+ aktif kayıt → üçüncü+   → 1440 dakika (24 saat)
     * HİÇBİR KADEMEDE 7 GÜN YOKTUR (10080 bilinçli olarak kullanılmaz).
     *
     * DB okunamazsa **EN YUMUŞAK** kademe seçilir: hatada 7 günlük kilit
     * yazmak, kademeli cezayı anlamsızlaştırırdı (meşru kullanıcıyı
     * kilitlememek ilkesi).
     *
     * @return int Uygulanacak süre (dakika); 15 ≤ değer ≤ 1440.
     */
    public function progressiveBlockMinutes(string $ip): int
    {
        try {
            $aktif = (int) $this->model('master.ipBlock')
                ->where('ip_address', '=', $ip)
                ->where('blocked_until', '>', now())
                ->count();
            $kademe = min($aktif, count(self::KADEMELI_CEZA_DAKIKA) - 1);
        } catch (\Throwable $e) {
            $kademe = 0;
        }

        return self::KADEMELI_CEZA_DAKIKA[max(0, $kademe)];
    }

    /**
     * IP adresini veritabanı üzerinden hem master hem yerel olarak engeller 🚫
     */
    public function blockIpAddress(string $ip, string $reason, int $durationMinutes = 1440, ?int $projectId = 0): void
    {
        try {
            $countryCode = $this->countryCode($ip);
            $this->repository('master.ipGuard')->blockIp($ip, $reason, $durationMinutes, $projectId, $countryCode);
        } catch (\Throwable $e) {
            // Sessizce geç
        }
    }
}
