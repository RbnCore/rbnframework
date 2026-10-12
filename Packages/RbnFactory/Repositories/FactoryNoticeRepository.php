<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnFactory\Repositories;

use Rbn\Framework\Core\Base\Attributes\Component;
use Rbn\Framework\Core\Base\Data\BaseRepository;

/**
 * FactoryNoticeRepository - Bildirim gönderim kaydı erişimi 🔔📦
 */
#[Component(alias: 'factory.notice', type: 'repository')]
class FactoryNoticeRepository extends BaseRepository
{
    /** @var string Hedef model takma adı */
    protected $targetModel = 'factory.notice';

    private function notices(): object
    {
        return $this->model('factory.notice')->withProjectScope($this->handler('factoryConfig')->projectKey());
    }

    /** $hours = 0: bu olay + anahtar için bir kez gönderildiyse bir daha asla. */
    public function alreadySent(string $event, string $dedupKey, int $hours): bool
    {
        $query = $this->notices()->query()
            ->where('event_key', $event)
            ->where('dedup_key', $dedupKey)
            ->where('ok', 1);
        if ($hours > 0) {
            $query->where('sent_at', '>', date('Y-m-d H:i:s', time() - $hours * 3600));
        }
        return $query->exists();
    }

    public function record(string $event, string $dedupKey, bool $ok, ?string $detail): void
    {
        $this->notices()->create([
            'event_key' => $event,
            'dedup_key' => mb_substr($dedupKey, 0, 120),
            'ok'        => $ok ? 1 : 0,
            'detail'    => $detail !== null ? mb_substr($detail, 0, 200) : null,
            'sent_at'   => date('Y-m-d H:i:s'),
        ]);
    }
}
