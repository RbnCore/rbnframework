<?php

namespace Rbn\Framework\Bundles\RbnSuite\RbnAdmin\Models;

use Rbn\Framework\Core\Base\Data\BaseConfig;

/**
 * PanelMap - RbnAdmin Paket Haritası ve İskeleti 🗺️🛰️🏛️⚓
 * RBN Framework Standard.
 * 
 * Bu sınıf paketin iç yapısını (Sub-Modules), Navigasyon hiyerarşisini 
 * ve Breadcrumb verilerini "Structural Map" olarak yönetir.
 */
class PanelMap extends BaseConfig
{
    /**
     * STRUCTURAL MAP: Paketin İç Gezinti ve Modül Hiyerarşisi 🗺️⚓
     */
    public const MAP = [
        'identity' => [
            'title' => 'Yönetim Paneli',
            'icon' => 'ri-dashboard-line',
            'description' => 'Genel sistem özeti, ziyaretçi metrikleri ve hızlı eylemler.',
        ],
        'sub_modules' => [
            'webtraffic' => [
                'title' => 'Webtraffic Analitik',
                'description' => 'Ziyaretçi trafiği, hit oranları ve anlık kullanıcı istatistiklerini izleyin.',
                'icon' => 'ri-line-chart-line',
                'service' => 'AnalyticsService',
                'assets' => ['rbnCharts', 'flag_icon'],
                'sub_modules' => [
                    'logs' => [
                        'title' => 'Detaylı Ziyaretçi Kayıtları',
                        'description' => 'Ziyaretçi trafiği detaylı logları.',
                        'icon' => 'ri-terminal-box-line',
                    ],
                    'report' => [
                        'title' => 'Tarihsel Trafik Raporları',
                        'description' => 'Tarih aralığına göre gelişmiş ziyaretçi ve cihaz raporları.',
                        'icon' => 'ri-bar-chart-grouped-line',
                    ],
                    'google-analytics' => [
                        'title' => 'Google Analytics',
                        'icon' => 'ri-google-fill',
                        'description' => 'Google Web Analytics API (GA4) entegrasyonu ve canlı raporları.',
                        'sub_modules' => [
                            'map' => [
                                'title' => 'Türkiye Harita Analizi',
                                'icon' => 'ri-map-pin-line',
                                'description' => 'Türkiye genelinde illere göre ziyaretçi dağılımı.',
                            ]
                        ]
                    ]
                ]
            ],

            'navigation' => [
                'title' => 'Navigasyon Yönetimi',
                'description' => 'Site içi menü ve panel sidebar hiyerarşisini düzenleyin.',
                'icon' => 'ri-menu-2-line',
                'sub_modules' => [
                    'sidebar' => [
                        'title' => 'Panel Sidebar',
                        'controller' => 'SidebarController',
                    ],
                ]
            ],

            'hostmailhub' => [
                'title' => 'E-Posta Yönetimi',
                'description' => 'Projelerinize ait e-posta hesaplarını oluşturun, yönetin ve webmail erişimi sağlayın.',
                'icon' => 'ri-mail-send-line',
                'controller' => 'HostmailhubController',
                'assets' => [],
            ],

            'seo-report' => [
                'title' => 'SEO Analizi',
                'description' => 'Yapay zeka destekli detaylı SEO raporu ve performans analizleri.',
                'icon' => 'ri-search-eye-line',
                'controller' => 'SeoReportController',
            ],

            'users' => [
                'title' => 'Kullanıcı Yönetimi',
                'description' => 'Sistem kullanıcılarını listeleyin, yetki ve durumlarını yönetin.',
                'icon' => 'ri-team-line',
                'controller' => 'UserManagementController',
                'sub_modules' => [
                    'profile' => [
                        'title' => 'Profil Ayarlarım',
                        'description' => 'Kişisel bilgilerinizi ve şifrenizi güncelleyin.',
                        'icon' => 'ri-user-settings-line',
                        'controller' => 'UserManagementController',
                    ],
                    'activities' => [
                        'title' => 'Kullanıcı Aktivite Kayıtları',
                        'description' => 'Sistem üzerindeki kullanıcı hareketlerini inceleyin.',
                        'icon' => 'ri-history-line',
                        'controller' => 'UserManagementController',
                    ],
                ]
            ],

            'settings' => [
                'title' => 'Sistem Ayarları',
                'description' => 'Site genel ayarlarını, SEO ve iletişim parametrelerini yönetin.',
                'icon' => 'ri-settings-4-line',
                'controller' => 'AdminSettingsController',
            ],

            'bot-settings' => [
                'title' => 'Bot & API Ayarları',
                'description' => 'Projelerinize ait bot aktivitesi, Gemini API ve Youtube API parametrelerini yönetin.',
                'icon' => 'ri-robot-2-line',
                'controller' => 'BotSettingsController',
                'sub_modules' => [
                    'apis' => [
                        'title' => 'API Anahtarları & Yapılandırma',
                        'description' => 'Yapay zeka botlarının ve veri çekme servislerinin API anahtarlarını yönetin.',
                        'icon' => 'ri-key-2-line',
                        'controller' => 'BotSettingsController',
                    ],
                    'tasks' => [
                        'title' => 'Otonom Görev Listesi',
                        'description' => 'Sistemdeki tüm otonom bot görevlerini, durumlarını ve detaylı çalışma parametrelerini listeleyin.',
                        'icon' => 'ri-cpu-line',
                        'controller' => 'BotSettingsController',
                    ],
                    'ai-usage' => [
                        'title' => 'AI Kullanım & Maliyet Analizi',
                        'description' => 'Gemini AI ve Imagen API kullanım istatistiklerini, token harcamalarını ve tahmini dolar maliyetlerini izleyin.',
                        'icon' => 'ri-cpu-line',
                        'controller' => 'BotSettingsController',
                    ],
                ]
            ],

            'cron' => [
                'title' => 'Zamanlanmış Görevler (Cron)',
                'description' => 'Botların ve arka plan servislerinin otonom çalışma parametrelerini yönetin.',
                'icon' => 'ri-time-line',
                'controller' => 'CronLogsController',
                'sub_modules' => [
                    'cronlogs' => [
                        'title' => 'Cron Log Geçmişi',
                        'description' => 'Zamanlanmış görevlerin geçmiş çalışma kayıtlarını ve günlüklerini inceleyin.',
                        'icon' => 'ri-terminal-box-line',
                        'controller' => 'CronLogsController',
                    ]
                ]
            ],

            'contact' => [
                'title' => 'İletişim Mesajları',
                'description' => 'Gelen ziyaretçi mesajlarını ve talepleri yönetin.',
                'icon' => 'ri-mail-star-line',
            ],

            'notification' => [
                'title' => 'Sistem Bildirimleri',
                'description' => 'Sistem olaylarını ve kritik bildirimleri takip edin.',
                'icon' => 'ri-notification-3-line',
            ],
        ],
    ];
}
