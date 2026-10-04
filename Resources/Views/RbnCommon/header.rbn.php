@php
    $projectKey = project_key() ?: 'default';
    $layoutsDir = \Rbn\Framework\Core\System\Paths\Paths::project()->layouts("Headers");
    $projectHeaderFile = $layoutsDir . DIRECTORY_SEPARATOR . $projectKey . ".rbn.php";

    if (!file_exists($projectHeaderFile)) {
        $files = glob($layoutsDir . DIRECTORY_SEPARATOR . "*.rbn.php");
        $projectHeaderFile = !empty($files) ? $files[0] : null;
    }
@endphp
<!DOCTYPE html>
<html lang="{{ DEFAULT_LANGUAGE }}">

<head>
    {!! $appSeoHtml ?? '' !!}
    <!-- Framework Dynamic Assets (Bootstrap, RemixIcon, Master.css etc.) -->
    {!! $headerAssets ?? '' !!}

    {!! $appSchemaHtml ?? '' !!}

    {!! $headStateHtml ?? '' !!}
    {!! $google_analytics_code ?? '' !!}
    {!! $google_adsense_code ?? '' !!}
    {!! $head_scripts ?? '' !!}
</head>

<body class="{{ $body_class ?? '' }}">
    {!! $body_scripts ?? '' !!}

    @if(!($no_navbar ?? false))
        <header>
            @import('framework', 'Resources/Views/RbnCommon/navbar.rbn.php')
        </header>
    @endif

    @if($projectHeaderFile && file_exists($projectHeaderFile))
        <?php require $this->compile($projectHeaderFile); ?>
    @endif
