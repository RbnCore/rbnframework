<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Database\Repositories\Project;

use Rbn\Framework\Core\Base\Data\BaseRepository;

/**
 * UserRepository - The RBN Framework Identity & User Data Repository 🧬🛡️⚓
 * 
 * RBN Framework Standard.
 * Specialized repository for identification, authentication status,
 * and high-priority authority synchronization (Master Developers).
 * 
 * @property \Rbn\Framework\Core\Database\Models\Project\UsersModel $usersModel
 */
class UserRepository extends BaseRepository
{
    /** @var string Target primary model alias */
    protected $targetModel = 'project.user';

    public function findByIdentity(string $identity): mixed
    {
        return $this->model('project.user')->select('z_users.*, z_users_security.password_hash')
            ->join('z_users_security', 'z_users.id', '=', 'z_users_security.user_id')
            ->where('z_users.email', $identity)
            ->orWhere('z_users.username', $identity)
            ->first();
    }

    /* ==========================================================================
       [ AUTHORITY SYNCHRONIZATION ] 🎯🏛️
       ========================================================================== */

    /**
     * Finds a developer in the central rbn_master authority 🛰️🛸
     * RBN Framework: Dual-Hub Discovery strategy.
     */
    public function findMasterDeveloper(string $identity): mixed
    {
        return $this->model('master.developer')->where('email', $identity)
            ->orWhere('username', $identity)
            ->first();
    }

    /**
     * Updates the online status of an identity 🚦✅
     */
    public function setOnlineStatus(int $userId, bool $online): bool
    {
        return $this->model('project.user')->update($userId, [
            'is_online' => $online ? 1 : 0
        ]);
    }

    /**
     * Verifies if the identity's account is active 🚦
     */
    public function isActive(int $userId): bool
    {
        $user = $this->model('project.user')->select('is_active')->where('id', $userId)->first();
        return (int)($user['is_active'] ?? 0) === 1;
    }

    /**
     * Get Polymorphic Profile Data (Standard User vs Master Developer) 🎭
     */
    public function getProfileData(?int $id = null): ?array
    {
        $session = $this->session();
        $isDeveloper = ($session->get('user_role') === 'developer' || $session->get('is_master_developer'));

        if ($isDeveloper) {
            $data = $this->model('master.developer')->where('id', $id ?? ($session->get('user_id') ?? 0))->first();

            if ($data) {
                $fullName = (string) ($data['full_name'] ?? '');
                $names = explode(' ', $fullName, 2);

                return array_merge((array) $data, [
                    'firstname' => $names[0] ?? '',
                    'lastname' => $names[1] ?? '',
                    'name' => $fullName ?: 'Master Developer',
                    'role_label' => 'Master Geliştirici',
                    'status' => 'active',
                    'password_score' => 100
                ]);
            }
        }

        $data = $this->model('project.user')->where('id', $id)->first();
        if ($data) {
            $dataArray = (array) $data;

            if (empty($dataArray['firstname']) && !empty($dataArray['name'])) {
                $names = explode(' ', (string) $dataArray['name'], 2);
                $dataArray['firstname'] = $names[0] ?? '';
                $dataArray['lastname'] = $names[1] ?? '';
            }

            return array_merge($dataArray, [
                'name' => $dataArray['name'] ?? (($dataArray['firstname'] ?? '') . ' ' . ($dataArray['lastname'] ?? '')),
                'role_label' => strtoupper((string) ($dataArray['role'] ?? 'USER')),
                'status' => $dataArray['status'] ?? 'active'
            ]);
        }

        return null;
    }

    /**
     * Polymorphic & Role-Aware Identity Update 🛡️⚖️
     */
    public function update(int $id, array $data): bool
    {
        if (!$id) {
            return false;
        }

        $role = $data['role'] ?? null;
        if ($role === null) {
            $existingUser = $this->getUser($id);
            $role = $existingUser['role'] ?? $this->session()->get('user_role', 'user');
        }

        $isDeveloper = ($role === 'developer' || $role === 'master_developer');

        $firstName = $data['firstname'] ?? '';
        $lastName = $data['lastname'] ?? '';
        $fullName = trim($firstName . ' ' . $lastName);

        unset($data['id']);

        if ($isDeveloper) {
            $updatePayload = array_merge($data, ['full_name' => $fullName]);
            return (bool) $this->model('master.developer')->update($id, $updatePayload);
        }

        $updatePayload = array_merge($data, ['name' => $fullName]);
        return (bool) $this->model('project.user')->update($id, $updatePayload);
    }

    /**
     * Specialized Password Mutation 🔐🛰️⚓
     *
     * FW-BASE-2 (T4): `developers.password` korumali alan; hash BURADA uretilir
     * (istemin gonderdigi deger asla yazilmaz), bu yuzden yetkili yol.
     */
    public function updatePassword(int $id, string $password, string $role): bool
    {
        $isDeveloper = ($role === 'developer');
        $hashedPassword = $this->helper('crypto')->hash($password);

        $payload = ['password' => $hashedPassword];

        if ($isDeveloper) {
            return (bool) $this->model('master.developer')
                ->authorizeFields(['password'])
                ->update($id, $payload);
        }

        return (bool) $this->model('project.user')->update($id, $payload);
    }

    /**
     * YETKİLİ ROL ATAMA 🛡️⚖️ (FW-BASE-2 T4)
     *
     * Rol alanı toplu atama ile YAZILAMAZ (`UsersModel::$guarded`). Bu metot
     * rol atamanın TEK yetkili yoludur ve iki şeyi birden yapar:
     *   1) `$role` degerini beyaz listeden geçirir (sabitler, istekten degil);
     *   2) `z_users.role` ya da `developers.role` yazımını yetkili kapıdan açar.
     *
     * @param string $role Sabitlerden gelen rol (`RoleConfig`).
     * @return bool Yanlış rol ya da hedef bulunamazsa false.
     */
    public function setRole(int $id, string $role): bool
    {
        if ($id <= 0 || !$this->isKnownRole($role)) {
            return false;
        }

        $existing = $this->getUser($id);
        if ($existing === []) {
            return false;
        }

        $isDeveloper = (($existing['role'] ?? 'user') === 'developer');

        if ($isDeveloper) {
            return (bool) $this->model('master.developer')
                ->authorizeFields(['role'])
                ->update($id, ['role' => $role]);
        }

        return (bool) $this->model('project.user')
            ->authorizeFields(['role'])
            ->update($id, ['role' => $role]);
    }

    /**
     * Rol beyaz listesi — veritabanı ENUM'u ile birebir aynı (ölçüldü).
     *
     * @var string[]
     */
    private const ROLES = [
        'developer', 'superadmin', 'admin', 'moderator', 'editor', 'user', 'guest',
    ];

    /** Rol beyaz listede mi? (bilinmeyen rol yazılmaz) */
    private function isKnownRole(string $role): bool
    {
        return in_array($role, self::ROLES, true);
    }

    /**
     * YETKİLİ E-POSTA YAZIMI 🛡️ (FW-BASE-3 BULGU-2)
     *
     * `z_users.email` bir KİMLİK alanıdır ve `UsersModel::$guarded` içindedir;
     * genel `update()` yolundan DEĞİŞTİRİLEMEZ (FW-BASE-2 T4). Profil ekranı
     * `email` alanını bu metoda taşır.
     *
     * @param string $email Doğrulanmış e-posta (controller `email` kuralı).
     * @return bool Hedef yoksa/geçersiz e-postaysa false.
     */
    public function updateEmail(int $id, string $email): bool
    {
        $email = trim($email);
        if ($id <= 0 || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        if ($this->getUser($id) === []) {
            return false;
        }

        return (bool) $this->model('project.user')
            ->authorizeFields(['email'])
            ->update($id, ['email' => $email]);
    }

    /**
     * YETKİLİ PROFİL GÜNCELLEMESİ 🧬 (FW-BASE-3 BULGU-2)
     *
     * Gerçek şemada `z_users.name` TEK kolondur; `firstname`/`lastname`
     * kolonları yoktur (eski ekran "Unknown column" ile 500 veriyordu).
     * `email` kimlik alanı olduğu için AYRI yetkili yoldan yazılır.
     *
     * @param array $data Yalnız `name` ve `email` okunur; rol/kimlik alanları
     *                    bu yoldan değiştirilemez.
     */
    public function updateProfile(int $id, array $data): bool
    {
        if ($id <= 0) {
            return false;
        }

        $ok = false;
        $name = trim((string) ($data['name'] ?? ''));
        if ($name !== '') {
            $ok = (bool) $this->model('project.user')->update($id, ['name' => $name]);
        }

        $email = trim((string) ($data['email'] ?? ''));
        if ($email !== '' && $this->updateEmail($id, $email)) {
            $ok = true;
        }

        return $ok;
    }

    /**
     * Store a new user (Polymorphic) 🏗️⚓
     */
    public function store(array $data): bool
    {
        $role = $data['role'] ?? 'user';
        $isDeveloper = ($role === 'developer');

        $data['is_active'] = $data['is_active'] ?? 1;

        if ($isDeveloper) {
            return (bool) $this->model('master.developer')->insert($data);
        }

        return (bool) $this->model('project.user')->insert($data);
    }

    /**
     * Fetch all users with basic filtering 👥
     */
    public function allUsers(array $filters = []): array
    {
        $query = $this->model('project.user')->query();

        if (!empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'LIKE', '%' . $filters['search'] . '%')
                    ->orWhere('email', 'LIKE', '%' . $filters['search'] . '%')
                    ->orWhere('username', 'LIKE', '%' . $filters['search'] . '%');
            });
        }

        return $query->get()->all();
    }

    /**
     * Get aggregate statistics 📊
     */
    public function stats(): array
    {
        $model = $this->model('project.user');
        return [
            'total' => $model->count(),
            'active' => (int) $model->where('is_active', 1)->count(),
            'inactive' => (int) $model->where('is_active', 0)->count(),
            'pending' => (int) $model->where('is_active', 2)->count(),
            'banned' => (int) $model->where('is_active', 3)->count()
        ];
    }

    /**
     * Fetch a single user 🔍
     */
    public function getUser(int $id): array
    {
        $user = $this->model('project.user')->where('id', $id)->get()->first();
        return $user ? $user->toArray() : [];
    }

    /**
     * Strategic Destroy: Cascading Relational Integrity 🗑️🛡️⚓
     */
    public function destroy(int $id): bool
    {
        $this->model('userActivities')->where('user_id', $id)->delete();
        $this->model('userSecurity')->where('user_id', $id)->delete();
        return (bool) $this->model('project.user')->destroy($id);
    }
}
