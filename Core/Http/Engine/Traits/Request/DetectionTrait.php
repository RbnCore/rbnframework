<?php

namespace Rbn\Framework\Core\Http\Engine\Traits\Request;

/**
 * DetectionTrait - The Structural Detective 🔎⚓
 */
trait DetectionTrait
{
    public function method(): string
    {
        return strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
    }

    public function isMethod(string $method): bool
    {
        return $this->method() === strtoupper($method);
    }

    public function isPost(): bool
    {
        return $this->isMethod('POST');
    }

    public function isGet(): bool
    {
        return $this->isMethod('GET');
    }

    public function isAjax(): bool
    {
        return (isset($this->server['HTTP_X_REQUESTED_WITH']) && strtolower($this->server['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
    }

    public function wantsJson(): bool
    {
        $accept = $this->server['HTTP_ACCEPT'] ?? '';
        return str_contains($accept, 'application/json');
    }

    /**
     * Kullanıcı benden XML mi bekliyor? 🧱
     */
    public function wantsXml(): bool
    {
        $accept = $this->server['HTTP_ACCEPT'] ?? '';
        return str_contains($accept, 'application/xml') || str_contains($accept, 'text/xml');
    }

    /**
     * Gelen veri JSON formatında mı? (Payload Detection) 🧬⚡
     */
    public function isJson(): bool
    {
        $contentType = $this->server['CONTENT_TYPE'] ?? $this->server['HTTP_CONTENT_TYPE'] ?? '';
        return str_contains($contentType, 'application/json');
    }

    /**
     * Gelen veri standart Form verisi mi? 🧼💎
     */
    public function isFormData(): bool
    {
        $contentType = $this->server['CONTENT_TYPE'] ?? $this->server['HTTP_CONTENT_TYPE'] ?? '';
        return str_contains($contentType, 'multipart/form-data') || str_contains($contentType, 'application/x-www-form-urlencoded');
    }

    /**
     * Kullanıcı belirli formatlardan hangisini tercih ediyor? (Content Negotiation) ⚖️
     */
    public function prefers(array $contentTypes): ?string
    {
        $accept = $this->server['HTTP_ACCEPT'] ?? '';
        foreach ($contentTypes as $type) {
            if (str_contains($accept, $type)) {
                return $type;
            }
        }
        return null;
    }

    public function isSecure(): bool
    {
        if (!empty($this->server['HTTP_X_FORWARDED_PROTO']) && strtolower($this->server['HTTP_X_FORWARDED_PROTO']) === 'https') {
            return true;
        }

        if (!empty($this->server['HTTP_X_FORWARDED_SSL']) && strtolower($this->server['HTTP_X_FORWARDED_SSL']) === 'on') {
            return true;
        }

        return (!empty($this->server['HTTPS']) && $this->server['HTTPS'] !== 'off') || ($this->server['SERVER_PORT'] ?? 0) == 443;
    }
}
