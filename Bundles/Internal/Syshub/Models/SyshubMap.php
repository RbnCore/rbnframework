<?php

declare(strict_types=1);

namespace Rbn\Framework\Bundles\Internal\Syshub\Models;

use Rbn\Framework\Core\Base\Data\BaseConfig;

/**
 * SyshubMap - System Architecture Map 🏛️🛰️⚓
 * RBN 3.5 Masterpiece Standard.
 */
class SyshubMap extends BaseConfig
{
    /**
     * STRUCTURAL MAP: Internal Module Hierarchy 🗺️⚓
     */
    public const MAP = [
        'identity' => [
            'name' => 'syshub',
            'title' => 'Syshub',
            'description' => 'Gelişmiş Sistem, Güvenlik ve Performans Yönetimi merkezi.',
            'icon' => 'ri-cpu-line',
        ],
        'sub_modules' => [
            'identity' => [
                'title' => 'Sistem Kimliği',
                'description' => 'Sistem sağlığı, performans ve genel istatistik merkezi.',
                'icon' => 'ri-cpu-line',
                'controller' => 'SyshubController',
            ],
            'security' => [
                'title' => 'Güvenlik Merkezi',
                'description' => 'IP engelleme, firewall ayarları ve rate limit yönetimi.',
                'icon' => 'ri-shield-check-line',
                'sub_modules' => [
                    'ratelimits' => ['title' => 'Rate Limits', 'icon' => 'ri-pulse-line'],
                    'ipBlock' => ['title' => 'IP Blocks', 'icon' => 'ri-shield-keyhole-line'],
                    'whitelist' => ['title' => 'IP Whitelist', 'icon' => 'ri-shield-user-line'],
                    'firewall' => ['title' => 'Firewall Ayarları', 'icon' => 'ri-fire-line'],
                ]
            ],
            'maintenance' => [
                'title' => 'Bakım Modu',
                'description' => 'Sistem bakım modu yapılandırması ve IP izinleri.',
                'icon' => 'ri-tools-line',
            ],
            'datapurge' => [
                'title' => 'Veri Temizliği',
                'description' => 'Önbellek, log ve oturum verilerinin otonom yönetimi.',
                'icon' => 'ri-delete-bin-line',
                'sub_modules' => [
                    'cache' => ['title' => 'Önbellek Yönetimi', 'icon' => 'ri-database-2-line'],
                    'logs' => ['title' => 'Sistem Logları', 'icon' => 'ri-file-list-3-line'],
                    'sessions' => ['title' => 'Oturum Yönetimi', 'icon' => 'ri-user-shared-line'],
                    'backups' => ['title' => 'Veritabanı Yedekleri', 'icon' => 'ri-save-3-line'],
                    'exports' => ['title' => 'Dışa Aktarılanlar', 'icon' => 'ri-file-download-line'],
                    'uploads' => ['title' => 'Dosya Yüklemeleri', 'icon' => 'ri-upload-cloud-2-line'],
                    'view' => ['title' => 'Derlenmiş Görünümler', 'icon' => 'ri-layout-masonry-line'],
                ]
            ],
            'dbconsole' => [
                'title' => 'Veritabanı Konsolu',
                'description' => 'SQL terminali ve tablo gezgini ile veritabanı yönetimi.',
                'icon' => 'ri-terminal-box-line',
                'sub_modules' => [
                    'console' => ['title' => 'SQL Console', 'icon' => 'ri-terminal-line'],
                    'tables' => ['title' => 'Table Explorer', 'icon' => 'ri-table-line'],
                    'browse' => ['title' => 'Table Browser', 'icon' => 'ri-search-eye-line'],
                ]
            ],
        ],
    ];
}
