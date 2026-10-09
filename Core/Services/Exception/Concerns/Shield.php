<?php
declare(strict_types=1);

namespace Rbn\Framework\Core\Services\Exception\Concerns;

use Rbn\Framework\Core\Support\Exceptions\DiagnosticException;
use Rbn\Framework\Core\Support\Exceptions\ViewNotFoundException;
use Rbn\Framework\Core\Support\Exceptions\ValidationException;
use Rbn\Framework\Core\Support\Exceptions\PreflightException;
use Rbn\Framework\Core\Support\Exceptions\PageNotFoundException;
use Rbn\Framework\Core\Services\Exception\Providers\UserErrorProvider;

/**
 * Shield Hub - The Central Error & Defense Orchestrator 🏛️🛡️
 * 
 * RBN Framework: Standard (Fluent API).
 * Provides a unified, semantic API for throwing framework-level exceptions.
 * Recommended Usage: shield()->notFound($path)
 * 
 * Location: Alongside ExceptionService for total architectural integrity.
 */
class Shield
{
    /**
     * Throw a Pre-flight Diagnostic Failure (Layer 1 - Initialization) 🩺🚀
     */
    public function preflight(string $message, string $hint = '', string $type = 'RbnShield Pre-Flight'): void
    {
        throw new PreflightException($message, $hint, $type);
    }

    /**
     * Throw a View Resolution Failure (Layer 1 - Diagnostic) 🩺🛰️
     */
    public function notFound(string $path): void
    {
        throw new ViewNotFoundException($path);
    }

    /**
     * Throw a Page/Route Resolution Failure (Layer 4 - User) 🏹🛣️
     */
    public function pageNotFound(string $uri): void
    {
        throw new PageNotFoundException($uri);
    }

    /**
     * Throw a Functional Validation Failure (Layer 4/2) 🧬⚖️
     */
    public function validation(array $errors, string $message = 'Form Validation failed.'): void
    {
        throw new ValidationException($message, $errors);
    }

    /**
     * Throw a Technical Diagnostic Report (Layer 1 - Diagnostic) 🩺🏛️
     */
    public function diagnostic(string $type, string $message, string $hint = ''): void
    {
        throw new DiagnosticException($type, $message, $hint);
    }

    /**
     * Throw a Catastrophic Kernel Failure (Layer 3 - Critical) 🆘🆘
     */
    public function panic(string $message, string $hint = ''): void
    {
        throw new \Exception("[RBN PANIC]:: {$message} | HINT: {$hint}");
    }

    /**
     * Standard Forbidden Abort (Layer 4 - User) 🔐🛡️⚓
     * RBN Framework: Directly renders the 403 page to bypass technical diagnostic screens.
     */
    public function forbidden(string $message = ''): void
    {
        $provider = new UserErrorProvider();
        $provider->render(403, ['message' => $message]);
        exit;
    }

    /**
     * Standard Not Found Abort (Layer 4 - User) 🏹
     */
    public function abort(int $code = 404, string $message = ''): void
    {
        throw new \Exception($message, $code);
    }
}
