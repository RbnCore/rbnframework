<?php

namespace Rbn\Framework\Core\Database\Engine\Traits\Query;

/**
 * ConditionTrait - The Logical Filter Engine 🔍🛰️
 */
trait ConditionTrait
{
    /**
     * Set a WHERE clause for the query 🛰️⚓
     */
    public function where($column, $operator = null, $value = null): static
    {
        if ($column instanceof \Closure) {
            $nested = new self($this->db, $this->table);
            $nested->params = $this->params; // 🚀 Copy parent parameters to prevent parameter key collision
            $nested->connectionName = $this->connectionName; // [D-09] nested builder aynı bağlantıyı kullanır
            $column($nested);
            
            $sql = $nested->buildWhereOnly();
            if (!empty($sql)) {
                $this->where[] = "({$sql})";
                // Grup, ILK kosulu orWhere ile basliyorsa OR baglanir;
                // icteki sonraki orWhere'ler grubun kendi icinde AND'li kalir.
                $this->whereOr[array_key_last($this->where)] = !empty($nested->whereOr[0]);
                $this->params = $nested->params; // 🚀 Update parent parameters with nested values
            }
            return $this;
        }

        if (is_array($column)) {
            foreach ($column as $key => $val) {
                $this->where($key, '=', $val);
            }
            return $this;
        }

        // 🎼 RBN Framework: [RBN Framework PARAMETER RBN Framework] 🎻🛰️⚓
        // Use func_num_args to detect exact intent regardless of NULL values
        $argCount = func_num_args();

        // [D-04] Operatör beyaz listesi + null/IN yönlendirmesi tek yerden.
        [$operator, $value, $donus] = $this->normalizeCondition($column, $operator, $value, $argCount, 'WHERE');

        // [D-04] `IS`/`IS NOT` + null → SQL'in kendi `IS NULL` deyimi (parametresiz).
        if ($donus === self::DONUS_NULL_TEST) {
            $this->where[] = $this->wrapColumn($column) . ' ' . $operator . ' NULL';
            return $this;
        }

        // [D-04] `IN`/`NOT IN` + dizi → her eleman için ayrı placeholder.
        if ($donus === self::DONUS_IN_LIST) {
            return strtoupper($operator) === 'NOT IN'
                ? $this->whereNotIn((string) $column, (array) $value)
                : $this->whereIn((string) $column, (array) $value);
        }

        $paramKey = 'p' . count($this->params);
        $this->params[$paramKey] = $value;
        $wrappedColumn = $this->wrapColumn($column);
        $this->where[] = "{$wrappedColumn} {$operator} :{$paramKey}";

        return $this;
    }

    /** [D-04] `IS NULL` deyimi döndüğünde. */
    private const DONUS_NULL_TEST = 'null_test';

    /** [D-04] `IN (...)` liste bağlama döndüğünde. */
    private const DONUS_IN_LIST = 'in_list';

    /**
     * [D-04] KOŞUL NORMALLEŞTİRİCİ — `where()` ve `orWhere()` ortak kullanır.
     *
     * ESKİ YARI(M) DOĞRULAMA: operatör beyaz listesi vardı ama `IS`/`IS NOT`
     * birleştirildiğinde değer yine placeholder olarak bağlanıyordu
     * (`col IS :p0`). PDO null'u stringe çevirdiği için `IS` anlamı değil,
     * boş string karşılaştırması çalışıyordu; `where('col', null)` ise
     * hiçbir anlam taşımayan `col = :p0` üretiyordu.
     *
     * KURALLAR (beyaz liste + null + dizi):
     *   1. 2 argüman → eşitlik. Değer `null` ise `IS NULL` (null eşitlik değildir).
     *   2. 3 argüman, operatör beyaz listede değilse → operatör ASLINDA değerdir
     *      (Proxy/Ghost Null koruması korunur).
     *   3. `IS`/`IS NOT` + `null` → parametresiz `IS NULL` / `IS NOT NULL`.
     *   4. `IN`/`NOT IN` + dizi → her eleman ayrı placeholder (boş dizi = koşul yok).
     *
     * @return array{0:string,1:mixed,2:string} [operatör, değer, dönüş tipi]
     *
     * @throws \InvalidArgumentException Operatör ve değer de yoksa.
     */
    private function normalizeCondition($kolon, $operator, $value, int $argCount, string $etiket): array
    {
        $validOperators = ['=', '<', '>', '<=', '>=', '<>', '!=', 'LIKE', 'NOT LIKE', 'IN', 'NOT IN', 'IS', 'IS NOT'];

        if ($argCount === 2) {
            $value = $operator;
            $operator = '=';
        } elseif (!is_null($operator)
            && !in_array(strtoupper(trim((string) $operator)), $validOperators, true)
        ) {
            $value = $operator;
            $operator = '=';
        }

        // Hâlâ operatör yoksa bu gerçek bir çağrı hatasıdır.
        if (is_null($operator)) {
            throw new \InvalidArgumentException(
                "{$etiket} clause requires column and value (or operator and value)."
                . " Received: ({$kolon}, null)"
            );
        }

        $normOperator = strtoupper(trim((string) $operator));

        // [D-04] SQL'in ÜÇ DEĞERLİ MANTIĞI: `col = NULL` ve `col != NULL`
        // **hiçbir zaman** doğru değildir (NULL karşılaştırılamaz). Çağıranın
        // kastı belli olduğu için bu iki biçim parametresiz deyime çevrilir:
        //   `= NULL`  → `IS NULL`      ·   `<> / != NULL` → `IS NOT NULL`
        // Ağaç ölçümü: 2 argümanlı where/orWhere çağrılarının 0/691'i null
        // geçiyor; 3 argümanlı null değerli çağrı 0 → meşru kullanım kırılmaz.
        if (is_null($value)) {
            if ($normOperator === '=') {
                return ['IS', null, self::DONUS_NULL_TEST];
            }
            if ($normOperator === '<>' || $normOperator === '!=') {
                return ['IS NOT', null, self::DONUS_NULL_TEST];
            }
        }

        if (in_array($normOperator, ['IS', 'IS NOT'], true)) {
            if (is_null($value)) {
                return [$normOperator, null, self::DONUS_NULL_TEST];
            }
            // `IS` + null olmayan değer: anlamlı (IS TRUE/FALSE/UNKNOWN) —
            // mevcut davranış korunur, değer placeholder'a gider.
            return [$operator, $value, 'bind'];
        }

        if (in_array($normOperator, ['IN', 'NOT IN'], true) && is_array($value)) {
            return [$normOperator, array_values($value), self::DONUS_IN_LIST];
        }

        return [$operator, $value, 'bind'];
    }

    /**
     * Add an OR WHERE clause to the query ⚓🛰️
     *
     * [D-09] IKI KALEMI:
     *  1) **2-ARGUMAN BIÇIMI.** `orWhere('col', $value)` cagrisinda eskiden
     *     deger/operator kararı `$value === null` kontrolüne bagliydi; bu
     *     yüzden 3-argüman `orWhere('c','=',null)` de "eşitlik null" yerine
     *     bozuk SQL üretiyordu. Artik `where()` ile AYNI kural uygulanır:
     *     2 argüman → eşitlik, 3 argüman → operator + değer.
     *  2) **İLK KOSULDA `OR` ÖNEKI.** `orWhere()` tek başına çağrıldığında
     *     koşul listenin başına "OR ..." olarak yazılıyor ve derleyici
     *     `WHERE OR ...` üretiyordu (geçersiz SQL). Önek artık
     *     `buildWhere()`/`buildWhereOnly()` tarafından konuma göre konur:
     *     ilk koşulda `OR` YOK, sonrakilerde `OR` var.
     */
    public function orWhere($column, $operator = null, $value = null): static
    {
        $argCount = func_num_args();

        if ($column instanceof \Closure || is_array($column)) {
            // where() ile aynı normalize davranışı; kapanış/array için
            // where() aynı kuralları uygular (tekrar etmeyelim).
            $oncekiSayi = count($this->where);
            $this->where($column, $operator, $value);
            // where() ekledigi kosullar bir GRUPtur: grubun ILK kosulu OR'lanir,
            // kalanlari grup icinde AND'li kalir (oncelik dogru kalir).
            if (count($this->where) > $oncekiSayi) {
                $this->whereOr[$oncekiSayi] = true;
            }
            return $this;
        }

        // [D-04] where() ile AYNI normalleştirme: 2 argüman = eşitlik (null ise IS NULL),
        // `IS`/`IS NOT` + null parametresiz deyim, `IN`/`NOT IN` + dizi liste bağlar.
        [$operator, $value, $donus] = $this->normalizeCondition($column, $operator, $value, $argCount, 'OR WHERE');

        $baslangic = count($this->where);

        if ($donus === self::DONUS_NULL_TEST) {
            $this->where[] = $this->wrapColumn($column) . ' ' . $operator . ' NULL';
            $this->whereOr[$baslangic] = true;
            return $this;
        }

        if ($donus === self::DONUS_IN_LIST) {
            $son = strtoupper($operator) === 'NOT IN'
                ? $this->whereNotIn((string) $column, (array) $value)
                : $this->whereIn((string) $column, (array) $value);
            if (count($this->where) > $baslangic) {
                $this->whereOr[$baslangic] = true;
            }
            return $son;
        }

        $paramKey = 'p' . count($this->params);
        $wrappedColumn = $this->wrapColumn($column);
        $this->where[] = "{$wrappedColumn} {$operator} :{$paramKey}";
        $this->whereOr[$baslangic] = true;
        $this->params[$paramKey] = $value;

        return $this;
    }

    public function whereIn(string $column, array $values): static
    {
        if (empty($values)) return $this;
        
        $keys = [];
        foreach ($values as $val) {
            $k = 'p' . count($this->params);
            $keys[] = ":{$k}";
            $this->params[$k] = $val;
        }
        
        $wrappedColumn = $this->wrapColumn($column);
        $this->where[] = "{$wrappedColumn} IN (" . implode(',', $keys) . ")";
        return $this;
    }

    public function whereNotIn(string $column, array $values): static
    {
        if (empty($values)) return $this;
        
        $keys = [];
        foreach ($values as $val) {
            $k = 'p' . count($this->params);
            $keys[] = ":{$k}";
            $this->params[$k] = $val;
        }
        
        $wrappedColumn = $this->wrapColumn($column);
        $this->where[] = "{$wrappedColumn} NOT IN (" . implode(',', $keys) . ")";
        return $this;
    }

    public function whereNull(string $column): static
    {
        $wrappedColumn = $this->wrapColumn($column);
        $this->where[] = "{$wrappedColumn} IS NULL";
        return $this;
    }

    public function orWhereNull(string $column): static
    {
        $wrappedColumn = $this->wrapColumn($column);
        $this->where[] = "{$wrappedColumn} IS NULL";
        $this->whereOr[array_key_last($this->where)] = true;
        return $this;
    }

    public function whereNotNull(string $column): static
    {
        $wrappedColumn = $this->wrapColumn($column);
        $this->where[] = "{$wrappedColumn} IS NOT NULL";
        return $this;
    }

    public function orWhereNotNull(string $column): static
    {
        $wrappedColumn = $this->wrapColumn($column);
        $this->where[] = "{$wrappedColumn} IS NOT NULL";
        $this->whereOr[array_key_last($this->where)] = true;
        return $this;
    }

    /**
     * [D-17] KONUMSAL `?` YER TUTUCULARI ADLANDIRILIR.
     *
     * Önceden `array_merge($this->params, $params)` ile ham dizi parametreler
     * birleştiriliyordu; derlenen SQL'de konumsal `?` ile adlandırılmış `:pN`
     * placeholder'ları aynı statement'ta karışıyor ve PDO
     * `SQLSTATE[HY093] Invalid parameter number: mixed named and positional`
     * veriyordu (D-02/D-35 ile aynı kök neden — sayaçların WHERE ile birleşmesi).
     *
     * Artık SQL'deki her `?` sırayla `:rw{n}` ADLANDIRILMIŞ parametreye çevrilir
     * ve değerler `$this->params` içine konur. Değer sırası ve SQL anlamı
     * BİREBİR korunur; `?` içermeyen SQL'de davranış eskisi gibi (parametreler
     * anahtarlarıyla gelir, örn. `:ad` → `['ad' => ...]`).
     */
    public function whereRaw(string $sql, array $params = []): static
    {
        $this->where[] = $this->bindRawParams($sql, $params);
        return $this;
    }

    /** [D-17] `whereRaw()`/`orWhereRaw()` ortak konumsal→adlı parametre bağlayıcısı. */
    protected function bindRawParams(string $sql, array $params): string
    {
        if (empty($params)) {
            return $sql;
        }
        if (!str_contains($sql, '?')) {
            // Adlandırılmış yer tutucu kullanımı (örn. `:ad`) — eski yol.
            $this->params = array_merge($this->params, $params);
            return $sql;
        }

        $sayac = 0;
        foreach (array_values($params) as $deger) {
            $anahtar = 'rw' . ($sayac++);
            $sql = preg_replace('/\?/', ":{$anahtar}", $sql, 1);
            $this->params[$anahtar] = $deger;
        }

        return $sql;
    }

    /**
     * [D-09] OR öneki koşul METNİNDE değil `$whereOr` bayrağında taşınır.
     * Önceden burada `"OR " . $sql` yazılıyordu ve derleyici koşulun metninden
     * "OR " öneki seziyordu; önek artık konuma göre konduğu için aynı önek
     * iki kez yazılır ve `OR OR ...` üretilirdi (canlı 500: `IS NULL OR OR ...`).
     */
    public function orWhereRaw(string $sql, array $params = []): static
    {
        $this->where[] = $this->bindRawParams($sql, $params); // [D-17]
        $this->whereOr[array_key_last($this->where)] = true;
        return $this;
    }

    /**
     * Internal SQL generator for WHERE clauses 🎻⚓
     *
     * [D-09] `orWhere()` artık koşulu "OR" öneksiz yazar; önek BURADA,
     * konuma göre konur: ilk koşulda önek yoktur (aksi hâlde `WHERE OR ...`),
     * sonraki koşullarda `OR ` veya `AND ` vardır.
     */
    protected function buildWhere(): string
    {
        return " WHERE " . $this->buildWhereOnly();
    }

    /**
     * Internal SQL generator for nested queries without the WHERE keyword 🧬🛰️
     */
    protected function buildWhereOnly(): string
    {
        $sql = "";
        foreach ($this->where as $i => $cond) {
            // [D-09] OR öneki bayrakta taşınır; metinde kalan eski "OR "
            // önekleri de temizlenir, böylece "OR OR" hiçbir yoldan oluşamaz.
            $orMu = !empty($this->whereOr[$i]);
            if (preg_match('/^(\s*)OR\s+/i', $cond, $m)) {
                $cond = substr($cond, strlen($m[0]));
                $orMu = true;
            }
            if ($i > 0) {
                $sql .= ($orMu ? " OR " : " AND ");
            }
            $sql .= $cond . " ";
        }
        return trim($sql);
    }

    /**
     * Fluent Conditional Builder 🧬🛰️
     */
    public function when($condition, callable $callback, ?callable $default = null): static
    {
        if ($condition) {
            $callback($this, $condition);
        } elseif ($default) {
            $default($this, $condition);
        }

        return $this;
    }
}
