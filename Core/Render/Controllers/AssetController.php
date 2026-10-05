<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Render\Controllers;

use Rbn\Framework\Core\Base\Web\BaseController;
use Rbn\Framework\Core\Http\Engine\Traits\Response\RedirectTrait;
use Rbn\Framework\Core\Render\Configs\AssetConfig;
use Rbn\Framework\Core\Support\Definitions\Render\AssetConvention;

/**
 * AssetController - High Performance Asset Proxy 🌐⚡⚓
 *
 * RBN 3.5 Masterpiece: Minimalist serving logic.
 * Serves physical assets with proper mime-types, banners and caching.
 *
 * [GÜVENLİK YAMASI 2026-10-02 · A0-5 · R-01] `media/<base64>` dalı kullanıcıdan
 * gelen base64'ı ÇÖZÜP `Location:` başlığına DOKUNMADAN yazıyordu. Bu bir **açık
 * yönlendirme**dir: saldırgan
 *   GET /project-assets/media/<b64 of "https://evil.example/">
 * deyip kullanıcıyı güvenilir alan adından dışarı çıkarabiliyordu; ayrıca
 *   `javascript:` / `data:` / `vbscript:` şemaları da geçtiği için aynı yol
 *   XSS'e de dönüşebilir (header splitting için kontrol karakteri de engellenmeli).
 *
 * Arttık: `Location` yazılmadan önce **3 katmanlı** doğrulama:
 *   1) kontrol karakteri / CRLF yok,
 *   2) sema `http`/`https` (veya aynı origin'e göreli yol) — `javascript:`/`data:` yok,
 *   3) host beyaz listede: **TEK KAYNAK** `RedirectTrait::guvenliHedef()`
 *      (aktif proje alan adı + grup alan adları + doğrulanmış istek host'u)
 *      **VEYA** açıkça tanımlanmış `AssetConfig::PROXY_ALLOWED_HOSTS` girdisinde.
 * Reddedilen hedef `Location` ÜRETMEZ — istek normal asset akışına düşer (404).
 * Dış CDN kullanımı kırılmasın diye beyaz liste **boş değil, yapılandırılabilir**.
 *
 * [GÜVENLİK YAMASI 2026-10-02 · A0-5 madde 2] `Cache-Control: ... immutable`
 * kaldırıldı. `immutable` tarayıcıya "bu yanıt asla değişmez" diyor; bir kez
 * önbelleğe alınan 307 hedefi, istediğimiz yeni bir hedefle **kalıcı olarak**
 * değiştirilemiyordu (cache poisoning kalıcı hâle geliyordu).
 */
class AssetController extends BaseController
{
    use RedirectTrait;
    /**
     * Serve a framework asset with minimal overhead.
     */
    public function serve(string $path, bool $isProject = false): void
    {
        // ⚖️ Masterpiece Versioning Shield: Strip versioning suffix (=v1.0 etc) 🛡️
        if (str_contains($path, '=')) {
            $path = explode('=', $path)[0];
        }

        // 🎼 RBN 3.5: [VIRTUAL ASSET HUB] 🏺🛰️⚓
        // 1. Sovereign Font Resolution: Masked URL to Remote Google URL 🎭⚓
        if (str_starts_with($path, 'fonts/')) {
            $fontSlug = str_replace('fonts/', '', $path);
            $library = \Rbn\Framework\Core\Support\Definitions\Render\AssetFonts::FONT_LIBRARY;

            foreach ($library as $name => $url) {
                $checkSlug = strtolower(str_replace(' ', '-', $name));
                if ($checkSlug === $fontSlug) {
                    // [A0-5 · R-01] Buradaki URL `AssetFonts::FONT_LIBRARY`
                    // sabitinden gelir (istekten GELMEZ), yani beyaz listeye
                    // sokmaya gerek yoktur. Yine de `Location` basligina giren
                    // her deger kontrol karakteri + sema denetiminden gecer:
                    // ileride kutuphane bir naberden beslenmeye baslarsa acik
                    // yonlendirme kapisi bu noktada sessizce acilmaz.
                    if (preg_match('/[\x00-\x1F\x7F]/', $url) === 1
                        || !in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true)) {
                        $this->abort(404, 'Asset not found (guvensiz font hedefi reddedildi)');
                        return;
                    }
                    header("Location: $url", true, 307);
                    exit;
                }
            }
        }

        // 2. Sovereign Media Resolution: Masked URL to Remote / Local Media Resource (Zero Inode / Browser & CDN Caching) 🎨🖼️⚓
        if (str_starts_with($path, 'media/')) {
            $parts = explode('/', str_replace('media/', '', $path));
            // Eğer kategori varsa (örn: media/actor/encoded/file) 2. eleman, yoksa (media/encoded/file) 1. eleman encoded'dır
            $encodedUrl = count($parts) > 2 ? $parts[1] : $parts[0];
            $rawUrl = base64_decode(strtr($encodedUrl, '-_', '+/'));

            if (!empty($rawUrl)) {
                // [A0-5 · R-01] Hedefi Location'a YAZMADAN once dogrula.
                $guvenliHedef = $this->guvenliProxyHedefi($rawUrl);
                if ($guvenliHedef === null) {
                    // Reddedildi: Location URETILMEZ (acik yonlendirme kapandi).
                    // Istek normal asset akisina dusur -> 404.
                    $this->abort(404, 'Asset not found (guvensiz medya hedefi reddedildi)');
                    return;
                }

                // ⚡ Sıfır İnode / Sıfır Disk Kullanımı: Tarayıcı & CDN Önbellek Başlıkları 🚀
                // [A0-5 madde 2] `immutable` KALDIRILDI: bir kez önbelleğe alınan 307
                // hedefi kalıcı olarak değiştirilemiyordu. 300 sn yeniden dogrulanabilir.
                header('Cache-Control: private, max-age=300');
                header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 300) . ' GMT');
                header('Location: ' . $guvenliHedef, true, 307);
                exit;
            }
        }

        // İster projede ister framework'te olsun, sanal kaynakları yakalıyoruz.
        if ($isProject && str_ends_with($path, '.svg')) {
            $this->serveVirtualResource($path);
            return;
        }

        // 🖼️ [FW-ASSET-KONVANSIYON] Sanal OpenGraph görseli:
        // `/project-assets/og-image-<project_key>.<png|jpg|webp>`.
        //
        // ÖLÇÜLEN KÖK HATA (05.10): bu dal YALNIZ `.svg` uçlantısında
        // açıldığı için raster og görseli fiziksel keşfe düşüyordu;
        // `AssetResolver` dosyayı proje kökünde arıyor, dosya `images/`
        // altında olduğu için "Asset not found" 404/500 dönüyordu.
        // `og:image` meta etiketi HER sitede ölü URL üretiyordu.
        //
        // GÜVENLİK: ad ve yol doğrulaması TEK YERDE —
        // `AssetConvention::resolveVirtualOgImage()`; orada `..`, mutlak yol,
        // ters bölü ve kök dışına çıkış reddedilir ([R-14] sertliği).
        if ($isProject && AssetConvention::isVirtualOgImageName($path)) {
            $this->serveVirtualOgImage($path);
            return;
        }

        // 1. Zemin Katmanına (Discovery) sor: "Bu dosya nerede?" 🕵️‍♂️
        $resolver = \Rbn\Framework\Core\System\Discovery\Engine\DiscoveryEngine::instance()->assets();
        $context = $isProject ? 'project' : 'framework';
        $detected = $resolver->resolve($path, $context);

        if (!$detected) {
            $this->abort(404, "Asset not found: " . htmlspecialchars($path) . " [Context: {$context}]");
            return;
        }

        $physicalPath = $detected->path;
        $label = $detected->label ?? 'RBN Framework';

        // 2. Uzantı ve Mime-Type Tespiti 🔍
        $extension = strtolower(pathinfo($physicalPath, PATHINFO_EXTENSION));
        $contentType = $this->getMimeType($extension);

        // 3. Dosya İçeriği ve "Sovereign Signature" 🔱🎨📝
        $content = file_get_contents($physicalPath);
        $content = $this->provider('asset')->decorate($content, $physicalPath, $label);

        // 4. HTTP Headers ve Dispatch 🚀
        header('Content-Type: ' . $contentType);
        header('Cache-Control: public, max-age=31536000'); // 1 Year Caching
        header('Content-Length: ' . strlen($content));
        header('X-RBN-Source: ' . $detected->source);

        echo $content;
        exit;
    }

    /**
     * [GÜVENLİK · A0-5 / R-01] `Location:` başlığına girecek bir varlık hedefini
     * doğrular. **TEK KAYNAK:** `RedirectTrait::guvenliHedef()` (aktif proje alan
     * adı + grup alan adları + doğrulanmış istek host'u).
     *
     * Kurallar:
     *  - kontrol karakteri / CRLF              -> RED
     *  - yalnız `http` / `https` şeması        -> RED (`javascript:`, `data:` kapanır)
     *  - host beyaz listede DEĞİLSE
     *      - `AssetConfig::PROXY_ALLOWED_HOSTS` içindeki bir host mu? -> KABUL
     *      - değilse                                                 -> RED
     * RED -> `null` döner; çağıran `Location` BAŞLIĞI ÜRETMEZ.
     *
     * Göreli (site içi) yollar serbesttir: `//host` ile başlamayan tek
     * bölü/çizgi ile başlayan yollar `guvenliHedef()` tarafından aynen
     * geçirilir (yerel `/storage/...` medya bu dalı kullanır), bu yüzden
     * şema beyaz listesi onlardan SONRA uygulanır.
     *
     * @return string|null
     */
    private function guvenliProxyHedefi(string $url): ?string
    {
        $url = trim($url);

        if ($url === '') {
            return null;
        }

        // 1) Kontrol karakteri / CRLF -> header splitting ve terminal kaçışı engellenir.
        if (preg_match('/[\x00-\x1F\x7F]/', $url) === 1) {
            return null;
        }

        // 2) [F-01] Ters bölü normalizasyonu. Tarayıcı (WHATWG URL) özel-şema
        //    girdilerinde `\`'i `/` sayar: `Location: /\evil.example/x` kullanıcıyı
        //    EVIL'e gönderir. Normalize edilmezse "göreli yol" sanılıp geçer.
        $url = str_replace('\\', '/', $url);

        // 3) Göreli (site içi) yol: tek bölü/çizgi ile başlar, şema-relative OLABİLMEZ.
        //    Yerel medya (`/storage/...`) bu dalı kullanır — olduğu gibi geçer.
        if (str_starts_with($url, '/') && !str_starts_with($url, '//')) {
            return $url;
        }

        // 4) Mutlak hedef: şema beyaz listesi — `javascript:` / `data:` / `vbscript:` yasak.
        $sema = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        if (!in_array($sema, ['http', 'https'], true)) {
            return null;
        }

        $host = rtrim(strtolower((string) parse_url($url, PHP_URL_HOST)), '.');
        if ($host === '') {
            return null;
        }

        // 3a) Ana beyaz liste: aktif proje alan adı + grup alan adları + istek host'u.
        if ($this->guvenliHedef($url) !== '/') {
            return $url;
        }

        // 3b) Yapılandırılmış dış kaynak listesi (CDN). İKİ KAYNAK BİRLİKTE:
        //     - `AssetConfig::PROXY_ALLOWED_HOSTS`: framework varsayılanı (boş).
        //     - AKTİF PROJENİN AYARI `project-settings.proxy_allowed_hosts`
        //       (`Config::get`; `has_route_map` açıkken `project-routemap.php`
        //       `view_mapping[<aktif project_key>]` değerleri project-settings
        //       üstüne yazıldığı için anahtar siteye özel de olabilir).
        //     Neden ayrı: motor geneldir; proje/müşteri adı ve CDN listesi
        //     framework'e YAZILMAZ, projenin kendi ayar dosyasında durur.
        //     `guvenliHedef()` bu listeyi bilmez; tam ALAN ADI ya da ALAN ADI
        //     SON EKI eşleşmesi gerekir (`notcdn.example.com` -> `cdn.example.com`
        //     son eki YANLIS eşleşme üretmesin diye son ek `.'.$sonEk` ile
        //     kontrol edilir).
        foreach (array_merge(
            AssetConfig::PROXY_ALLOWED_HOSTS,
            $this->projectAllowedProxyHosts()
        ) as $izin) {
            $izin = rtrim(strtolower(trim((string) $izin)), '.');
            if ($izin === '') {
                continue;
            }
            if ($host === $izin || str_ends_with($host, '.' . $izin)) {
                return $url;
            }
        }

        // 4) Fail-closed.
        return null;
    }

    /**
     * [PROJE YAPILANDIRMASI · A0-5] Aktif projenin beyaz liste girdisini okur:
     * `Config::get('project-settings.proxy_allowed_hosts')` -> host dizisi.
     *
     * MOTOR GENEL KALIR: framework'e proje/müşteri adı ya da host listesi
     * YAZILMAZ (Anayasa §9). Host'lar projenin kendi ayar dosyasında
     * (`project-settings.php` / `project-routemap.php`) durur; burada yalnız
     * OKUNUR ve normalize edilir.
     *
     * DEĞER KURALLARI (yalnız host; şema/yol/port YAZILMAZ):
     *  - `project-settings.php` `has_route_map` açıksa `Config::get`, aktif
     *    `project_key` için `project-routemap.php` `view_mapping` değerlerini
     *    project-settings ÜSTÜNE yazar; anahtar bu yüzden siteye özel de
     *    verilebilir.
     *  - GEÇERSİZ girdi (dizi olmayan, boş, şema/port/yol/boşluk/kontrol
     *    karakteri içeren eleman) **SESSİZCE** yok sayılır — beyaz liste
     *    genişlemez, hata fırlatılmaz, gürültü üretilmez.
     *  - Eşleşme kuralı framework sabitiyle AYNI: tam host veya `.` son ek.
     *
     * @return array<int,string> normalize edilmiş, buyuk/kucuk harf ve sondaki
     *                           noktadan arindirilmis host listesi
     */
    private function projectAllowedProxyHosts(): array
    {
        try {
            $ayarlar = \Rbn\Framework\Core\System\Config\Config::get('project-settings.proxy_allowed_hosts');
        } catch (\Throwable $e) {
            // Ayar okunamazsa beyaz liste BOS kalir (fail-closed).
            return [];
        }

        if (!is_array($ayarlar)) {
            return [];
        }

        $temiz = [];
        foreach ($ayarlar as $izin) {
            // Yalnizca duz string kabul; sayi/nesne/dizi elemanlari yoksayilir.
            if (!is_string($izin)) {
                continue;
            }
            // Kontrol karakteri / CRLF -> sessizce yok say.
            if (preg_match('/[\x00-\x1F\x7F]/', $izin) === 1) {
                continue;
            }
            $izin = rtrim(strtolower(trim($izin)), '.');
            // Bosluk, sema (`https://`), yol (`/a.jpg`), port (`:8443`) ve
            // kullanici/fragment kalintilari -> bu bir HOST degildir.
            if ($izin === '' || preg_match('/^[a-z0-9._-]+$/', $izin) !== 1) {
                continue;
            }
            // Kaba sekilde supheli: ard arda nokta, basta/ sonda tire.
            if (str_contains($izin, '..') || str_starts_with($izin, '.')
                || str_starts_with($izin, '-') || str_ends_with($izin, '-')) {
                continue;
            }
            $temiz[] = $izin;
        }

        return $temiz;
    }

    /**
     * Serve a project-specific asset.
     */
    public function serveProject(string $path): void
    {
        $this->serve($path, true);
    }

    /**
     * Determine Content-Type using global Mime Definitions. 🛰️⚓
     */
    protected function getMimeType(string $ext): string
    {
        // 🏛️ RBN 3.5: [CENTRALIZED DEFINITIONS] 🛰️⚓
        $mimeMap = $this->validation('mime.MAP');

        if (is_array($mimeMap) && isset($mimeMap[$ext][0])) {
            return $mimeMap[$ext][0];
        }

        return match ($ext) {
            'css' => 'text/css',
            'js' => 'text/javascript',
            'ico' => 'image/x-icon',
            'svg' => 'image/svg+xml',
            default => 'application/octet-stream'
        };
    }

    /**
     * Deliver a Virtual Resource (SVG from Database/Code). 🏺🛰️⚓
     */
    /**
     * [R-14] Bir basligin DEGERI olarak basilacak metni guvenli hale getirir.
     *
     *  - kontrol karakteri / CRLF ayiklanir (baslik satiri enjeksiyonu)
     *  - yolun yalnizca SON SEGMENTI kalir (dizin yapisini sizdirmaz)
     *  - sonuc bos ise sabit bir yer tutucu doner (bos baslik yazmaz)
     */
    private function guvenliBaslikDegeri(string $deger): string
    {
        $temiz = (string) preg_replace('/[\x00-\x1F\x7F]/', '', $deger);
        $temiz = basename(str_replace('\\', '/', $temiz));
        $temiz = trim($temiz);

        if ($temiz === '' || $temiz === '.' || $temiz === '..') {
            return 'bilinmiyor';
        }

        // PHP `header()` zaten CRLF'i engeller; yine de tek satirlik
        // deger garantisi veriyoruz.
        return substr($temiz, 0, 255);
    }

    /**
     * [FW-ASSET-KONVANSIYON] Sanal OpenGraph görselini DOĞRU `Content-Type`
     * ile sunar (`/project-assets/og-image-<key>.<png|jpg|webp>`).
     *
     * Önceki hâlde raster og görselleri fiziksel keşfe düşüyor ve her sitede
     * 404/500 dönüyordu; sosyal paylaşım önizlemesi görselsiz kalıyordu.
     *
     * Çözümleme ve yol güvenliği TEK YERDE (`AssetConvention`); burada yalnız
     * HTTP teslimi yapılır. Bulunamazsa 404 (uydurma görsel üretilmez).
     */
    private function serveVirtualOgImage(string $name): void
    {
        $fiziksel = AssetConvention::resolveVirtualOgImage($name);

        if ($fiziksel === null) {
            $this->abort(404, 'Asset not found: ' . $this->guvenliBaslikDegeri($name));
            return;
        }

        $contentType = AssetConvention::ogImageMimeType($name);
        if ($contentType === null) {
            $this->abort(404, 'Asset not found: ' . $this->guvenliBaslikDegeri($name));
            return;
        }

        $content = @file_get_contents($fiziksel);
        if ($content === false || $content === '') {
            $this->abort(404, 'Asset not found: ' . $this->guvenliBaslikDegeri($name));
            return;
        }

        header('Content-Type: ' . $contentType);
        header('Cache-Control: public, max-age=31536000');
        header('Content-Length: ' . strlen($content));
        header('X-RBN-Source: ProjectConvention');
        // [R-14] Tanılama başlığı: ad HTTP YOLUNDAN gelir; kontrol karakteri
        // ayıklanır ve `basename()` ile sınırlandırılır (içerik ETKİLENMEZ).
        header('X-RBN-Resource: ' . $this->guvenliBaslikDegeri($name));

        echo $content;
        exit;
    }

    private function serveVirtualResource(string $name): void
    {
        // 🎼 RBN 3.5: [SOVEREIGN RESOURCE DISPATCH] 🏛️🛰️
        // 1. Controller artık isim bazlı (favicon.svg, logo.svg) veri talep eder.
        $svgData = $this->resolver('seo')->getVirtualResourceRaw($name);

        // 2. Code Defaults Fallback (If service returns empty or non-svg)
        if (empty($svgData) || (!str_contains($svgData, '<svg') && !str_contains($svgData, 'data:image/svg'))) {
            // Default to Logo if no specific resource found
            $svgData = \Rbn\Framework\Core\Render\Configs\SeoConfig::LOGO_SVG;
        }

        // 3. Clean up (Remove data URI prefix if exists)
        if (str_starts_with($svgData, 'data:image/svg+xml')) {
            $commaPos = strpos($svgData, ',');
            if ($commaPos !== false) {
                $svgData = substr($svgData, $commaPos + 1);
            } else {
                $tagPos = strpos($svgData, '<svg');
                if ($tagPos !== false) {
                    $svgData = substr($svgData, $tagPos);
                }
            }
            $svgData = rawurldecode($svgData);
        }

        // 4. Content Seal & Delivery
        $content = trim($svgData);
        $header = 'image/svg+xml; charset=utf-8';

        header('Content-Type: ' . $header);
        header('Cache-Control: public, max-age=31536000'); // 1 Year Caching
        header('Content-Length: ' . strlen($content));
        header('X-RBN-Source: VirtualHub');
        // [R-14] `$name` HTTP YOLUNDAN gelir (`{path:.+}` -> `serveVirtualResource`).
        // Ham basildiginda istemci basligi satiri (`\r\n`) veya kontrol karakteri
        // enjekte edebilir, ayrica dis dizin yapisi sizabilir. Rapor bu basligi
        // yalnizca tanilama icin kullaniliyor; degeri `basename()` ile sinirlandirip
        // kontrol karakterlerinden arindiriyoruz. Icrik zaten `$svgData`'dan gelir,
        // bu basliktan ETKILENMEZ.
        header('X-RBN-Resource: ' . $this->guvenliBaslikDegeri($name));

        echo $content;
        exit;
    }
}
