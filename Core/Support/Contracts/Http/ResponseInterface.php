<?php

namespace Rbn\Framework\Core\Support\Contracts\Http;

/**
 * ResponseInterface - The Grand Contract for RBN HTTP Response ⚓🛡️
 */
interface ResponseInterface
{
    /**
     * Set the HTTP response status code.
     */
    public function status(int $code): self;

    /**
     * Set a single HTTP response header.
     */
    public function header(string $key, string $value): self;

    /**
     * Set Content-Type header.
     */
    public function contentType(string $type = 'text/html', string $charset = 'utf-8'): self;

    /**
     * Set a cookie.
     *
     * [http #11] `$secure = null` means "decide from the request": `Secure` is
     * set when the request is HTTPS (see `RequestEnvironment::isHttpsRequest`).
     * Passing `true`/`false` explicitly overrides that decision. `$sameSite`
     * is appended last and is optional, so positional callers stay valid.
     */
    public function cookie(string $name, string $value, int $minutes = 60, string $path = '/', ?string $domain = null, ?bool $secure = null, bool $httpOnly = true, ?string $sameSite = 'Lax'): self;

    /**
     * Send a JSON response and exit.
     */
    public function json(array $data, int $status = 200): void;

    /**
     * Send a standardized success JSON response.
     */
    public function success(mixed $data = [], string $message = 'OK', int $status = 200): void;

    /**
     * Send a standardized error JSON response.
     */
    public function error(string $message, int $status = 400, mixed $data = null): void;

    /**
     * Special Alert (Notification) JSON response.
     */
    public function alertJson(array $alertData, int $statusCode = 200): void;

    /**
     * Set the raw response body.
     */
    public function body(?string $content): self;

    /**
     * Finalize and send the response headers and body. 🛳️
     */
    public function send(): void;

    /**
     * Perform an HTTP redirect and exit.
     */
    public function redirect(string $url, int $code = 302): void;

    /**
     * Send no-content response (204).
     */
    public function noContent(): void;
}
