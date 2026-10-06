<!-- 🌌 RBN Dashboard - Command Center View 🏛️🛰️⚓ -->


@if(!empty($stats['today_hits']) || isset($stats['active_now']))
<!-- 🌐 Web Traffic Summary Stat Cards Component -->
@import('framework', 'Resources/Views/RbnAdmin/Components/webtraffic_stat_cards.rbn.php')
@endif

@if(!empty($botActive) && !empty($aiReport['summary']['total_requests']) && $aiReport['summary']['total_requests'] > 0)
<!-- 🤖 AI Telemetry & Usage Stat Cards (Auto-enabled when bot_activity is active and total_requests > 0) -->
@php
$summary = $aiReport['summary'];
$totalCostTry = $aiReport['totalCostTry'] ?? 0;
$textCostTry = $aiReport['textCostTry'] ?? 0;
$imageCostTry = $aiReport['imageCostTry'] ?? 0;
$containerId = 'dashboard-ai-usage-stats';
@endphp
@import('framework', 'Resources/Views/RbnAdmin/Components/ai_stat_cards.rbn.php')
@endif

@php
$dashboardsDir = \Rbn\Framework\Core\System\Paths\Paths::project()->dashboards();
$projectDashboardFile = $dashboardsDir . DIRECTORY_SEPARATOR . $projectKey . ".rbn.php";
@endphp
@if(file_exists($projectDashboardFile))
<?php require $this->compile($projectDashboardFile); ?>
@endif