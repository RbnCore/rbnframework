<?php
declare(strict_types=1);

namespace Rbn\Framework\Core\Database\Models\Project;

use Rbn\Framework\Core\Base\Data\BaseModel;
use Rbn\Framework\Core\Support\Contracts\Base\BaseModelInterface;

/**
 * FaqsModel - Knowledge Base Hub ❓🏛️
 * 
 * @property int    $id
 * @property string $question
 * @property string $answer
 * @property int    $order_num
 * @property int    $is_active
 * @property int    $has_blog
 * @property int|null $blog_id
 * @property string $created_at
 * @property string $updated_at
 */
class FaqsModel extends BaseModel implements BaseModelInterface
{
    protected string $connection = 'database_project';
    protected $table = 'z_app_faqs';
    /**
     * FW-ALTYAPI-2 H / G1 - kiraci izolasyonu BEYANI (beyan zorunlulugu).
     *
     * Kapsam  : kolonu var, tablo gercekten cok-kiracili.
     * Gerekce: kiraci kolonu ZATEN var ve tablo olcumde gercekten cok-kiracili; kapsam BU DALGADA acilmaz, G4 dongusunde `true`ye cevrilecek.
     *
     * Varsayilan `BaseModel::$scoped` DEGISTIRILMEDI: kiraci izolasyonu
     * varsayilan olarak KAPALI kalir (acmak girisi 7 veritabaninda
     * kirar - FW-ALTYAPI-1 H 4.2 olculdu). G1 yalniz BEYAN ZORUNLULUGU
     * getirir: her model kararini kendi dosyasinda yazar.
     *
     * @tenant-scope multi-tenant-ready
     */
    protected bool $scoped = true;
    protected bool $timestamps = true;

    /**
     * Sıkça Sorulan Soruları filtreli sorgular ❓
     */
    public function getFaqs(array $filters = []): array
    {
        try {
            // [FW-ALTYAPI-3 / H · G4] Kapsam modelde açık. Çağıran AÇIKÇA bir
            // anahtar verirse `withProjectScope()` ile kapsam O anahtara
            // daraltılır; verilmezse aktif bağlam kullanılır (davranış aynı).
            // ÖNCE: `where('project_key', $filters['project_key'] ?? project_key())`
            // kapsamla çakışıyordu.
            $model = !empty($filters['project_key'])
                ? $this->withProjectScope((string) $filters['project_key'])
                : $this;
            $query = $model->query();

            if (isset($filters['status'])) {
                $query->where('is_active', (int) $filters['status']);
            } elseif (isset($filters['is_active'])) {
                $query->where('is_active', (int) $filters['is_active']);
            } else {
                $query->where('is_active', 1);
            }

            if (isset($filters['with_blog'])) {
                $query->where('has_blog', $filters['with_blog'] ? 1 : 0);
            } elseif (isset($filters['has_blog'])) {
                $query->where('has_blog', (int) $filters['has_blog']);
            }

            if (isset($filters['blog_id'])) {
                $query->where('blog_id', (int) $filters['blog_id']);
            }

            $orderBy = $filters['order_by'] ?? 'order_num';
            $direction = $filters['direction'] ?? 'ASC';
            $query->orderBy($orderBy, $direction);

            if (isset($filters['limit']) && (int) $filters['limit'] > 0) {
                $query->limit((int) $filters['limit']);
            }

            $results = $query->get();
            if (is_object($results) && method_exists($results, 'all')) {
                $results = $results->all();
            }

            return (array) $results;
        } catch (\Throwable $e) {
            return [];
        }
    }
}
