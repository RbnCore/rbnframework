<?php

namespace Rbn\Framework\Core\System\Storage\Drivers;

use Rbn\Framework\Core\Base\Services\BaseService;
use Exception;

/**
 * UniversalFileDriver - RBN 3.0 Storage Engine 🌪️
 * 
 * Tüm depolama birimleri için standart, güvenli ve performanslı 
 * dosya işlemlerini yöneten ana motor.
 */
class UniversalFileDriver
{
    /**
     * Dosya Yazma (Ön-işlem: Format & Encryption)
     */
    public function write(string $path, mixed $content, string $format = 'raw', bool $append = false, bool $encrypt = false): bool
    {
        try {
            // 1. Veriyi Formatla
            $data = $this->formatContent($content, $format);

            // 2. Şifrele (Opsiyonel)
            if ($encrypt) {
                $crypto = BaseService::get()->helper('crypto');
                if ($crypto) {
                    $data = $crypto::encrypt($data);
                }
            }

            // 3. Klasör Kontrolü (Recursive)
            $dir = dirname($path);
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }

            // 4. Diske Yaz
            $flags = ($append ? FILE_APPEND : 0) | LOCK_EX;
            $res = file_put_contents($path, $data . ($append ? PHP_EOL : ''), $flags);
            return $res !== false;

        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Dosya Okuma (Son-işlem: Decryption & Parse)
     */
    public function read(string $path, string $format = 'raw', bool $decrypt = false): mixed
    {
        if (!file_exists($path))
            return null;

        try {
            $content = file_get_contents($path);
            if ($content === false)
                return null;

            // 1. Şifre Çöz (Opsiyonel)
            if ($decrypt) {
                $crypto = BaseService::get()->helper('crypto');
                if ($crypto) {
                    $decrypted = $crypto::decrypt($content);
                    if ($decrypted === false || $decrypted === null) {
                        return null; // Decryption failed, treat as corrupted/empty
                    }
                    $content = $decrypted;
                }
            }

            // 2. Veriyi Parse Et
            return $this->parseContent($content, $format);

        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Dosya Silme
     */
    public function delete(string $path): bool
    {
        if (file_exists($path)) {
            return @unlink($path);
        }
        return true;
    }

    /**
     * İçerik Formatlama (PHP -> String)
     */
    private function formatContent(mixed $content, string $format): string
    {
        return match ($format) {
            'json', 'jsonl' => json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'serialized' => serialize($content),
            default => (string) $content
        };
    }

    /**
     * İçerik Ayıklama (String -> PHP)
     */
    private function parseContent(string $content, string $format): mixed
    {
        if ($format === 'jsonl') {
            // 🎼 RBN 3.5: [ROBUST JSONL PARSER] 🛰️🪐⚓
            // 1. BOM Temizliği (Görünmez kirleri temizle)
            $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

            // 2. Esnek Satır Bölme
            $lines = preg_split('/\r\n|\r|\n/', trim($content));

            // 3. Otonom Decode & Filtreleme (Hardened)
            $results = [];
            foreach (array_filter($lines, 'trim') as $line) {
                try {
                    $decoded = json_decode(trim($line), true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $results[] = $decoded;
                    }
                } catch (\Throwable $e) {
                    continue; // Hatalı satırı sessizce atla, nizamî akışı bozma.
                }
            }
            return $results;
        }

        return match ($format) {
            'json' => json_decode($content, true),
            'serialized' => @unserialize($content),
            default => $content
        };
    }
}
