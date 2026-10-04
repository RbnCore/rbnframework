<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages;

use Rbn\Framework\Core\Base\Data\BaseConfig;

/**
 * PackageData - RBN Packages Sovereign Registry 🏛️🛰️⚓
 * 
 * RBN 3.5: Centralized orchestrator for all standalone packages.
 */
class PackageData extends BaseConfig
{
    public function registerMap(): array
    {
        return [
            // --- [ SERVICES ] ---
            'services' => [
                'file' => \Rbn\Framework\Packages\RbnFile\Services\FileService::class,
                'image' => \Rbn\Framework\Packages\RbnFile\Services\ImageService::class,
                'email' => \Rbn\Framework\Packages\RbnEmail\Services\EmailService::class,
                'gemini' => \Rbn\Framework\Packages\RbnApi\Services\GeminiService::class,
                'instagram' => \Rbn\Framework\Packages\RbnApi\Services\InstagramService::class,
                'facebook' => \Rbn\Framework\Packages\RbnApi\Services\FacebookService::class,
                'twitter' => \Rbn\Framework\Packages\RbnApi\Services\TwitterService::class,
                'youtube' => \Rbn\Framework\Packages\RbnApi\Services\YoutubeService::class,
                'google' => \Rbn\Framework\Packages\RbnApi\Services\GoogleService::class,
                'shopier' => \Rbn\Framework\Packages\RbnApi\Services\ShopierService::class,
                'tmdb' => \Rbn\Framework\Packages\RbnApi\Services\TmdbService::class,
                'api' => \Rbn\Framework\Packages\RbnApi\ApiService::class,
                'indexNow' => \Rbn\Framework\Packages\RbnApi\Services\IndexNowService::class,
                'telegram' => \Rbn\Framework\Packages\RbnApi\Services\TelegramService::class,
                'rssParser' => \Rbn\Framework\Packages\RbnUtility\Services\RssParserService::class,
                'googleTrends' => \Rbn\Framework\Packages\RbnUtility\Services\GoogleTrendsService::class,
            ],


            // --- [ MANAGERS ] ---
            'managers' => [
                'api' => \Rbn\Framework\Packages\RbnApi\Managers\ApiManager::class,
                'aiUsage' => \Rbn\Framework\Packages\RbnApi\Managers\AiUsageManager::class,
                'autoTask' => \Rbn\Framework\Packages\RbnPipeline\Services\AutoTaskManager::class,
                'externalFetch' => \Rbn\Framework\Packages\RbnPipeline\Services\ExternalFetchManager::class,
            ],

            // --- [ HANDLERS ] ---
            'handlers' => [
                'fileUpload' => \Rbn\Framework\Packages\RbnFile\Handlers\FileUploadHandler::class,
                'fileValidator' => \Rbn\Framework\Packages\RbnFile\Handlers\FileValidatorHandler::class,
                'fileUtility' => \Rbn\Framework\Packages\RbnFile\Handlers\FileUtilityHandler::class,
                'fileExport' => \Rbn\Framework\Packages\RbnFile\Handlers\FileExportHandler::class,
                'fileImport' => \Rbn\Framework\Packages\RbnFile\Handlers\FileImportHandler::class,
                'fileImage' => \Rbn\Framework\Packages\RbnFile\Handlers\FileImageHandler::class,

                // --- [ EMAIL HANDLERS ] ---
                'emailConfig' => \Rbn\Framework\Packages\RbnEmail\Handlers\EmailConfigHandler::class,
                'emailGuard' => \Rbn\Framework\Packages\RbnEmail\Handlers\EmailGuardHandler::class,
                'emailRender' => \Rbn\Framework\Packages\RbnEmail\Handlers\EmailRenderHandler::class,
                'emailTransport' => \Rbn\Framework\Packages\RbnEmail\Handlers\EmailTransportHandler::class,
                'emailImap' => \Rbn\Framework\Packages\RbnEmail\Handlers\ImapClientHandler::class,
                'emailParser' => \Rbn\Framework\Packages\RbnEmail\Handlers\MailParserHandler::class,
            ],

            // --- [ RULES ] ---
            'rules' => [
                'promptRule.json' => \Rbn\Framework\Packages\RbnPipeline\Rules\Prompt\JsonResponseRule::class,
                'promptRule.seo' => \Rbn\Framework\Packages\RbnPipeline\Rules\Prompt\SeoMetaRule::class,
                'promptRule.image' => \Rbn\Framework\Packages\RbnPipeline\Rules\Prompt\ImagePromptRule::class,
                'promptRule.imageSafety' => \Rbn\Framework\Packages\RbnPipeline\Rules\Prompt\ImageSafetyRule::class,
                'promptRule.imageCompiler' => \Rbn\Framework\Packages\RbnPipeline\Rules\Prompt\ImageCompilerRule::class,
                'promptRule.youtube' => \Rbn\Framework\Packages\RbnPipeline\Rules\Youtube\YoutubeScriptRule::class,
                'promptRule.innerLinking' => \Rbn\Framework\Packages\RbnPipeline\Rules\Blog\InnerLinkingRule::class,
                'promptRule.blogStructure' => \Rbn\Framework\Packages\RbnPipeline\Rules\Blog\BlogStructureRule::class,
                'promptRule.writingTone' => \Rbn\Framework\Packages\RbnPipeline\Rules\Prompt\WritingToneRule::class,
                'promptRule.identity' => \Rbn\Framework\Packages\RbnPipeline\Rules\Prompt\IdentityRule::class,
                'promptRule.contentQuality' => \Rbn\Framework\Packages\RbnPipeline\Rules\Blog\ContentQualityRule::class,
            ],

            // --- [ PRESETS ] ---
            'presets' => [
                'youtube' => \Rbn\Framework\Packages\RbnPipeline\Presets\YoutubePreset::class,
                'blog' => \Rbn\Framework\Packages\RbnPipeline\Presets\BlogPreset::class,
                'trends' => \Rbn\Framework\Packages\RbnPipeline\Presets\TrendsPreset::class,
                'custom' => \Rbn\Framework\Packages\RbnPipeline\Presets\CustomPreset::class,
                'news' => \Rbn\Framework\Packages\RbnPipeline\Presets\NewsPreset::class,
                'image' => \Rbn\Framework\Packages\RbnPipeline\Presets\ImagePreset::class,
            ],

            // --- [ BUILDERS ] ---
            'builders' => [
                'prompt' => \Rbn\Framework\Packages\RbnPipeline\Builders\PromptBuilder::class,
                'task.content' => \Rbn\Framework\Packages\RbnPipeline\Builders\Tasks\ContentTaskBuilder::class,
                'task.content_rewrite' => \Rbn\Framework\Packages\RbnPipeline\Builders\Tasks\ContentRewriteTaskBuilder::class,
            ],

            // --- [ PROVIDERS ] ---
            'providers' => [
                'apiGemini' => \Rbn\Framework\Packages\RbnApi\Providers\GeminiProvider::class,
                'apiInstagram' => \Rbn\Framework\Packages\RbnApi\Providers\InstagramProvider::class,
                'apiFacebook' => \Rbn\Framework\Packages\RbnApi\Providers\FacebookProvider::class,
                'apiTwitter' => \Rbn\Framework\Packages\RbnApi\Providers\TwitterProvider::class,
                'apiYoutube' => \Rbn\Framework\Packages\RbnApi\Providers\YoutubeProvider::class,
                'apiShopier' => \Rbn\Framework\Packages\RbnApi\Providers\ShopierProvider::class,
                'googleMaps' => \Rbn\Framework\Packages\RbnApi\Providers\GoogleMapsProvider::class,
                'googleAnalytics' => \Rbn\Framework\Packages\RbnApi\Providers\GoogleAnalyticsProvider::class,
                'apiTmdb' => \Rbn\Framework\Packages\RbnApi\Providers\TmdbProvider::class,
                'apiTelegram' => \Rbn\Framework\Packages\RbnApi\Providers\TelegramProvider::class,
            ],

            // --- [ METADATA ] ---
            'metadata' => [
                'EMAIL_' => \Rbn\Framework\Packages\RbnEmail\Models\EmailConstant::class,
            ],
        ];
    }
}
