<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Support\Exceptions;

/**
 * PageNotFoundException - RBN standard HTTP 404 Exception 🏹 🌊
 * 
 * RBN 3.5: Masterpiece Standard.
 * Representing a clean "User-Level" routing failure.
 * Distinct from ViewNotFoundException which represents a technical/dev failure.
 */
class PageNotFoundException extends \Exception
{
    protected string $uri;

    public function __construct(string $uri, int $code = 404, ?\Throwable $previous = null)
    {
        $this->uri = $uri;
        parent::__construct("Page Not Found: /{$uri}", $code, $previous);
    }

    public function getUri(): string
    {
        return $this->uri;
    }
}
