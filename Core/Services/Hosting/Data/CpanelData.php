<?php
declare(strict_types=1);

namespace Rbn\Framework\Core\Services\Hosting\Data;

/**
 * CpanelData - cPanel altyapı SABİTLERİ (yalnız veri) 🏛️🛰️
 *
 * Bu dosyada METOT YOKTUR: yalnız `public const` taşır (varsayılan kota ve
 * yönlendirme hedefleri). Kimlik bilgisi (host + user + token) kaynak kodda
 * SABİT DEĞİLDİR; `Core/System/Config/Secrets/secrets.php` dosyasının `cpanel`
 * bölümünden `Rbn\Framework\Core\Services\Hosting\Providers\CPanelProvider` okur (`CPanelService::host()` ile erişilir).
 *
 * Bağlantı kapısı tek yerdedir: `Rbn\Framework\Core\Services\Hosting\Providers\CPanelProvider`
 * (Anayasa §8: dış servise bağlanan = Provider).
 */
final class CpanelData
{
    public const DEFAULT_QUOTA = 1024; // MB

    /**
     * cPanel Destinations Mappings 🗺️
     */
    public const DESTINATIONS = [
        'cpanel'      => [
            'port'        => 2083,
            'path'        => '/frontend/jupiter/index.html',
            'title'       => 'cPanel Panel Yönlendiriliyor...',
            'message'     => 'cPanel Yönetim Paneline Yönlendiriliyorsunuz',
            'sub_message' => 'Lütfen bekleyin, kontrol paneli güvenli bir şekilde yükleniyor...'
        ],
        'filemanager' => [
            'port'        => 2083,
            'path'        => '/frontend/jupiter/filemanager/index.html',
            'title'       => 'Dosya Yöneticisi Yönlendiriliyor...',
            'message'     => 'Dosya Yöneticisine Yönlendiriliyorsunuz',
            'sub_message' => 'Lütfen bekleyin, dosyalarınız güvenli bir şekilde yükleniyor...'
        ],
        'phpmyadmin'  => [
            'port'        => 2083,
            'path'        => '/3rdparty/phpMyAdmin/index.php',
            'title'       => 'phpMyAdmin Yönlendiriliyor...',
            'message'     => 'Veritabanı Yönetimine Yönlendiriliyorsunuz',
            'sub_message' => 'Lütfen bekleyin, veritabanı yönetim paneli güvenli bir şekilde yükleniyor...'
        ],
        'email'       => [
            'port'        => 2083,
            'path'        => '/frontend/jupiter/mail/pops/index.html',
            'title'       => 'E-Posta Ayarları Yönlendiriliyor...',
            'message'     => 'E-Posta Yönetim Paneline Yönlendiriliyorsunuz',
            'sub_message' => 'Lütfen bekleyin, e-posta ayarlarınız yükleniyor...'
        ],
        'domains'     => [
            'port'        => 2083,
            'path'        => '/frontend/jupiter/domains/index.html',
            'title'       => 'Alan Adı Ayarları Yönlendiriliyor...',
            'message'     => 'Alan Adı Yönetim Paneline Yönlendiriliyorsunuz',
            'sub_message' => 'Lütfen bekleyin, alan adı ayarlarınız yükleniyor...'
        ],
        'cron'        => [
            'port'        => 2083,
            'path'        => '/frontend/jupiter/cron/index.html',
            'title'       => 'Zamanlanmış Görevler Yönlendiriliyor...',
            'message'     => 'Cron Yönetim Paneline Yönlendiriliyorsunuz',
            'sub_message' => 'Lütfen bekleyin, zamanlanmış görev ayarlarınız yükleniyor...'
        ],
        'webmail'     => [
            'port'        => 2096,
            'path'        => '/3rdparty/roundcube/index.php',
            'title'       => 'Webmail Yönlendiriliyor...',
            'message'     => 'Webmail Hesabınıza Yönlendiriliyorsunuz',
            'sub_message' => 'Lütfen bekleyin, gelen kutunuz güvenli bir şekilde yükleniyor...'
        ],
    ];
}
