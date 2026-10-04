<?php

namespace Rbn\Framework\Core\Http;

use Rbn\Framework\Core\Support\Contracts\Http\ResponseInterface;
use Rbn\Framework\Core\Http\Engine\Traits\Response\HeaderTrait;
use Rbn\Framework\Core\Http\Engine\Traits\Response\ContentTrait;
use Rbn\Framework\Core\Http\Engine\Traits\Response\RedirectTrait;

/**
 * Response - The HTTP Output Motor ⚓🛡️
 * 
 * RBN 3.0: High-Performance, modular, and instance-based response engine.
 */
class Response implements ResponseInterface
{
    use HeaderTrait, ContentTrait, RedirectTrait;

    /** @var self|null Singleton Instance 🏛️ */
    protected static ?self $instance = null;

    /**
     * Get the singleton Response instance 🛰️
     */
    public static function getInstance(): self
    {
        return self::$instance ??= new static();
    }
    /**
     * Finalize and send the response headers and body. 🛳️⚓
     */
    public function send(): void
    {
        // Headers are already sent via HeaderTrait methods as they are called.
        // We just need to emit the body content if it exists.
        if ($this->bodyContent !== null) {
            echo $this->bodyContent;
        }
    }
}
