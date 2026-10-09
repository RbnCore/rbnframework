<?php

declare(strict_types=1);

namespace Rbn\Framework\Bundles\Internal\Syshub\Controllers\Security;

use Rbn\Framework\Core\Base\Attributes\SubModule;
use Rbn\Framework\Bundles\Internal\Syshub\Controllers\SyshubController;

/**
 * RateLimitsController - IP Tabanlı Hız Sınırlama Yönetimi 🛡️⚡
 * RBN Framework Standard.
 */
#[SubModule(
    entity: 'security/ratelimits', // 🎼 Removed hyphen to match the new route
    service: 'syshub',
    handler: 'syshubSecurity'
)]
class RateLimitsController extends SyshubController
{
    /**
     * Rate Limits Listesi 📊
     */
    public function index(): void
    {
        // 🎼 RBN Framework: Veriyi otonom provider üzerinden (GeoIP zenginleştirilmiş olarak) çekiyoruz
        $limits = $this->service->rateLimit()->getRecords();

        $paginator = $this->paginate($limits, 10);

        // Statistics (Otonom Provider'dan hazır gelir)
        $stats = $this->service->rateLimit()->getStats();

        $this->render('Security/rate_limits', [
            'limits' => $paginator->items(),
            'paginator' => $paginator,
            'stats' => $stats,
        ]);
    }

    /**
     * Tekli Kayıt Sil 🗑️
     */
    public function deleteIP(int $id): void
    {
        $result = $this->service->rateLimit()->destroy($id);
        $this->handleResult($result, "Kayıt (#{$id})", 'security/ratelimits');
    }

    /**
     * Toplu Kayıt Sil 🗑️
     */
    public function bulkDeleteIP(): void
    {
        $ids = $this->request->input('ids');
        if (empty($ids)) {
            $this->handleResult(false, "Seçili kayıt", 'security/ratelimits');
            return;
        }

        $result = $this->service->rateLimit()->destroyBulk($ids);
        $this->handleResult($result > 0, "Seçili kayıtlar", 'security/ratelimits');
    }

    /**
     * Tüm deneme kayıtlarını temizler 🧹
     */
    public function clearAll(): void
    {
        $result = $this->service->rateLimit()->clearAll();

        // 🎼 RBN Framework: Explicit redirect to maintain security context 🛰️⚓
        $this->handleResult($result, 'Tüm kayıtlar', 'security/ratelimits');
    }
}
