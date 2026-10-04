<?php
declare(strict_types=1);

namespace Rbn\Framework\Core\Support\Exceptions;

/**
 * ViewNotFoundException - Hub Rendering Engine Exception 🛡️ 🔍
 * 
 * RBN 3.5: Masterpiece Standard (Diagnostic Integrated).
 * Thrown when the resolver fails to locate a requested template path.
 * 
 * Part of the Layer 1 (Diagnostic) shield for framework-level troubleshooting.
 */
class ViewNotFoundException extends DiagnosticException
{
    protected string $path;

    public function __construct(string $path, int $code = 404, ?\Exception $previous = null)
    {
        $this->path = $path;

        parent::__construct(
            "View Resolution Failure",
            "[RBN]:: Görünüm Dosyası Bulunamadı: {$path}",
            "Check if the file exists in the correct [views/] subdirectory (e.g. frontend/ or admin/). Ensure the path in your Layout or View call matches the physical folder structure. If using a module, verify the 'suite' registration in FolderMatrix.",
            $code
        );
    }

    public function getPath(): string
    {
        return $this->path;
    }
}
