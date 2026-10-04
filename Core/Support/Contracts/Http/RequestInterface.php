<?php

namespace Rbn\Framework\Core\Support\Contracts\Http;

use Rbn\Framework\Core\Http\Engine\UploadedFile;

/**
 * RequestInterface - The Grand Contract for RBN 3.0 HTTP Engine ⚓🛡️
 * 
 * Defines the standard API for input handling, detection, and validation.
 */
interface RequestInterface
{
    /**
     * Get all input data (GET + POST + JSON) 🛰️⚡
     */
    public function all(): array;

    /**
     * Ham gövdeyi al — yazma yoluna verilebilir hâli (kural UYGULANMAZ) 📦
     *
     * FW-ALTYAPI-2 / B (adım 1). Kural dizisinde olmayan alanları okuyan
     * çağıranların açık yolu; `form()`'un ham karışımının yalnız `all()` kısmı.
     */
    public function rawAll(array $options = []): array;

    /**
     * Get a specific value from the request (JSON priority)
     */
    public function input(string $key, $default = null);

    /**
     * Get specific GET parameters 🔎
     */
    public function query(?string $key = null, $default = null);

    /**
     * Determine if a value exists in the request ✅
     */
    public function has(string $key): bool;

    /**
     * Determine if a value exists and is not empty ✨
     */
    public function filled(string $key): bool;

    /**
     * Determine if a value is missing from the request ❌
     */
    public function missing(string $key): bool;

    /**
     * Get only specific keys from the request
     */
    public function only(array $keys): array;

    /**
     * Get request data except specific keys
     */
    public function except(array $keys): array;

    /**
     * Get the HTTP request method 🔎
     */
    public function method(): string;

    /**
     * Check the HTTP request method ⚖️
     */
    public function isMethod(string $method): bool;

    /**
     * Determine if the request is POST
     */
    public function isPost(): bool;

    /**
     * Determine if the request is GET
     */
    public function isGet(): bool;

    /**
     * Determine if the request is AJAX ⚡
     */
    public function isAjax(): bool;

    /**
     * Determine if the client expects JSON 🧱
     */
    public function wantsJson(): bool;

    /**
     * Determine if the client expects XML 🧱
     */
    public function wantsXml(): bool;

    /**
     * Determine if the incoming payload is JSON 🧬⚡
     */
    public function isJson(): bool;

    /**
     * Determine if the incoming payload is standard Form Data 🧼💎
     */
    public function isFormData(): bool;

    /**
     * Content Negotiation for specific types ⚖️
     */
    public function prefers(array $contentTypes): ?string;

    /**
     * Determine if the request is over HTTPS 🔒
     */
    public function isSecure(): bool;

    /**
     * Get the client's IP address 🌍⚓
     */
    public function ip(): string;

    /**
     * Get the User Agent string
     */
    public function userAgent(): string;

    /**
     * Get the request path (e.g. /contact) 🗺️
     */
    public function path(): string;

    /**
     * Get the current URL (without query string) 🌍
     */
    public function url(): string;

    /**
     * Get the full URL (with query string) 🛰️⚡
     */
    public function fullUrl(): string;

    /**
     * Get the root URL (Protocol + Host) 🏗️
     */
    public function root(): string;

    /**
     * Get the Bearer Token from Authorization Header 🔑✨
     */
    public function bearerToken(): ?string;

    /**
     * Get a specific HTTP header 🧱
     */
    public function header(string $key, $default = null);

    /**
     * Get a specific uploaded file 📎✨
     */
    public function file(string $key): ?UploadedFile;

    /**
     * Determine if a file exists and is valid ✅
     */
    public function hasFile(string $key): bool;

    /**
     * Get all uploaded files 📂
     */
    public function allFiles(): array;

    /**
     * Activate RbnShield Security Shield 🛡️
     */
    public function shield(array $options = []): self;

    /**
     * Validate the request data 🛡️⚓
     */
    public function validate(array $rules, array $messages = []): array;

    /**
     * RBN Masterpiece "Form" - Combine Shield & Validation 🛡️✨
     */
    public function form(array $rules, array $options = []): array;

    /**
     * RBN Masterpiece "Filter" - Filter and validate GET/Query parameters safely 🛡️🔎
     */
    public function filter(array $rules, array $options = []): array;
}
