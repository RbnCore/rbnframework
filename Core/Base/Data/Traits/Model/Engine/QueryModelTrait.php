<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Base\Data\Traits\Model\Engine;

use Rbn\Framework\Core\System\Config\Config;
use Rbn\Framework\Core\Support\Bridges\Helpers\Library\LogThrottle;

/**
 * QueryModelTrait - The Model Query Engine Bridge 🏎️🛰️
 * 
 * @property string $connection The connection authority for the current model 🏛️
 */
trait QueryModelTrait
{
    /**
     * YP-1 ölçüm defteri: `MODEL_NOT_SCOPED` yazılmış model SINIFLARI. 📊
     *
     * Bir trait'in statik özelliği, onu kullanan HER SINIFTA ayrı bir kopyadır;
     * `QueryModelTrait`'i tek kullanan kök sınıf `BaseModel` olduğu için bu tek
     * dizi tüm model ağacında paylaşılır ve "süreç başına bir kez" kuralı
     * gerçekten süreç genelinde geçerli olur.
     *
     * ANCAK: PHP-FPM'de her istek yeni süreç olduğu için bu dizi HER İSTEKTE
     * sıfırlanır (ölçüm: FW-LOG-GURULTU — 162 isteklik duman koşusunda 2098
     * satır). Gerçek sınır artık `LogThrottle::once()` (saatlik, süreçler arası).
     *
     * @var array<string, true>
     */
    protected static array $notScopedLogged = [];

    /** YP-1 throttle anahtarı etiketi (değer/PII YOK; sınıf adı eklenir). */
    public const MODEL_NOT_SCOPED_THROTTLE_TAG = 'yp1-model-not-scoped';

    /**
     * YP-1 ölçüm anahtarı (varsayılan `on`, `off` ile kapatılabilir).
     */
    public const MODEL_NOT_SCOPED_LOG_FLAG = 'security.model_not_scoped_log';

    /**
     * Start a fresh fluent query (Muscle Spoke) 🏎️
     */
    public function query(): object
    {
        // 📊 YP-1 (log-only): kapsam DIŞI modelin görünürlüğü. Bu tek satır
        // hiçbir koşulu DEĞİŞTİRMEZ — yalnız ölçüm. `scoped` varsayılanı
        // BİLİNÇLİ OLARAK `false` KALIYOR (kiraci izolasyonu bugün de kapalı).
        $this->logModelScopeVisibility();

        $provider = $this->queryProvider;

        // [FW-ALTYAPI-1 / B-02] `$this->db->connection(...)` CAGRISI KALDIRILDI.
        //
        // OLÇÜMSEL KANIT: `Database::connection()` `self` doner, yani
        // `$this->db->connection($x)` her iki durumda da **AYNI nesneyi**
        // verir; tek farki global `$activeConnection`i degistirmesidir — ve o
        // degisiklik geri alinmaz. Halbuki yonlendirme zaten asagidaki
        // `$builder->connection($this->connection)` ile saglaniyor ve
        // `QueryBuilder::onConnection()` her ifadeyi kendi kapsamina aliyor.
        //
        // Yani bu satir yalniz ve yalniz "kapsam disi sizma"ydi. Kaldirildi:
        // davranis birebir ayni, global durum artik kirletilmiyor.
        $db = $this->db;

        /** @var \Rbn\Framework\Core\Database\Engine\Providers\QueryBuilder $builder */
        $builder = new $provider($db, $this->table);

        // 🎼 RBN 3.5: [CONNECTION SYMMETRY] 🏛️⚙️⚓
        // Ensure the builder knows which authoritative connection it belongs to.
        if ($this->connection !== 'default' && method_exists($builder, 'connection')) {
            $builder->connection($this->connection);
        }

        // 🎼 RBN 3.5: [SOVEREIGN INJECTION] 🏺⚓
        // Tell the builder who its parent model is for automatic hydration.
        if (method_exists($builder, 'setModelClass')) {
            $builder->setModelClass(static::class);
        }

        // 🛡️ RBN 3.5: [MULTI-TENANT QUERY SCOPE] 🔍🔑
        //
        // [FW-ALTYAPI-3 / H · G4] İSTİSNA KURALA TAŞINDI.
        // Önceden burada `cm_sys_ip_blocks` **tablo adı** sabiti vardı ve o tablo
        // `whereIn([aktif, 'GLOBAL'])`, diğerleri `where('project_key', aktif)`
        // alıyordu. Artık istisna modelin kendi dosyasında beyan edilir
        // (`BaseModel::$projectScopeIncludes`), kural kabukta TEK.
        if (!empty($this->scoped) && method_exists($this, 'resolveScopeProjectKey')) {
            $activeKey = $this->resolveScopeProjectKey();
            // Boş bağlam (CLI/erken boot) → kapsam UYGULANMAZ. `''` ile
            // süzgeç yazmak tüm tabloyu 0'a indirirdi (design §4.6 tuzağı).
            if ($activeKey !== null && $activeKey !== '') {
                $kolon = method_exists($this, 'getProjectScopeColumn')
                    ? $this->getProjectScopeColumn()
                    : 'project_key';
                $includes = method_exists($this, 'getProjectScopeIncludes')
                    ? $this->getProjectScopeIncludes()
                    : [];
                if (is_array($includes) && $includes !== []) {
                    $builder->whereIn(
                        $kolon,
                        array_values(array_unique(array_merge([$activeKey], $includes)))
                    );
                } else {
                    $builder->where($kolon, '=', $activeKey);
                }
            }
        }

        return $builder;
    }

    /**
     * YP-1 (karar 2) — KAPSAM DIŞI MODEL GÖRÜNÜRLÜĞÜ 📊 (LOG-ONLY)
     *
     * ÖLÇÜLEN (karar): `BaseModel::$scoped` varsayılanı `false`, yani framework'ün
     * kendi modellerinin çoğu kiraci izolasyonu DIŞINDA çalışıyor. Bu bir
     * güvenlik eksiği olabilir ama varsayılanı değiştirmek 19 projeyi kırardı.
     * Karar: **varsayılan aynen kalır, yalnız görünürlük ölçülür.**
     *
     * DAVRANIŞ DEĞİŞMEZ:
     *   - hiçbir `where`/`insert` değişmez, kapsam ZORLANMAZ;
     *   - yalnızca `security` kanalına bir satır düşer.
     *
     * LOG GÜRÜLTÜSÜ SINIRI: MODEL SINIFI başına **saatte BİR KEZ**. Dizi
     * bayrağı aynı süreçte erken çıkış sağlar; asıl sınır süreçler arası
     * `LogThrottle::once()` kapısıdır (PHP-FPM'de dizi her istekte sıfırlanır).
     *
     * SATIR İÇERİĞİ: yalnız model sınıfının adı + `log_only` modu. Aktif proje
     * anahtarı gibi hassas değerler YAZILMAZ.
     */
    protected function logModelScopeVisibility(): void
    {
        // Kapsam İÇİ model doğru yapılandırılmıştır: sinyal üretmez.
        if (!empty($this->scoped)) {
            return;
        }

        $sinif = static::class;
        if (isset(self::$notScopedLogged[$sinif])) {
            return;
        }

        $flag = Config::get(self::MODEL_NOT_SCOPED_LOG_FLAG, 'on');
        if (!is_string($flag) || strtolower(trim($flag)) !== 'on') {
            return;
        }

        // Bayrak KAPALIYKEN de "görüldü" damgası vurulur: kapatmak ölçümü
        // susturur, sonradan açıldığında geçmişe dönük gürültü üretmez.
        self::$notScopedLogged[$sinif] = true;

        // Saatlik kapı: bu olmadan PHP-FPM'de HER İSTEKTE bir satır düşerdi
        // (ölçüm: duman koşusunda tek kalem 2098 satır).
        if (!LogThrottle::once(self::MODEL_NOT_SCOPED_THROTTLE_TAG . ':' . $sinif)) {
            return;
        }

        try {
            $this->logs()?->channel('security')->notice('MODEL_NOT_SCOPED', [
                'model' => $sinif,
                'mode'  => 'log_only',
            ]);
        } catch (\Throwable $e) {
            // Log yazılamazsa ÖLÇÜM DÜŞER; sorgu yine de çalışır.
        }
    }

    /**
     * Static factory for quick query building
     */
    public static function staticQuery(): object
    {
        return (new static())->query();
    }

    /**
     * Fluent Proxies 🏗️ 
     */
    public function where($column, $operator = null, $value = null)
    {
        return $this->query()->where($column, $operator, $value);
    }

    public function whereIn(string $column, array $values)
    {
        return $this->query()->whereIn($column, $values);
    }

    public function whereNotIn(string $column, array $values)
    {
        return $this->query()->whereNotIn($column, $values);
    }

    public function orWhere($column, $operator = null, $value = null)
    {
        return $this->query()->orWhere($column, $operator, $value);
    }

    public function whereNull(string $column)
    {
        return $this->query()->whereNull($column);
    }

    public function whereNotNull(string $column)
    {
        return $this->query()->whereNotNull($column);
    }

    public function whereRaw(string $sql, array $params = [])
    {
        return $this->query()->whereRaw($sql, $params);
    }

    public function orWhereRaw(string $sql, array $params = [])
    {
        return $this->query()->orWhereRaw($sql, $params);
    }

    public function orderByRaw(string $sql)
    {
        return $this->query()->orderByRaw($sql);
    }

    public function with($relations)
    {
        return $this->query()->with($relations);
    }

    public function select(string $columns = '*')
    {
        return $this->query()->select($columns);
    }

    public function join(string $table, string $first, string $operator, string $second, string $type = 'INNER')
    {
        return $this->query()->join($table, $first, $operator, $second, $type);
    }

    public function leftJoin(string $table, string $first, string $operator, string $second)
    {
        return $this->query()->leftJoin($table, $first, $operator, $second);
    }

    public function rightJoin(string $table, string $first, string $operator, string $second)
    {
        return $this->query()->rightJoin($table, $first, $operator, $second);
    }

    public function innerJoin(string $table, string $first, string $operator, string $second)
    {
        return $this->query()->innerJoin($table, $first, $operator, $second);
    }

    public function orderBy(string $column, string $direction = 'ASC')
    {
        return $this->query()->orderBy($column, $direction);
    }

    public function groupBy(string $column)
    {
        return $this->query()->groupBy($column);
    }

    public function having(string $condition)
    {
        return $this->query()->having($condition);
    }

    public function limit(int $limit)
    {
        return $this->query()->limit($limit);
    }

    public function offset(int $offset)
    {
        return $this->query()->offset($offset);
    }

    public function count(): int
    {
        return $this->query()->count();
    }

    public function exists(): bool
    {
        return $this->query()->exists();
    }

    public function when($condition, $callback, $default = null)
    {
        return $this->query()->when($condition, $callback, $default);
    }

    /**
     * Raw insert path 📥
     *
     * [FW-BASE-1 T3 / PATRON KARARI 4] Bu yol `CrudModelTrait::create()`'den
     * GECMEZ; **kapsam (project_key) enjekteyonu burada YAPILMAZ** — bu bir
     * kiraci izolasyonu kararıdır ve ayrı bir iş gerektirir.
     *
     * [FW-GECE-BASE / BULGU-4] Ancak MODEL BEYAZ LİSTESİ artık bu yoldan da
     * geçer: `create()` `applyMassAssignmentGuard()` uygularken `insert()`
     * uygulamıyordu, yani aynı modelde iki farklı yazma politikası vardı.
     *
     * GÜVENLİK ÖLÇÜMÜ (brif kuralı: "yalnız ÖLÇÜLEN çağıranlar güvenliyse"):
     *   - gerçek `insert()` çağıranı = 5 dosya / 5 çağrı (elle denetlendi)
     *   - depoda `$fillable` TANIMLAYAN model = 0
     * `filterFillable()` beyaz listesi BOŞ olan modellerde veri DÖNDÜRMEZ
     * (T1 kilidi) ve `security.mass_assignment=off` iken de etkisizdir.
     * Dolayısıyla bu değişiklik bugün HİÇBİR yazma yolunu etkilemez; yalnız
     * beyaz listesi tanımlayan (opt-in) modellerde süzgeci devreye alır.
     */
    public function insert(array $data): bool|int
    {
        if (method_exists($this, 'logProtectedFieldAttempt')) {
            $this->logProtectedFieldAttempt($data, 'insert');
        }

        if (method_exists($this, 'filterFillable')) {
            $data = $this->filterFillable($data);
        }

        return $this->query()->insert($data);
    }

    /**
     * [B-27 · FW-KARAR-2-B] ⚠️ GÖLGE METOT (ölü kod DEĞİL, erişilemez) — SİLMEYİN ⚠️
     *
     * KAYNAK RAPORU DÜZELTİLDİ: "QueryModelTrait'in `update`/`delete`/`truncate()`
     * metodlarını kaldır" önerisi **YANLIŞTIR**. Ölçüm:
     *   - `update()`  → `ActionModelTrait.php:35` bunu `CrudModelTrait::update
     *     insteadof QueryModelTrait` ile **gölgeliyor**; framework ağacında
     *     `use QueryModelTrait` deyeni YALNIZCA `ActionModelTrait`'te geçiyor ve
     *     O da `CrudModelTrait::update` gölgelemesini yapıyor → `update()`
     *     erişilemez (ölçülen çağrı sayısı: **0**).
     *   - `delete()` ve `truncate()` → **ÖLÜ DEĞİL**, gölgelenmez, canlıdır.
     *
     * Bu yüzden K-4 (bilinçli kapatılacaklar) kararı: **SİLME YOK.** Kısmi
     * silme imza/API yüzeyini değiştirirdi (Anayusa §10) ve kaynak raporun
     * "üçünü birden kaldır" tarzı bir temizlik `delete()`/`truncate()`'i de
     * götürürdü. Metot bilinçli olarak bırakıldı; bu yorum, sonraki bir
     * "ölü kod temizliği" turunun **yalnız `update()`'i sildiğini** kanıtlar.
     *
     * @see ActionModelTrait (gölgeleme satırı 35)
     */
    public function update(array $data): bool
    {
        return $this->query()->update($data);
    }

    public function delete(): bool
    {
        return $this->query()->delete();
    }

    public function truncate(): bool
    {
        return $this->query()->truncate();
    }
}
