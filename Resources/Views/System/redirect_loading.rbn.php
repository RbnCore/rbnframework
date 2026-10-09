<?php if ($isTemplate ?? false): ?>
<template id="rbn-redirect-loader-template">
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yönlendiriliyorsunuz...</title>
    <link rel="stylesheet" href="<?= url('framework-assets/rbncommon/css/rbn-master.css') ?>">
    <link rel="stylesheet" href="<?= \Rbn\Framework\Core\Support\Definitions\Render\AssetDefinition::REMIX_ICON ?>" integrity="<?= \Rbn\Framework\Core\Support\Definitions\Render\AssetDefinition::REMIX_ICON_ATTRS['integrity'] ?>" crossorigin="anonymous">
    <style>
        body, html {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
        }
    </style>
</head>
<body>
<div class="rbn-redirect-screen" id="rbn-redirect-loader" style="display: flex;">
    <div class="rbn-redirect-card">
        <div class="rbn-spinner-box">
            <div class="rbn-glow"></div>
            <div class="rbn-spinner">
                <div class="rbn-spinner-inner"></div>
            </div>
        </div>
        <h2 id="rbn-redirect-title">Yönlendiriliyorsunuz...</h2>
        <p id="rbn-redirect-subtitle">Lütfen bekleyin, işleminiz gerçekleştiriliyor.</p>

        <div class="rbn-progress-bar">
            <div class="rbn-progress-line" style="transform: scaleX(0.9);"></div>
        </div>
    </div>
</div>
</body>
</html>
</template>
<?php else: ?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars((string) ($message ?? 'Yönlendiriliyorsunuz...')) ?></title>
    <link rel="stylesheet" href="<?= url('framework-assets/rbncommon/css/rbn-master.css') ?>">
    <link rel="stylesheet" href="<?= \Rbn\Framework\Core\Support\Definitions\Render\AssetDefinition::REMIX_ICON ?>" integrity="<?= \Rbn\Framework\Core\Support\Definitions\Render\AssetDefinition::REMIX_ICON_ATTRS['integrity'] ?>" crossorigin="anonymous">
    <style>
        body, html {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
        }
    </style>
</head>
<body>
<div class="rbn-redirect-screen" id="rbn-redirect-loader" style="display: flex;"
    data-redirect-url="<?= isset($redirectUrl) ? htmlspecialchars((string) $redirectUrl, ENT_QUOTES, 'UTF-8') : '' ?>"
    data-email="<?= isset($email) ? htmlspecialchars((string) $email, ENT_QUOTES, 'UTF-8') : '' ?>"
    data-delay="<?= (int) ($delay ?? 0) ?>">
    <div class="rbn-redirect-card">
        <div class="rbn-spinner-box">
            <div class="rbn-glow"></div>
            <div class="rbn-spinner">
                <div class="rbn-spinner-inner"></div>
            </div>
        </div>
        <h2><?= htmlspecialchars((string) ($message ?? 'Yönlendiriliyorsunuz...')) ?></h2>
        <p><?= htmlspecialchars((string) ($subMessage ?? 'Lütfen bekleyin, işleminiz güvenli bir şekilde gerçekleştiriliyor.')) ?></p>

        <div class="rbn-progress-bar">
            <div class="rbn-progress-line"></div>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var loader = document.getElementById('rbn-redirect-loader');
        if (loader) {
            var progressLine = loader.querySelector('.rbn-progress-line');
            if (progressLine) {
                setTimeout(function() {
                    progressLine.style.transform = 'scaleX(1)';
                }, 50);
            }
            var redirectUrl = loader.getAttribute('data-redirect-url');
            var delay = parseInt(loader.getAttribute('data-delay') || '0', 10);
            if (redirectUrl) {
                setTimeout(function() {
                    window.location.href = redirectUrl;
                }, Math.max(delay, 500));
            }
        }
    });
</script>
</body>
</html>
<?php endif; ?>