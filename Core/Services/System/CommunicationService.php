<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Services\System;

use Rbn\Framework\Core\Base\Services\BaseService;
use Rbn\Framework\Core\Services\System\Providers\Fluent\ContactChannel;
use Rbn\Framework\Core\Services\System\Providers\Fluent\NotificationChannel;

/**
 * CommunicationService - Global Framework Communication Bridge 🛰️🏛️⚓
 * 
 * RBN Framework: Core service for global messaging and notification access.
 * This service provides the essential data needed by the Framework Panel (UI) 
 * regardless of which module is active.
 * 
 * @property \Rbn\Framework\Core\Database\Repositories\Common\ContactRepository $contactRepository
 * @property \Rbn\Framework\Core\Database\Repositories\Common\NotificationRepository $notificationRepository
 */
class CommunicationService extends BaseService
{
    /** --- Public Hub Data (Global Access) 🛰️⚓ --- */
    public array $noti = ['count' => 0, 'items' => []];
    public array $msg = ['count' => 0, 'items' => []];

    /**
     * Boot Sequence: Prepares global unread data for the Panel UI 🎻
     */
    public function boot(): void
    {
        $notiRepo = $this->repository('common.notification');
        $contactRepo = $this->repository('common.contact');

        $this->noti = [
            'count' => $notiRepo ? ($notiRepo->getStatusStats()['unread'] ?? 0) : 0,
            'items' => $notiRepo ? $notiRepo->getLatest() : []
        ];

        $this->msg = [
            'count' => $contactRepo ? ($contactRepo->getStats()['unread'] ?? 0) : 0,
            'items' => $contactRepo ? $contactRepo->getLatest() : []
        ];
    }

    /**
     * Fluent Accessor for Contact Messages 📩🕊️
     */
    public function contacts(): ContactChannel
    {
        return new ContactChannel();
    }

    /**
     * Fluent Accessor for System Notifications 🔔🕊️
     */
    public function notifications(): NotificationChannel
    {
        return new NotificationChannel();
    }

    /* 🎼 [ SHORTHAND PROXIES ] 🎼 */

    /**
     * Get contact-related statistics
     */
    public function getContactStats(): array
    {
        return $this->repository('common.contact')?->getStats() ?: [];
    }

    /**
     * Get notification-related statistics
     */
    public function getNotificationStats(): array
    {
        return $this->repository('common.notification')?->getStatusStats() ?: [];
    }

    /**
     * Manual Refresh of global counts 🔄
     */
    public function refresh(): void
    {
        $this->boot();
    }
}
