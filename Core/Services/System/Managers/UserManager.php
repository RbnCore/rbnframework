<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Services\System\Managers;

use Rbn\Framework\Core\Base\Services\BaseManager;

/**
 * UserManager - Global System Identity Orchestrator 🏛️🛰️⚓
 * RBN Framework Standard.
 * 
 * @property \Rbn\Framework\Core\Database\Repositories\Project\UserRepository $projectUserRepository
 * @property \Rbn\Framework\Core\Database\Repositories\Project\UserActivityRepository $projectUserActivityRepository
 */
class UserManager extends BaseManager
{
    /**
     * Store a new user polymorphic 🆕
     */
    public function store(array $data): bool
    {
        return $this->repository('project.user')->store($data);
    }

    /**
     * Update user details polymorphic (Supports single array or dual args) 📝
     */
    public function update(mixed $idOrData, ?array $data = null): bool
    {
        if (is_array($idOrData)) {
            $id = $idOrData['id'] ?? null;
            return $this->repository('project.user')->update((int)$id, $idOrData);
        }
        return $this->repository('project.user')->update((int)$idOrData, $data ?? []);
    }

    /**
     * Update user password polymorphic 🔐
     */
    public function updatePassword(int $id, string $password, string $role): bool
    {
        return $this->repository('project.user')->updatePassword($id, $password, $role);
    }

    /**
     * Yetkili profil güncellemesi 🧬 (FW-BASE-3 BULGU-2)
     *
     * Gerçek şemada `z_users.name` TEK kolondur (`firstname`/`lastname`
     * kolonları yok). `email` kimlik alanı olduğu için repository içindeki
     * yetkili yazıcıdan geçer.
     */
    public function updateProfile(int $id, array $data): bool
    {
        return $this->repository('project.user')->updateProfile($id, $data);
    }

    /**
     * Yetkili e-posta yazımı ✉️🛡️ (FW-BASE-3 BULGU-2)
     *
     * `email` `UsersModel::$guarded` içinde; genel `update()` yolundan
     * değiştirilemez. Profil ekranı bu yolu kullanır.
     */
    public function updateEmail(int $id, string $email): bool
    {
        return $this->repository('project.user')->updateEmail($id, $email);
    }

    /**
     * Yetkili rol atama 🛡️⚖️ (FW-BASE-2 T4)
     *
     * `role` artık `UsersModel::$guarded` içinde olduğu için genel
     * `update()` yolundan DEĞİŞTİRİLEMEZ. Rol atamanın tek yetkili yolu
     * budur; `updateRole()` ekranı da buradan geçer.
     *
     * @param string $role Beyaz listedeki bir rol sabiti.
     */
    public function setRole(int $id, string $role): bool
    {
        return $this->repository('project.user')->setRole($id, $role);
    }

    /**
     * Safe user deletion cascading 🗑️
     */
    public function destroy(int $id): bool
    {
        return $this->repository('project.user')->destroy($id);
    }

    /**
     * Fetch user activities logs with filtering 🔍
     */
    public function getActivities(array $filters = []): array
    {
        return $this->repository('project.userActivity')->getActivities($filters);
    }

    /**
     * Clear user activity logs 🧹
     */
    public function clearActivities(): bool
    {
        return $this->repository('project.userActivity')->clear();
    }

    /**
     * Fetch all users 👥
     */
    public function allUsers(array $filters = []): array
    {
        return $this->repository('project.user')->allUsers($filters);
    }

    /**
     * Fetch single user 🔍
     */
    public function getUser(int $id): array
    {
        return $this->repository('project.user')->getUser($id);
    }

    /**
     * Get polymorphic profile data 🎭
     */
    public function getProfileData(int $id): ?array
    {
        return $this->repository('project.user')->getUser($id);
    }

    /**
     * Get aggregate statistics for users 📊
     */
    public function getUserStats(): array
    {
        return $this->repository('project.user')->stats();
    }

    /**
     * Get Activity Statistics for Dashboard/Header 📊
     */
    public function getActivityStats(): array
    {
        return $this->repository('project.userActivity')->stats();
    }
}
