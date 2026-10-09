<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Http\Security;

/**
 * MachineApiRegistry - "Bu uc bir makine API'sidir" beyaninin TEK kaynagi 🤖📜
 *
 * [FW-APIGUARD · TASARIM GOREV 1 · 2026-10-03 · team member]
 *
 * SORUN (tasarim raporu §3.0): bugun "hangi uc makine API'sidir?" sorusunun
 * UC BAGIMSIZ cevabi var:
 *   (1) `IpGuardService::isAgentEndpoint()` — SABIT yol listesi,
 *   (2) `session-policy.php` — proje bazli yol onekleri,
 *   (3) route middleware — YOK.
 * Ucleri birbirinden habersiz; her yeni makine ucu ayni tuzaga duser.
 *
 * COZUM: beyan tablosu. Bir uc `machine-api` olarak BEYAN EDILDIGINDE:
 *   - IP katmani (IpGuardHandler) muafiyeti buradan sorar,
 *   - ApiGuard anahtar/kapsam/hiz/govde/denetim kararlarini buradan sorar.
 * Yeni bir koruma eklemek icin ikinci bir liste YAZILMAZ.
 *
 * ======================================================================
 * !!! ACIL UYARI: KERNEL SIRA KISITI (olculdu, 2026-10-03) !!!
 * ======================================================================
 * `KernelFactory::create()` sirasi:
 *   Autoload -> **ShieldSentinel (ipGuard->check())** -> DatabaseGuard ->
 *   ComponentRegistry -> SessionSandbox -> **Routing (Route::loadRoutes())**
 *
 * Yani IP KATMANI rotalar YUKLENMEDEN ONCE calisir. Bu yuzden bir ucu
 * `Route::middleware('machine-api')` ile beyan etmek IP katmanini BESLEMEZ
 * (beyan kaydi rotalar yuklenirken olusur, IP kontrolu ondan once biter).
 * Bu yuzden:
 *   - `SEED` tablosu sinif sabiti olarak **boot'tan once hazirdir** ve
 *     IP katmanini besler,
 *   - `declare()` ile runtime ekleme yalniz ApiGuard tarafinda anlamlidir.
 * Boylece "beyan yoksa eski davranis" kurali BOZULMAZ.
 *
 * GUVENLIK (ayni `isAgentEndpoint()` kurallari, kaldirilmadi):
 * Muafiyet YALNIZ URL yoluna bakar; IP'ye ve User-Agent'a GUVENILMEZ.
 * Normalizasyon:
 *   - sorgu dizesi (`?`) ve parca (`#`) atilir,
 *   - bir kez kod cozulur (`%2e%2e` -> `..`),
 *   - `..`, `//`, `\` iceren yollar beyanli DEGILDIR (muafiyet BYPASS edilemez),
 *   - BUYUK HARF = fail-closed (beyan muaf DEGILDIR),
 *   - sondaki `/` kirpilir.
 *
 * @see \Rbn\Framework\Core\Services\Gatekeepers\IpGuardService::isAgentEndpoint()
 *      (sabit liste SILINMEDI; bu eski ajanlar icin geri uyum emnetyeti olarak KALIR)
 */
final class MachineApiRegistry
{
    /**
     * Varsayilan zorlama bayragi: yeni korumalar LOG-ONLY baslar. 🛡️
     *
     * [PATRON KARARI 6] Yeni korumalar `enforce=false`/log-only ile baslar
     * (fail-open). Mevcani ajan/entegrasyon davranisi bit-bit AYNI kalmalidir.
     */
    public const ENFORCE_DEFAULT = false;

    /**
     * Tohum beyanlari (seed declarations). 🔒
     *
     * `ip_exempt` ALANI KRITIKTIR: beyan ile IP muafiyeti AYRI kararlardir.
     *
     * - `ip_exempt = true`  -> IP katmani (`IpGuardHandler`) bu yolu muaf sayar.
     *   Yalniz **bugun zaten muaf olan** yollarda `true` olur; boylece tohumun
     *   kendisi bir davranis DEGISIKLIGI uretmez.
     * - `ip_exempt = false` -> uc BEYAN EDILMISTIR (tek kaynak, dokumasyon,
     *   ileride koruma ekleme noktasi) ama IP katmani muafiyeti HENUZ VERILMEMISTIR.
     *
     * NEDEN BU ISLEME UCUN `ip_exempt = false` (A-11):
     * Tasarim raporu §4, "beyan ucu bagla + kapsam daraltma" adimini
     * **"1-3'un gozlem verisi olmadan yapilmaz"** ve **"EN RISKLI adimdir"**
     * diye isaretler; oncesinde her ucten 48 saat `log-only` olcumu onerilir.
     * Bu turda kaldirilan panel modulundeki ekran bir tarayicidan
     * ozel bir jeton basligiyla cagriliyordu; bu yollarda muafiyeti ACMAK
     * WAF/ban kapsamini daraltir = bir güvenlik GERILEMESi. Gozlem verisi
     * toplanmadan acilmasi "cok kritik" sayilir -> ATLANDI, onerilen karar
     * rapora yazildi.
     * Beyan yine de yazildi: boylece tek kaynak hazir, muafiyet anahtari tek
     * satirlik bir karar.
     *
     * - `agent-telemetry`: `isAgentEndpoint()` sabit listesiyle AYNI kapsam
     *   (geri uyum emnetyeti; sabit liste de durur).
     * - `agent-realtime`: `/ws/agent`. Session katmani (`session-policy.php`)
     *   bu yolu zaten makine istegi sayiyor; IP katmaninda olmamasi A-12
     *   tutarsizligiydi. Beyan bu boslugu kapatir. Bu yolun canli bir rotasi
     *   YOKTUR (yalniz session-policy'de aniliyor) -> karar kaydi disinda
     *   etkisi yoktur.
     *
     * @var array<int, array{path:string, scope:string, enforce:bool, ip_exempt:bool}>
     */
    public const SEED = [
        ['path' => '/api/agent',            'scope' => 'agent-telemetry', 'enforce' => self::ENFORCE_DEFAULT, 'ip_exempt' => true],
        ['path' => '/api/agent/',           'scope' => 'agent-telemetry', 'enforce' => self::ENFORCE_DEFAULT, 'ip_exempt' => true],
        ['path' => '/api/telegram/webhook', 'scope' => 'agent-telemetry', 'enforce' => self::ENFORCE_DEFAULT, 'ip_exempt' => true],
        ['path' => '/ws/agent',             'scope' => 'agent-realtime',  'enforce' => self::ENFORCE_DEFAULT, 'ip_exempt' => true],
        // A-11 tutarsizligi: beyan yazildi, muafiyet HENUZ ACILMADI (olum gerekiyor).
        ['path' => '/api/v1/media/upload',  'scope' => 'worker-ingest',   'enforce' => self::ENFORCE_DEFAULT, 'ip_exempt' => false],
        ['path' => '/api/v1/icerik-aktar',  'scope' => 'worker-ingest',   'enforce' => self::ENFORCE_DEFAULT, 'ip_exempt' => false],
    ];

    /**
     * Calisma aninda eklenen beyanlar (runtime declarations).
     *
     * @var array<int, array{path:string, scope:string, enforce:bool, ip_exempt:bool}>|null
     */
    private static ?array $runtime = null;

    /**
     * Tum beyanlari dondurur (tohum + runtime). 📜
     *
     * @return array<int, array{path:string, scope:string, enforce:bool, ip_exempt:bool}>
     */
    public static function declarations(): array
    {
        return array_merge(self::SEED, self::$runtime ?? []);
    }

    /**
     * Calisma aninda beyan ekler. 🏷️
     *
     * Yalniz `ApiGuard` tarafi icin anlamlidir (IP katmani bu asamada
     * coktan calismistir — bkz. sinif ustundeki kernel sira uyarisi).
     * Ayni yol icin son ekleme kazanir.
     *
     * @param string $path     URL yolu (normalize EDILMEZ; eslesme normalize yolda olur)
     * @param string $scope    Kapsam kimligi (orn. `agent-telemetry`)
     * @param bool   $enforce  Korumayi zorlayacak mi? Varsayilan: LOG-ONLY
     * @param bool   $ipExempt IP katmani muafiyeti verilsin mi? Varsayilan: HAYIR
     */
    public static function declare(
        string $path,
        string $scope = 'default',
        bool $enforce = self::ENFORCE_DEFAULT,
        bool $ipExempt = false
    ): void {
        $path = self::normalize($path);
        if ($path === null || $path === '') {
            return;
        }
        self::$runtime ??= [];
        // Ayni yol icin yalniz SON beyan gecerli (cift kayit birikmesin).
        self::$runtime = array_values(array_filter(
            self::$runtime,
            static fn(array $d): bool => ($d['path'] ?? '') !== $path
        ));
        self::$runtime[] = [
            'path'      => $path,
            'scope'     => $scope,
            'enforce'   => $enforce,
            'ip_exempt' => $ipExempt,
        ];
    }

    /**
     * Verilen yol beyanli mi? Beyan varsa kaydini dondurur. 🔍
     *
     * @return array{path:string, scope:string, enforce:bool, ip_exempt:bool}|null
     */
    public static function match(?string $uri = null): ?array
    {
        $yol = self::normalize($uri ?? (string) ($_SERVER['REQUEST_URI'] ?? ''));
        if ($yol === null || $yol === '') {
            return null;
        }

        $enIyi = null;
        foreach (self::declarations() as $d) {
            $beyanYolu = (string) ($d['path'] ?? '');
            if ($beyanYolu === '') {
                continue;
            }
            if ($yol === $beyanYolu || str_starts_with($yol, rtrim($beyanYolu, '/') . '/')) {
                // En UZUN (en spesifik) beyan kazanir: `/api/agent/logs` icin
                // hem `/api/agent` hem `/api/agent/logs` varsa spesifik olan gecer.
                if ($enIyi === null || strlen($beyanYolu) > strlen((string) $enIyi['path'])) {
                    $enIyi = $d;
                }
            }
        }

        return $enIyi;
    }

    /**
     * Verilen yol beyanli mi? (yalniz muzeyip bool)
     */
    public static function isDeclared(?string $uri = null): bool
    {
        return self::match($uri) !== null;
    }

    /**
     * Guvenli yol normalizasyonu. 🧯
     *
     * `isAgentEndpoint()` ile BIRE BIR ayni kurallar (bypass testleri
     * `fw_ipguard_ajan_muafiyeti` bunlarin AYNI sonuc vermesini olcutur).
     *
     * @return string|null normalize yol; muafiyete GIREMEYEN yol icin null
     */
    public static function normalize(?string $uri): ?string
    {
        $yol = trim((string) $uri);
        if ($yol === '') {
            return null;
        }

        foreach (['?', '#'] as $ayirac) {
            $konum = strpos($yol, $ayirac);
            if ($konum !== false) {
                $yol = substr($yol, 0, $konum);
            }
        }
        if ($yol === '') {
            return null;
        }

        $yol = rawurldecode($yol);

        // Traversal / bos segment: muafiyata GIREMEZ.
        if (str_contains($yol, '..') || str_contains($yol, '\\') || str_contains($yol, '//')) {
            return null;
        }

        // BUYUK HARF = fail-closed (muafiyet BYPASS edilemez).
        $kucuk = mb_strtolower($yol);
        if ($kucuk !== $yol) {
            return null;
        }

        $yol = rtrim($yol, '/');

        return $yol === '' ? null : $yol;
    }
}