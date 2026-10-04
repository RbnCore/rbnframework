<?php

namespace Rbn\Framework\Core\Http\Engine\Traits\Response;

/**
 * RedirectTrait - The Flow Navigator
 */
trait RedirectTrait
{
    /**
     * Perform an HTTP redirect and exit.
     *
     * [GUVENLIK YAMASI 2026-10-01] Open Redirect kapatildi.
     * Onceki hali `header('Location: ' . $url)` idi: host DOGRULAMASI YOKTU.
     * ValidationTrait::handleFailedValidation / handleFailedShield / handleHookFailure
     * bu metodu `$_SERVER['HTTP_REFERER']` ile cagirIYordu; saldirgan kendi alan
     * adindaki bir form sayfasindan POST yaparak kullaniciyi guvenilir alan adindan
     * cikis yapan 302 zincirine sokabiliyordu (güvenlik incelemesi 52 raporu, YUKSEK-2, T10/T11).
     *
     * KURAL (fail-closed):
     *  - Goreli yol ("/", "/iletisim")           -> serbest, oldugu gibi gecer.
     *  - Mutlak URL (http/https veya "//host")  -> host ayni-origin degilse "/"e duser.
     *  - Kontrol karakteri / CRLF                -> "/"e duser (header splitting).
     *  - Tum HTML fallback'leri de escape edilir  -> JS/noscript enjeksiyonu kapanir.
     *
     * Beyaz liste = aktif projenin kanonik alan adi + ayni proje grubundaki diger
     *              alan adlari (group_projects) + DOGRULANMIS istek host'u.
     *
     * [GUVENLIK YAMASI 2026-10-01 / gorev 94] Sifa (a) + backslash (F-01):
     *  - `$_SERVER['HTTP_HOST']` artik DOGRUDAN beyaz listeye GIRMEZ. Saldirgan
     *    `Host: evil.example` gonderip `Referer: https://evil.example/x` ile
     *    korumayi gecmisti (Banu-71 #1, YUKSEK). Artik istek host'u once
     *    guvenilir koke gore DOGRULANIR (bkz. hostKokeUyuyorMu()).
     *  - Ters bolu `\` -> `/` olarak NORMALIZE edilir. Tarayici da WHATWG URL
     *    Standard'i geregi `/\evil.example` ve `//evil.example\@site` girdilerinde
     *    `\`'i `/` sayar ve hedefi SALDIRGANIN HOSTUNA gonderir (güvenlik incelemesi F-01,
     *    Banu-71 #3, YUKSEK). Normalize etmezsek koruma "kendi segmentimiz"
     *    deyip gecirdi.
     *  - Port kurali sertlestirildi: beyaz listede port ACIK yazili degilse
     *    hedefte acik yazili port REDDEDILIR (Banu-71 #7; ayni sunucudaki baska
     *    servise, or. redis paneli, yonlendirme engellenir).
     */
    public function redirect(string $url, int $code = 302): void
    {
        $url = $this->guvenliHedef($url);
        $guvenli = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');

        if (!headers_sent($file, $line)) {
            http_response_code($code);
            header('Location: ' . $url);
        } else {
            // Javascript fallback when headers already sent
            echo "<script type='text/javascript'>window.location.href='" . $guvenli . "';</script>";
            echo "<noscript><meta http-equiv='refresh' content='0;url=" . $guvenli . "'></noscript>";
            echo "<div style='text-align:center; margin-top:50px; font-family:sans-serif;'>";
            echo "Yönlendiriliyorsunuz... <a href='" . $guvenli . "'>Tıklayın</a>";
            echo "</div>";
        }
        exit;
    }

    /**
     * [GUVENLIK] TEK CHOKE NOKTASI: bir redirect hedefini beyaz listeye gore
     * guvenli hale getirir.
     *
     * Acik yonlendirme iki AYRI yoldan doluyordu ve yalnizca biri korumaliydi:
     *   1) PHP disi yol: `header('Location')` -> redirect()          [KORUMALI]
     *   2) AJAX yolu: AlertService::send() -> alertJson() -> JSON'a ham
     *      yazilir -> rbnService.js / rbnAlert.js `window.location.href = redirect`
     *      [KORUMASIZ - 257 form'un 45'i data-rbn-ajax, yani bu yol BASKIN]
     * Simdi her iki yol da bu TEK metodu cagiriyor:
     *   - `redirect()` icinden `$this->guvenliHedef(...)`
     *   - `AlertService::send()` icinden `response()->guvenliHedef(...)`
     *
     * [PHP 8.3] Bu metot STATIK DEGIL, instance metottur. Neden: bir trait'in
     * statik metodunu `Trait::metot()` seklinde cagirmak PHP 8.1'de deprecated
     * oldu; PHP 8.3'te "Calling static trait method ... is deprecated" UYARISI
     * basar. O uyari AJAX yanitinda JSON'dan ONCE ekrana yazilir ve JS'in
     * `JSON.parse` cagrisini kirdigi icin korumayi eklemek formu BOZARDI.
     * `response()->guvenliHedef()` (Response bu trait'i kullar) temiz instance
     * cagrisidir ve uyari uretmez.
     *
     * @return string
     */
    public function guvenliHedef(string $url): string
    {
        $url = trim($url);

        // 1) Bos hedef -> guvenli varsayilan.
        if ($url === '') {
            return '/';
        }

        // 2) Kontrol karakteri / CRLF enjeksiyonu (header splitting) -> reddet.
        if (preg_match('/[\x00-\x1F\x7F]/', $url) === 1) {
            return '/';
        }

        // 3) [F-01] Ters bolu normalizasyonu. Tarayici (WHATWG URL Standard)
        //    ozel-sema girdilerinde `\`'i `/` sayar. Biz de onunla AYNI sekilde
        //    normalize ediyoruz; aksi halde `/\evil.example` "goreli yol"
        //    sanilip korumadan gecer, tarayici ise kullaniciyi evil.example'a
        //    gonderir. Normalize etmek korumayi gecersiz kilmaz, girdiyi
        //    tarayicinin gordugu seye indirger.
        $url = str_replace('\\', '/', $url);

        // 3b) [Y-4] Başı `/` OLMAYAN GÖRELİ hedefleri mutlak yola normalize et.
        //    SORUN: `dashboard/x`, `sayfa/ekle` gibi site içi göreli hedefler
        //    `str_starts_with($url,'/')` testinden geçemediği için "mutlak URL"
        //    sayılıyor, `parse_url()` host vermiyordu ve guvenli varsayılana
        //    (`/`) düşüyordu. Kullanıcı sessizce ana sayfaya atılıyordu; 257
        //    form'un 45'i data-rbn-ajax olduğu için BU YOL (AlertService ->
        //    JSON `redirect`) baskındı.
        //    KURAL: yalnız GERÇEKTEN göreli olan girdiye `/` eklenir. Sema
        //    tasiyan her girdi (`javascript:`, `data:`, `https:`) DOKUNULMAZ —
        //    yoksa `/javascript:alert(1)` diye "kendi yolumuz" gibi geçer ve
        //    atlatma kapısı açılırdı (B-6 test listesi yeşil kalmalı).
        $url = $this->goreliyiMutlakYap($url);

        // 4) Goreli yol mu? "//evil.com" de mutlak sayilir (sema-relative).
        //    NOT: normalize edildiginden sonra `/\evil.example` -> `//evil.example`
        //    olur ve burada MUTLAK sayilir (beyaz listeye girer).
        $goreli = !str_starts_with($url, '/') || str_starts_with($url, '//');

        if (!$goreli) {
            return $url; // Kendi segmentlerimiz - serbest.
        }

        // 5) Mutlak URL: host beyaz listede mi?
        $hedefHost = parse_url($url, PHP_URL_HOST);
        if (!is_string($hedefHost) || $hedefHost === '') {
            return '/';
        }
        // Buyuk/kucuk harf ve sondaki nokta normalizasyonu (host buyuk harf,
        // "site.example.test." -> ayni host). Tam genel lik (IDN) yazmiyoruz:
        // tarayici punycode'a cevirir ve ASCII-olmayan host zaten hic giremez.
        $hedefHost = rtrim(strtolower($hedefHost), '.');

        $hedefPort = $this->urlPort($url);

        foreach ($this->beyazListeHostlari() as $izin) {
            if ($hedefHost !== $izin['host']) {
                continue;
            }
            // Port kurali (serlestirildi):
            //  - Hedefte port ACIK yazili degilse -> ayni sema (80/443) varsayilir, gecer.
            //  - Beyaz listede port ACIK yazili degilse ve HEDEFTE yaziliysa -> REDDEDILIR.
            //    (onceki halde `https://site:8443/x` hic karsilastirma yapilmadan
            //     gcerdi: saldirgan ayni sunucudaki baska servise yonlendirebiliyordu.)
            if ($hedefPort === null) {
                return $url;
            }
            if ($izin['port'] !== null && $hedefPort === $izin['port']) {
                return $url;
            }
            // Hedeften acik port yazili, beyaz listede port yok -> bu origin degil.
            continue;
        }

        // 6) Beyaz listede yok -> guvenli varsayilan (fail-closed).
        return '/';
    }

    /**
     * [GUVENLIK · Y-4] Göreli hedefi mutlak site içi yola çevirir.
     *
     * YALNIZ "gerçekten göreli" olan girdiye `/` eklenir:
     *  - `/x`, `//host/x`      -> zaten yol / şema-göreli: DOKUNULMAZ
     *  - `dashboard/x`        -> `/dashboard/x`   (Y-4'in asıl hedefi)
     *  - `sayfa/ekle?a=1`     -> `/sayfa/ekle?a=1`
     *  - `javascript:alert(1)` -> DOKUNULMAZ -> şema sayılır, reddedilir
     *  - `https://…`, `data:…`, `vbscript:…` -> DOKUNULMAZ
     *  - `?a=1`, `#x`          -> DOKUNULMAZ (yol değil, sorgu/parça)
     *
     * Neden `javascript:` gibi girdiler dışlanıyor? `/` eklenirse
     * `/javascript:alert(1)` ortaya çıkar ve `guvenliHedef()` bunu "kendi
     * segmentimiz" diye GEÇİRİR — yani normalizasyon atlatmayı açardı.
     *
     * @param string $url `\`→`/` normalize edilmiş girdi.
     * @return string
     */
    protected function goreliyiMutlakYap(string $url): string
    {
        if ($url === '' || str_starts_with($url, '/')) {
            return $url;
        }
        // Sema tasiyan girdi (şablon: `[A-Za-z][A-Za-z0-9+.-]*:`) ASLA yola cevrilmez.
        if (preg_match('#^[A-Za-z][A-Za-z0-9+.\-]*:#', $url) === 1) {
            return $url;
        }
        // Yalnizca sorgu dizesi / parca ile baslayan girdi de yol degildir.
        if (str_starts_with($url, '?') || str_starts_with($url, '#')) {
            return $url;
        }

        return '/' . $url;
    }

    /**
     * [GUVENLIK] Geriye donuk uyum: eski korumali metot adi korunur, cunku
     * 71 numarali inceleme harness'i ve gecmis testler `guvenliRedirectHedefi()`
     * cagiriyor. Ayni mantik, tek kaynak.
     *
     * @return string
     */
    protected function guvenliRedirectHedefi(string $url): string
    {
        return $this->guvenliHedef($url);
    }

    /**
     * [GUVENLIK] Ayni-origin beyaz listesi. Kaynaklari IKI ASAMALI.
     *
     * ASAMA 1 - "KOK" (guvenilir, config'den gelir; saldirgan YAZAMAZ):
     *   - Aktif projenin alan adi: `project_data('domain')` (BootCache kesfi).
     *   - Ayni proje grubundaki diger alan adlari: `group_projects()`.
     *     (coklu alan adli projeler: ornek proje, baska bir proje -
     *      bu kayit sayesinde gecisler KIRILMAZ.)
     *
     * ASAMA 2 - "TUREYEN" (yalnizca koka gore DOGRULANIRSA listeye girer):
     *   - `Paths::project()->baseUrl()` host'u
     *   - `$_SERVER['HTTP_HOST']` (istek host'u)
     *
     * [gorev 94 / Banu-71 #1] NEDEN bu ayrim? `baseUrl()` kesfi verisi bos
     * oldugunda `$_SERVER['HTTP_HOST']`'a GERI DUSER (ProjectContext.php:116-117).
     * Yani baseUrl'i dogrudan koke saymak, atlatmayi yine acik birakardi.
     * Artik koker yalnizca config'den cikariliyor; HTTP_HOST ise kok bos
     * olmadikca hicbir zaman listeye giremez.
     *
     * [FAIL-CLOSED] Kok kaynaklarin HICBIRI cozulemezse liste BOS doner: o
     * durumda yalnizca GORELI yollar gecer, hicbir mutlak URL gecmez. Brief'in
     * "guvenli varsayilan: yalniz ayni-origin goreli yol" kurali. Bu, siteyi
     * calisir halde tutar (goreli yonlendirme her zaman gecer).
     *
     * @return array<int,array{host:string,port:int|null}>
     */
    protected function beyazListeHostlari(): array
    {
        // --- ASAMA 1: kok kaynaklar (config) ---
        $kok = [];

        try {
            if (function_exists('project_data')) {
                $domain = project_data('domain');
                if (is_string($domain) && $domain !== '') {
                    // ProjectContext::baseUrl() yerel gelistirme icin `.test`/`.local`
                    // sonekini korur; ayni mantigi uygulariz, aksi halde kok
                    // `site.example` kalir ve istek host'u `site.example.test` ile
                    // eslesmezdi, yerel yonlendirmeler kirilirdi.
                    $kok[] = $this->urlHostPort($this->yerelSonEkliUrl($domain));
                }
            }
        } catch (\Throwable $e) {
            // proje verisi okunamazsa grup alan adlari devralir.
        }

        try {
            if (function_exists('group_projects')) {
                foreach ((array) group_projects() as $proje) {
                    $domain = is_array($proje) ? ($proje['domain'] ?? null) : null;
                    if (is_string($domain) && $domain !== '') {
                        $kok[] = $this->urlHostPort($this->yerelSonEkliUrl($domain));
                    }
                }
            }
        } catch (\Throwable $e) {
            // grup verisi okunamazsa yukaridaki aktif proje alan adi yeterlidir.
        }

        $kok = $this->normalizeListe($kok);

        // FAIL-CLOSED: guvenilir kok yoksa hicbir mutlak hedef gecmez.
        if ($kok === []) {
            return [];
        }

        // --- ASAMA 2: koke gore dogrulanan tureyenler ---
        $liste = $kok;

        // Aktif projenin kanonik host'u (koke uymuyorsa eklenmez).
        try {
            $baseUrl = \Rbn\Framework\Core\System\Paths\Paths::project()->baseUrl();
            if (is_string($baseUrl) && $baseUrl !== '') {
                $girdi = $this->urlHostPort($baseUrl);
                if ($girdi['host'] !== '' && $this->hostKokeUyuyorMu($girdi['host'], $kok)) {
                    $liste[] = $girdi;
                }
            }
        } catch (\Throwable $e) {
            // baseUrl cozulemezse kok + HTTP_HOST yeterlidir.
        }

        // Istek host'u (HTTP_HOST) - DOGRULANDIKTAN SONRA eklenir.
        $rawHost = $_SERVER['HTTP_HOST'] ?? null;
        if (is_string($rawHost) && $rawHost !== '') {
            $girdi = $this->urlHostPort('//' . $rawHost);
            if ($girdi['host'] !== '' && $this->hostKokeUyuyorMu($girdi['host'], $kok)) {
                $liste[] = $girdi;
            }
        }

        return $this->normalizeListe($liste);
    }

    /**
     * [GUVENLIK] Yerel gelistirme son ekini korur (`.test` / `.local`).
     *
     * ProjectContext::baseUrl() ile ayni kural: kesfi verisindeki alan adi
     * `site.example` ise ve istek host'u `.test` ile bitiyorsa kok `site.example.test`
     * olur. Boylece yerel coklu alan adi gecisleri kirilmaz.
     *
     * @return string tam URL ('//host' biciminde olabilir)
     */
    private function yerelSonEkliUrl(string $domain): string
    {
        $url = str_contains($domain, '://') ? $domain : '//' . $domain;
        $host = rtrim(strtolower((string) parse_url($url, PHP_URL_HOST)), '.');
        // [FW-110 / 94-1] Onceki `str_contains($host,'.test')` ALT DIZGE kontroluydu:
        // `notmysite.test.attacker.com` kesfi verisinde olsaydi yerel sanilirdi.
        // Artik **TAM son-ek** (K-1 ile ayni kural).
        if ($host === '' || str_ends_with($host, '.test') || str_ends_with($host, '.local')) {
            return $url;
        }

        // [FW-110 / 94-1] GUENILIR KOK LISTESI ISTEK `Host`'UNA BAGLANMAZ.
        // Eski kod: istek host'u `.test` ile bitiyorsa kok alan adina `.test`
        // ekleniyordu. Uretimde tek bir `Host: x.test` istegi butun kok alan
        // adlarini `*.test` ile listeye sokuyordu (beyaz liste guvenlik
        // yuzeyi istek basligindan degismemeli).
        // Yeni kural: son-ek eklemesi YALNIZ guvenilir YEREL ORTAMDA olur ve
        // karar TEK KAYNAKTAN gelir: PreBoot::isTrustedLocalEnvironment()
        // (beyaz liste TAM eslesme VEYA `*.test` TAM son-ek **VE** istemci
        // IP'si loopback/ozel ag; X-Forwarded-For bilincli olarak yok sayilir).
        if (!$this->guvenilirYerelOrtam()) {
            return $url;
        }

        foreach (['.test', '.local'] as $sonEk) {
            if (str_ends_with($this->normalizeIstekHost(), $sonEk)) {
                return '//' . $host . $sonEk;
            }
        }

        return $url;
    }

    /**
     * [FW-110 / 94-1] Istek, guvenilir yerel ortam mi? TEK KAYNAK: PreBoot.
     *
     * `RBN_DEV`/`RBN_DEBUG` sabitleri `PreBoot::detectEnvironment()` tarafindan
     * uretilir; ayrica birim/CLI ortaminda sabit tanimli olmayabilir, o durumda
     * dogrudan `isTrustedLocalEnvironment()` cagrilir.
     *
     * X-Forwarded-For **BILINCLI OLARAK** kullanilmaz (guvenilmeyen, istemci
     * tarafindan uretilir).
     */
    private function guvenilirYerelOrtam(): bool
    {
        if (defined('RBN_DEV') && RBN_DEV) {
            return true;
        }

        if (!class_exists(\Rbn\Framework\Core\System\Kernel\Base\PreBoot::class)) {
            // PreBoot yok (CLI/birim): guvenilir kayit yok -> fail-closed.
            return false;
        }

        return \Rbn\Framework\Core\System\Kernel\Base\PreBoot::isTrustedLocalEnvironment(
            (string) ($_SERVER['HTTP_HOST'] ?? ''),
            (string) ($_SERVER['REMOTE_ADDR'] ?? '')
        );
    }

    /**
     * [FW-110 / 94-1] ISTEK host'unun **normalize** edilmis hali.
     *
     * Onceki kod `explode(':')` ile portu atiyordu: `notmysite.test:99999`,
     * `SITE.TEST` (buyuk harf), sondaki nokta ve bozuk portlu girdiler
     * yanlis son-ek karari veriyordu. Artik `PreBoot::normalizeHost()` tek
     * merkezden gecer ve gecersiz girdi **bos string** doner (fail-closed:
     * hicbir son-ek eklenmez).
     */
    private function normalizeIstekHost(): string
    {
        $raw = (string) ($_SERVER['HTTP_HOST'] ?? '');

        if (class_exists(\Rbn\Framework\Core\System\Kernel\Base\PreBoot::class)) {
            return \Rbn\Framework\Core\System\Kernel\Base\PreBoot::normalizeHost($raw);
        }

        // PreBoot yok: en kucuk guvenli indirgeme (port at, kucuk harf, sondaki nokta).
        return rtrim(strtolower((string) preg_replace('/:\d{1,5}$/', '', $raw)), '.');
    }

    /**
     * [GUVENLIK] Host, guvenilir koke gore uyuyor mu?
     *
     * Kural: HTTP_HOST/baseUrl, **kendi kendine** guvenilir bir kaynak DEGILDIR
     * (saldirgan yazabilir). Bu yuzden listeye eklenmeden once guvenilir koke
     * gore DOGRULANIR:
     *   - Tam eslesme                       -> guvenilir (kanonik host / grup alan adi).
     *   - Guvenilir host'un ALT ALAN adiyi -> guvenilir.
     *     (Bunu yapmazsak gercek "coklu alan adi" gecisleri kirilir:
     *      `app.site.example.test` -> `site.example.test` ve tersi.)
     *
     * Sonek tuzagi `notsite.example.test` veya `site.example.test.evil.example`
     * ALT ALAN ADI DEGILDIR (nokta ile baslamaz) -> reddedilir.
     *
     * @param array<int,array{host:string,port:int|null}> $kok
     */
    private function hostKokeUyuyorMu(string $host, array $kok): bool
    {
        $host = rtrim(strtolower($host), '.');
        if ($host === '') {
            return false;
        }

        foreach ($kok as $girdi) {
            $k = rtrim(strtolower((string) $girdi['host']), '.');
            if ($k === '') {
                continue;
            }
            if ($host === $k) {
                return true;
            }
            // Alt alan adi: "app.site.example.test" icin kok "site.example.test"
            if (str_ends_with($host, '.' . $k)) {
                return true;
            }
        }

        return false;
    }

    /**
     * [GUVENLIK] Liste normalize: buyuk/kucuk harf + sondaki nokta, bos kayitlari ele.
     *
     * @param array<int,array{host:string,port:int|null}> $liste
     * @return array<int,array{host:string,port:int|null}>
     */
    private function normalizeListe(array $liste): array
    {
        $temiz = [];
        foreach ($liste as $girdi) {
            $host = rtrim(strtolower((string) ($girdi['host'] ?? '')), '.');
            if ($host === '') {
                continue;
            }
            $temiz[] = ['host' => $host, 'port' => $girdi['port'] ?? null];
        }

        return $temiz;
    }

    /**
     * URL'den [host, port] cikarir.
     *
     * @return array{host:string,port:int|null}
     */
    private function urlHostPort(string $url): array
    {
        $host = parse_url($url, PHP_URL_HOST);

        return [
            'host' => is_string($host) ? $host : '',
            'port' => $this->urlPort($url),
        ];
    }

    /**
     * Port URL'de ACIKca yazilmissa onu, yoksa null doner.
     * (Sema (443/80) BILINCEKLI olarak null'dir: hedefte port yazilmadiginda
     *  karsilastirma yapilmasin, aksi halde yerel https redirect'leri kirilir.)
     */
    private function urlPort(string $url): ?int
    {
        $port = parse_url($url, PHP_URL_PORT);

        return is_int($port) ? $port : null;
    }
}