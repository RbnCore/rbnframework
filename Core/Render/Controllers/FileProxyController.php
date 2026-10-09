<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Render\Controllers;

use Rbn\Framework\Core\Base\Web\BaseController;
use Rbn\Framework\Core\System\Paths\Paths;

/**
 * FileProxyController - Secure Storage Streamer 🛡️📦
 * 
 * RBN Framework: Powered by Autonomous DNA.
 * Securely streams files from private storage (uploads, exports) to the client
 * with strict path traversal protection and Shield-integrated error handling.
 */
class FileProxyController extends BaseController
{
    /**
     * Serves an uploaded file privately.
     */
    public function serveUpload(string $path = ''): void
    {
        $this->serveFile(Paths::project()->uploads($path));
    }

    /**
     * Serves an exported file privately (with force download).
     */
    public function serveExport(string $path = ''): void
    {
        $this->serveFile(Paths::project()->exports($path), true);
    }

    /**
     * Core streaming logic with RBN Framework Security.
     */
    private function serveFile(string $fullPath, bool $forceDownload = false): void
    {
        // 🛡️ Path Traversal Security Check
        $realPath = realpath($fullPath);
        $storageRoot = realpath(Paths::project()->storage());

        if ($realPath === false || !str_starts_with($realPath, $storageRoot) || !is_file($realPath)) {
            // 🏹 RBN Framework: Use centralized DNA abort mechanism
            $this->abort(404, 'File not found or access denied.');
        }

        // 🧬 Determine content type safely
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $realPath) ?: 'application/octet-stream';

        // ⚓ Standardize Response Headers
        // [R-12] Content-Disposition dosya adi KACISLANMADEN basliga giriyordu:
        //   '... ; filename="' . basename($realPath) . '"'
        // Depolama alani adi kullanici etkileyebilirse (yukleme, dis kaynakli ad,
        // eski yedek) tirnak (") veya CRLF basligi kirabilir. Rapor bunu Windows'ta
        // "sinirli" sayiydi; sinirli olmasi koruma olmadigi anlamina gelmez.
        //
        // Duzeltme: TEK KAYNAK — `FileTransferServiceTrait::contentDispositionHeader()`.
        // Bu yardimci `1ffde51` ile `Core/Base/.../FileTransferServiceTrait` icinde
        // yazildi ve zaten `ConcernsContextTrait` uzerinden `BaseController`a
        // baglanmis durumda; `BaseController` miras alan bu controller'da
        // EK BIR KULLANIM GEREKTIRMEZ. Iki ayri kacis kodu olusmaz.
        // Yardimci `filename*` (RFC 5987) ile ASCII ad + gercek UTF-8 ad verir.
        $disposition = $forceDownload
            ? $this->contentDispositionHeader(basename($realPath))
            : 'inline; filename="' . addcslashes(basename(str_replace('\\', '/', $realPath)), '"\\') . '"';

        $this->response
            ->header('Content-Type', $mimeType)
            ->header('Cache-Control', 'private, max-age=86400, mutable')
            ->header('Content-Disposition', $disposition)
            ->header('Content-Length', (string) filesize($realPath))
            ->header('X-Content-Type-Options', 'nosniff');

        // 🌊 Clear buffer for clean streaming
        if (ob_get_level()) {
            ob_end_clean();
        }

        // 🛳️ Final Stream
        readfile($realPath);
        exit;
    }
}
