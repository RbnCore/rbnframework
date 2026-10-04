<?php

namespace Rbn\Framework\Core\Http\Engine\Traits\Alert;

/**
 * NotificationTrait - The Voice of the System 📢🛡️
 */
trait NotificationTrait
{
    /**
     * Send a success alert. 🔔✨
     */
    public function success(string $message, ?string $redirectUrl = null, ?string $message2 = null, array $data = []): self
    {
        $this->send('success', $message, $redirectUrl, $message2, $data);
        return $this;
    }

    /**
     * Send an error alert. 🔔❌
     */
    public function error(string $message, ?string $redirectUrl = null, ?string $message2 = null, array $data = []): self
    {
        $this->send('error', $message, $redirectUrl, $message2, $data);
        return $this;
    }

    /**
     * Send an info alert. 🔔ℹ️
     */
    public function info(string $message, ?string $redirectUrl = null, ?string $message2 = null, array $data = []): self
    {
        $this->send('info', $message, $redirectUrl, $message2, $data);
        return $this;
    }

    /**
     * Send a warning alert. 🔔⚠️
     */
    public function warning(string $message, ?string $redirectUrl = null, ?string $message2 = null, array $data = []): self
    {
        $this->send('warning', $message, $redirectUrl, $message2, $data);
        return $this;
    }
}
