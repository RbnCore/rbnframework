<?php
declare(strict_types=1);

namespace Rbn\Framework\Core\Http\Security\Handlers;

use Rbn\Framework\Core\Base\BaseComponent;
use Rbn\Framework\Core\Support\Blueprints\Validations\MimeValidations;
use Rbn\Framework\Core\Support\Blueprints\Validations\ThreatValidations;

/**
 * FileSecurityHandler - The File Sentinel Actor 🛡️📦
 * 
 * RBN 3.5: Atomic actor for "Hard" file security validation.
 * Detects Extension Spoofing, Magic Byte mismatch, and Double Extensions.
 */
class FileSecurityHandler extends BaseComponent
{
    /** Gömülü "<?php" taramasının üst sınırı (bayt). */
    private const PHP_TAG_SCAN_LIMIT = 1048576;

    /**
     * Validate all uploaded files in the request.
     * 
     * @return array ['success' => bool, 'reason' => string|null]
     */
    public function validateRequestFiles(array $files): array
    {
        foreach ($files as $field => $file) {
            // RBN 3.5: Handle multiple files (array of objects or legacy array)
            if (is_array($file)) {
                // Check if it's a legacy $_FILES structure (nested arrays)
                if (isset($file['name']) && is_array($file['name'])) {
                    foreach ($file['name'] as $index => $name) {
                        $item = [
                            'name' => $file['name'][$index],
                            'type' => $file['type'][$index],
                            'tmp_name' => $file['tmp_name'][$index],
                            'error' => $file['error'][$index],
                            'size' => $file['size'][$index]
                        ];
                        $check = $this->checkFile($item);
                        if (!$check['success']) return $check;
                    }
                } else {
                    // Modern array of UploadedFile objects
                    foreach ($file as $item) {
                        $check = $this->smartCheck($item);
                        if (!$check['success']) return $check;
                    }
                }
            } else {
                // Single file (Object or legacy array)
                $check = $this->smartCheck($file);
                if (!$check['success']) return $check;
            }
        }

        return ['success' => true];
    }

    /**
     * Smart routing for file check based on type.
     */
    private function smartCheck($file): array
    {
        if ($file instanceof \Rbn\Framework\Core\Http\Engine\UploadedFile) {
            return $this->checkUploadedFile($file);
        }
        return $this->checkFile((array)$file);
    }

    /**
     * Perform checks on an UploadedFile object.
     */
    private function checkUploadedFile(\Rbn\Framework\Core\Http\Engine\UploadedFile $file): array
    {
        if (!$file->isValid()) {
            return ['success' => true];
        }

        return $this->performChecks($file->name(), $file->tmpName());
    }

    /**
     * Perform security checks on a single file array.
     */
    private function checkFile(array $file): array
    {
        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK || empty($file['tmp_name'])) {
            return ['success' => true]; 
        }

        return $this->performChecks($file['name'], $file['tmp_name']);
    }

    /**
     * Core security logic for files.
     */
    private function performChecks(string $filename, string $tmpPath): array
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $imageExtensions = MimeValidations::getExtensionsByCategory('image');
        $isImage = in_array($extension, $imageExtensions);

        // 1. Double Extension Check (e.g. image.php.jpg)
        // F-16: yerel geliştirme muafiyeti KALDIRILDI; is_local() Host/proxy ile yanılırsa prod'a sızardı.
        if (preg_match('/\.(php|phtml|sh|exe|pl|py|cgi|asp|aspx|jsp)\./i', $filename)) {
            return ['success' => false, 'reason' => "Double Extension Detected: $filename"];
        }

        // 2. Forbidden Extension Check
        if (ThreatValidations::isForbiddenExtension($extension)) {
            return ['success' => false, 'reason' => "Forbidden Extension: .$extension"];
        }

        // 3. Magic Byte (MIME) Check
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detectedMime = finfo_file($finfo, $tmpPath) ?: 'application/octet-stream';

        // 🛡️ RBN 3.5 [SOVEREIGN SECURITY ORCHESTRATION] 🏛️🛰️⚓
        if (!MimeValidations::isValidMime($extension, $detectedMime)) {
            // Eğer ikisi de resimse (image/*) esneklik göster, değilse blokla.
            $detectedIsImage = str_starts_with($detectedMime, 'image/');
            if (!($isImage && $detectedIsImage)) {
                return ['success' => false, 'reason' => "Magic Byte Mismatch: $filename ($detectedMime)"];
            }
        }

        // 4. Embedded PHP Script Check
        // Tam desen listesi ilk 1 KB'ta (kısa desenler büyük ikili dosyada rastgele eşleşir); F-17: "<?php"
        // açılış etiketi ise 1 MB'a kadar taranır (5 bayt: rastlantısal eşleşme ihmal edilebilir).
        $content = @file_get_contents($tmpPath, false, null, 0, 1024);
        if ($content && ThreatValidations::containsSuspiciousPattern($content)) {
            return ['success' => false, 'reason' => "Embedded Security Threat found in $filename"];
        }
        $wide = @file_get_contents($tmpPath, false, null, 0, self::PHP_TAG_SCAN_LIMIT);
        if ($wide !== false && stripos($wide, '<?php') !== false) {
            return ['success' => false, 'reason' => "Embedded Security Threat found in $filename"];
        }

        return ['success' => true];
    }
}
