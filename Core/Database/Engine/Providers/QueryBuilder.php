<?php

namespace Rbn\Framework\Core\Database\Engine\Providers;

use Rbn\Framework\Core\Database\Database;
use Rbn\Framework\Core\Support\Contracts\Database\QueryProviderInterface;
use Rbn\Framework\Core\Database\Engine\Traits\Query\ConditionTrait;
use Rbn\Framework\Core\Database\Engine\Traits\Query\JoinTrait;
use Rbn\Framework\Core\Database\Engine\Traits\Query\AggregateTrait;
use Rbn\Framework\Core\Database\Engine\Traits\Query\CrudTrait;
use Rbn\Framework\Core\Database\Engine\Traits\Query\SelectionTrait;
use Rbn\Framework\Core\Database\Engine\Traits\Query\SqlHelperTrait;
use Rbn\Framework\Core\Database\Engine\Traits\Query\ExecutionTrait;

/**
 * QueryBuilder - High-Performance SQL Constructor 🎻⚙️
 * 
 * RBN 3.0: The "Zen" state of the Query Engine. 🏛️
 * Extreme modularity via Traits. All specialized logic moved to muscle traits.
 * Fully compliant with the elite "Masterpiece" architectural standards.
 */
class QueryBuilder implements QueryProviderInterface
{
    // The Muscle System (Traits) 🧬🏗️⚓
    use SqlHelperTrait, SelectionTrait, ConditionTrait, JoinTrait, AggregateTrait, CrudTrait, ExecutionTrait;

    protected Database $db;
    protected string $table;
    protected string $connectionName = 'default';

    /** --- Query Registry (Storage for traits) --- */
    protected string $select = '*';
    protected array $where = [];
    /** [D-09] Koşul başına OR bayrağı (ilk koşulda OR öneki yazılmaz). */
    protected array $whereOr = [];
    protected array $params = [];
    protected array $joins = [];
    protected string $orderBy = '';
    protected string $groupBy = '';
    protected string $having = '';
    protected ?int $limit = null;
    protected ?int $offset = null;
    protected array $eagerLoads = [];

    /** @var string|null Active Hydration Target 🏺 */
    protected ?string $modelClass = null;

    /**
     * Set the model class for result hydration 🧬
     */
    public function setModelClass(string $class): self
    {
        $this->modelClass = $class;
        return $this;
    }

    /**
     * Initialize Builder with Connection Hub 🏛️
     */
    public function __construct(?Database $db = null, ?string $table = null)
    {
        $this->db = $db ?? Database::getInstance();

        // RBN 3.0: Support property-based connection discovery 🛰️
        //
        // [FW-ALTYAPI-1 / B-02] ESKIDEN `$this->db = $this->db->connection(...)` vardi.
        // OLÇÜMSEL KANIT: `connection()` `self` dondurunce **ayni nesne** geri
        // doner; yani atamanin tek etkisi GLOBAL `$activeConnection`i degistirmekti
        // ve o degisiklik HICBIR ZAMAN geri alinmiyordu. Yonlendirme zaten
        // `$connectionName` uzerinden yapiliyor (asagidaki satir + `onConnection()`).
        // Bu yuzden cagri KALDIRILDI: davranis birebir ayni, sizma YOK.
        if ($db === null && property_exists($this, 'connection') && $this->connection !== 'default') {
            $this->connectionName = $this->connection;
        }

        if ($table !== null) {
            $this->table = $table;
        }
    }

    /**
     * [B-24 / FW-ALTYAPI-1] SORGUYU KENDI BAGLANTISINDA CALISTIR — global durumu kirletmez 🔒🧬
     *
     * Tum mutasyon trait'leri (`CrudTrait`, `ExecutionTrait`) sorguyu `$this->db`
     * uzerinden `query()` ile kosturur ve `Database` o AN hangi baglanti
     * cekiyorsa oraya gider. Burada o an, islem bitince **geri alinir**.
     *
     * ONCEDEN: `$this->db->connection($this->connectionName)->query(...)`
     * -> yonlendirme YAPILIRDI ama geri alinmazdi. Yani bir model sorgusu,
     * kendisinden SONRA gelen her ham `$db->raw()` cagrisinin hedefini
     * degistirebiliyordu (olculen canli ornek: `DatabaseHandlers::dbInfo()`
     * hangi veritabaninda oldugunu `$db->connection()` ile ANLAMAYA calisiyor).
     *
     * @param callable $islem Yalniz bu builder'in baglantisinda calisacak is.
     * @return mixed          Isin donus degeri AYNEN doner.
     */
    protected function onConnection(callable $islem): mixed
    {
        return $this->db->connectionScoped($this->connectionName, $islem);
    }

    /**
     * Switch context connection factory 🏹
     */
    public function connection(string $name): static
    {
        $this->connectionName = $name;
        return $this;
    }
}
