<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnFactory\Handlers;

use Rbn\Framework\Core\Base\BaseComponent;
use Rbn\Framework\Core\System\Paths\Paths;

/**
 * FactoryConfigHandler - Sitenin fabrika ayarlarını ve imza anahtarını okur 🏭⚙️
 *
 * Ayarlar: `Core/Tasks/<ProjectKey>Factory.php` (site başına AYRI sınıf, `SETTINGS` sabiti).
 * İmza anahtarı: projenin `z_settings_api` tablosu (setting_type `factory`, group_key `factory`,
 * setting_key `FACTORY_SIGNING_KEY`, project_key = sitenin anahtarı; framework standardı SettingsApiRepository).
 * Ayar dosyası yoksa, anahtar kaydı yoksa ya da is_active=0 ise paket o sitede PASİFTİR: rota eklenmez, uçlar 404 verir.
 * Siteye özel hiçbir değer (imza anahtarı dahil) paketin içinde durmaz.
 * Şablon: Packages/RbnFactory/factory-settings.example.php
 */
class FactoryConfigHandler extends BaseComponent
{
    /** z_settings_api kaydı: imza anahtarı */
    public const SIGNING_KEY_NAME = 'FACTORY_SIGNING_KEY';
    public const SIGNING_KEY_TYPE = 'factory';
    public const SIGNING_KEY_GROUP = 'factory';
    public const SIGNING_KEY_LABEL = 'İçerik Fabrikası imza anahtarı';
    public const SETTINGS_NAMESPACE = 'Rbn\\Project\\Core\\Tasks\\';
    public const SETTINGS_SUFFIX = 'Factory';

    /** @var array<string,string|null> site anahtarı => imza anahtarı (süreç başına bir kez; kayıt yoksa null) */
    private static array $keys = [];

    /** @var array<string,array<string,mixed>> site anahtarı => ayarlar */
    private static array $settings = [];

    /** Cron gibi istek dışı bağlamlarda site anahtarını elle belirler */
    private static ?string $siteKey = null;

    /** Bu istek/görev hangi sitenin: elle verilen, yoksa framework'ün aktif proje anahtarı */
    public function siteKey(): string
    {
        return self::$siteKey ?? (string) (function_exists('active_project_key') ? active_project_key() : project_key());
    }

    public function projectKey(): string
    {
        return $this->siteKey();
    }

    /** Cron görevi gibi bağlamlarda çalışılacak siteyi seçer (boş = aktif proje) */
    public function useSite(string $siteKey): void
    {
        self::$siteKey = $siteKey !== '' ? $siteKey : null;
    }

    public function settingsPath(): string
    {
        return Paths::project()->tasks($this->settingsClassName() . '.php');
    }

    /** Fabrika bu sitede açık mı: sitenin ayar dosyası var VE z_settings_api'de etkin anahtar kaydı var */
    public function exists(): bool
    {
        return $this->all() !== [] && $this->rawSigningKey() !== null;
    }

    /** İmza anahtarı (ham; doğrulama FactorySignatureHandler'da). Kayıt yoksa ya da is_active=0 ise null. */
    public function rawSigningKey(): ?string
    {
        $siteKey = $this->siteKey();
        if (!array_key_exists($siteKey, self::$keys)) {
            $value = $this->repository('project.settingsApi')?->getApiKey(self::SIGNING_KEY_NAME, null, $siteKey);
            self::$keys[$siteKey] = is_string($value) ? $value : null;
        }
        return self::$keys[$siteKey];
    }

    /** @return array<string,mixed> yalnız bu sitenin ayarları (dosya yoksa boş) */
    public function all(): array
    {
        $siteKey = $this->siteKey();
        if (!isset(self::$settings[$siteKey])) {
            self::$settings[$siteKey] = $this->loadSettings();
        }
        return self::$settings[$siteKey];
    }

    /** @return array<string,mixed> */
    private function loadSettings(): array
    {
        if (!preg_match('/^[a-z0-9_-]+$/', $this->siteKey())) {
            return [];
        }
        $file = $this->settingsPath();
        if (!is_file($file)) {
            return [];
        }
        require_once $file;
        $class = self::SETTINGS_NAMESPACE . $this->settingsClassName();
        $settings = class_exists($class, false) ? constant($class . '::SETTINGS') : [];
        return is_array($settings) ? $settings : [];
    }

    /** project_key => sınıf adı: baş harf büyük, ayraçlar atılır (<project_key> → <ProjectKey>Factory) */
    private function settingsClassName(): string
    {
        return str_replace(['-', '_'], '', ucwords($this->siteKey(), '-_')) . self::SETTINGS_SUFFIX;
    }

    /** Noktalı yol destekler: get('selection.max_pending', 3) */
    public function get(string $path, mixed $default = null): mixed
    {
        $value = $this->all();
        foreach (explode('.', $path) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        return $value;
    }

    /** Tablo adı ayarı (tables.drafts / tables.jobs); yalnız güvenli karakterler */
    public function table(string $name): string
    {
        return $this->safeTable((string) $this->get('tables.' . $name, ''), 'tables.' . $name);
    }

    /** İçerik türünün yazı tablosu (content_types.<tür>.table) */
    public function postTable(string $contentType): string
    {
        return $this->safeTable((string) $this->get('content_types.' . $contentType . '.table', ''), 'content_types.' . $contentType . '.table');
    }

    /** @return array<string,array<string,mixed>> yalnız açık olan içerik türleri */
    public function enabledTypes(): array
    {
        return array_filter(
            (array) $this->get('content_types', []),
            static fn($type): bool => is_array($type) && !empty($type['enabled'])
        );
    }

    /** @return array<string,mixed> */
    public function contentType(string $contentType): array
    {
        $type = $this->get('content_types.' . $contentType, []);
        return is_array($type) ? $type : [];
    }

    private function safeTable(string $name, string $setting): string
    {
        if (!preg_match('/^[A-Za-z0-9_]+$/', $name)) {
            throw new \RuntimeException("Geçersiz tablo adı ayarı: {$setting}");
        }
        return $name;
    }
}
