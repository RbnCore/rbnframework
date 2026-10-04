@php
    $projectKey = project_key() ?: 'default';
    $navbarsDir = \Rbn\Framework\Core\System\Paths\Paths::project()->components("Navbars");
    $projectNavbarFile = $navbarsDir . DIRECTORY_SEPARATOR . $projectKey . ".rbn.php";
@endphp

<?php require $this->compile($projectNavbarFile); ?>
