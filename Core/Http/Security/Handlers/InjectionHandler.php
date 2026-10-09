<?php
declare(strict_types=1);

namespace Rbn\Framework\Core\Http\Security\Handlers;

use Rbn\Framework\Core\Base\BaseComponent;

/**
 * InjectionHandler - The Input Guard Actor 🛡️🏮
 * 
 * RBN Framework: Atomic actor for XSS detection and injection filtering.
 */
class InjectionHandler extends BaseComponent
{
    /**
     * [F-05 · 2026-10-03 · team member] XSS kalıp kümesi.
     *
     * TABAN: yalnız 2 regex vardı — `/<\s*script/i` ve `/(\bon\w+\s*=)/i`.
     * Ölçümde 10 saldırı vektörünün 7'si bu ağdan GEÇİYORDU
     * (`javascript:alert(1)`, `data:text/html;base64,…`, `<object data=…>`,
     *  `<style>expression(…)</style>`, `<iframe srcdoc=…>`, `<embed src=…>`,
     *  `<form action="javascript:…">`).
     *
     * TASARIM KURALI — YANLIŞ POZİTİF ÖNCELİĞİ:
     * `detectXss()` TEK çağrı yerinde kullanılıyor:
     * `FormGuardHandler::audit()` (:152) → `detectXss($this->post)`, yani
     * formun **TAMAMI** (uzun metin, açıklama, mesaj gövdeleri dahil).
     * Bu yüzden serbest metinde geçen kelimeler (`javascript öğreniyorum`,
     * `data: gelecek hafta`, `online = true olmalı`) ENGELLENMEMELİDİR.
     * Kurallar:
     *   • şema (`javascript:`) YALNIZCA bir URL niteliğinin değeri (`href=`,
     *     `src=`, `action=`, `data=`…) ya da boşluksuz çağrı biçimi
     *     (`javascript:alert(`) olduğunda aranır;
     *   • `on…=` YALNIZCA etikişİÇİNDE aranır → tabandaki `\bon\w+\s*=`
     *     deseni "online = true olmalı" gibi meşru Türkçe metni yanlış
     *     pozitif olarak engelliyordu; bu düzeltildi;
     *   • tehlikeli etiketler (iframe/object/svg/…) adı zorunlu olarak
     *     aranır → "Merhaba, <3 kalbim" gibi yazılar tetiklenmez.
     *
     * DİZİ İÇİ DÜZEYLER: taban yalnız üst düzey `string` değerlere bakıyordu;
     * `foo[]=…` / `foo[bar]=…` gönderilen iç içe diziler TARANMIYORDU (atlatma).
     * Artık özyinelemeli taranır.
     *
     * @var string[] PCRE kalıpları (ilk eşleşmede yakalanır)
     */
    private const XSS_PATTERNS = [
        // 1) Zararlı etiketler (açılış VE kapanış): script, iframe, object, embed,
        //    applet, svg, math, base, meta, link, style, frame(set), form
        '#<\s*/?\s*(?:script|iframe|object|embed|applet|svg|math|base|meta|link|style|frame|frameset|form)\b#i',

        // 2) Etiket İÇİNDE olay niteliği: <img … onerror=…>, <svg/onload=…>,
        //    <input onfocus =…>, <b>"onmouseover=…
        //    Serbest metindeki "online = true olmalı" burada TETİKLENMEZ.
        '#<[a-z][^>]*?[\s/\'"(]on[a-z]+\s*=#i',

        // 3) URL niteliği bağlamında scripting şeması
        '#(?:href|src|action|formaction|data|srcdoc|xlink:href|background|poster|codebase)\s*=\s*["\']?\s*(?:javascript|vbscript|livescript|mocha)\s*:#i',

        // 4) URL niteliği bağlamında data:text/html (base64 yükü dâhil)
        '#(?:href|src|action|formaction|data|srcdoc|xlink:href)\s*=\s*["\']?\s*data\s*:\s*text/html#i',

        // 5) Boşluksuz çağrı biçimi (tırnak/öznitelik bağlamı olmayan):
        //    javascript:alert(1) · vbscript:msgbox(1)
        '#\b(?:javascript|vbscript|livescript|mocha)\s*:\s*[a-z0-9_.$]*\s*\(#i',

        // 6) Serbest metinde data:text/html (payload zaten taşınıyor)
        '#\bdata\s*:\s*text/html\b#i',

        // 7) IE expression() ve srcdoc= yükleri
        '#\bexpression\s*\(#i',
        '#\bsrcdoc\s*=#i',
    ];

    /**
     * Check for XSS patterns in the given data.
     *
     * @return bool true = temiz, false = olası enjeksiyon tespit edildi
     */
    public function detectXss(array $data): bool
    {
        foreach ($data as $value) {
            if ($this->looksUnsafe($value)) {
                return false; // Possible injection detected
            }
        }
        return true;
    }

    /**
     * [F-05] Tek bir değeri (iç içe diziler dâhil) tara.
     */
    private function looksUnsafe($value): bool
    {
        // [F-05] İç içe diziler de taranır: `foo[bar][]=…` gönderilen alanlar
        // üst düzey string değil dizi olduğu için tabanda atlanıyordu.
        if (is_array($value)) {
            foreach ($value as $item) {
                if ($this->looksUnsafe($item)) {
                    return true;
                }
            }
            return false;
        }

        if (!is_string($value) || $value === '') {
            return false;
        }

        foreach (self::XSS_PATTERNS as $pattern) {
            // `=== 1`: preg_match hata durumunda false döner, "eşleşti" sayılmaz.
            if (preg_match($pattern, $value) === 1) {
                return true;
            }
        }

        return false;
    }
}