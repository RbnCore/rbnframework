<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnEmail\Handlers;

use Rbn\Framework\Core\Base\BaseComponent;
use Rbn\Framework\Packages\RbnEmail\Models\MailHtmlConstant;

/**
 * MailHtmlSanitizerHandler - Gelen e-posta HTML'ini allowlist ile temizler.
 *
 * Yalnız izin verilen etiket ve öznitelikler kalır; `script`, `iframe`,
 * `object`, `form`, `on*` öznitelikleri, `javascript:`/`vbscript:` ve
 * görsel dışı `data:` adresleri atılır. Uzak görseller varsayılan olarak
 * ENGELLİDİR: `src` → `data-blocked-src`. Bağlantılar yeni sekmede ve
 * `rel="noopener noreferrer nofollow"` ile açılır.
 *
 * Çıktı yine de sandbox'lı iframe içinde gösterilmelidir (ikinci katman).
 */
class MailHtmlSanitizerHandler extends BaseComponent
{
    /**
     * @return array{html:string, remote_blocked:bool}
     */
    public function sanitize(string $html, bool $allowRemoteImages = false): array
    {
        if (trim($html) === '') {
            return ['html' => '', 'remote_blocked' => false];
        }
        if (!class_exists(\DOMDocument::class)) {
            // DOM uzantısı yoksa güvenli geri dönüş: düz metne çevir.
            $text = htmlspecialchars(trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8')), ENT_QUOTES, 'UTF-8');
            return ['html' => nl2br($text), 'remote_blocked' => false];
        }

        $doc = new \DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        // Sarmalayıcı YOK: iletideki sahipsiz `</div>`/`</body>` bir sarmalayıcıyı
        // kapatıp sonrasını sildiriyordu (EMAIL-T4 F-B4). Ayrıştırıcının kurduğu
        // `<body>` gezilir; body yoksa kök eleman. `</body>`/`</html>` atılır:
        // libxml sonrasını body DIŞINA koyar, HTML5 tarayıcısı ise body'de tutar.
        $html = (string) preg_replace('#</\s*(body|html)\s*>#i', '', $html);
        // Açık `<html><body>` öneki: düz metne örtük `<p>` eklenmez; iletinin kendi
        // `<head>`/`<body>` etiketleri bu body'ye katlanır (`title`/`style` sonra atılır).
        $doc->loadHTML('<?xml encoding="UTF-8"><!DOCTYPE html><html><body>' . $html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $doc->getElementsByTagName('body')->item(0) ?? $doc->documentElement;
        if ($root === null) {
            return ['html' => '', 'remote_blocked' => false];
        }

        $blocked = false;
        $this->cleanChildren($root, $allowRemoteImages, $blocked);

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $doc->saveHTML($child);
        }
        return ['html' => $out, 'remote_blocked' => $blocked];
    }

    private function cleanChildren(\DOMNode $node, bool $allowRemote, bool &$blocked): void
    {
        // Canlı liste üzerinde silme yapıldığı için önce kopyala.
        $children = [];
        foreach ($node->childNodes as $child) {
            $children[] = $child;
        }

        foreach ($children as $child) {
            if ($child instanceof \DOMComment || $child instanceof \DOMProcessingInstruction || $child instanceof \DOMCdataSection) {
                $node->removeChild($child);
                continue;
            }
            if (!$child instanceof \DOMElement) {
                continue;
            }

            $tag = strtolower($child->tagName);
            if (in_array($tag, MailHtmlConstant::DROP_WITH_CONTENT, true)) {
                $node->removeChild($child);
                continue;
            }
            if (!isset(MailHtmlConstant::ALLOWED_TAGS[$tag])) {
                // İzinsiz etiket: içeriği koru, etiketi aç.
                $this->cleanChildren($child, $allowRemote, $blocked);
                while ($child->firstChild !== null) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);
                continue;
            }

            $this->cleanAttributes($child, $tag, $allowRemote, $blocked);
            $this->cleanChildren($child, $allowRemote, $blocked);
        }
    }

    private function cleanAttributes(\DOMElement $el, string $tag, bool $allowRemote, bool &$blocked): void
    {
        $allowed = array_merge(MailHtmlConstant::GLOBAL_ATTRIBUTES, MailHtmlConstant::ALLOWED_TAGS[$tag]);
        $names = [];
        foreach ($el->attributes as $attr) {
            $names[] = $attr->nodeName;
        }

        foreach ($names as $name) {
            $lower = strtolower($name);
            $value = (string) $el->getAttribute($name);
            if (!in_array($lower, $allowed, true) || str_starts_with($lower, 'on')) {
                $el->removeAttribute($name);
                continue;
            }
            if ($lower === 'style') {
                $clean = $this->cleanStyle($value);
                $clean === '' ? $el->removeAttribute($name) : $el->setAttribute($name, $clean);
                continue;
            }
            if ($lower === 'href') {
                if (!$this->isSafeUrl($value, ['http', 'https', 'mailto', 'tel'])) {
                    $el->removeAttribute($name);
                }
                continue;
            }
            if ($lower === 'src' || $lower === 'background') {
                $el->removeAttribute($name);
                if ($this->isInlineImage($value) || str_starts_with(strtolower(trim($value)), 'cid:')) {
                    $el->setAttribute($name, $value);
                } elseif ($this->isSafeUrl($value, ['http', 'https'])) {
                    if ($allowRemote) {
                        $el->setAttribute($name, $value);
                    } else {
                        $el->setAttribute('data-blocked-' . $lower, $value);
                        $blocked = true;
                    }
                }
            }
        }

        if ($tag === 'a' && $el->hasAttribute('href')) {
            $el->setAttribute('target', '_blank');
            $el->setAttribute('rel', 'noopener noreferrer nofollow');
        }
    }

    /**
     * Satır içi stil, İZİN LİSTESİYLE: yalnız `MailHtmlConstant::STYLE_PROPERTIES`
     * / `STYLE_PROPERTY_PREFIXES` adlı bildirimler kalır; değerinde CSS kaçışı,
     * `rgb(`/`rgba(` dışı parantez ya da görsel işlevi geçen bildirim atılır.
     * Ters ölçüt (yalnız `url(` aramak) `u\72l(` ve `image-set(` ile aşılıyordu
     * (EMAIL-T4 F-B2). CSS görseli hiçbir durumda yüklenmez, bu yüzden
     * `remote_blocked` bayrağını etkilemez: uzak görsel izni yalnız `<img src>`
     * ve `background` özniteliği içindir.
     */
    private function cleanStyle(string $style): string
    {
        $out = [];
        foreach (explode(';', $style) as $decl) {
            $decl = trim($decl);
            $colon = strpos($decl, ':');
            if ($colon === false) {
                continue;
            }
            $name = strtolower(trim(substr($decl, 0, $colon)));
            $value = trim(substr($decl, $colon + 1));
            // Yalnız renk taşıyan `background` (bültenlerde koyu zemin) rengi kalsın;
            // atılırsa açık zeminde açık renkli yazı okunmaz.
            if ($name === 'background' && preg_match(MailHtmlConstant::STYLE_COLOR_ONLY, $value)) {
                $name = 'background-color';
            }
            if ($value === '' || !$this->isAllowedStyleProperty($name)) {
                continue;
            }
            $probe = (string) preg_replace('/\b(rgba?)\(/i', '$1 ', preg_replace('#/\*.*?\*/#s', '', $value));
            if (preg_match(MailHtmlConstant::STYLE_VALUE_DENY, $probe)) {
                continue;
            }
            $out[] = $name . ':' . $value;
        }
        return implode('; ', $out);
    }

    private function isAllowedStyleProperty(string $name): bool
    {
        if (!preg_match('/^[a-z-]+$/', $name)) {
            return false;
        }
        if (in_array($name, MailHtmlConstant::STYLE_PROPERTIES, true)) {
            return true;
        }
        foreach (MailHtmlConstant::STYLE_PROPERTY_PREFIXES as $prefix) {
            if (str_starts_with($name, $prefix)) {
                return true;
            }
        }
        return false;
    }

    private function isSafeUrl(string $url, array $schemes): bool
    {
        $url = trim(html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $compact = strtolower((string) preg_replace('/[\x00-\x20]+/', '', $url));
        if ($compact === '' || str_starts_with($compact, '#')) {
            return $compact !== '';
        }
        if (!preg_match('/^([a-z][a-z0-9+.-]*):/', $compact, $m)) {
            // Göreli adres: e-postada anlamsız ama zararsız; protokolsüz "//" engellenir.
            return !str_starts_with($compact, '//');
        }
        return in_array($m[1], $schemes, true);
    }

    private function isInlineImage(string $value): bool
    {
        return (bool) preg_match('#^data:image/(png|gif|jpe?g|webp);base64,[a-z0-9+/=\s]+$#i', trim($value));
    }
}
