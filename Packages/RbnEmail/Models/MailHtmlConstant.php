<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnEmail\Models;

/**
 * MailHtmlConstant - E-posta HTML temizleyicisinin allowlist'i.
 */
class MailHtmlConstant
{
    /** İçeriğiyle birlikte tamamen atılan etiketler. */
    public const DROP_WITH_CONTENT = [
        'script', 'style', 'iframe', 'frame', 'frameset', 'object', 'embed', 'applet',
        'form', 'input', 'button', 'select', 'textarea', 'option', 'link', 'meta', 'base',
        'svg', 'math', 'template', 'noscript', 'head', 'title', 'audio', 'video', 'source', 'track', 'portal',
    ];

    /**
     * Her izinli etikette kalabilen öznitelikler.
     * `class` YOK: ileti stil sayfası taşıyamaz (`style` etiketi atılır), sınıf
     * adı yalnız panelin kendi CSS'ine bağlanıp görünümü bozmaya yarardı (EMAIL-T4 F-B3).
     */
    public const GLOBAL_ATTRIBUTES = ['style', 'title', 'dir', 'lang', 'align', 'valign', 'width', 'height', 'bgcolor', 'color'];

    /**
     * Satır içi stilde kalabilen bildirimler (izin listesi, EMAIL-T4 F-B2/F-B3).
     * `position`, `z-index`, `background(-image)`, `content` vb. YOK: ekran
     * bindirmesi ve CSS ile uzak istek kapısı kapalı.
     */
    public const STYLE_PROPERTIES = ['color', 'background-color', 'line-height', 'width', 'height', 'vertical-align', 'white-space'];

    /** Bu öneklerle başlayan bildirimler de kalır (`margin-top`, `border-left-color`...). */
    public const STYLE_PROPERTY_PREFIXES = ['font-', 'text-', 'margin', 'padding', 'border'];

    /**
     * Değerinde bunlardan biri geçen bildirim atılır. `(` yalnız `rgb(`/`rgba(`
     * çıkarıldıktan sonra aranır; `\` CSS kaçışıdır (`u\72l(` = `url(`).
     */
    /** `background` kısaltmasında tek renk değeri (`#fff`, `black`, `rgb(...)`) → `background-color`. */
    public const STYLE_COLOR_ONLY = '/^(#[0-9a-f]{3,8}|[a-z]+|rgba?\([0-9.,%\s]*\))(\s*!important)?$/i';

    public const STYLE_VALUE_DENY = '/[\\\\(]|image-set|image\(|cross-fade|element\(|src\(|javascript:|vbscript:|@import/i';

    /** İzinli etiket => ek öznitelikler. */
    public const ALLOWED_TAGS = [
        'a' => ['href', 'name'],
        'abbr' => [], 'address' => [], 'b' => [], 'big' => [], 'blockquote' => ['cite'], 'br' => [],
        'caption' => [], 'center' => [], 'cite' => [], 'code' => [], 'col' => ['span'], 'colgroup' => ['span'],
        'dd' => [], 'del' => [], 'div' => [], 'dl' => [], 'dt' => [], 'em' => [],
        'font' => ['face', 'size'], 'h1' => [], 'h2' => [], 'h3' => [], 'h4' => [], 'h5' => [], 'h6' => [],
        'hr' => ['size', 'noshade'], 'i' => [], 'img' => ['src', 'alt', 'border', 'hspace', 'vspace'],
        'ins' => [], 'kbd' => [], 'li' => ['value'], 'mark' => [], 'ol' => ['start', 'type'], 'p' => [],
        'pre' => [], 'q' => [], 's' => [], 'small' => [], 'span' => [], 'strike' => [], 'strong' => [],
        'sub' => [], 'sup' => [],
        'table' => ['border', 'cellpadding', 'cellspacing', 'background', 'summary'],
        'tbody' => [], 'td' => ['colspan', 'rowspan', 'background', 'nowrap'], 'tfoot' => [],
        'th' => ['colspan', 'rowspan', 'background', 'scope'], 'thead' => [], 'tr' => [],
        'tt' => [], 'u' => [], 'ul' => ['type'], 'wbr' => [],
        'article' => [], 'section' => [], 'header' => [], 'footer' => [], 'main' => [], 'nav' => [],
        'figure' => [], 'figcaption' => [], 'picture' => [],
    ];
}
