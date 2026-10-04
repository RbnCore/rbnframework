<?php

namespace Rbn\Framework\Core\Http\Engine\Traits\Request;

use Rbn\Framework\Core\Base\Services\BaseService;

/**
 * ContextTrait - The Environmental Scout 🌍⚓
 */
trait ContextTrait
{
    public function ip(): string
    {
        $rateLimiter = BaseService::get()->service('rateLimiter');
        return $rateLimiter ? $rateLimiter->getClientIP() : ($this->server['REMOTE_ADDR'] ?? '127.0.0.1');
    }

    public function projectId(): int
    {
        return project_id();
    }

    public function userAgent(): string
    {
        return $this->server['HTTP_USER_AGENT'] ?? '';
    }

    /**
     * Get the request path (e.g. /contact) 🗺️
     */
    public function path(): string
    {
        $uri = $this->server['REQUEST_URI'] ?? '/';
        return strtok($uri, '?');
    }

    /**
     * Get the current URL (without query string) 🌍
     */
    public function url(): string
    {
        return $this->root() . $this->path();
    }

    /**
     * Get the full URL (with query string) 🛰️⚡
     */
    public function fullUrl(): string
    {
        $uri = $this->server['REQUEST_URI'] ?? '/';
        return $this->root() . $uri;
    }

    /**
     * Get the root URL (Protocol + Host) 🏗️
     */
    public function root(): string
    {
        $protocol = $this->isSecure() ? 'https://' : 'http://';
        return $protocol . ($this->server['HTTP_HOST'] ?? 'localhost');
    }

    /**
     * Get the Bearer Token from Authorization Header 🔑✨
     */
    public function bearerToken(): ?string
    {
        $header = $this->header('Authorization', '');
        if (str_starts_with($header, 'Bearer ')) {
            return substr($header, 7);
        }
        return null;
    }

    public function header(string $key, $default = null)
    {
        $key = str_replace('_', '-', strtolower($key));
        return $this->headers[$key] ?? $default;
    }

    /**
     * Parse all incoming HTTP headers from SERVER DNA 🧱
     */
    protected function parseHeaders(array $server): array
    {
        $headers = [];
        foreach ($server as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace('_', '-', strtolower(substr($key, 5)));
                $headers[$name] = $value;
            } elseif ($key === 'CONTENT_TYPE' || $key === 'CONTENT_LENGTH') {
                $name = str_replace('_', '-', strtolower($key));
                $headers[$name] = $value;
            }
        }
        return $headers;
    }
}
