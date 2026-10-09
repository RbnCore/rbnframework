<?php

namespace Rbn\Framework\Core\Database\Engine\Traits\Query;

use PDO;

/**
 * ExecutionTrait - The Query Performance Hub ⚔️🛰️⚓
 * 
 * Compiles internal state into SQL and executes it with Collections integration.
 */
trait ExecutionTrait
{
    /**
     * Execute the query and return an elite smart Collection 🔱🧬
     */
    public function get(): mixed
    {
        $sql = $this->toSql();
        // [B-24] Yonlendirme KAPSAMDA: `get()` bittiginde aktif baglanti ESKI
        // HALINE DONER. Onceden burada dogrudan `connection()` cagrisi vardi ve
        // geri alinmiyordu (kapsam disi sizma).
        $stmt = $this->onConnection(fn(): mixed => $this->db->query($sql, $this->params));

        // [D-12] PANIK FRENİ ASİMETRİSİ: `Database::query()` `RBN_PANIC_ACTIVE`
        // tanımlıysa `false` döndürüyor (bellek tükenmesi koruması); `raw()` bu
        // durumu denetliyordu, `get()` denetlemiyordu → `Error: Call to a member
        // function fetchAll() on false` (ölümcül hata → HTTP 500). Artık aynı
        // güvenli yol: boş sonuç kümesi.
        if (!$stmt instanceof \PDOStatement) {
            return function_exists('collect') ? collect([]) : [];
        }

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 🎼 RBN Framework: [HYDRATION ENGINE] 🏺🛰️
        // If a model is assigned, transform raw arrays into full model instances.
        if ($this->modelClass && class_exists($this->modelClass)) {
            $results = array_map(function ($row) {
                $model = new $this->modelClass();
                if (method_exists($model, 'forceJsonFill')) {
                    $model->forceJsonFill($row);
                } else {
                    // 🎼 RBN Framework: [DYNAMIC HYDRATION] 🛰️⚓
                    foreach ($row as $key => $value) {
                        $model->{$key} = $value;
                    }
                }
                return $model;
            }, $results);
        }

        // Return wrapping with the RBN Collections Engine 🚀
        return function_exists('collect') ? collect($results) : $results;
    }

    /**
     * Sorguyu çalıştırır ve DÜZ satır dizisi döndürür: `list<array<string, mixed>>`.
     *
     * `get()` bir `Collection` döndürür (model atanmışsa öğeleri model nesnesidir);
     * `array` dönüş tipli yöntemde `return ...->get();` ya da `->get() ?: []` TypeError
     * verir/işe yaramaz (nesne her zaman doğrudur). Satır dizisi gereken yerde bu kullanılır.
     *
     * @return array<int, array<string, mixed>>
     */
    public function rows(): array
    {
        $result = $this->get();
        $items = is_array($result) ? $result : (method_exists($result, 'all') ? $result->all() : (array) $result);

        return array_values(array_map(
            static fn ($row) => is_array($row) ? $row : (is_object($row) && method_exists($row, 'toArray') ? $row->toArray() : (array) $row),
            $items
        ));
    }

    /**
     * Execute and return the first matched record 🏹
     */
    public function first(): mixed
    {
        // [D-10] LIMIT KALICI YAZILMAZ. `first()` gövdesi `$this->limit(1)`
        // yazıyordu; aynı builder ikinci kez kullanıldığında LIMIT 1 kalıyordu
        // ve `get()` yalnız bir satır döndürüyordu (ölçüldü: 3 satır beklenirken 1).
        // Kullanıcının KENDİ limit()'i varsa korunur; yoksa kopyaya 1 yazılır.
        $kopya = clone $this;
        if ($kopya->limit === null) {
            $kopya->limit(1);
        }
        $results = $kopya->get();
        return $results[0] ?? null;
    }

    /**
     * [D-16] MySQL/MariaDB `LIMIT` olmadan `OFFSET` kabul ETMEZ; `offset()` tek
     * başına çağrıldığında `SELECT ... OFFSET 2` üretiliyordu → PDOException 1064.
     * MySQL'in "sınırsız LIMIT" deyimi (`18446744073709551615`) eklenir: anlam
     * DEĞİŞMEZ (offset'ten sonraki tüm satırlar), yalnız SQL geçerli olur.
     */
    public const LIMIT_SINIRSIZ = '18446744073709551615';

    /**
     * Compile internal state into a final SQL string 🏛️⚓
     */
    public function toSql(): string
    {
        $sql = "SELECT {$this->select} FROM `{$this->table}`";

        if (!empty($this->joins))
            $sql .= " " . implode(" ", $this->joins);
        if (!empty($this->where))
            $sql .= $this->buildWhere();
        if ($this->groupBy)
            $sql .= " GROUP BY {$this->groupBy}";
        if ($this->having)
            $sql .= " HAVING {$this->having}";
        if ($this->orderBy)
            $sql .= " ORDER BY {$this->orderBy}";
        if ($this->limit !== null)
            $sql .= " LIMIT {$this->limit}";
        elseif ($this->offset !== null)
            $sql .= " LIMIT " . self::LIMIT_SINIRSIZ; // [D-16]
        if ($this->offset !== null)
            $sql .= " OFFSET {$this->offset}";

        return $sql;
    }
}
