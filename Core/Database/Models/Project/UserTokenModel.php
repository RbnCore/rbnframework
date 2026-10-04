<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Database\Models\Project;

use Rbn\Framework\Core\Base\Data\BaseModel;
use Rbn\Framework\Core\Support\Contracts\Base\BaseModelInterface;

/**
 * UserTokenModel - Tek Kullanımlık E-posta Doğrulama / Parola Sıfırlama Token Kasası 🔐🕐
 *
 * [A0-6] `z_users_security` içindeki `verification_token` / `reset_token` tek kolonlu
 * kasası yerine kullanılır. Nedeni (ölçülen şema kanıtı):
 *   - `z_users_security` tekil `user_id` PK'sı taşıyor -> kullanıcı başına YALNIZCA
 *     bir doğrulama ve bir sıfırlama token'ı sığar; "tüm bekleyen token'ları iptal et"
 *     (A0-6 kabul kriteri) bu tabloda **ifade edilemez**.
 *   - `expires_at` / `used_at` kolonları yok -> token **süresiz** ve **sınırsız kez**
 *     geçerli (YA-4).
 *   - `type` kolonu **yok** -> `LifecycleHandler` bu kolona `where()` attığı için
 *     akış `SQLSTATE[42S22] Unknown column 'type'` ile 500 veriyor (A-04).
 *
 * Buradaki token satırı: `token_hash` (sha256, düz token YOK), `expires_at`,
 * `used_at`. Tablo satırı = tek token; kullanıldıktan sonra `used_at` dolar.
 *
 * Table: z_user_tokens
 *
 * @property int         $id
 * @property int         $user_id
 * @property string      $purpose      email_verification | password_recovery
 * @property string      $token_hash   sha256 ham token (64 hex karakter)
 * @property string      $expires_at
 * @property string|null $used_at
 * @property string|null $ip
 * @property string      $created_at
 */
class UserTokenModel extends BaseModel implements BaseModelInterface
{
    protected string $connection = 'database_project';
    protected $table = 'z_user_tokens';
    /**
     * FW-ALTYAPI-2 H / G1 - kiraci izolasyonu BEYANI (beyan zorunlulugu).
     *
     * Kapsam  : kimlik/oturum tablosu.
     * Gerekce: kimlik tablosu: `project_key` kapsam alani degil, ayri veritabani zaten kiraci ayrimi yapar; kolon bilincli EKLENMEZ.
     *
     * Varsayilan `BaseModel::$scoped` DEGISTIRILMEDI: kiraci izolasyonu
     * varsayilan olarak KAPALI kalir (acmak girisi 7 veritabaninda
     * kirar - FW-ALTYAPI-1 H 4.2 olculdu). G1 yalniz BEYAN ZORUNLULUGU
     * getirir: her model kararini kendi dosyasinda yazar.
     *
     * @tenant-scope identity
     */
    protected bool $scoped = false;
    protected $primaryKey = 'id';
    protected bool $timestamps = false;
}