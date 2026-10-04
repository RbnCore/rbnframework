<?php

namespace Rbn\Framework\Core\Support\Bridges\Helpers\Library\Icons;

/**
 * SmartIconCategories - Akıllı Icon Kategorizasyonu ve Emoji Veri Portalı
 * 
 * @package App\Core\System\Icons
 * @version 2.0 (Emoji Support)
 * @author RBN Bilişim
 */
class SmartIconCategories
{
    /**
     * Get admin panel specific icons
     *
     * @return array
     */
    public static function getAdminIcons(): array
    {
        return [
            // DASHBOARD & OVERVIEW
            'bi bi-speedometer2' => ['label' => 'Dashboard', 'emoji' => '📊'],
            'bi bi-grid-3x3-gap' => ['label' => 'Genel Bakış', 'emoji' => '🖥️'],
            'bi bi-house-gear' => ['label' => 'Ana Panel', 'emoji' => '🏠'],
            'bi bi-layout-three-columns' => ['label' => 'Layout Yönetimi', 'emoji' => '📐'],
            'fas fa-tachometer-alt' => ['label' => 'Kontrol Paneli', 'emoji' => '🎛️'],
            'fas fa-chart-pie' => ['label' => 'Analiz Paneli', 'emoji' => '🥧'],

            // USER MANAGEMENT
            'bi bi-people' => ['label' => 'Kullanıcı Yönetimi', 'emoji' => '👥'],
            'bi bi-person-gear' => ['label' => 'Kullanıcı Ayarları', 'emoji' => '👤⚙️'],
            'bi bi-person-plus' => ['label' => 'Kullanıcı Ekle', 'emoji' => '👤➕'],
            'bi bi-person-check' => ['label' => 'Kullanıcı Onayı', 'emoji' => '👤✅'],
            'bi bi-person-x' => ['label' => 'Kullanıcı Engelle', 'emoji' => '👤❌'],
            'bi bi-person-badge' => ['label' => 'Kullanıcı Rolleri', 'emoji' => '🪪'],
            'fas fa-users' => ['label' => 'Kullanıcı Grupları', 'emoji' => '👪'],
            'fas fa-user-shield' => ['label' => 'Kullanıcı Güvenliği', 'emoji' => '🛡️'],
            'fas fa-user-cog' => ['label' => 'Kullanıcı Konfigürasyonu', 'emoji' => '🧑‍💻'],

            // CONTENT MANAGEMENT
            'bi bi-file-text' => ['label' => 'İçerik Yönetimi', 'emoji' => '📄'],
            'bi bi-journal-text' => ['label' => 'Sayfa Yönetimi', 'emoji' => '📓'],
            'bi bi-newspaper' => ['label' => 'Haber Yönetimi', 'emoji' => '📰'],
            'bi bi-card-text' => ['label' => 'Makale Yönetimi', 'emoji' => '📃'],
            'bi bi-collection' => ['label' => 'İçerik Koleksiyonu', 'emoji' => '📚'],
            'bi bi-images' => ['label' => 'Medya Galerisi', 'emoji' => '🖼️'],
            'bi bi-camera' => ['label' => 'Medya Yükle', 'emoji' => '📸'],
            'fas fa-edit' => ['label' => 'İçerik Düzenle', 'emoji' => '✍️'],
            'fas fa-file-alt' => ['label' => 'Doküman Yönetimi', 'emoji' => '📁'],

            // COMMUNICATION & MESSAGING
            'bi bi-envelope' => ['label' => 'Mesajlar', 'emoji' => '✉️'],
            'bi bi-envelope-at' => ['label' => 'E-Posta Ayarları', 'emoji' => '📧'],
            'bi bi-search' => ['label' => 'SEO / Arama Ayarları', 'emoji' => '🔍'],
            'bi bi-chat-dots' => ['label' => 'Canlı Destek', 'emoji' => '💬'],
            'bi bi-bell' => ['label' => 'Bildirimler', 'emoji' => '🔔'],
            'bi bi-megaphone' => ['label' => 'Duyurular', 'emoji' => '📢'],
            'bi bi-telephone' => ['label' => 'İletişim', 'emoji' => '📞'],
            'bi bi-chat-square-text' => ['label' => 'Sohbet Yönetimi', 'emoji' => '🗨️'],
            'fas fa-comments' => ['label' => 'Yorumlar', 'emoji' => '💭'],
            'fas fa-envelope-open' => ['label' => 'Gelen Kutusu', 'emoji' => '📥'],

            // SYSTEM SETTINGS
            'bi bi-gear' => ['label' => 'Sistem Ayarları', 'emoji' => '⚙️'],
            'bi bi-building' => ['label' => 'Kurumsal Kimlik', 'emoji' => '🏢'],
            'bi bi-building-gear' => ['label' => 'Kurumsal Kimlik (Yönetim)', 'emoji' => '🏢⚙️'],
            'bi bi-toggles' => ['label' => 'Genel Ayarlar', 'emoji' => '🎚️'],
            'bi bi-sliders' => ['label' => 'Parametre Ayarları', 'emoji' => '🎛️'],
            'bi bi-palette' => ['label' => 'Tema Ayarları', 'emoji' => '🎨'],
            'bi bi-translate' => ['label' => 'Dil Ayarları', 'emoji' => '🌐'],
            'bi bi-globe' => ['label' => 'Site Ayarları', 'emoji' => '🌍'],
            'bi bi-server' => ['label' => 'Sunucu Ayarları', 'emoji' => '🖧'],
            'fas fa-cogs' => ['label' => 'Gelişmiş Ayarlar', 'emoji' => '🧩'],
            'fas fa-wrench' => ['label' => 'Sistem Araçları', 'emoji' => '🔧'],

            // SECURITY & ACCESS CONTROL
            'bi bi-shield-check' => ['label' => 'Güvenlik', 'emoji' => '🛡️✅'],
            'bi bi-key' => ['label' => 'Erişim Anahtarları', 'emoji' => '🔑'],
            'bi bi-lock' => ['label' => 'Güvenlik Ayarları', 'emoji' => '🔒'],
            'bi bi-fingerprint' => ['label' => 'İki Faktörlü Doğrulama', 'emoji' => '👆'],
            'bi bi-shield-lock' => ['label' => 'Erişim Kontrolü', 'emoji' => '🔐'],
            'bi bi-person-lines-fill' => ['label' => 'Yetki Yönetimi', 'emoji' => '📋'],
            'fas fa-shield-alt' => ['label' => 'Güvenlik Duvarı', 'emoji' => '🧱'],
            'fas fa-user-secret' => ['label' => 'Güvenlik Denetimi', 'emoji' => '🕵️'],

            // ANALYTICS & REPORTS
            'bi bi-graph-up' => ['label' => 'Raporlar', 'emoji' => '📈'],
            'bi bi-bar-chart' => ['label' => 'İstatistikler', 'emoji' => '📊'],
            'bi bi-pie-chart' => ['label' => 'Analitik', 'emoji' => '🥧'],
            'bi bi-clipboard-data' => ['label' => 'Veri Raporu', 'emoji' => '📋'],
            'bi bi-activity' => ['label' => 'Site Aktivitesi', 'emoji' => '📡'],
            'bi bi-graph-up-arrow' => ['label' => 'Trend Analizi', 'emoji' => '💹'],
            'fas fa-chart-line' => ['label' => 'Grafik Raporları', 'emoji' => '📉'],
            'fas fa-chart-bar' => ['label' => 'Çubuk Grafikler', 'emoji' => '📊'],

            // BACKUP & MAINTENANCE
            'bi bi-archive' => ['label' => 'Yedekleme', 'emoji' => '🗄️'],
            'bi bi-cloud-download' => ['label' => 'Yedek İndir', 'emoji' => '☁️⬇️'],
            'bi bi-cloud-upload' => ['label' => 'Yedek Yükle', 'emoji' => '☁️⬆️'],
            'bi bi-tools' => ['label' => 'Bakım Araçları', 'emoji' => '🛠️'],
            'bi bi-wrench' => ['label' => 'Sistem Bakımı', 'emoji' => '🔧'],
            'bi bi-arrow-clockwise' => ['label' => 'Sistem Güncelleme', 'emoji' => '🔄'],
            'fas fa-sync' => ['label' => 'Senkronizasyon', 'emoji' => '🔁'],
            'fas fa-download' => ['label' => 'Veri İndirme', 'emoji' => '⏬'],

            // PLUGINS & MODULES
            'bi bi-puzzle' => ['label' => 'Modüller', 'emoji' => '🧩'],
            'bi bi-box' => ['label' => 'Paketler', 'emoji' => '📦'],
            'bi bi-plugin' => ['label' => 'Eklentiler', 'emoji' => '🔌'],
            'bi bi-cpu' => ['label' => 'Sistem Bileşenleri', 'emoji' => '🖩'],
            'fas fa-cube' => ['label' => 'Bileşenler', 'emoji' => '🧊'],
            'fas fa-cubes' => ['label' => 'Modül Grupları', 'emoji' => '📦'],

            // E-COMMERCE (if applicable)
            'bi bi-shop' => ['label' => 'Mağaza Yönetimi', 'emoji' => '🏪'],
            'bi bi-cart' => ['label' => 'Sipariş Yönetimi', 'emoji' => '🛒'],
            'bi bi-credit-card' => ['label' => 'Ödeme Ayarları', 'emoji' => '💳'],
            'bi bi-receipt' => ['label' => 'Faturalar', 'emoji' => '🧾'],
            'bi bi-currency-dollar' => ['label' => 'Finansal Yönetim', 'emoji' => '💵'],
            'bi bi-tags' => ['label' => 'Ürün Etiketleri', 'emoji' => '🏷️'],
            'fas fa-shopping-cart' => ['label' => 'Alışveriş Sepeti', 'emoji' => '🛍️'],
            'fas fa-money-bill' => ['label' => 'Fiyat Yönetimi', 'emoji' => '💸']
        ];
    }

    public static function getDeveloperIcons(): array
    {
        return [
            // DEVELOPMENT ENVIRONMENT
            'bi bi-code-slash' => ['label' => 'Kod Editörü', 'emoji' => '💻'],
            'bi bi-terminal' => ['label' => 'Terminal', 'emoji' => '🖥️'],
            'bi bi-braces' => ['label' => 'JSON Editör', 'emoji' => '｛'],
            'bi bi-file-code' => ['label' => 'Kod Dosyaları', 'emoji' => '📜'],
            'bi bi-brackets' => ['label' => 'Kod Blokları', 'emoji' => '［'],
            'fas fa-code' => ['label' => 'Geliştirme Ortamı', 'emoji' => '🧑‍💻'],
            'fas fa-laptop-code' => ['label' => 'IDE', 'emoji' => '💻'],

            // VERSION CONTROL & GIT
            'fab fa-git-alt' => ['label' => 'Git Yönetimi', 'emoji' => '🔀'],
            'fab fa-github' => ['label' => 'GitHub', 'emoji' => '🐱'],
            'fab fa-gitlab' => ['label' => 'GitLab', 'emoji' => '🦊'],
            'fab fa-bitbucket' => ['label' => 'Bitbucket', 'emoji' => '🪣'],
            'bi bi-arrow-clockwise' => ['label' => 'Commit', 'emoji' => '🔄'],
            'bi bi-arrow-repeat' => ['label' => 'Pull/Push', 'emoji' => '🔁'],
            'bi bi-diagram-2' => ['label' => 'Branch Yönetimi', 'emoji' => '🌿'],

            // DEBUGGING & TESTING
            'bi bi-bug' => ['label' => 'Debug Araçları', 'emoji' => '🐛'],
            'bi bi-search' => ['label' => 'Kod Analizi', 'emoji' => '🔍'],
            'bi bi-clipboard-check' => ['label' => 'Test Sonuçları', 'emoji' => '📑'],
            'bi bi-stopwatch' => ['label' => 'Performance Test', 'emoji' => '⏱️'],
            'bi bi-shield-exclamation' => ['label' => 'Güvenlik Testleri', 'emoji' => '🛡️❗'],
            'fas fa-flask' => ['label' => 'Test Ortamı', 'emoji' => '🧪'],
            'fas fa-microscope' => ['label' => 'Kod İnceleme', 'emoji' => '🔬'],

            // DATABASE & API MANAGEMENT
            'bi bi-database' => ['label' => 'Veritabanı Yönetimi', 'emoji' => '🗄️'],
            'bi bi-database-gear' => ['label' => 'DB Konfigürasyonu', 'emoji' => '🗄️⚙️'],
            'bi bi-diagram-3' => ['label' => 'DB Şeması', 'emoji' => '🗺️'],
            'bi bi-table' => ['label' => 'Tablo Yapısı', 'emoji' => '🗂️'],
            'bi bi-clipboard-data' => ['label' => 'API Dokümantasyonu', 'emoji' => '📋🔌'],
            'bi bi-arrow-left-right' => ['label' => 'API Testi', 'emoji' => '⇄'],
            'bi bi-cloud-arrow-up' => ['label' => 'API Upload', 'emoji' => '☁️⬆️'],
            'bi bi-shuffle' => ['label' => 'URL Yönlendirme / Redirect', 'emoji' => '🔀'],
            'fas fa-server' => ['label' => 'Sunucu API', 'emoji' => '🖧'],

            // SYSTEM MONITORING & PERFORMANCE
            'bi bi-speedometer' => ['label' => 'Performance Monitor', 'emoji' => '🏎️'],
            'bi bi-cpu' => ['label' => 'CPU Kullanımı', 'emoji' => '🧠'],
            'bi bi-memory' => ['label' => 'Bellek İzleme', 'emoji' => '📀'],
            'bi bi-hdd' => ['label' => 'Disk Kullanımı', 'emoji' => '💽'],
            'bi bi-activity' => ['label' => 'Sistem Aktivitesi', 'emoji' => '📈'],
            'bi bi-graph-up' => ['label' => 'Performance Metrikleri', 'emoji' => '📉'],
            'bi bi-thermometer-half' => ['label' => 'Sistem Sıcaklığı', 'emoji' => '🌡️'],

            // ARCHITECTURE & SYSTEM DESIGN
            'bi bi-layers' => ['label' => 'Katmanlı Mimari', 'emoji' => '🍔'],
            'bi bi-diagram-2-fill' => ['label' => 'Sistem Şeması', 'emoji' => '📐'],
            'bi bi-boxes' => ['label' => 'Modül Yapısı', 'emoji' => '📦'],
            'bi bi-grid-3x3' => ['label' => 'Component Grid', 'emoji' => '🔲'],
            'bi bi-arrows-angle-expand' => ['label' => 'Sistem Ölçeklendirme', 'emoji' => '↔️'],
            'fas fa-project-diagram' => ['label' => 'Proje Mimarisi', 'emoji' => '🏗️'],
            'fas fa-sitemap' => ['label' => 'Site Haritası', 'emoji' => '🗺️'],

            // SECURITY & AUTHENTICATION
            'bi bi-shield-fill-check' => ['label' => 'Güvenlik Doğrulama', 'emoji' => '🛡️✅'],
            'bi bi-key-fill' => ['label' => 'API Anahtarları', 'emoji' => '🔑'],
            'bi bi-lock-fill' => ['label' => 'Şifreleme', 'emoji' => '🔒'],
            'bi bi-eye-slash' => ['label' => 'Gizlilik Ayarları', 'emoji' => '🙈'],
            'bi bi-fingerprint' => ['label' => 'Kimlik Doğrulama', 'emoji' => '👍'],
            'fas fa-user-secret' => ['label' => 'Güvenlik Denetimi', 'emoji' => '🕵️'],
            'fas fa-shield-virus' => ['label' => 'Güvenlik Taraması', 'emoji' => '🦠'],

            // DEPLOYMENT & DevOps
            'bi bi-cloud-upload' => ['label' => 'Deployment', 'emoji' => '☁️🚀'],
            'bi bi-arrow-repeat-fill' => ['label' => 'CI/CD Pipeline', 'emoji' => '🔁'],
            'bi bi-hdd-network' => ['label' => 'Ağ Yapılandırması', 'emoji' => '🌐'],
            'bi bi-server' => ['label' => 'Sunucu Yönetimi', 'emoji' => '🖥️'],
            'bi bi-globe' => ['label' => 'Domain Yönetimi', 'emoji' => '🌍'],
            'fas fa-rocket' => ['label' => 'Canlı Alma', 'emoji' => '🚀'],
            'fab fa-docker' => ['label' => 'Docker Konteyner', 'emoji' => '🐳'],

            // LOGGING & ERROR TRACKING
            'bi bi-journal-text' => ['label' => 'Log Dosyaları', 'emoji' => '📓'],
            'bi bi-exclamation-triangle' => ['label' => 'Hata Logları', 'emoji' => '⚠️'],
            'bi bi-info-circle' => ['label' => 'Sistem Logları', 'emoji' => 'ℹ️'],
            'bi bi-clipboard2-data' => ['label' => 'Error Tracking', 'emoji' => '📋'],
            'bi bi-graph-down' => ['label' => 'Hata Analizi', 'emoji' => '📉'],
            'fas fa-chart-line' => ['label' => 'Log Analizi', 'emoji' => '📈'],
            'fas fa-exclamation' => ['label' => 'Critical Errors', 'emoji' => '❗'],

            // CONFIGURATION & ENVIRONMENT
            'bi bi-gear-wide-connected' => ['label' => 'Sistem Konfigürasyonu', 'emoji' => '⚙️🌐'],
            'bi bi-toggles' => ['label' => 'Feature Flags', 'emoji' => '🎚️'],
            'bi bi-sliders' => ['label' => 'Environment Değişkenleri', 'emoji' => '🎛️'],
            'bi bi-file-earmark-text' => ['label' => 'Config Dosyaları', 'emoji' => '📄'],
            'bi bi-layers-half' => ['label' => 'Environment Layers', 'emoji' => '🥞'],
            'fas fa-cog' => ['label' => 'Sistem Ayarları', 'emoji' => '⚙️'],

            // PACKAGE & DEPENDENCY MANAGEMENT
            'fab fa-npm' => ['label' => 'NPM Paketleri', 'emoji' => '📦'],
            'fab fa-node-js' => ['label' => 'Node.js', 'emoji' => '🟢'],
            'fas fa-box' => ['label' => 'Paket Yönetimi', 'emoji' => '📥'],
            'bi bi-archive' => ['label' => 'Dependency Management', 'emoji' => '🗄️'],
            'bi bi-diagram-3-fill' => ['label' => 'Dependency Graph', 'emoji' => '📈']
        ];
    }

    public static function getPopularIcons(): array
    {
        return [
            // TOP 30 MOST USED ICONS
            'bi bi-house' => ['label' => 'Ana Sayfa', 'emoji' => '🏠'],
            'bi bi-person' => ['label' => 'Kullanıcı', 'emoji' => '👤'],
            'bi bi-people' => ['label' => 'Kullanıcılar', 'emoji' => '👥'],
            'bi bi-gear' => ['label' => 'Ayarlar', 'emoji' => '⚙️'],
            'bi bi-search' => ['label' => 'Arama', 'emoji' => '🔍'],
            'bi bi-bell' => ['label' => 'Bildirim', 'emoji' => '🔔'],
            'bi bi-envelope' => ['label' => 'E-posta', 'emoji' => '✉️'],
            'bi bi-calendar' => ['label' => 'Takvim', 'emoji' => '📅'],
            'bi bi-clock' => ['label' => 'Saat', 'emoji' => '⏱️'],
            'bi bi-plus' => ['label' => 'Ekle', 'emoji' => '➕'],
            'bi bi-pencil' => ['label' => 'Düzenle', 'emoji' => '✏️'],
            'bi bi-trash' => ['label' => 'Sil', 'emoji' => '🗑️'],
            'bi bi-eye' => ['label' => 'Görüntüle', 'emoji' => '👁️'],
            'bi bi-download' => ['label' => 'İndir', 'emoji' => '⬇️'],
            'bi bi-upload' => ['label' => 'Yükle', 'emoji' => '⬆️'],
            'bi bi-share' => ['label' => 'Paylaş', 'emoji' => '🔗'],
            'bi bi-star' => ['label' => 'Yıldız', 'emoji' => '⭐'],
            'bi bi-heart' => ['label' => 'Kalp', 'emoji' => '❤️'],
            'bi bi-lock' => ['label' => 'Kilit', 'emoji' => '🔒'],
            'bi bi-check' => ['label' => 'Onay', 'emoji' => '✅'],
            'bi bi-x' => ['label' => 'Kapat', 'emoji' => '❌'],
            'bi bi-list' => ['label' => 'Liste', 'emoji' => '☰'],
            'bi bi-grid' => ['label' => 'Izgara', 'emoji' => '▦'],
            'bi bi-filter' => ['label' => 'Filtre', 'emoji' => 'Filter'],
            'bi bi-save' => ['label' => 'Kaydet', 'emoji' => '💾'],
            'bi bi-copy' => ['label' => 'Kopyala', 'emoji' => '📑'],
            'bi bi-file' => ['label' => 'Dosya', 'emoji' => '📄'],
            'bi bi-folder' => ['label' => 'Klasör', 'emoji' => '📁'],
            'bi bi-image' => ['label' => 'Resim', 'emoji' => '🖼️'],
            'bi bi-play' => ['label' => 'Oynat', 'emoji' => '▶️'],
            'bi bi-shuffle' => ['label' => 'Yönlendirme', 'emoji' => '🔀']
        ];
    }

    public static function getSidebarCategoryIcons(): array
    {
        return [
            // MAIN NAVIGATION CATEGORIES
            'bi bi-speedometer2' => ['label' => 'Dashboard', 'emoji' => '📊'],
            'bi bi-collection' => ['label' => 'Blog Kategorileri', 'emoji' => '📝'],
            'bi bi-tags' => ['label' => 'Etiketler & Tags', 'emoji' => '🏷️'],
            'bi bi-folder' => ['label' => 'Dosya Kategorileri', 'emoji' => '📁'],
            'bi bi-grid-3x3' => ['label' => 'Menü Kategorileri', 'emoji' => '🔲'],
            'bi bi-bookmark' => ['label' => 'Sayfa Kategorileri', 'emoji' => '🔖'],
            'bi bi-kanban' => ['label' => 'Proje Kategorileri', 'emoji' => '📋'],
            'bi bi-diagram-3' => ['label' => 'Sistem Kategorileri', 'emoji' => '🗺️'],
            'bi-link' => ['label' => 'Bağlantı', 'emoji' => '🔗'],

            // CONTENT CATEGORIES
            'bi bi-newspaper' => ['label' => 'Haber Kategorileri', 'emoji' => '📰'],
            'bi bi-card-text' => ['label' => 'Makale Kategorileri', 'emoji' => '📃'],
            'bi bi-journal' => ['label' => 'Dergi Kategorileri', 'emoji' => '📓'],
            'bi bi-file-text' => ['label' => 'Doküman Kategorileri', 'emoji' => '📄'],
            'bi bi-images' => ['label' => 'Galeri Kategorileri', 'emoji' => '🖼️'],
            'bi bi-camera' => ['label' => 'Fotoğraf Kategorileri', 'emoji' => '📸'],
            'bi bi-film' => ['label' => 'Video Kategorileri', 'emoji' => '🎞️'],

            // BUSINESS CATEGORIES
            'bi bi-briefcase' => ['label' => 'İş Kategorileri', 'emoji' => '💼'],
            'bi bi-building' => ['label' => 'Şirket Kategorileri', 'emoji' => '🏢'],
            'bi bi-shop' => ['label' => 'Ürün Kategorileri', 'emoji' => '🛍️'],
            'bi bi-cart' => ['label' => 'Sipariş Kategorileri', 'emoji' => '🛒'],
            'bi bi-receipt' => ['label' => 'Fatura Kategorileri', 'emoji' => '🧾'],
            'bi bi-graph-up' => ['label' => 'Rapor Kategorileri', 'emoji' => '📈'],

            // USER & PERMISSION CATEGORIES
            'bi bi-people' => ['label' => 'Kullanıcı Kategorileri', 'emoji' => '👥'],
            'bi bi-person-badge' => ['label' => 'Rol Kategorileri', 'emoji' => '🪪'],
            'bi bi-shield' => ['label' => 'Yetki Kategorileri', 'emoji' => '🛡️'],
            'bi bi-key' => ['label' => 'Erişim Kategorileri', 'emoji' => '🔑'],

            // SYSTEM & TECHNICAL
            'bi bi-gear' => ['label' => 'Ayar Kategorileri', 'emoji' => '⚙️'],
            'bi bi-tools' => ['label' => 'Araç Kategorileri', 'emoji' => '🛠️'],
            'bi bi-puzzle' => ['label' => 'Modül Kategorileri', 'emoji' => '🧩'],
            'bi bi-cpu' => ['label' => 'Sistem Kategorileri', 'emoji' => '🖩']
        ];
    }

    public static function getSidebarMenuIcons(): array
    {
        return [
            // COMMON SIDEBAR MENU ICONS (using keys directly without extra "bi " to simulate exact class)
            'bi-house' => ['label' => 'Anasayfa', 'emoji' => '🏠'],
            'bi-people' => ['label' => 'Kullanıcılar', 'emoji' => '👥'],
            'bi-person-gear' => ['label' => 'Kullanıcı Yönetimi', 'emoji' => '👤⚙️'],
            'bi-gear' => ['label' => 'Ayarlar', 'emoji' => '⚙️'],
            'bi-tools' => ['label' => 'Araçlar', 'emoji' => '🛠️'],
            'bi-wrench' => ['label' => 'Yapılandırma', 'emoji' => '🔧'],
            'bi-bar-chart' => ['label' => 'Raporlar', 'emoji' => '📊'],
            'bi-graph-up' => ['label' => 'İstatistikler', 'emoji' => '📈'],
            'bi-pie-chart' => ['label' => 'Analizler', 'emoji' => '🥧'],
            'bi-folder' => ['label' => 'Dosyalar', 'emoji' => '📁'],
            'bi-folder2-open' => ['label' => 'Klasörler', 'emoji' => '📂'],
            'bi-file-text' => ['label' => 'Dökümanlar', 'emoji' => '📄'],
            'bi-shield' => ['label' => 'Güvenlik', 'emoji' => '🛡️'],
            'bi-shield-check' => ['label' => 'Güvenlik Kontrol', 'emoji' => '🛡️✅'],
            'bi-lock' => ['label' => 'Kilit', 'emoji' => '🔒'],
            'bi-bell' => ['label' => 'Bildirimler', 'emoji' => '🔔'],
            'bi-bell-fill' => ['label' => 'Aktif Bildirimler', 'emoji' => '🔕'],
            'bi-envelope' => ['label' => 'Mesajlar', 'emoji' => '✉️'],
            'bi-envelope-open' => ['label' => 'Okunmuş Mesajlar', 'emoji' => '📧'],
            'bi-chat' => ['label' => 'Sohbet', 'emoji' => '💬'],
            'bi-calendar' => ['label' => 'Takvim', 'emoji' => '📅'],
            'bi-calendar-event' => ['label' => 'Etkinlikler', 'emoji' => '📆'],
            'bi-clock' => ['label' => 'Zaman', 'emoji' => '🕒'],
            'bi-credit-card' => ['label' => 'Ödemeler', 'emoji' => '💳'],
            'bi-wallet' => ['label' => 'Cüzdan', 'emoji' => '👛'],
            'bi-currency-dollar' => ['label' => 'Para', 'emoji' => '💵'],
            'bi-box' => ['label' => 'Ürünler', 'emoji' => '📦'],
            'bi-cart' => ['label' => 'Sepet', 'emoji' => '🛒'],
            'bi-bag' => ['label' => 'Alışveriş', 'emoji' => '🛍️'],
            'bi-clipboard' => ['label' => 'Görevler', 'emoji' => '📋'],
            'bi-list-check' => ['label' => 'Yapılacaklar', 'emoji' => '✅'],
            'bi-bookmark' => ['label' => 'İşaretler', 'emoji' => '🔖'],
            'bi-star' => ['label' => 'Favoriler', 'emoji' => '⭐'],
            'bi-heart' => ['label' => 'Beğeniler', 'emoji' => '❤️'],
            'bi-eye' => ['label' => 'Görüntüleme', 'emoji' => '👁️'],
            'bi-camera' => ['label' => 'Kamera', 'emoji' => '📷'],
            'bi-image' => ['label' => 'Resimler', 'emoji' => '🖼️'],
            'bi-music-note' => ['label' => 'Müzik', 'emoji' => '🎵'],
            'bi-film' => ['label' => 'Video', 'emoji' => '🎞️'],
            'bi-printer' => ['label' => 'Yazdırma', 'emoji' => '🖨️'],
            'bi-download' => ['label' => 'İndirme', 'emoji' => '⬇️'],
            'bi-upload' => ['label' => 'Yükleme', 'emoji' => '⬆️'],
            'bi-share' => ['label' => 'Paylaşım', 'emoji' => '🔗'],
            'bi-link' => ['label' => 'Bağlantı', 'emoji' => '🔗'],
            'bi-globe' => ['label' => 'Web', 'emoji' => '🌐'],
            'bi-wifi' => ['label' => 'Ağ', 'emoji' => '📶'],
            'bi-database' => ['label' => 'Veritabanı', 'emoji' => '🗄️'],
            'bi-server' => ['label' => 'Sunucu', 'emoji' => '🖥️'],
            'bi-cloud' => ['label' => 'Bulut', 'emoji' => '☁️'],
            'bi-lightning' => ['label' => 'Hızlı', 'emoji' => '⚡'],
            'bi-fire' => ['label' => 'Popüler', 'emoji' => '🔥'],
            'bi-trophy' => ['label' => 'Başarılar', 'emoji' => '🏆'],
            'bi-award' => ['label' => 'Ödüller', 'emoji' => '🎖️'],
            'bi-gift' => ['label' => 'Hediyeler', 'emoji' => '🎁'],
            'bi-puzzle' => ['label' => 'Puzzle', 'emoji' => '🧩'],
            'bi-controller' => ['label' => 'Oyun', 'emoji' => '🎮'],
            'bi-headphones' => ['label' => 'Ses', 'emoji' => '🎧'],
            'bi-mic' => ['label' => 'Mikrofon', 'emoji' => '🎙️'],
            'bi-telephone' => ['label' => 'Telefon', 'emoji' => '📞'],
            'bi-phone' => ['label' => 'Mobil', 'emoji' => '📱'],
            'bi-laptop' => ['label' => 'Bilgisayar', 'emoji' => '💻'],
            'bi-tablet' => ['label' => 'Tablet', 'emoji' => '💊'], // using emoji visually
            'bi-smartwatch' => ['label' => 'Akıllı Saat', 'emoji' => '⌚'],
            'bi-tv' => ['label' => 'Televizyon', 'emoji' => '📺'],
            'bi-display' => ['label' => 'Ekran', 'emoji' => '🖥️'],
            'bi-keyboard' => ['label' => 'Klavye', 'emoji' => '⌨️'],
            'bi-mouse' => ['label' => 'Fare', 'emoji' => '🖱️'],
            'bi-cpu' => ['label' => 'İşlemci', 'emoji' => '🖩'],
            'bi-memory' => ['label' => 'Hafıza', 'emoji' => '💾'],
            'bi-hdd' => ['label' => 'Sabit Disk', 'emoji' => '💽'],
            'bi-usb' => ['label' => 'USB', 'emoji' => '🔌'],
            'bi-bluetooth' => ['label' => 'Bluetooth', 'emoji' => '📶'],
            'bi-battery' => ['label' => 'Batarya', 'emoji' => '🔋'],
            'bi-plug' => ['label' => 'Güç', 'emoji' => '🔌'],
            'bi-outlet' => ['label' => 'Priz', 'emoji' => '🔌'],
            'bi-lightbulb' => ['label' => 'Fikir', 'emoji' => '💡'],
            'bi-sun' => ['label' => 'Güneş', 'emoji' => '☀️'],
            'bi-moon' => ['label' => 'Ay', 'emoji' => '🌙'],
            'bi-cloudy' => ['label' => 'Bulutlu', 'emoji' => '☁️'],
            'bi-snow' => ['label' => 'Kar', 'emoji' => '❄️'],
            'bi-umbrella' => ['label' => 'Şemsiye', 'emoji' => '☂️'],
            'bi-thermometer' => ['label' => 'Sıcaklık', 'emoji' => '🌡️'],
            'bi-speedometer' => ['label' => 'Hız', 'emoji' => '🏎️'],
            'bi-fuel-pump' => ['label' => 'Yakıt', 'emoji' => '⛽'],
            'bi-truck' => ['label' => 'Kamyon', 'emoji' => '🚚'],
            'bi-bicycle' => ['label' => 'Bisiklet', 'emoji' => '🚲'],
            'bi-airplane' => ['label' => 'Uçak', 'emoji' => '✈️'],
            'bi-geo' => ['label' => 'Konum', 'emoji' => '📍'],
            'bi-map' => ['label' => 'Harita', 'emoji' => '🗺️'],
            'bi-compass' => ['label' => 'Pusula', 'emoji' => '🧭'],
            'bi-flag' => ['label' => 'Bayrak', 'emoji' => '🏁'],
            'bi-building' => ['label' => 'Bina', 'emoji' => '🏢'],
            'bi-house-door' => ['label' => 'Ev', 'emoji' => '🚪'],
            'bi-shop' => ['label' => 'Mağaza', 'emoji' => '🛍️'],
            'bi-hospital' => ['label' => 'Hastane', 'emoji' => '🏥'],
            'bi-bank' => ['label' => 'Banka', 'emoji' => '🏦'],
            'bi-book' => ['label' => 'Kitap', 'emoji' => '📖'],
            'bi-journal' => ['label' => 'Dergi', 'emoji' => '📓'],
            'bi-newspaper' => ['label' => 'Gazete', 'emoji' => '📰'],
            'bi-pencil' => ['label' => 'Kalem', 'emoji' => '✏️'],
            'bi-brush' => ['label' => 'Fırça', 'emoji' => '🖌️'],
            'bi-palette' => ['label' => 'Palet', 'emoji' => '🎨'],
            'bi-scissors' => ['label' => 'Makas', 'emoji' => '✂️'],
            'bi-hammer' => ['label' => 'Çekiç', 'emoji' => '🔨'],
            'bi-screwdriver' => ['label' => 'Tornavida', 'emoji' => '🪛'],
            'bi-nut' => ['label' => 'Somun', 'emoji' => '🔩'],
            'bi-wrench-adjustable' => ['label' => 'Ayarlanabilir Anahtar', 'emoji' => '🔧'],
            'bi bi-question-square' => ['label' => 'Soru İşareti Kare', 'emoji' => '❓'],
            'bi bi-question-circle' => ['label' => 'Soru İşareti Daire', 'emoji' => '❔'],
            'bi bi-question-octagon' => ['label' => 'Soru İşareti Sekizgen', 'emoji' => '🛑'],
            'bi bi-question-diamond' => ['label' => 'Soru İşareti Elmas', 'emoji' => '💠'],
            'bi bi-patch-question' => ['label' => 'Soru İşareti Yaması', 'emoji' => '🩹'],
            'bi bi-pc-display' => ['label' => 'Bilgisayar Ekranı', 'emoji' => '💻'],
            'bi-pc-display-horizontal' => ['label' => 'Yatay Bilgisayar Ekranı', 'emoji' => '🖥️'],
            'bi-pc' => ['label' => 'Bilgisayar', 'emoji' => 'PC'],
            'bi-shuffle' => ['label' => 'Yönlendirme', 'emoji' => '🔀']
        ];
    }

    public static function getFormActionIcons(): array
    {
        return [
            // CRUD OPERATIONS
            'bi bi-plus-circle' => ['label' => 'Yeni Ekle', 'emoji' => '➕'],
            'bi bi-pencil-square' => ['label' => 'Düzenle', 'emoji' => '✏️'],
            'bi bi-trash' => ['label' => 'Sil', 'emoji' => '🗑️'],
            'bi bi-eye' => ['label' => 'Görüntüle', 'emoji' => '👁️'],
            'bi bi-copy' => ['label' => 'Kopyala', 'emoji' => '📋'],
            'bi bi-archive' => ['label' => 'Arşivle', 'emoji' => '🗄️'],

            // FORM ACTIONS
            'bi bi-check-circle' => ['label' => 'Kaydet', 'emoji' => '✅'],
            'bi bi-x-circle' => ['label' => 'İptal', 'emoji' => '❌'],
            'bi bi-arrow-clockwise' => ['label' => 'Sıfırla', 'emoji' => '🔄'],
            'bi bi-send' => ['label' => 'Gönder', 'emoji' => '🚀'],
            'bi bi-download' => ['label' => 'İndir', 'emoji' => '⬇️'],
            'bi bi-upload' => ['label' => 'Yükle', 'emoji' => '⬆️']
        ];
    }

    public static function getStatusIcons(): array
    {
        return [
            // SUCCESS STATES
            'bi bi-check-circle-fill' => ['label' => 'Başarılı', 'emoji' => '🟢'],
            'bi bi-check-square-fill' => ['label' => 'Tamamlandı', 'emoji' => '✅'],
            'bi bi-shield-check' => ['label' => 'Güvenli', 'emoji' => '🛡️'],
            'bi bi-patch-check' => ['label' => 'Doğrulandı', 'emoji' => '☑️'],

            // ERROR STATES
            'bi bi-x-circle-fill' => ['label' => 'Hata', 'emoji' => '🔴'],
            'bi bi-x-square-fill' => ['label' => 'Başarısız', 'emoji' => '❌'],
            'bi bi-exclamation-triangle-fill' => ['label' => 'Kritik Hata', 'emoji' => '🛑'],
            'bi bi-bug-fill' => ['label' => 'Sistem Hatası', 'emoji' => '🐛'],

            // WARNING STATES
            'bi bi-exclamation-circle-fill' => ['label' => 'Uyarı', 'emoji' => '🟡'],
            'bi bi-exclamation-diamond-fill' => ['label' => 'Dikkat', 'emoji' => '⚠️'],
            'bi bi-shield-exclamation' => ['label' => 'Güvenlik Uyarısı', 'emoji' => '🛡️❗'],

            // INFO STATES
            'bi bi-info-circle-fill' => ['label' => 'Bilgi', 'emoji' => '🔵'],
            'bi bi-info-square-fill' => ['label' => 'Detay', 'emoji' => 'ℹ️'],
            'bi bi-question-circle-fill' => ['label' => 'Yardım', 'emoji' => '❔'],

            // PROCESS STATES
            'bi bi-clock-fill' => ['label' => 'Beklemede', 'emoji' => '⏳'],
            'bi bi-arrow-repeat' => ['label' => 'İşleniyor', 'emoji' => '🔄'],
            'bi bi-hourglass-split' => ['label' => 'Yükleniyor', 'emoji' => '⌛'],
            'bi bi-pause-circle-fill' => ['label' => 'Duraklatıldı', 'emoji' => '⏸️'],

            // CONNECTION STATES
            'bi bi-wifi' => ['label' => 'Bağlı', 'emoji' => '📶'],
            'bi bi-wifi-off' => ['label' => 'Bağlantı Yok', 'emoji' => '📴'],
            'bi bi-cloud-check' => ['label' => 'Senkronize', 'emoji' => '☁️✅'],
            'bi bi-cloud-slash' => ['label' => 'Offline', 'emoji' => '☁️❌']
        ];
    }

    public static function getRecommendedIcons(string $useCase): array
    {
        $recommendations = [
            'sidebar_menu' => self::getSidebarCategoryIcons(),
            'form_actions' => self::getFormActionIcons(),
            'status_indicators' => self::getStatusIcons(),
            'admin_panel' => self::getAdminIcons(),
            'developer_panel' => self::getDeveloperIcons(),
            'popular_choices' => self::getPopularIcons()
        ];

        return $recommendations[$useCase] ?? [];
    }

    public static function getContextualIcons(string $context, string $action = ''): array
    {
        $contextual = [
            'user' => [
                'list' => ['class' => 'bi bi-people', 'label' => 'Kullanıcılar', 'emoji' => '👥'],
                'add' => ['class' => 'bi bi-person-plus', 'label' => 'Kullanıcı Ekle', 'emoji' => '👤➕'],
                'edit' => ['class' => 'bi bi-person-gear', 'label' => 'Düzenle', 'emoji' => '⚙️'],
                'delete' => ['class' => 'bi bi-person-x', 'label' => 'Sil', 'emoji' => '❌'],
                'view' => ['class' => 'bi bi-person-check', 'label' => 'Görüntüle', 'emoji' => '✅']
            ],
            'content' => [
                'list' => ['class' => 'bi bi-file-text', 'label' => 'İçerikler', 'emoji' => '📄'],
                'add' => ['class' => 'bi bi-file-plus', 'label' => 'İçerik Ekle', 'emoji' => '📄➕'],
                'edit' => ['class' => 'bi bi-pencil-square', 'label' => 'Düzenle', 'emoji' => '✏️'],
                'delete' => ['class' => 'bi bi-trash', 'label' => 'Sil', 'emoji' => '🗑️'],
                'view' => ['class' => 'bi bi-eye', 'label' => 'Görüntüle', 'emoji' => '👁️']
            ]
        ];

        if ($action && isset($contextual[$context][$action])) {
            return [$contextual[$context][$action]['class'] => $contextual[$context][$action]];
        }

        return $contextual[$context] ?? [];
    }

    /**
     * AI-Like Matcher: Takes a keyword and returns the best matching icon class.
     * 
     * @param string $keyword The search keyword.
     * @return string|null The best icon class, or null if no match.
     */
    public static function matchKeyword(string $keyword): ?string
    {
        $keyword = mb_strtolower(trim($keyword));

        $dictionary = [
            'ayar' => 'bi-gear',
            'setting' => 'bi-gear',
            'kullanıcı' => 'bi-people',
            'user' => 'bi-people',
            'üye' => 'bi-people',
            'dashboard' => 'bi-speedometer2',
            'panel' => 'bi-house-gear',
            'seo' => 'bi-globe',
            'ekle' => 'bi-plus-circle',
            'yeni' => 'bi-plus-circle',
            'sil' => 'bi-trash',
            'delete' => 'bi-trash',
            'güncelle' => 'bi-arrow-clockwise',
            'update' => 'bi-arrow-clockwise',
            'düzenle' => 'bi-pencil-square',
            'edit' => 'bi-pencil-square',
            'profil' => 'bi-person',
            'profile' => 'bi-person',
            'çıkış' => 'bi-box-arrow-right',
            'logout' => 'bi-box-arrow-right',
            'giriş' => 'bi-box-arrow-in-right',
            'login' => 'bi-box-arrow-in-right',
            'hata' => 'bi-exclamation-triangle',
            'error' => 'bi-exclamation-triangle',
            'başarılı' => 'bi-check-circle',
            'success' => 'bi-check-circle',
            'güvenlik' => 'bi-shield-check',
            'security' => 'bi-shield-check',
            'şifre' => 'bi-key',
            'password' => 'bi-key',
            'dosya' => 'bi-file-earmark',
            'file' => 'bi-file-earmark',
            'resim' => 'bi-image',
            'image' => 'bi-image',
            'video' => 'bi-film',
            'mail' => 'bi-envelope',
            'mesaj' => 'bi-chat-dots',
            'message' => 'bi-chat-dots',
            'iletişim' => 'bi-telephone',
            'contact' => 'bi-telephone',
            'hakkımızda' => 'bi-info-circle',
            'about' => 'bi-info-circle',
            'yardım' => 'bi-question-circle',
            'help' => 'bi-question-circle'
        ];

        foreach ($dictionary as $word => $class) {
            if (str_contains($keyword, $word)) {
                return $class;
            }
        }

        return null;
    }
}
