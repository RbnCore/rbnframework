<?php
declare(strict_types=1);

namespace Rbn\Framework\Core\Database\Models\Project;

use Rbn\Framework\Core\Base\Data\BaseModel;
use Rbn\Framework\Core\Support\Contracts\Base\BaseModelInterface;

/**
 * PagesModel - Static Content Orchestrator 📄🏛️
 * 
 * @property int    $id
 * @property string $title
 * @property string $slug
 * @property string $content
 * @property string $template
 * @property string $custom_class
 * @property int    $show_in_footer
 * @property int    $order_num
 * @property string $status (active, draft, archived)
 * @property string $created_at
 * @property string $updated_at
 */
class PagesModel extends BaseModel implements BaseModelInterface
{
    protected string $connection = 'database_project';
    protected $table = 'z_app_pages';
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
     * Sayfaları filtreli sorgular 📄📜
     */
    public function getPages(array $filters = []): array
    {
        try {
            // [FW-ALTYAPI-3 / H · G4] Kapsam modelde açık; çağıran AÇIKÇA bir
            // anahtar verirse kapsam O anahtara daraltılır (çakışma yok).
            // ÖNCE: `where('project_key', $filters['project_key'] ?? project_key())`
            // kapsamla ikinci kez çalışıyordu.
            $model = !empty($filters['project_key'])
                ? $this->withProjectScope((string) $filters['project_key'])
                : $this;
            $query = $model->query()->where('status', 'active');

            if (isset($filters['slug'])) {
                $query->where('slug', $filters['slug']);
            }

            if (isset($filters['show_in_footer'])) {
                $query->where('show_in_footer', (int) $filters['show_in_footer']);
            }

            if (isset($filters['id'])) {
                $query->where('id', (int) $filters['id']);
            }

            $query->orderBy('order_num', 'ASC');

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
