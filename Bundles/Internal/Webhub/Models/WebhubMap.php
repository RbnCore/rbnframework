<?php

declare(strict_types=1);

namespace Rbn\Framework\Bundles\Internal\Webhub\Models;

use Rbn\Framework\Core\Base\Data\BaseConfig;

/**
 * WebhubMap - Frontend Architecture Map 🌐🏛️⚓
 * RBN Framework Standard.
 */
class WebhubMap extends BaseConfig
{
    /**
     * STRUCTURAL MAP: Internal Module Hierarchy 🗺️⚓
     */
    public const MAP = [
        'identity' => [
            'name' => 'webhub',
            'title' => 'Web Hub',
            'description' => 'Frontend kimlik, SEO, navigasyon, entegrasyon ve yasal sayfalar yönetim merkezi.',
            'icon' => 'ri-global-line',
        ],
        'sub_modules' => [
            'identity' => [
                'title' => 'Kimlik & Marka',
                'description' => 'Firma adı, slogan, favicon ve iletişim bilgilerini düzenleyin.',
                'icon' => 'ri-store-2-line',
            ],
            'seo' => [
                'title' => 'SEO Ayarları',
                'description' => 'Global meta etiketler, Analytics entegrasyonu ve arama motoru yapılandırması.',
                'icon' => 'ri-search-eye-line',
                'sub_modules' => [
                    'score' => [
                        'title' => 'SEO Skoru',
                        'description' => 'Sistemdeki içeriklerin gerçek SEO sağlığını ölçümleyin.',
                        'icon' => 'ri-pulse-line',
                    ],
                    'report' => [
                        'title' => 'AI SEO Raporu',
                        'description' => 'Yapay zeka destekli detaylı sistem analizi ve çözüm önerileri.',
                        'icon' => 'ri-robot-2-line',
                    ]
                ]
            ],
            'integrations' => [
                'title' => 'Entegrasyonlar',
                'description' => 'Analytics, Tag Manager, AdSense ve Custom Script (Head, Body, Footer) yönetimi.',
                'icon' => 'ri-plug-line',
                'required_role' => 'developer',
                'sub_modules' => [
                    'manage' => [
                        'title' => 'Mimari Yapılandırma',
                        'description' => 'Script alanlarının teknik özelliklerini, yetki seviyelerini ve giriş tiplerini yönetin.',
                        'icon' => 'ri-tools-line',
                        'required_role' => 'developer',
                    ],
                    'adsense' => [
                        'title' => 'Reklam Yönetimi',
                        'description' => 'Google AdSense entegrasyonu, reklam durumları ve şablon slot ayarları.',
                        'icon' => 'ri-advertisement-line',
                        'required_role' => 'developer',
                    ]
                ]
            ],
            'navigation' => [
                'title' => 'Navigasyon',
                'description' => 'Frontend navbar ve footer menülerini dinamik olarak yapılandırın.',
                'icon' => 'ri-menu-2-line',
            ],
            'policy' => [
                'title' => 'Yasal Sayfalar',
                'description' => 'Gizlilik Sözleşmesi, Çerez Politikası ve KVKK içeriklerini yönetin.',
                'icon' => 'ri-file-text-line',
            ],
            'faq' => [
                'title' => 'SSS Yönetimi',
                'description' => 'Sıkça sorulan sorular, cevaplar ve SEO Schema yapılandırması.',
                'icon' => 'ri-questionnaire-line',
            ],
        ],
    ];
}
