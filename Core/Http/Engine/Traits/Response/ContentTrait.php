<?php

namespace Rbn\Framework\Core\Http\Engine\Traits\Response;

/**
 * ContentTrait - The Data Payload Engine 🛡️🧬
 */
trait ContentTrait
{
    /** @var string|null The raw response body 🧬 */
    protected ?string $bodyContent = null;

    /**
     * Set the raw response body.
     */
    public function body(?string $content): self
    {
        $this->bodyContent = $content;
        return $this;
    }

    /**
     * Send a JSON response and exit.
     */
    public function json(array $data, int $status = 200): void
    {
        $this->status($status);
        $this->contentType('application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Send a standardized success JSON response.
     */
    public function success(mixed $data = [], string $message = 'OK', int $status = 200): void
    {
        $this->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => now(), // RBN Helper used here 🛰️
        ], $status);
    }

    /**
     * Send a standardized error JSON response.
     */
    public function error(string $message, int $status = 400, mixed $data = null): void
    {
        $this->json([
            'success' => false,
            'message' => $message,
            'data' => $data,
            'timestamp' => now(), // RBN Helper used here 🛰️
        ], $status);
    }

    /**
     * Special Alert (Notification) Output Format
     */
    public function alertJson(array $alertData, int $statusCode = 200): void
    {
        $this->status($statusCode);
        $this->contentType('application/json');

        $responsePayload = array_merge([
            'success' => in_array($alertData['type'], ['success', 'info', 'warning']),
            'timestamp' => now(), // RBN Helper used here 🛰️
            'status' => $statusCode
        ], $alertData);

        echo json_encode($responsePayload, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
