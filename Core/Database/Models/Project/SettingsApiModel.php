<?php
declare(strict_types=1);

namespace Rbn\Framework\Core\Database\Models\Project;

use Rbn\Framework\Core\Base\Data\BaseModel;
use Rbn\Framework\Core\Support\Contracts\Base\BaseModelInterface;

/**
 * SettingsApiModel - Project-level API and Integration Keys ⚙️🔑
 * 
 * RBN 3.5: Optional table (z_settings_api) created in project database on demand.
 * 
 * @property int         $id
 * @property string|null $group_key
 * @property string      $project_key
 * @property string      $setting_type (api, notification, bot, etc.)
 * @property string      $setting_label
 * @property string      $setting_key (GEMINI_API_KEY, SHOPIER_API_KEY, etc.)
 * @property string|null $setting_value
 * @property int         $is_active
 * @property string      $created_at
 * @property string      $updated_at
 */
class SettingsApiModel extends BaseModel implements BaseModelInterface
{
    protected string $connection = 'database_project';
    protected $table = 'z_settings_api';
    protected bool $timestamps = true;
    protected bool $scoped = true;

    /**
     * [FW-ALTYAPI-3 / H · G4] Kiraci kapsamı istisnası **BİLEREK BOŞ** 🚫
     *
     * ÖNCE: `SettingsApiRepository:34,82` elle `whereIn([X,'shared'])` yazıyordu
     * ve kapsam açık olduğu için sonuç
     * `project_key = X AND project_key IN (X,'shared')` → `shared` satırları
     * GÖRÜNMEZ oluyordu (çift süzgeç). G4 bu çakışmayı repository'de çözdü.
     *
     * NEDEN `['shared']` BURAYA YAZILMADI (ölçülen karşı-risk):
     * istisna modele taşınırsa `saveApiKey()`'in `setting_key` araması da
     * `IN(X,'shared')` olur ve projeye özel kayıt yokken **paylaşılan satırı
     * GÜNCELLEYEREK** projeye özel kaydı üretmez. Yerel ölçümde `shared`
     * satırı olan 3 veritabanı var (sırasıyla 2, 1 ve 4 satır) —
     * yani bu bir soyut risk değil, ÖLÇÜLMÜŞ bir risktir.
     *
     * KARAR: `shared` görünürlüğü **patron kararı** (rapor §Açık Sorular).
     * Bugünkü GÖRÜNÜRLÜK davranışı (yalnız proje özel satırlar) korunur.
     */
    protected array $projectScopeIncludes = [];
}
