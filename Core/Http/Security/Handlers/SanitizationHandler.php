<?php
declare(strict_types=1);

namespace Rbn\Framework\Core\Http\Security\Handlers;

use Rbn\Framework\Core\Base\BaseComponent;

/**
 * SanitizationHandler - The Data Cleaner Actor 🛡️🧹
 * 
 * RBN 3.5: Atomic actor for trimming, filtering and cleaning form data.
 */
class SanitizationHandler extends BaseComponent
{
    /**
     * Recursively trims all string values in an array.
     */
    public function trimData(array $data): array
    {
        array_walk_recursive($data, function (&$value) {
            if (is_string($value)) {
                $value = trim($value);
            }
        });
        return $data;
    }

    /**
     * Strips HTML tags from specific fields or the entire dataset.
     */
    public function stripTags(array $data, array $fields = []): array
    {
        if (empty($fields)) {
            array_walk_recursive($data, function (&$value) {
                if (is_string($value)) {
                    $value = strip_tags($value);
                }
            });
            return $data;
        }

        foreach ($fields as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $data[$field] = strip_tags($data[$field]);
            }
        }

        return $data;
    }

    /**
     * Generic sanitize for basic XSS prevention without blocking.
     *
     * [F-25] `null` girdi artık **sessizce** boş metne dönüşür. Dosyada
     * `strict_types=1` olduğu için eski imza (`string $value`) `clean(null)`
     * çağrısında `TypeError` veriyordu (çağıran taraf ölümcül hata alıyordu).
     * Boş değer zaten doğru kaçış sonucudur (`''`).
     */
    public function clean(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
