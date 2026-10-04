<?php

namespace Rbn\Framework\Core\Database\Engine\Traits\Query;

/**
 * AggregateTrait - The Mathematical Query Engine 📊🛰️⚓
 */
trait AggregateTrait
{
    /**
     * Get the count of records for the query 📊
     */
    public function count(): int
    {
        $result = $this->aggregateQuery('COUNT(*) as aggregate');
        return (int)($result['aggregate'] ?? 0);
    }

    /**
     * [D-10] Aggregate sorgusunu builder'ın KENDİ KOPYASI üzerinde çalıştırır.
     *
     * NEDEN KOPYA: `count()/sum()/avg()` gövdesi `$this->select` yazıyordu;
     * `first()` de `$this->limit(1)` yazıyordu. İkisi de KALICI olduğu için
     * aynı builder ikinci kez kullanıldığında yanlış SQL çalışıyordu
     * (ölçüldü: `count()` sonrası aynı builder ile `get()` → 1 satır
     * `[{"aggregate":3}]` beklenen 3 gerçek satır yerine).
     *
     * Kopyalama `clone` ile yapılır: builder'ın tüm durumu skaler/array
     * alanlardan oluşur (tablo, where, params, joins...), derin kopya gerekmez;
     * `Database` bağlantı nesnesi paylaşılır (paylaşılması DAHA DOĞRU: aynı
     * bağlantı üzerinde transaction görünürlüğü bozulmaz).
     */
    protected function aggregateQuery(string $selectSql): mixed
    {
        $kopya = clone $this;
        $kopya->select = $selectSql;
        // Aggregate tek satır döner: çağrının limit'i (varsa) aggregate için
        // anlamsız (SUM/COUNT tek satır) ama OFFSET varsa sonucu bozabilirdi;
        // bu yüzden aggregate kopyasında limit/offset sıfırlanır.
        $kopya->limit = 1;
        $kopya->offset = null;
        return $kopya->first();
    }

    /**
     * Calculate the sum of a column 📊
     *
     * [D-08] Kolon adi [D-06] ile kacisli degil, **dogrulanarak** gelir:
     * `wrapColumn()` icinde `(` veya ` AS ` varsa ifadeyi OLDUGU GIBI
     * dondurur (HAM yol), bu yuzden aggregate icin yeterli degildir.
     * Burada yalniz gercek tanimlayici kabul edilir.
     */
    public function sum(string $column): float
    {
        $result = $this->aggregateQuery("SUM(" . $this->wrapAggregateColumn($column) . ") as aggregate");
        return (float)($result['aggregate'] ?? 0);
    }

    /**
     * Calculate the average of a column 📊
     */
    public function avg(string $column): float
    {
        $result = $this->aggregateQuery("AVG(" . $this->wrapAggregateColumn($column) . ") as aggregate");
        return (float)($result['aggregate'] ?? 0);
    }

    /**
     * [D-08] Aggregate kolon adini dogrular ve backtick ile sarar.
     *
     * NEDEN DOGRULAMA, NEDEN KACIS? `SUM(...)` ZATEN bir fonksiyon cagrisi
     * oldugu icin icerigi ifade olarak yorumlanir; backtick kacisi tek
     * basina yetmez (`SUM(`n) as agg` ...`)` yine kirilir). Bu yuzden
     * yalniz `sutun` ya da `tablo.sutun` bicimine izin verilir; ham ifade
     * gerekiyorsa `select('SUM(...) ...')->get()` ham yol olarak vardir.
     *
     * @throws \InvalidArgumentException Kolon tanimlayici degilse.
     */
    protected function wrapAggregateColumn(string $column): string
    {
        $parcalar = explode('.', $column);
        foreach ($parcalar as $p) {
            if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $p)) {
                throw new \InvalidArgumentException(
                    "Invalid aggregate column: {$column} (only plain column or table.column is allowed)"
                );
            }
        }
        return implode('.', array_map(fn($p) => "`{$p}`", $parcalar));
    }

    /**
     * Check if any records exist for the query 🛰️⚓
     */
    public function exists(): bool
    {
        return $this->count() > 0;
    }
}
