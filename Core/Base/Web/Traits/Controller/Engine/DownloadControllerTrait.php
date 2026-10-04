<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Base\Web\Traits\Controller\Engine;

use Exception;

/**
 * DownloadControllerTrait - Optimized RBN 3.5 Download Actions 📥🎡
 * 
 * RBN 3.5: Renamed to ControllerTrait to maintain layer-specific standards.
 */
trait DownloadControllerTrait
{
    /**
     * Standard Single File Download Endpoint & Logic 📥
     */
    public function download($id = null, $service = null, string $method = 'download'): void
    {
        $service = $service ?? $this->activeService;

        // RBN 3.0 Standard: Validate and get from form if id not provided
        if (!$id) {
            $data = $this->request->form(['file' => 'required|string']);
            $id = $data['file'];
        }

        if (!method_exists($service, $method)) {
            throw new Exception("Download target " . get_class($service) . " does not implement {$method}() method.");
        }

        // Service is expected to use deliverFile or deliverRaw internally
        $service->$method($id);
    }

    /**
     * Standard Bulk File Download (ZIP) Endpoint & Logic 📦
     */
    public function bulkDownload($ids = null, $service = null, string $method = 'downloadBulk'): void
    {
        $service = $service ?? $this->activeService;

        // RBN 3.0 Standard: Use request->form() for validation
        if (!$ids) {
            $data = $this->request->form(['ids' => 'required|array']);
            $ids = $data['ids'];
        }

        if (!method_exists($service, $method)) {
            throw new Exception("Bulk download target " . get_class($service) . " does not implement {$method}() method.");
        }

        // Service is expected to use deliverZip internally
        $service->$method($ids);
    }


    /**
     * Respond with a file download (AJAX/SweetAlert Friendly) 📥
     */
    protected function respondDownload(string $filename, string $message): void
    {
        // RBN 3.5 Standard download route pattern
        $url = $this->Route->url($this->indexRoute . '/download-temp', 'developer') . '?file=' . urlencode($filename);

        $this->Route->handleResult(true, [
            'success_message' => $message,
            'download_url' => $url,
            'path' => false
        ]);
    }

    /**
     * Respond with multiple file downloads 📥📥📥
     */
    protected function respondMultiDownload(array $downloads, string $message): void
    {
        $this->Route->handleResult(true, [
            'success_message' => $message,
            'downloads' => $downloads,
            'path' => false
        ]);
    }
}
