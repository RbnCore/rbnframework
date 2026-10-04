<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Base\Data;

use Rbn\Framework\Core\Database\Database;
use Rbn\Framework\Core\Database\Engine\Providers\QueryBuilder;
use Rbn\Framework\Core\Support\Contracts\Base\BaseModelInterface;
use Rbn\Framework\Core\Base\Data\Traits\Model\ActionModelTrait;
use Rbn\Framework\Core\Base\Concerns\Data\HybridAccessTrait;
use Rbn\Framework\Core\Base\BaseComponent;

/**
 * BaseModel - RBN Framework Modern Engine 🎻⚙️⚓
 * 
 * RBN 3.5: Masterpiece Simplicity.
 * Artık BaseComponent'tan türeyerek tüm keşif ve bağlam yeteneklerine "doğuştan" sahiptir.
 * 
 * @method self when(mixed $condition, callable $callback, callable $default = null)
 * @method self where(string|array|callable $column, mixed $operator = null, mixed $value = null)
 * @method self orWhere(string|array|callable $column, mixed $operator = null, mixed $value = null)
 * @method self whereNull(string $column)
 * @method self whereNotNull(string $column)
 * @method self select(string|array $columns = '*')
 * @method self join(string $table, string $first, string $operator = null, string $second = null)
 * @method self orderBy(string $column, string $direction = 'asc')
 * @method self limit(int $value, int $offset = 0)
 * @method self offset(int $value)
 * @method mixed get()
 * @method ?array first()
 * @method ?array find(mixed $id)
 * @method bool exists()
 * @method int count()
 * @method int|bool create(array $data)
 * @method bool update(mixed $id, array $data)
 * @method bool save(array $data)
 * @method bool delete()
 * @method bool destroy(mixed $id)
 * @method bool toggleStatus(mixed $id, string $field = 'is_active')
 * @method int increment(mixed $id, string $column, int $amount = 1)
 * @method int decrement(mixed $id, string $column, int $amount = 1)
 * @method int insert(array $data)
 * @method int insertBatch(array $data)
 * @method bool truncate()
 */
abstract class BaseModel extends BaseComponent implements BaseModelInterface, \ArrayAccess
{
    /** --- The Unified Model Power powerhouse (Data Methods) --- */
    use ActionModelTrait, HybridAccessTrait;

    /** --- Identity & Connection Configuration --- */
    protected $table;
    protected $primaryKey = 'id';
    protected string $connection = 'database_project';
    protected string $queryProvider = QueryBuilder::class;
    protected bool $scoped = false; // 🛡️ RBN 3.5: Multi-Tenant Scoping Switch

    /**
     * [FW-ALTYAPI-3 / H · G4] Bağlam ÇÖZÜLEMEDİĞİ durumun sentineli 🚩
     *
     * `getActiveProjectKey()` bağlam yokken bu değeri döner. ÖLÇÜLEN GERÇEK
     * (7 yerel veritabanı, 74 kolon): hiçbir tabloda `project_key = 'default'`
     * satırı YOKTUR. Yani bu değer ne okumada bir işe yarar (her zaman 0
     * satır) ne de yazmada (görünmez, sahipsiz satır).
     *
     * Bu yüzden yazma yolu bu sentineli `project_key` olarak **yazmaz**.
     */
    public const SCOPE_KEY_UNRESOLVED = 'default';

    /**
     * [FW-ALTYAPI-3 / H · G4] Kiraci kapsamının AÇIK istisnaları 🔓
     *
     * TASARIM (§4.7): `QueryModelTrait` içinde `cm_sys_ip_blocks` **tablo adı**
     * sabiti gömülüydü; tek bir tablo için yazılmış istisna büyüyünce kural
     * kabukta kalır ve yeni tablo eklemek kabuğu düzenlemek olurdu.
     * İstisna ARTık **modelin kendi dosyasında** beyan edilir.
     *
     * Örnekler:
     *   - `CmSysIpBlocksModel` → `['GLOBAL']` (aktif proje + GLOBAL kurallar)
     *   - `SettingsApiModel`   → `['shared']` (proje özel + ortak API anahtarları)
     *
     * @var string[] Aktif kiraciya ek olarak kapsama giren `project_key` degerleri.
     */
    protected array $projectScopeIncludes = [];

    /**
     * [FW-ALTYAPI-3 / H · G4] Kapsam için AÇIKÇA seçilmiş anahtar 🔑
     *
     * `null` (varsayılan) → aktif bağlam kullanılır.
     * Dolu → kapsam bu anahtara bağlanır (`withProjectScope()`).
     *
     * NEDEN: `scoped = true` modellerde çağıranların elle yazdığı
     * `where('project_key', X)` ikinci kez çalışıyor ve farklı değerlerde
     * sonucu boş çeviriyordu. Çağıran artık sorguyu
     * `withProjectScope($X)->query()` ile kuruyor: kapsam **daha geniş** değil,
     * yalnızca **istenen** anahtara daralıyor.
     */
    protected ?string $projectScopeKey = null;

    /* ==========================================================================
       [ OPTIONAL MASS ASSIGNMENT ] - FW-BASE-1 T1/T2/T3 🛡️⚓
       Opsiyonel beyaz/kara liste desteği + korumalı alan log-only sayacı +
       kiracı izolasyonu (`project_key`) TEK trait'te yaşar:
       `Engine/MassAssignmentTrait` (ActionModelTrait üzerinden gelir).

       Model bu özelliklerden birini TANIMLAMIYORSA hiçbir süzme yapılmaz ve
       iki bayrak da (`security.mass_assignment`, `security.protected_field_log`)
       VARSAYILAN KAPALI olduğu için yazma davranışı birebir korunur.
       ========================================================================== */

    /** --- Internal Registry --- */
    protected Database $db;

    /**
     * Resolve active project key safely 🔑
     */
    public function getActiveProjectKey(): string
    {
        return function_exists('active_project_key') ? (active_project_key() ?: 'default') : 'default';
    }

    /**
     * [FW-ALTYAPI-3 / H · G4] Bu model kiraci kapsamı AÇIK mi? 🔍
     *
     * Çağıranlar (ör. `ContentServiceTrait`) elle `project_key` süzgeci
     * yazmadan önce bunu sorar: kapsam açıksa `withProjectScope()` kullanılır,
     * kapalıysa ESKİ elle süzgeç yolu BİREBİR korunur (geri uyumluluk).
     */
    public function isProjectScoped(): bool
    {
        return !empty($this->scoped);
    }

    /**
     * [FW-ALTYAPI-3 / H · G4] Kiraci kapsamının AÇIK istisnaları 🔓
     *
     * @return string[]
     */
    public function getProjectScopeIncludes(): array
    {
        return $this->projectScopeIncludes;
    }

    /**
     * [FW-ALTYAPI-3 / H · G4] Kapsamın uygulanacağı anahtar 🔑
     *
     * `withProjectScope()` ile açıkça seçilmiş anahtar varsa O, yoksa aktif
     * bağlam kullanılır. **Boş bağlam** (`''`) durumunda `null` döner;
     * `null` dönen yerde kapsam filtresi hiç UYGULANMAZ — böylece CLI'da
     * yanlışlıkla `project_key = ''` ile tüm tabloyu silen bir süzgeç yazılmaz.
     *
     * @return string|null
     */
    public function resolveScopeProjectKey(): ?string
    {
        if ($this->projectScopeKey !== null && $this->projectScopeKey !== '') {
            return $this->projectScopeKey;
        }
        $aktif = $this->getActiveProjectKey();
        return ($aktif === '' || $aktif === null) ? null : $aktif;
    }

    /**
     * [FW-ALTYAPI-3 / H · G4] Kapsamı BELİRTİLEN kiracıya bağla 🎯
     *
     * Elle yazılmış `where('project_key', X)` yerine kullanılır. Kapsamı
     * genişletmez, yalnızca istenen anahtara daraltır (fail-CLOSED).
     *
     * @param string|null $projectKey Boş/`null` → aktif bağlama döner.
     */
    public function withProjectScope(?string $projectKey): self
    {
        $clone = clone $this;
        $clone->projectScopeKey = ($projectKey === null || $projectKey === '') ? null : $projectKey;
        $clone->scoped = true;
        return $clone;
    }

    /**
     * [FW-ALTYAPI-3 / H · G4] Kapsamın yazılacağı SQL kolonu 🎯
     *
     * JOIN'li sorgularda `project_key` iki tabloda da varsa MySQL **1052
     * "Column 'project_key' in where clause is ambiguous"** verir. Bu yüzden
     * kapsam süzgeci **tablo adı ile nitelikli** yazılır.
     */
    public function getProjectScopeColumn(): string
    {
        $tablo = $this->getTableOrNull();
        if ($tablo === null || str_contains($tablo, '.')) {
            return 'project_key';
        }
        return $tablo . '.project_key';
    }

    /**
     * Disable project scoping for a query instance 🌐
     */
    public function withoutProjectScope(): self
    {
        $clone = clone $this;
        $clone->scoped = false;
        $clone->projectScopeKey = null;
        return $clone;
    }

    /**
     * Initialize Model with the Database Hub ⚓🧬
     */
    public function __construct()
    {
        // 1. Kök DNA'yı (BaseComponent) ayağa kaldır
        // Bu çağrı otomatik olarak discovery, route ve request'i doldurur!
        parent::__construct();

        // 2. Database instance initialization
        $this->db = Database::getInstance();
    }

    /* ==========================================================================
       [ IDENTITY ACCESSORS ] - Connection & Table Discovery
       ========================================================================== */

    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * [D-30 / FW-ALTYAPI-1 A-02] TABLOSUZ modeller için AÇIK yol 🎯
     *
     * SORUN: `getTable(): string` sözleşmesi `$table` alanının `string`
     * olduğunu varsayar; `protected $table = null` bildiren bir model
     * (`SchemaDoctorModel`) bu yolu çağırırsa `strict_types=1` altında
     * anlaşılmaz bir `TypeError` alır.
     *
     * ÇÖZÜM (B-24'ün önerisi, imza KIRILMADI):
     *   - `getTable(): string` **AYNEN korunur** — `?string` yapmak strict
     *     types altında HER çağıranı ve arayüzü kırardı.
     *   - "Tablom yok" durumu TİP DOĞRU (nullable) ayrı bir metotla açılır.
     *
     * Geri uyum: yeni metot EKLEMEDİR; hiçbir mevcut çağrı değişmez.
     */
    public function getTableOrNull(): ?string
    {
        return $this->table !== null && $this->table !== '' ? (string) $this->table : null;
    }

    public function getPrimaryKey(): string
    {
        return $this->primaryKey;
    }

    public function getConnection(): string
    {
        return $this->connection;
    }

    public function getDb(): Database
    {
        return $this->db;
    }

    /**
     * Bridge to any other table in the system (Static Gateway)
     */
    public static function fromTable(string $name)
    {
        return Database::getInstance()->table($name);
    }
}
