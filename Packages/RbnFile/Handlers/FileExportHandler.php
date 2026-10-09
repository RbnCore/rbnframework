<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnFile\Handlers;

use Rbn\Framework\Core\Base\BaseComponent;
use Rbn\Framework\Core\System\Paths\Paths;

/**
 * FileExportHandler - The Format Worker 📤🛰️
 * 
 * RBN Framework: Standard (BaseComponent Actor).
 * Location: Bundles\RbnSuite\RbnFile\Handlers\
 */
class FileExportHandler extends BaseComponent
{
    /**
     * Export data to a JSON file
     */
    public function toJson(string $filename, array $data, int $options = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE): array
    {
        try {
            $filename = $this->sanitizeFileName($filename, 'json');
            $fullPath = Paths::project()->exports($filename);

            $jsonData = json_encode($data, $options);
            if ($jsonData === false) return $this->sendError('JSON encode error.');

            if (file_put_contents($fullPath, $jsonData) !== false) {
                return $this->sendSuccess('JSON exported', [
                    'filename' => $filename,
                    'path' => $fullPath,
                    'download_url' => '/fw-proxy/export/' . $filename
                ]);
            }

            return $this->sendError('JSON yazılamadı.');

        } catch (\Exception $e) {
            return $this->sendError('Export JSON error: ' . $e->getMessage());
        }
    }

    /**
     * Export data to a CSV file
     */
    public function toCsv(string $filename, array $data, string $delimiter = ','): array
    {
        try {
            if (empty($data)) return $this->sendError('Dizi boş.');

            $filename = $this->sanitizeFileName($filename, 'csv');
            $fullPath = Paths::project()->exports($filename);

            $fileHandle = fopen($fullPath, 'w');
            if ($fileHandle === false) return $this->sendError('CSV açılamadı.');

            // BOM
            fputs($fileHandle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            $headers = array_keys(reset($data));
            fputcsv($fileHandle, $headers, $delimiter);

            foreach ($data as $row) {
                fputcsv($fileHandle, $row, $delimiter);
            }

            fclose($fileHandle);

            return $this->sendSuccess('CSV exported', [
                'filename' => $filename,
                'path' => $fullPath,
                'download_url' => '/fw-proxy/export/' . $filename
            ]);

        } catch (\Exception $e) {
            return $this->sendError('Export CSV error: ' . $e->getMessage());
        }
    }
}
