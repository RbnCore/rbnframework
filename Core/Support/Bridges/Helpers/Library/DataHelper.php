<?php

namespace Rbn\Framework\Core\Support\Bridges\Helpers\Library;

use Rbn\Framework\Core\System\Paths\Paths;

/**
 * DataHelper - Unified Resource Data Gateway 🗃️🪐
 * RBN 3.5 Masterpiece Standard.
 */
class DataHelper
{
    /**
     * Reads and queries any JSON data file from the framework's Resources/Data folder.
     * Supports both indexed arrays of objects (locales.json) and associative key-value maps (countries.json).
     *
     * @param string $fileName The JSON file name without extension (e.g. 'locales', 'countries')
     * @param string|null $searchValue Optional value to search for (e.g. 'en', 'AD')
     * @param string $searchKey The key to match the search value against (e.g. 'language', 'id')
     * @param string|null $returnKey Optional specific field to return (e.g. 'name')
     * @return mixed Array of all items, single matched item, single field value, or null
     */
    public function get(string $fileName, ?string $searchValue = null, string $searchKey = 'id', ?string $returnKey = null)
    {
        $path = Paths::frameworkRoot() . '/Resources/Data/' . ltrim($fileName, '/\\') . '.json';
        if (!file_exists($path)) {
            return $searchValue !== null ? null : [];
        }

        $data = json_decode(file_get_contents($path), true);
        if (!is_array($data)) {
            return $searchValue !== null ? null : [];
        }

        // 1. If no search value is specified, return the entire data array/object
        if ($searchValue === null) {
            return $data;
        }

        $searchValueLower = strtolower(trim($searchValue));

        // 2. Search logic that supports both Indexed lists and Associative maps
        foreach ($data as $key => $item) {
            // A. Check if the searchValue matches the main array key (e.g., "AD" in countries.json)
            if (in_array(strtolower($searchKey), ['id', 'key', 'code', 'index']) && strtolower(trim((string)$key)) === $searchValueLower) {
                return $this->resolveReturn($item, $returnKey);
            }

            // B. If the item itself is an array/object, check its internal fields
            if (is_array($item)) {
                $itemValue = $item[$searchKey] ?? '';
                if (strtolower(trim((string)$itemValue)) === $searchValueLower) {
                    return $this->resolveReturn($item, $returnKey);
                }
            } else {
                // C. If the item is a flat value (e.g. tr-locations.json where value is a flat array of districts)
                if (strtolower(trim((string)$key)) === $searchValueLower) {
                    return $item;
                }
            }
        }

        return null;
    }

    /**
     * Resolves the value to be returned, handling specific key mapping and parenthesis cleaning.
     */
    private function resolveReturn($item, ?string $returnKey = null)
    {
        if ($returnKey === null || !is_array($item)) {
            return $item;
        }

        $val = $item[$returnKey] ?? null;

        // Auto-clean parentheses for name/language keys (e.g. "İngilizce (English)" -> "İngilizce")
        if (is_string($val) && strpos($val, ' (') !== false) {
            $parts = explode(' (', $val);
            return trim($parts[0]);
        }

        return $val;
    }
}
