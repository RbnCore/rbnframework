<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnFactory\Models;

/**
 * FactoryContract - Site ↔ VPS JSON sözleşme alan adları (TEK eşleme yeri) 🤝
 *
 * Anahtar = paketin kendi (İngilizce) adı, değer = VPS (Go) tarafının bugün konuştuğu alan adı.
 * Go tarafı değişince yalnız bu dosya güncellenir; paketin başka hiçbir yerinde sözleşme adı yazılmaz.
 */
final class FactoryContract
{
    /** jobs cevabı: iş zarfı */
    public const JOBS_ENVELOPE = 'isler';

    /** jobs cevabı: sitedeki mevcut yazılar (VPS benzersizlik kapısı için) */
    public const EXISTING_POSTS_ENVELOPE = 'existing_posts';

    /** mevcut yazı satırı */
    public const EXISTING_POST = [
        'slug'       => 'slug',
        'meta_title' => 'meta_title',
        'title'      => 'title',
    ];

    /** jobs cevabındaki her iş */
    public const JOB = [
        'job_no'          => 'is_no',
        'topic'           => 'konu',
        'content_type'    => 'icerik_turu',
        'image_dir'       => 'gorsel_dizini',
        'site_docroot'    => 'site_docroot',
        'site_note'       => 'site_notu',
        'location'        => 'location',
        'site_domain'     => 'site_alan_adi',
        'tone'            => 'ton',
        'banned_words'    => 'yasaklilar',
        'options'         => 'secenekler',
        'internal_links'  => 'ic_linkler',
        'link_candidates' => 'ic_baglanti_adaylari',
    ];

    /**
     * jobs cevabında YENİLEME işine eklenen alanlar (standart iş alanlarının ÜSTÜNE): eski içerik GÖNDERİLMEZ.
     * `kind` yalnız yenilemede gelir; yeni yazı işinde bulunmaz.
     */
    public const JOB_RENEW = [
        'kind'         => 'kind',
        'post_id'      => 'post_id',
        'title'        => 'title',
        'category'     => 'category',
        'current_slug' => 'current_slug',
        'url'          => 'url',
        'has_cover'    => 'has_cover',
    ];

    /** yenileme işindeki kategori nesnesi */
    public const JOB_RENEW_CATEGORY = ['name' => 'name', 'slug' => 'slug'];

    /** `kind` değeri: mevcut yazının yenilenmesi */
    public const KIND_RENEW = 'renew';

    /** iç bağlantı adayı */
    public const CANDIDATE = [
        'title'    => 'baslik',
        'url'      => 'url',
        'summary'  => 'ozet',
        'category' => 'kategori',
    ];

    /** result gövdesi (VPS → site) */
    public const RESULT = [
        'job_no'         => 'is_no',
        'content_type'   => 'icerik_turu',
        'status'         => 'durum',
        'error'          => 'hata',
        'title'          => 'baslik',
        'slug'           => 'slug',
        'summary'        => 'ozet',
        'html'           => 'html',
        'images'         => 'gorseller',
        'post_images'    => 'images',
        'faqs'           => 'sss',
        'meta'           => 'meta',
        'tags'           => 'etiketler',
        'cta'            => 'cta',
        'pros_cons'      => 'pros_cons',
        'author_comment' => 'analiz',
    ];

    /** result gövdesinde yenilemeye özgü alanlar: `kind` + `post_id` iş ile AYNEN eşleşmeli; `new_slug` yalnız eski slug bozuksa gelir */
    public const RESULT_RENEW = [
        'kind'     => 'kind',
        'post_id'  => 'post_id',
        'new_slug' => 'new_slug',
    ];

    public const IMAGE = ['role' => 'rol', 'path' => 'yol'];

    /** Yazı içi görsel (`images` listesi öğesi): içeriğe GÖMÜLMEZ, `after_section` bölümünün sonunda gösterilir */
    public const POST_IMAGE = ['src' => 'src', 'alt' => 'alt', 'after_section' => 'after_section', 'width' => 'width', 'height' => 'height'];

    /** `images` listesi üst sınırı: yeni yazıda en çok 2, yenilemede TAM 1 */
    public const POST_IMAGES_MAX = 2;
    public const POST_IMAGES_RENEW = 1;
    public const FAQ = ['question' => 'soru', 'answer' => 'cevap'];
    public const META = ['title' => 'baslik', 'description' => 'aciklama'];

    /** result `durum` değerleri (VPS → site) */
    public const RESULT_STATUS = ['ok' => 'ok', 'error' => 'hata'];

    /** görsel rolleri */
    public const IMAGE_ROLE = ['cover' => 'kapak', 'content' => 'icerik'];

    /** result cevabı (site → VPS) */
    public const REPLY = [
        'status'       => 'durum',
        'job_no'       => 'is_no',
        'scheduled_at' => 'planlanan_yayin',
        'error'        => 'hata',
    ];

    /** result cevabı durum değerleri */
    public const REPLY_STATUS = [
        'received'         => 'alindi',
        'already_received' => 'zaten-alindi',
        'error_recorded'   => 'hata-kaydedildi',
    ];
}
