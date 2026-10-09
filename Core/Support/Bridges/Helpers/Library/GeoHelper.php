<?php
namespace Rbn\Framework\Core\Support\Bridges\Helpers\Library;

/**
 * GeoHelper - Coğrafi veriler ve lokasyon araçları (RBN Framework Core) 🌍🛰️⚓
 * RBN Framework: Tüm veriler merkezi JSON kaynaklarından beslenir.
 */
class GeoHelper
{
    /** @var array|null Veri Önbelleği */
    protected ?array $locationData = null;

    /**
     * Merkezi lokasyon verisini yükler 🧬
     */
    protected function loadData(): array
    {
        if ($this->locationData !== null) {
            return $this->locationData;
        }

        // Framework içindeki merkezi JSON dosyasına ulaş
        $path = dirname(__DIR__, 5) . '/Resources/Data/Locations/tr-locations.json';
        
        if (file_exists($path)) {
            $json = file_get_contents($path);
            $this->locationData = json_decode($json, true) ?: [];
        } else {
            $this->locationData = [];
        }

        return $this->locationData;
    }

    public function countries(): array
    {
        $data = $this->countryFlags();
        $list = [];
        foreach ($data as $code => $info) {
            $list[$code] = $info['name'];
        }
        return $list;
    }

    /**
     * Ülke Bayrakları ve Detayları 🏳️
     */
    public function countryFlags(): array
    {
        $path = dirname(__DIR__, 5) . '/Resources/Data/Locations/countries.json';
        if (file_exists($path)) {
            $json = file_get_contents($path);
            return json_decode($json, true) ?: [];
        }
        return [];
    }

    /**
     * Türkiye'nin 81 ilini merkezi JSON'dan alfabetik olarak döndürür 🌍
     */
    public function trCities(): array
    {
        $data = $this->loadData();
        $cities = array_keys($data);
        sort($cities, SORT_LOCALE_STRING);
        return $cities;
    }

    /**
     * Seçilen ile ait ilçeleri döndürür 🏙️
     */
    public function trDistricts(string $city): array
    {
        $data = $this->loadData();
        return $data[$city] ?? [];
    }
}
