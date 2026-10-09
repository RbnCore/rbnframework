<?php

declare(strict_types=1);

namespace Rbn\Framework\Bundles\RbnSuite\RbnAdmin\Controllers;

use Rbn\Framework\Core\Base\Attributes\SubModule;
use Rbn\Framework\Bundles\RbnSuite\RbnAuth\Models\AuthRole;

/**
 * UserManagementController - The Unified Identity & Activities Hub 🛡️🛰️⚓
 * RBN Framework Standard.
 */
#[SubModule(
    entity: 'user',
    manager: 'user'
)]
class UserManagementController extends RbnAdminController
{
    /* ==========================================================================
       [ 1. USER MANAGEMENT SECTOR ] 👥
       ========================================================================== */

    /**
     * Kullanıcı Listesi ve Gelişmiş Filtreleme 📑🛰️
     */
    public function index(): void
    {
        $search = (string) $this->request->query('search', '');
        $currentStatus = (string) $this->request->query('status', '');
        $currentRole = (string) $this->request->query('role', '');

        // 🪐 RBN Framework Fetch via Autonomous Service
        $channel = $this->manager->allUsers([
            'search' => $search,
            'status' => $currentStatus,
            'role' => $currentRole
        ]);

        $paginator = $this->paginate($channel);

        $this->render('User/index', [
            'users' => $paginator->items(),
            'pager' => $paginator,
            'stats' => $this->manager->getUserStats(),
            'search' => $search,
            'currentStatus' => $currentStatus,
            'currentRole' => $currentRole
        ]);
    }

    /**
     * [STRATEGIC HOOK] Modal Data & Context Resolution 🎭🛰️⚓
     * RBN Framework: Returns record and dynamically sets view context for the Trait.
     */
    public function getModalData($id): array
    {
        $id = ($id && is_numeric($id)) ? (int) $id : null;
        $type = $this->request->input('type', $id ? 'edit' : 'add');

        // 🎻 [RBN Framework VIEW INJECTION]
        // Setting $this->modalView before Trait resolves it.
        $this->modalView = ($type === 'role') ? 'User/Partials/modal_role' : 'User/Partials/modal_user';

        return [
            'id' => $id,
            'type' => $type,
            'user' => $id ? ($this->manager->getUser($id) ?: []) : ['id' => 0, 'role' => 'user'],
            'roles' => AuthRole::ROLES,
            'appContext' => 'panel'
        ];
    }

    /**
     * Şifre Değiştir 🔐
     */
    public function password($id = null): void
    {
        $id = $id ?? $this->request->input('id');
        $data = $this->request->form([
            'new_password'    => 'required|min:8',
            'repeat_password' => 'required|same:new_password'
        ]);

        $user = $this->manager->getUser((int) $id);
        $role = $user['role'] ?? 'user';
        $result = $this->manager->updatePassword((int) $id, $data['new_password'], $role);

        $this->handleResult($result, 'Şifre başarıyla güncellendi.');
    }

    /**
     * Yetki Güncelle 🛡️
     *
     * FW-BASE-2 (T4): `role` korumali alan olduğu için genel `update()` yolu
     * artık rolü YAZAMAZ. Ekran `UserManager::setRole()` yetkili yolunu çağırır;
     * metot rolü beyaz listeden geçirir (bilinmeyen rol yazılmaz) ve her çağrı
     * `security` kanalına `MASS_ASSIGNMENT_AUTHORIZED_WRITE` olarak düşer.
     */
    public function updateRole(): void
    {
        $id = (int) $this->request->input('id');
        $role = (string) $this->request->input('role');

        $this->handleResult($this->manager->setRole($id, $role), 'Yetki başarıyla güncellendi.');
    }

    /**
     * Kullanıcı Silme İşlemi 🗑️
     */
    public function delete($id): void
    {
        $targetId = (int) $id;
        $currentUserId = (int) $this->session()->get('user_id');

        if ($targetId === $currentUserId) {
            $this->handleResult(false, 'Kendi hesabınızı silemezsiniz.', 'users');
            return;
        }

        $result = $this->manager->delete($targetId);
        $this->handleResult($result, 'Kullanıcı başarıyla silindi.', 'users');
    }

    /* ==========================================================================
       [ 2. PERSONAL PROFILE SECTOR ] 🧬
       ========================================================================== */

    /**
     * Kişisel Profil Görünümü (Self) 🎨
     */
    public function profile(): void
    {
        $targetId = (int) $this->session()->get('user_id');

        // 🎻 RBN Framework Fetch via Explicit Service Discovery
        $user = $this->manager->getProfileData($targetId);

        $this->render('User/profile', [
            'profile' => $user,
            'isSelf' => true,
            'sub_module' => 'profile'
        ]);
    }

    /**
     * Profil Bilgilerini Güncelle 📝
     *
     * FW-BASE-3 (BULGU-2): `z_users` tablosunda `firstname`/`lastname`
     * kolonları YOK — eski ekran bu ikisini gönderdiği için "Unknown column"
     * ile 500 veriyordu. Ekran gerçek şemaya (`name`) uyarlandı.
     *
     * `email` `UsersModel::$guarded` içinde olduğu için genel `update()`
     * yolundan DÜŞER; bu yüzden profil güncellemesi yetkili `updateProfile()`
     * yolundan geçer (içinde `email` ayrı bir yetkili yazıcıya taşınır).
     */
    public function profileUpdate(): void
    {
        $data = $this->request->form([
            'name' => 'required',
            'email' => 'required|email'
        ]);

        $targetId = (int) $this->session()->get('user_id');
        $result = $this->manager->updateProfile($targetId, $data);

        $this->handleResult($result, 'Profil bilgileriniz güncellendi.', 'users/profile');
    }

    /**
     * Profil Şifre Değiştir 🔐
     */
    public function profilePassword(): void
    {
        $data = $this->request->form([
            'new_password' => 'required|min:8',
            'repeat_password' => 'required|same:new_password'
        ]);

        $targetId = (int) $this->session()->get('user_id');
        $user = $this->manager->getUser($targetId);
        $role = $user['role'] ?? 'user';
        $result = $this->manager->updatePassword($targetId, $data['new_password'], $role);

        $this->handleResult($result, 'Şifreniz başarıyla güncellendi.', 'users/profile');
    }

    /* ==========================================================================
       [ 3. ACTIVITIES AUDIT SECTOR ] 🕵️‍♂️
       ========================================================================== */

    /**
     * Aktivite Kayıtları Görünümü 🕵️‍♂️📑
     */
    public function activities(): void
    {
        $search = (string) $this->request->query('search', '');
        $type = (string) $this->request->query('type', 'all');
        $order = (string) $this->request->query('order', 'desc');

        // 🪐 RBN Framework Fetch via Autonomous Service
        $channel = $this->manager->getActivities([
            'search' => $search,
            'type' => $type,
            'order' => $order
        ]);

        $records = $this->paginate($channel);

        $this->render('User/activities', [
            'activities' => is_object($records) && method_exists($records, 'items') ? $records->items() : $records,
            'pager' => $records,
            'stats' => $this->manager->getActivityStats(),
            'search' => $search,
            'currentType' => $type,
            'sub_module' => 'activities'
        ]);
    }

    /**
     * Tüm Logları Temizle 🗑️
     */
    public function clearActivities(): void
    {
        $result = $this->manager->clearActivities();
        $this->handleResult($result, 'Tüm aktivite geçmişi başarıyla temizlendi.', 'users/activities');
    }
}
