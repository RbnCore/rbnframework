<?php

declare(strict_types=1);

namespace Rbn\Project\Core\Tasks;

/**
 * İçerik Fabrikası — SİTE AYAR DOSYASI ŞABLONU (tek site, anahtarsız).
 *
 * Kullanım: projenin `Core/Tasks/` klasörüne `<ProjectKey>Factory.php` adıyla kopyalayın ve sınıf adını aynı yapın
 * (ör. project_key `siteadi` ise dosya `SiteadiFactory.php`, sınıf `SiteadiFactory`). Her site için AYRI dosya.
 * İmza anahtarı bu dosyada DEĞİL, projenin `z_settings_api` tablosunda: setting_type `factory`, group_key `factory`,
 * setting_key `FACTORY_SIGNING_KEY`, project_key = sitenin anahtarı, setting_value = en az 32 karakter (is_active=1).
 * Ayar dosyası ya da etkin anahtar kaydı yoksa fabrika o sitede kapalıdır (404).
 */
final class ExampleFactory
{
    public const SETTINGS = [
        // --- Bağlantı ve güvenlik (imza anahtarı burada DEĞİL: z_settings_api FACTORY_SIGNING_KEY) ---
        'site_code'       => 'site-kodu',          // VPS'in X-Fab-Site başlığında göndereceği site kodu
        'clock_tolerance' => 300,                  // İstek saatinin kabul edilen sapması (saniye)
        'max_body_bytes'  => 2000000,              // result gövdesi üst sınırı (bayt)
        'site_hosts'      => [                     // Sitenin kendi adresleri (iç bağlantı ve görsel denetimi için)
            'site.example',
            'www.site.example',
        ],

        // --- Tablolar ---
        'tables' => [
            'drafts'  => 'app_content_drafts',     // Konu kaynağı taslaklar
            'jobs'    => 'app_factory_jobs',       // İş takibi (ilk kullanımda otomatik kurulur)
            'notices' => 'app_factory_notices',    // Bildirim gönderim kaydı (ilk kullanımda otomatik kurulur)
        ],

        // --- İçerik türleri ---
        'content_types' => [
            'blog' => [
                'enabled'    => true,              // Bu tür için iş sunulsun mu
                'table'      => 'blog_post',       // Yazının yazıldığı tablo
                'image_dir'  => 'images/blog',     // Görsellerin yükleneceği dizin (public köküne göre)
                'url_prefix' => 'blog',            // Yazı adresi: /blog/{kategori}/{yazı}
            ],
            'news' => [
                'enabled'    => false,
                'table'      => '',
                'image_dir'  => '',
                'url_prefix' => '',
            ],
        ],

        // --- Yazı kuralları (yayın denetimi) ---
        'rules' => [
            'min_words'       => 700,              // En az kelime sayısı
            'faq_count'       => 3,                // Tam olarak istenen SSS adedi
            'required_fields' => ['title', 'slug', 'summary', 'content', 'seo_title', 'seo_description', 'faqs'],
            'optional_fields' => [                 // true = VPS üretsin ve denetim zorunlu tutsun
                'cta'            => false,
                'pros_cons'      => false,
                'author_comment' => false,
            ],
        ],

        // --- Konu seçimi ---
        'selection' => [
            'mode'                  => 'balanced', // balanced = en az yazılı kategoriden · ordered = taslak sırasıyla
            'jobs_per_request'      => 1,          // Her jobs isteğinde sunulan iş sayısı
            'max_pending'           => 3,          // Yayın bekleyen yazı bu sayıya ulaşınca yeni iş sunulmaz (0 = sınırsız)
            'reoffer_after_minutes' => 120,        // Yanıtı gelmeyen iş bu sürede yeniden sunulur
            'retry_after_hours'     => 24,         // Hatalı iş bu sürede yeniden denenir
            'max_retries'           => 2,          // Bundan sonra kalıcı hata
        ],

        // --- Yazı yönü (VPS'e giden) ---
        'direction' => [
            'site_note'      => 'Sitenin konusu ve bölgesi hakkında kısa not',
            'location'       => '',                // Sitenin hizmet bölgesi; VPS yalnız konu konumla ilgiliyse doğal biçimde kullanır, zorlamaz
            'site_domain'    => '',                // Sitenin KENDİ alan adı (örn. site.com, https/www'suz); VPS'e gider, CTA/bağlantıda başka alan adı reddedilir
            'tone'           => 'samimi ama uzman',
            'banned_words'   => [],
            'internal_links' => [                  // Her yazıda geçebilecek sabit bağlantılar
                ['url' => 'iletisim', 'title' => 'İletişim'],
            ],
        ],

        // --- İç bağlantı adayları ---
        'link_candidates' => [
            'enabled' => true,                     // Konuya yakın yayındaki yazılar VPS'e aday olarak gönderilsin mi
            'limit'   => 12,                       // En çok kaç aday (en fazla 15)
        ],

        // --- Benzersizlik kapısı (VPS'e giden mevcut yazı listesi) ---
        'uniqueness' => [
            'existing_posts_limit' => 300,         // jobs cevabındaki existing_posts en çok kaç yazı (en yeni önce, aktif + pasif)
        ],

        // --- Yayın takvimi ---
        'schedule' => [
            'days'  => [3, 6],                     // 1=Pazartesi … 7=Pazar (3=Çarşamba, 6=Cumartesi)
            'hours' => [10],                       // Yayın saatleri
        ],
        'publishing' => [
            'flush_cache'   => true,               // Yayından sonra sitenin önbelleği temizlensin mi
            // ⚠️ ZORUNLU: sitenin blog içerik servisinin takma adı (alias). Örnek: 'blogs.post' (proje modülünün blog servisi takma adı).
            // Bu servis yayından sonra önbelleği temizler (flushContentCache) ve yenileme (renew) içeriğini yazıya uygular (savePost).
            // BOŞ bırakılırsa: önbellek TEMİZLENMEZ (yeni yazı/yenileme sitede geç görünür) ve yenilemeler yazıya UYGULANAMAZ.
            // Doğru adı bulmak için: sitenin blog controller'ında kullanılan servis — ör. $this->service('blogs.post').
            'cache_service' => '',
        ],

        // --- Eski yazıları yenileme (renew) ---
        'renew' => [
            'enabled' => false,                    // true = yayındaki yazılar için yenileme işi sunulur
            'per_day' => 2,                        // Günde en çok kaç yenileme işi (sunulan + teslim alınan)
            'hours'   => [11, 16],                 // Yenilemenin yazıya uygulandığı saatler (her gün)
            'order'   => 'oldest',                 // oldest = en eski yayın tarihi önce · newest = en yeni önce
        ],

        // --- Bildirimler (Telegram) ---
        'notifications' => [
            'enabled' => false,
            'events'  => [
                'draft_low'       => true,         // Taslak azaldı
                'draft_empty'     => true,         // Taslak bitti
                'publish_summary' => true,         // Günlük yayın özeti
                'permanent_error' => true,         // Kalıcı hata
                'stuck'           => true,         // Yayında takıldı
                'renew_done'      => true,         // Eski yazıların yenilenmesi tamamlandı
            ],
            'low_draft_threshold' => 5,            // Kategori başına bu sayının altı = taslak azaldı
            'summary_hour'        => 20,           // Günlük özet bu saatten sonra gönderilir
            'stuck_hours'         => 24,           // Yayında bu süreden fazla takılan bildirilir
            'repeat_hours'        => [             // Aynı bildirimin tekrar süresi (0 = bir kez)
                'draft_empty'     => 24,
                'draft_low'       => 24,
                'stuck'           => 24,
                'publish_summary' => 24,
                'permanent_error' => 0,
                'renew_done'      => 0,
            ],
            'site_base_url' => 'https://site.example',
        ],
    ];
}
