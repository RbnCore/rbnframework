<?php

declare(strict_types=1);

namespace Rbn\Framework\Bundles\Internal\Syshub\Models;

/**
 * PurgeConfig - Data Purge UI Configuration Hub 🧩🧹🛰️
 * 
 * Defines metadata for storage types to keep Controllers and Providers clean.
 */
class PurgeConfig
{
    public const TYPES = [
        'cache' => [
            'title' => 'Önbellek (Cache)',
            'icon' => 'bi-lightning-charge-fill',
            'color' => 'primary',
            'bg_color' => '#0ea5e9',
            'description' => 'Sistem hızını artırmak için kullanılan geçici veriler.',
            'info_points' => [
                '📁 Önbellek sistemi <code>storage/cache</code> dizini altında çalışmaktadır.',
                '🔐 Dosyalar güvenlik için <strong>Base64/Serialized</strong> yöntemle depolanır.',
                '⚠️ Temizlik sonrası ilk yüklemeler biraz yavaş olabilir.'
            ],
            'bulk_actions' => [
                'download' => 'Seçilenleri Zip İndir',
                'delete' => 'Seçilenleri Sil'
            ],
            'table_header' => 'DOSYA ADI',
            'confirm_text' => 'Tüm sistem cache dosyaları silinecektir.'
        ],
        'sessions' => [
            'title' => 'Oturumlar (Sessions)',
            'icon' => 'bi-person-badge-fill',
            'color' => 'warning',
            'bg_color' => '#f59e0b',
            'description' => 'Aktif kullanıcı oturumları ve giriş verileri.',
            'info_points' => [
                '📁 Oturum dosyaları <code>storage/sessions</code> dizini altında saklanır.',
                '🔐 Bu dosyalar kullanıcı girişlerini ve geçici oturum verilerini barındırır.',
                '⚠️ Temizlik yapıldığında tüm aktif kullanıcıların oturumları <strong>sonlanacaktır</strong>.'
            ],
            'bulk_actions' => [
                'download' => 'Seçilenleri Zip İndir',
                'delete' => 'Oturumları Sonlandır'
            ],
            'table_header' => 'SESSION ID',
            'confirm_text' => 'Tüm session dosyaları silinecektir. Herkesin oturumu sonlanacaktır.'
        ],
        'framework' => [
            'title' => 'Framework Geçici Dosyaları',
            'icon' => 'bi-cpu-fill',
            'color' => 'info',
            'bg_color' => '#ec4899',
            'description' => 'Derlenmiş görünümler ve framework çalışma dosyaları.',
            'info_points' => [
                '📁 Derlenmiş dosyalar <code>storage/framework</code> dizini altında tutulur.',
                '⚡ Bu dosyalar uygulamanın çalışma performansını optimize eder.',
                '🛠️ Temizlik sonrası sayfalar ilk seferde yeniden derlenecektir.'
            ],
            'bulk_actions' => [
                'delete' => 'Derlenmişleri Temizle'
            ],
            'table_header' => 'DOSYA / PATH',
            'confirm_text' => 'Tüm derlenmiş framework dosyaları silinecektir.'
        ],
        'backups' => [
            'title' => 'Veritabanı Yedekleri',
            'icon' => 'bi-database-fill-check',
            'color' => 'success',
            'bg_color' => '#10b981',
            'description' => 'Sistem tarafından alınan SQL yedek dosyaları.',
            'info_points' => [
                '📁 Yedekler <code>storage/backups</code> dizini altında saklanır.',
                '📦 SQL formatındaki yedekleri buradan indirebilir veya temizleyebilirsiniz.',
                '🔐 Yedeklerin güvenliği için periyodik temizlik önerilir.'
            ],
            'bulk_actions' => [
                'download' => 'Yedekleri Zip İndir',
                'delete' => 'Yedekleri Sil'
            ],
            'table_header' => 'YEDEK DOSYASI',
            'confirm_text' => 'Tüm yedek dosyaları silinecektir.'
        ],
        'exports' => [
            'title' => 'Dışa Aktarılanlar (Exports)',
            'icon' => 'bi-file-earmark-arrow-down-fill',
            'color' => 'secondary',
            'bg_color' => '#3b82f6',
            'description' => 'Tablo ve rapor dışa aktarım dosyaları.',
            'info_points' => [
                '📁 Dosyalar <code>storage/exports</code> dizini altında bulunur.',
                '📊 Excel, CSV ve PDF formatındaki rapor dosyalarını içerir.',
                '🧹 Eski raporları temizleyerek disk alanını koruyun.'
            ],
            'bulk_actions' => [
                'download' => 'Seçilenleri Zip İndir',
                'delete' => 'Dosyaları Temizle'
            ],
            'table_header' => 'RAPOR DOSYASI',
            'confirm_text' => 'Tüm dışa aktarılan dosyalar silinecektir.'
        ],
        'uploads' => [
            'title' => 'Dosya Yüklemeleri (Temp)',
            'icon' => 'bi-cloud-upload-fill',
            'color' => 'dark',
            'bg_color' => '#06b6d4',
            'description' => 'Yükleme sırasında oluşan geçici dosyalar.',
            'info_points' => [
                '📁 Geçici dosyalar <code>storage/uploads</code> dizini altında tutulur.',
                '⏳ Tamamlanmamış veya hatalı yükleme kalıntılarını içerir.',
                '🧹 Düzenli temizlik sistem performansını artırır.'
            ],
            'bulk_actions' => [
                'delete' => 'Geçici Dosyaları Sil'
            ],
            'table_header' => 'GEÇİCİ DOSYA',
            'confirm_text' => 'Tüm geçici yükleme dosyaları silinecektir.'
        ]
    ];

    public static function get(string $type): ?array
    {
        return self::TYPES[$type] ?? null;
    }
}
