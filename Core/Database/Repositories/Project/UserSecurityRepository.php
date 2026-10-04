<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Database\Repositories\Project;

use Rbn\Framework\Core\Base\Data\BaseRepository;

/**
 * UserSecurityRepository - Specialized Data Repository for User Security & Vault Management 🛡️🏛️⚓
 * 
 * RBN 3.5 Masterpiece Standard.
 * Specialized repository for IP blocks, brute-force protection,
 * and security vault mutations.
 * 
 * @property \Rbn\Framework\Core\Database\Models\Project\UserSecurityModel $userSecurityModel
 */
class UserSecurityRepository extends BaseRepository
{
    /** @var string Target primary model alias */
    protected $targetModel = 'project.userSecurity';

    /**
     * IP adresinin engelli olup olmadığını kontrol eder 🛡️
     */
    public function isIpBlocked(string $ip): ?array
    {
        return $this->model('common.ipBlock')->where('ip_address', $ip)
            ->where('blocked_until', '>', now())
            ->first();
    }

    /* ==========================================================================
       [ MUTATION WORKERS ] 🖊️⚙️🛰️⚓
       ========================================================================== */

    /**
     * Blocks an IP address due to security policy 🚫🛡️
     */
    public function blockIp(string $ip, string $reason, int $hours = 1): bool
    {
        return (bool) $this->model('common.ipBlock')->create([
            'ip_address' => $ip,
            'reason' => $reason,
            'blocked_until' => now('Y-m-d H:i:s', time() + ($hours * 3600))
        ]);
    }

    /**
     * Clears all security blocks for a specific IP (Redemption logic) 🕊️🧹
     */
    public function unblockIp(string $ip): bool
    {
        return (bool) $this->model('common.ipBlock')->where('ip_address', $ip)->delete();
    }

    /**
     * Saves or updates security-specific data (Atomic Upsert) 🧬🏗️⚓
     *
     * FW-BASE-2 (T4): `user_id` bu tablonun birincil anahtarı ve KORUMALI
     * alandır. Daima metot imzasindaki `$userId` yazilir; `$data` icindeki
     * ham `user_id` degeri yok sayilir (baskasinin kasasina yazma yolu kapali).
     */
    public function saveSecurityData(int $userId, array $data): bool
    {
        if ($userId <= 0) {
            return false;
        }

        unset($data['user_id']);

        $securityModel = $this->model('project.userSecurity')->authorizeFields(['user_id']);
        $exists = $securityModel->where('user_id', $userId)->exists();

        if ($exists) {
            return $securityModel->update($userId, $data);
        }

        $data['user_id'] = $userId;

        // FW-BASE-3 (BULGU-3): `create()` motoru `LAST_INSERT_ID()` degerini
        // dondurur; bu tabloda `user_id` birincil anahtar ve AUTO_INCREMENT
        // DEGIL -> deger 0 gelir ve `(bool) 0` = false olurdu (satir yine
        // yaziliyordu). Burada INSERT'in basarili olup olmadigini satirdan
        // DOGRULAYARAK donuyoruz; boylece "kayit yazildi ama false" durumu
        // kapanir. Satir bulunamazsa hata istisnasi atilir ( sessiz yalanc YESIL
        // olmaz).
        $securityModel->create($data);
        if (!$securityModel->where('user_id', $userId)->exists()) {
            return false;
        }

        return true;
    }

    /**
     * Kullanıcının güvenlik kasası (Vault) verilerini getirir 🔐
     */
    public function getSecurityData(int $userId): ?array
    {
        return $this->model('project.userSecurity')->where('user_id', $userId)->first();
    }
}
