<?php

namespace Rbn\Framework\Core\Support\Contracts\Http;

/**
 * AlertServiceInterface - The Grand Contract for RBN Notifications 🔔🛡️
 */
interface AlertServiceInterface
{
    /**
     * Send a success alert.
     */
    public function success(string $message, ?string $redirectUrl = null, ?string $message2 = null, array $data = []): self;

    /**
     * Send an error alert.
     */
    public function error(string $message, ?string $redirectUrl = null, ?string $message2 = null, array $data = []): self;

    /**
     * Send an info alert.
     */
    public function info(string $message, ?string $redirectUrl = null, ?string $message2 = null, array $data = []): self;

    /**
     * Send a warning alert.
     */
    public function warning(string $message, ?string $redirectUrl = null, ?string $message2 = null, array $data = []): self;

    /**
     * Force the alert to be stored in a Cookie (for JS consumption).
     */
    public function viaCookie(): self;

    /**
     * Force the alert to be stored in the Session only.
     */
    public function viaSession(): self;

    /**
     * The core sending logic.
     */
    public function send(string $type, string $message, ?string $redirectUrl = null, ?string $message2 = null, array $data = []): void;
}
