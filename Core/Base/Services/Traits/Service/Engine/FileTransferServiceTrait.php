<?php

namespace Rbn\Framework\Core\Base\Services\Traits\Service\Engine;

use Rbn\Framework\Core\System\Paths\Paths;
use Exception;
use ZipArchive;

/**
 * FileTransferServiceTrait - Unified File Transfer Engine (Download, Upload, Excel) 🚀
 * 
 * Provides centralized logic for:
 * 1. Physical File Delivery (Streaming)
 * 2. Bulk Data Packaging (ZIP)
 * 3. Raw String Delivery (SQL/CSV/Txt)
 * 4. File Upload Handling
 * 5. Data Export/Import (Excel/CSV)
 */
trait FileTransferServiceTrait
{
    /**
     * R-12: Content-Disposition değeri. CR/LF (başlık bölme) ve çift tırnak filename içinden
     * sızamaz; ASCII olmayan adlar RFC 5987 `filename*` ile taşınır.
     */
    protected function contentDispositionHeader(string $filename): string
    {
        $clean = trim(str_replace(["\r", "\n", "\0"], '', $filename));
        $clean = basename(str_replace('\\', '/', $clean));
        if ($clean === '') {
            $clean = 'download';
        }
        $ascii = str_replace(['"', '\\', '%'], '_', $clean);
        $ascii = preg_replace('/[^\x20-\x7E]/', '_', $ascii) ?? 'download';
        $value = 'attachment; filename="' . $ascii . '"';
        if ($ascii !== $clean) {
            $value .= "; filename*=UTF-8''" . rawurlencode($clean);
        }
        return $value;
    }

    /* ==========================================================================
       [ DOWNLOAD ACTIONS ] - Sending data to user 📥
       ========================================================================== */

    /**
     * Standard File Delivery (Streaming)
     */
    protected function deliverFile(string $path, ?string $displayName = null): void
    {
        if (!file_exists($path)) {
            throw new Exception("İndirilecek dosya sistemde bulunamadı: " . basename($path));
        }

        $displayName = $displayName ?: basename($path);

        while (ob_get_level()) {
            ob_end_clean();
        }

        $this->response->contentType('application/octet-stream');
        $this->response->header('Content-Description', 'File Transfer');
        $this->response->header('Content-Disposition', $this->contentDispositionHeader($displayName));
        $this->response->header('Content-Length', (string) filesize($path));
        $this->response->header('Pragma', 'public');
        $this->response->header('Cache-Control', 'must-revalidate');

        readfile($path);
        exit;
    }

    /**
     * Bulk Data Delivery (ZIP)
     */
    protected function deliverZip(array $files, ?string $zipName = null): void
    {
        if (empty($files)) {
            throw new Exception("İndirilecek dosya seçilmedi.");
        }

        $zipName = $zipName ?: 'export_' . now('Ymd_His') . '.zip';
        $tempPath = Paths::project()->storage('framework/temp/' . $zipName);

        if (!is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($tempPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception("ZIP dosyası oluşturulamadı.");
        }

        $addedCount = 0;
        foreach ($files as $file) {
            if (file_exists($file)) {
                $zip->addFile($file, basename($file));
                $addedCount++;
            }
        }
        $zip->close();

        if ($addedCount === 0) {
            @unlink($tempPath);
            throw new Exception("Geçerli hiçbir dosya bulunamadı.");
        }

        // Use register_shutdown_function for cleanup to ensure file is deleted after streaming
        register_shutdown_function(fn() => @unlink($tempPath));

        $this->deliverFile($tempPath, $zipName);
    }

    /**
     * Raw String Delivery (Simulated CSV/SQL/Txt)
     */
    protected function deliverRaw(string $content, string $filename): void
    {
        while (ob_get_level()) {
            ob_end_clean();
        }

        $this->response->contentType('application/octet-stream');
        $this->response->header('Content-Disposition', $this->contentDispositionHeader($filename));
        echo $content;
        exit;
    }

    /* ==========================================================================
       [ UPLOAD ACTIONS ] - Receiving data from user 📤
       ========================================================================== */

    /**
     * Handle File Upload
     */
    protected function receiveUpload(string $inputName, string $targetPath, array $allowedExtensions = []): string
    {
        if (!isset($_FILES[$inputName]) || $_FILES[$inputName]['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Dosya yükleme hatası oluştu.");
        }

        $file = $_FILES[$inputName];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!empty($allowedExtensions) && !in_array($ext, $allowedExtensions)) {
            throw new Exception("Geçersiz dosya uzantısı: " . $ext);
        }

        if (!is_dir($targetPath)) {
            mkdir($targetPath, 0755, true);
        }

        $newFilename = uniqid('upload_') . '.' . $ext;
        $fullPath = rtrim($targetPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $newFilename;

        if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
            throw new Exception("Dosya hedef dizine taşınamadı.");
        }

        return $newFilename;
    }

    /* ==========================================================================
       [ DATA ACTIONS ] - Excel & CSV Handling (Export/Import) 📊
       ========================================================================== */

    /**
     * Export data to CSV/Excel
     */
    protected function exportData(array $items, array $headerMap, string $filename = 'export.csv', string $format = 'csv'): void
    {
        if ($format === 'csv') {
            $this->exportCsv($items, $headerMap, $filename);
        }
        // Excel integration would go here...
    }

    /**
     * Standard CSV Export
     */
    protected function exportCsv(array $items, array $headerMap, string $filename): void
    {
        while (ob_get_level()) {
            ob_end_clean();
        }

        $this->response->header('Content-Type', 'text/csv; charset=utf-8');
        $this->response->header('Content-Disposition', $this->contentDispositionHeader($filename));

        $output = fopen('php://output', 'w');
        fwrite($output, "\xEF\xBB\xBF"); // UTF-8 BOM

        if (!empty($headerMap)) {
            fputcsv($output, array_keys($headerMap), ';');
        }

        foreach ($items as $item) {
            $row = [];
            foreach ($headerMap as $header => $key) {
                $value = is_object($item) ? ($item->$key ?? '') : ($item[$key] ?? '');
                $row[] = is_scalar($value) ? (string) $value : '';
            }
            fputcsv($output, $row, ';');
        }

        fclose($output);
        exit;
    }

    /**
     * Import data from CSV
     */
    protected function importCsv(string $filePath, callable $callback, string $delimiter = ';'): void
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            throw new Exception("İçe aktarılacak dosya bulunamadı: " . $filePath);
        }

        $handle = fopen($filePath, 'r');
        fgetcsv($handle, 0, $delimiter); // Skip header

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $callback($row);
        }

        fclose($handle);
    }
}
