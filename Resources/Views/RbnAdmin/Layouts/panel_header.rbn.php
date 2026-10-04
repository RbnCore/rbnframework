<!DOCTYPE html>
<html lang="tr" data-theme="<?= $adminTheme ?? 'blue' ?>">

<head>
    <?= $seoHtml ?? '' ?>
    <!-- [FW-F08] AJAX/CSRF: rbnService.js tokeni buradan okur. Once meta etiketi
         YOKTI; bosta sayfa hicbir yerde uretilmiyordu ve JS'in
         `_getCsrf()` null donuyordu -> framework AJAX POST'lari token'siz
         kalirdi. CSRF zorunlu kilininca panelin JS akislari kirilmamasi icin
         meta etiketi her panel sayfasinda bulunmali. -->
    <?= $this->csrfMeta(); ?>
    <!-- App Assets & Branding -->
<?= $headerAssets ?? '' ?>
<?= $headStateHtml ?? '' ?>
</head>

<body>
    <div class="rbn-dash-layout">
        <!-- Sidebar Component -->
        @import('framework', 'Resources/Views/RbnAdmin/Layouts/sidebar.rbn.php')

        <!-- Main Content -->
        <div class="rbn-dash-content">
            <!-- Top Navbar Component -->
            @import('framework', 'Resources/Views/RbnAdmin/Layouts/navbar.rbn.php')

            <!-- Automated Content Wrapper (Scrollable Body) -->
            <div class="rbn-dash-main-scroll flex-grow-1">
                <main class="container-fluid py-4 px-4">
                    <!-- Automated Page Header -->
                    @import('framework', 'Resources/Views/RbnAdmin/Layouts/content_header.rbn.php')