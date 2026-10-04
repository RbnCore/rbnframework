<?php

namespace Rbn\Framework\Core\Database\Engine\Traits\Query;

/**
 * SelectionTrait - The Query Configuration Engine 🏹🛰️⚓
 * 
 * RBN 3.0: Powers selection, ordering, grouping and limit clauses.
 */
trait SelectionTrait
{
    /**
     * Set the columns to select 🏹
     */
    public function select(string $columns = '*'): static
    {
        $this->select = $columns;
        return $this;
    }

    /**
     * Add relations to eager load 🧬
     */
    public function with($relations): static
    {
        if (is_string($relations)) {
            $relations = func_get_args();
        }
        $this->eagerLoads = array_merge($this->eagerLoads, $relations);
        return $this;
    }

    /**
     * Get the requested relations for this builder 🛰️
     */
    public function getEagerLoads(): array
    {
        return $this->eagerLoads;
    }

    /**
     * Add an ORDER BY clause 📉
     *
     * [D-05] YON BEYAZ LISTESI: `$direction` daha once dogrulanmadan SQL'e
     * dogrudan birlestiriliyordu; `orderBy('n','ASC; DROP TABLE t')` ikinci
     * ifadeyi MySQL'e ulasirdi. Artik yalniz `ASC` / `DESC` kabul edilir
     * (buyuk/kucuk harf duyarsiz, bastaki/sondaki bosluk kirpilir). Baska bir
     * deger `InvalidArgumentException` firlatir — **bu kırıcı bir davranış
     * değişikliğidir**, kasitlidir (UPGRADING.md).
     *
     * Sutun adi [D-06] ile backtick kacisli gelir (`SqlHelperTrait::wrapColumn`).
     * Ham ifade gerekiyorsa `orderByRaw()` vardir.
     *
     * @throws \InvalidArgumentException Yön beyaz listede değilse.
     */
    public function orderBy(string $column, string $direction = 'ASC'): static
    {
        $yon = strtoupper(trim($direction));
        if ($yon !== 'ASC' && $yon !== 'DESC') {
            throw new \InvalidArgumentException("Invalid ORDER BY direction: {$direction} (allowed: ASC, DESC)");
        }

        $wrappedColumn = $this->wrapColumn($column);
        $this->orderBy = $this->orderBy ? "{$this->orderBy}, {$wrappedColumn} {$yon}" : "{$wrappedColumn} {$yon}";
        return $this;
    }

    public function orderByRaw(string $sql): static
    {
        $this->orderBy = $this->orderBy ? "{$this->orderBy}, {$sql}" : $sql;
        return $this;
    }

    /**
     * Add a GROUP BY clause 📦
     */
    public function groupBy(string $column): static
    {
        $wrappedColumn = $this->wrapColumn($column);
        $this->groupBy = $this->groupBy ? "{$this->groupBy}, {$wrappedColumn}" : $wrappedColumn;
        return $this;
    }

    /**
     * Add a HAVING clause 🔍
     *
     * [D-03] BU METOT HIC BIR TRAIT'TE TANIMLI DEGILDI. `$having` alani
     * tasiniyor, `toSql()` " HAVING {$this->having}" yaziyor, ve
     * `QueryModelTrait::having()` bu metodu cagiriyordu -> calisma aninda
     * `Error: Call to undefined method`. Yani ya olu kod ya da eksik metot;
     * kapsam taramasi sonucu: framework + projects + domains altinda
     * `having(` CAGIRISI **0** (yalniz bu proxy vardir). Burada metot
     * EKLENIYOR: `$having` alani zaten tasindigi ve `toSql()` zaten
     * yazdigi icin bu, olu kodu calisir hale getirir.
     *
     * `$condition` SQL aggregator ifadesidir (`SUM(n) > 40`) — dogal olarak
     * ham metindir; degerler `$params` ile BIND edilir, interpolasyon
     * kullanilmaz. Ham metin gerekiyorsa `havingRaw()` vardir.
     *
     * @param array $params `?` konumsal yer tutucular icin degerler.
     */
    public function having(string $condition, array $params = []): static
    {
        $kosul = $this->bindAggregateParams($condition, $params);
        $this->having = $this->having ? "{$this->having} AND {$kosul}" : $kosul;
        return $this;
    }

    /**
     * [D-03] `having()` icin konumsal `?` yer tutucularini adlandirilmis
     * parametreye cevirir. Boylece HAVING, ayni statement'taki `:pN`
     * WHERE parametreleriyle KARIŞMAZ (konumsal + adli karisimi PDO'da
     * `SQLSTATE[HY093]` verir — D-02 ile ayni kok neden).
     */
    protected function bindAggregateParams(string $condition, array $params): string
    {
        if (empty($params)) {
            return $condition;
        }
        if (!str_contains($condition, '?')) {
            throw new \InvalidArgumentException('having(): params given but condition has no ? placeholder');
        }
        foreach ($params as $deger) {
            $anahtar = 'h' . count($this->params);
            $condition = preg_replace('/\?/', ":{$anahtar}", $condition, 1);
            $this->params[$anahtar] = $deger;
        }
        return $condition;
    }

    /**
     * Set the query LIMIT 🚪
     */
    public function limit(int $limit): static
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Set the query OFFSET 🛤️
     */
    public function offset(int $offset): static
    {
        $this->offset = $offset;
        return $this;
    }
}
