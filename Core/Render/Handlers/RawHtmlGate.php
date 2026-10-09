<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Render\Handlers;

/**
 * RawHtmlGate — veritabanindan gelen ham HTML icin ACIK kacis kapisi 🛡️
 *
 * R-04: `integrations` ayarlari (`google_analytics_code`, `head_scripts`,
 * `body_scripts`, `footer_scripts`, `google_adsense_code`) veritabaninda
 * saklanir ve duzenden `{!! !!}` ile OLDUGU GIBI basilir. Ayarin amaci
 * zaten ham HTML olmak (analytics snippet'i eklemek), dolayisiyla kacis
 * kapisi KALDIRILAMAZ — kaldirilirsa urun ozelligi bozulur.
 *
 * KAPI NE YAPAR:
 *   1. INLINE OLAY HANDLER'LARI siler: `<img onerror=...>` yazilabilir,
 *      hicbir meşru analytics kodu `on*=` kullanmaz.
 *   2. TEHLIKELI URI SEMALARINI etkisizlestirir: `javascript:`,
 *      `vbscript:`, `data:text/html` — HTML entity / backslash / kontrol
 *      karakteri kaçaklari dahil (`jav&#x09;ascript:` gibi).
 *   3. `<script>` BLOKLARINI KORUR (analytics/GTM/AdSense icin gerekli).
 *
 * YAZAN KISI: bu ayarlar `SettingsConfig`'te `required_role` = admin /
 * developer ile korunur, yani "yalnizca yonetici onayli alan" sartini
 * saglar. GATE ise ikinci katmandir: bir yonetici/developer hesabi
 * ele gecirilse veya CSRF ile yazilasa bile basit XSS vektorlari
 * calismaz.
 *
 * Meşru cikti DEGISMEZ (bkz. test `fw_render_ifade_koruma.php`).
 *
 * @see \Rbn\Framework\Core\Render\Providers\UI\FrontendProvider
 */
class RawHtmlGate
{
    /**
     * Yalnizca bu HTML etiketlerinde olasi oLAY HANDLER'i silinir.
     * Haric tutulanlar: `<script>` icerigi (JS kodu) ve yorumlar.
     *
     * @var string[]
     */
    private const EVENT_ATTR_PATTERN = '/(<[a-z][a-z0-9:-]*)((?:[^>"\']|"[^"]*"|\'[^\']*\')*?)(\s+on[a-z]+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+))((?:[^>"\']|"[^"]*"|\'[^\']*\')*?>)/i';

    /**
     * `href` / `src` / `action` / `formaction` / `xlink:href` gibi URI
     * tutan ozelliklerdeki tehlikeli sema.
     *
     * @var string
     */
    private const URI_ATTR_PATTERN = '/(<[a-z][a-z0-9:-]*\b[^>]*?\s(?:href|src|action|formaction|xlink:href|poster|data)\s*=\s*)(["\'])(.*?)\2/i';

    /** URI degerinde yasakli semalar (kontrol karakterleri/entity arindirilmis halde). */
    private const DANGEROUS_SCHEMES = ['javascript:', 'vbscript:', 'livescript:', 'mocha:', 'data:text/html', 'data:application/xhtml', 'data:image/svg+xml'];

    /**
     * Veritabanindan gelen ham HTML'i temizler.
     *
     * Meşru analytics kodu AYNEN korunur; yalnizca olay handler'lari ve
     * tehlikeli URI semalari notrlestirilir.
     */
    public function sanitize(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        // 1) Inline olay handler'larini kaldir (her etiketten).
        $once = $html;
        $html = $this->dongusuzUygula(
            $html,
            static function (string $s): string {
                return preg_replace(self::EVENT_ATTR_PATTERN, '$1$2$4', $s);
            }
        );

        // 2) Tehlikeli URI semali ozellikleri notrlestir.
        $html = $this->dongusuzUygula(
            $html,
            function (string $s): string {
                return preg_replace_callback(
                    self::URI_ATTR_PATTERN,
                    function (array $m): string {
                        $deger = $m[3];
                        if (!$this->uriTehlikeliMi($deger)) {
                            return $m[0];
                        }
                        return $m[1] . $m[2] . 'about:blank#rbn-blocked' . $m[2];
                    },
                    $s
                );
            }
        );

        return $html === null ? $once : $html;
    }

    /** İçerik kipinde (sayfa metni) tamamen kaldırılan etiketler: betik/gömme/belge başı. */
    private const CONTENT_BLOCK_TAGS = 'script|style|iframe|object|embed|applet|frame|frameset|noscript|template';
    private const CONTENT_VOID_TAGS = 'base|meta|link';

    /**
     * Veritabanından gelen SAYFA İÇERİĞİ (yasal metin, CMS gövdesi) için kapı.
     *
     * `sanitize()`'dan farkı: entegrasyon kodu değil düz içerik olduğu için `<script>`,
     * `<style>`, `<iframe>`, `<object>`/`<embed>` blokları ve `<base>`/`<meta>`/`<link>`
     * da KALDIRILIR (içerik metni bunlara ihtiyaç duymaz; yönetici hesabı ele geçirilse
     * de ziyaretçide betik çalışmaz). Başlık, paragraf, liste, tablo, bağlantı, görsel korunur.
     */
    public function sanitizeContent(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        $html = $this->dongusuzUygula(
            $html,
            static function (string $s): string {
                $s = (string) preg_replace('#<(' . self::CONTENT_BLOCK_TAGS . ')\b[^>]*>.*?</\1\s*>#is', '', $s);
                // Kapanmamış blok/boş etiket artıkları (kapanışsız `<script ...>` dahil).
                return (string) preg_replace('#</?(?:' . self::CONTENT_BLOCK_TAGS . '|' . self::CONTENT_VOID_TAGS . ')\b[^>]*>?#i', '', $s);
            }
        );

        return $this->sanitize($html);
    }

    /**
     * Temizlenmis HTML'de hala tehlikeli bir kalip var mi? (dogrulama/test)
     */
    public function hasDangerousMarkup(string $html): bool
    {
        if (preg_match(self::EVENT_ATTR_PATTERN, $html) === 1) {
            return true;
        }
        if (preg_match(self::URI_ATTR_PATTERN, $html, $m) === 1 && $this->uriTehlikeliMi($m[3])) {
            return true;
        }
        return false;
    }

    /**
     * `data` ozelligi yalnizca data-ozelliklerinde gecmesin diye ayrildi:
     * `data-*` ozellikleri zaten onceki desenle eslesmez.
     */
    private function uriTehlikeliMi(string $deger): bool
    {
        $d = $deger;
        // HTML entity kodlarini coz (jav&#x09;ascript: gibi kacaklari kapatir).
        $d = html_entity_decode($d, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        // Ters eğikli kacaklari ve kontrol karakterlerini temizle.
        $d = str_replace('\\', '', $d);
        $d = (string) preg_replace('/[\x00-\x20\x7F]+/', '', $d);
        $d = strtolower(trim($d));

        foreach (self::DANGEROUS_SCHEMES as $sema) {
            if (str_starts_with($d, $sema)) {
                return true;
            }
        }
        return false;
    }

    /**
     * `preg_replace` deseni bir kez daha uygulandiginda degisiyorsa
     * (ORN. silinen ozelligin bosluklari yeni bir eslesme uretmesi)
     * sinirli sayida tekrarlar; ASLA sonsuz dongu olusmaz.
     */
    private function dongusuzUygula(string $html, callable $adim): string
    {
        for ($i = 0; $i < 5; $i++) {
            $yeni = $adim($html);
            if (!is_string($yeni) || $yeni === $html) {
                return $yeni;
            }
            $html = $yeni;
        }
        return $html;
    }
}