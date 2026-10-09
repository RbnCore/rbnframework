<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnEmail\Handlers;

use Rbn\Framework\Core\Base\BaseComponent;

/**
 * SendQuotaHandler - Kayan 60 dakikalık gönderim penceresinin durumunu hesaplar.
 *
 * Sayımı çağıran yapar (kendi gönderim günlüğünden); eşikler de çağırandan
 * gelir — barındırma sağlayıcısının limiti projeye özgüdür.
 */
class SendQuotaHandler extends BaseComponent
{
    public const STATE_OK = 'ok';
    public const STATE_WARN = 'warn';
    public const STATE_BLOCKED = 'blocked';

    /**
     * @param int      $sentLastHour son 60 dakikadaki gönderim sayısı
     * @param int|null $oldestTs     penceredeki en eski gönderimin Unix zamanı
     * @param array    $limits       limit, warn_at, block_at
     * @return array{sent_last_hour:int, limit:int, warn_at:int, block_at:int, state:string, resets_at:?string}
     */
    public function evaluate(int $sentLastHour, ?int $oldestTs, array $limits): array
    {
        $warnAt = (int) $limits['warn_at'];
        $blockAt = (int) $limits['block_at'];
        $state = $sentLastHour >= $blockAt ? self::STATE_BLOCKED : ($sentLastHour >= $warnAt ? self::STATE_WARN : self::STATE_OK);
        return [
            'sent_last_hour' => $sentLastHour,
            'limit' => (int) $limits['limit'],
            'warn_at' => $warnAt,
            'block_at' => $blockAt,
            'state' => $state,
            'resets_at' => $oldestTs !== null ? date(DATE_ATOM, $oldestTs + 3600) : null,
        ];
    }

    public function canSend(array $quota): bool
    {
        return $quota['state'] !== self::STATE_BLOCKED;
    }
}
