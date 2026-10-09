<?php

namespace Rbn\Framework\Bundles\RbnSuite\RbnAuth\Models;

use Rbn\Framework\Core\Support\Definitions\System\FrameworkIdentity;
use Rbn\Framework\Core\Base\Data\BaseConfig;

/**
 * AuthIdentity - RbnAuth Paket Kimliği ve UI Meta Verileri 🛡️🛰️🏛️⚓
 * RBN Framework Standard.
 * 
 * Bu sınıf paketin SEO, Slogan ve Görünüm (View) meta verilerini 
 * "Identity Map" olarak merkezi HUB üzerinden yönetir.
 */
class AuthIdentity extends BaseConfig
{

    public const THEME_COLOR = '#4f46e5'; // Indigo-600
    public const FAVICON = '@fw/images/favicon-rbnauth.svg';

    /**
     * AUTH_IDENTITY: Paketin SEO ve Sayfa Meta Veri Haritası 🏺🗺️⚓
     */
    public const AUTH_IDENTITY = [
        'seo' => [
            'title' => 'RbnAuth | Güvenli Kimlik Doğrulama',
            'description' => 'RbnAuth profesyonel kimlik doğrulama ve kullanıcı yönetim servisidir.',
            'keywords' => 'kimlik doğrulama, giriş, kullanıcı yönetimi, güvenlik, authentication, login, RbnAuth',
            'robots' => 'noindex, nofollow',
        ],
        'metadata' => [
            'viewport' => 'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no',
            'theme-color' => self::THEME_COLOR,
            'og:title' => 'RbnAuth | Profesyonel Kimlik Doğrulama',
            'og:type' => 'website',
            'og:site_name' => FrameworkIdentity::AUTH_NAME
        ],
        'ui' => [
            'theme' => self::THEME_COLOR,
            'favicon' => self::FAVICON,
            'slogan' => FrameworkIdentity::AUTH_SLOGAN
        ]
    ];

    /**
     * Varsayılan Bilgilendirme Metinleri (Settings tablosu boşsa kullanılır) 🎻
     */
    public const DEFAULT_INFO = [
        'login' => [
            'title' => 'Giriş Yap',
            'icon' => 'fas fa-shield-alt',
            'features' => [
                '✨ Çok Faktörlü Doğrulama Desteği',
                '✨ Gelişmiş Log ve İzleme Yönetimi',
                '✨ Askeri Düzeyde Veri Şifreleme'
            ]
        ],
        'register' => [
            'title' => 'Kayıt Ol',
            'icon' => 'fas fa-rocket',
            'features' => [
                '✨ Hızlı ve Kolay Hesap Oluşturma',
                '✨ Tamamen Özelleştirilebilir Profil',
                '✨ Güçlü Güvenlik Altyapısı',
                '✨ Modern Kullanıcı Deneyimi'
            ]
        ],
        'forgot' => [
            'title' => 'Şifremi Unuttum',
            'icon' => 'fas fa-key',
            'features' => [
                '✨ E-posta ile Güvenli Doğrulama',
                '✨ Geçici Erişim Kodu Desteği',
                '✨ 256-bit Güvenli Şifre Yenileme'
            ]
        ],
        'lock' => [
            'title' => 'Ekran Kilidi',
            'icon' => 'fas fa-user-lock',
            'features' => [
                '✨ Otomatik Kilitleme Güvenliği',
                '✨ Hızlı Oturum Kurtarma',
                '✨ Kesintisiz Çalışma Deneyimi'
            ]
        ],
        'verify' => [
            'title' => 'Kodu Doğrula',
            'icon' => 'fas fa-user-check',
            'features' => [
                '✨ E-postanızı Kontrol Edin',
                '✨ 15 Dakika Boyunca Geçerli Kod',
                '✨ Güvenli ve Hızlı Doğrulama'
            ]
        ],
        'reset' => [
            'title' => 'Şifreyi Yenile',
            'icon' => 'fas fa-user-shield',
            'features' => [
                '✨ En az 6 karakter uzunluğunda',
                '✨ Büyük ve küçük harf içermeli',
                '✨ Rakam ve özel karakter kullanın'
            ]
        ]
    ];

    /**
     * View-to-Config Map: Resolves internal keys for specific Auth views. 🛰️
     */
    public const VIEW_MAP = [
        'login' => 'login',
        'register' => 'register',
        'forgot-password' => 'forgot',
        'reset-password' => 'reset',
        'verify-code' => 'verify',
        'lockscreen' => 'lock'
    ];
}
