<?php

namespace Rbn\Framework\Core\Http;

use Rbn\Framework\Core\Support\Contracts\Http\RequestInterface;
use Rbn\Framework\Core\Http\Engine\Traits\Request\InputTrait;
use Rbn\Framework\Core\Http\Engine\Traits\Request\ValidationTrait;
use Rbn\Framework\Core\Http\Engine\Traits\Request\DetectionTrait;
use Rbn\Framework\Core\Http\Engine\Traits\Request\ContextTrait;
use Rbn\Framework\Core\Http\Engine\Traits\Request\FileTrait;

/**
 * Request - HTTP Request Engine & Security Kalkanı 🛡️⚓
 * 
 * High-Performance "Pro" architecture with autonomous input handling.
 */
class Request implements RequestInterface
{
    /** @var self|null Singleton Instance 🏛️ */
    protected static ?self $instance = null;

    use InputTrait, ValidationTrait, DetectionTrait, ContextTrait, FileTrait;

    protected array $post;
    protected array $get;
    protected array $files;
    protected array $server;
    protected array $headers;
    protected ?array $json = null;
    protected ?array $mergedInput = null;

    // RbnShield Security Context
    protected bool $shieldActive = false;
    protected array $shieldOptions = [];

    /**
     * Request Constructor - DNA Capture 🧬
     */
    public function __construct()
    {
        if (self::$instance === null) {
            self::$instance = $this;
        }

        $this->post = $_POST;
        $this->get = $_GET;
        $this->files = $_FILES;
        $this->server = $_SERVER;
        $this->headers = $this->parseHeaders($_SERVER);
    }

    /**
     * Lazy Load JSON Body Payload 🛰️⚡
     */
    public function getJsonData(): array
    {
        if ($this->json === null) {
            if ($this->isJson()) {
                $input = file_get_contents('php://input');
                $this->json = json_decode((string) $input, true) ?: [];
            } else {
                $this->json = [];
            }
        }
        return $this->json;
    }

    /**
     * Capture the current HTTP request 🛰️
     * 
     * @return static
     */
    public static function capture(): self
    {
        return self::$instance ??= new static();
    }
}
