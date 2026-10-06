<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, follow">
    <meta name="error_description" content="<?= htmlspecialchars(strip_tags($error_message ?? 'Diagnostic Report')) ?>">

    <!-- RBN Shield High-Fidelity Diagnostic Metadata ✨🏺 -->
    <title><?= ($branding['shield_seo_title'] ?? 'RbnShield | Sistem Koruması ve Hata Yönetimi') ?></title>
    <meta name="description" content="<?= ($branding['shield_seo_desc'] ?? 'RbnShield Diagnostic Report') ?>">
    <meta name="keywords" content="<?= $branding['keywords'] ?? 'rbnframework' ?>">
    <link rel="canonical" href="<?= $branding['site_url'] ?? '#' ?>">

    <!-- 👤 2. BRANDING & FRAMEWORK -->
    <meta name="author" content="<?= $branding['author'] ?? 'RbnBilisim' ?>">
    <meta name="designer" content="<?= $branding['designer'] ?? 'RBN Framework' ?>">
    <meta name="generator" content="<?= trim(($branding['fw_name'] ?? 'RBN') . ' ' . (($branding['fw_version'] ?? '3.5') !== '' ? 'v' . ($branding['fw_version'] ?? '3.5') : '')) ?>">
    <meta name="rbnshield-version"
        content="<?= $branding['shield_name'] ?? 'RbnShield' ?> <?= $branding['shield_version'] ?? 'v2.1' ?>">
    <meta name="theme-color" content="#020617">

    <!-- 📣 3. OPENGRAPH (Shield Identity) -->
    <meta property="og:title" content="<?= ($errorType ?? 'Hata') ?> | <?= $branding['shield_name'] ?? 'RbnShield' ?>">
    <meta property="og:description"
        content="<?= ($branding['shield_seo_desc'] ?? 'Professional Error & Security Service') ?>">
    <meta property="og:url" content="<?= $branding['site_url'] ?? '#' ?>">
    <meta property="og:site_name" content="<?= $branding['shield_name'] ?? 'RbnShield' ?>">
    <meta property="og:type" content="website">

    <!-- 🐦 4. TWITTER CARD (Shield Identity) -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= ($errorType ?? 'Hata') ?> | <?= $branding['shield_name'] ?? 'RbnShield' ?>">
    <meta name="twitter:description"
        content="<?= ($branding['shield_seo_desc'] ?? 'Professional Error & Security Service') ?>">

    <!-- 1. Fonts & Icons (Base) 🏹🪐🛡️ -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">

    <!-- 2. RBN Master Design Engine (Framework CSS) 🏛️💎 -->
    <link rel="stylesheet" href="/framework-assets/rbncommon/css/rbn-master.css?v=<?= $asset_v ?>">
    <link rel="stylesheet" href="/framework-assets/rbncommon/css/rbn-shield.css?v=<?= $asset_v ?>" id="rbn_shield_master_css">
</head>

<body class="shield-error-layout <?= $view ?? '' ?> d-flex align-items-center justify-content-center min-vh-100 p-3">
    <!-- Ana İçerik Konteyneri (RBN Core Layout Engine) -->
    <main class="rbn-container">
