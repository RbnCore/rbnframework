<?php

namespace Rbn\Framework\Core\Database\Engine\Traits\Query;

/**
 * CrudTrait - The Data Modification Engine 🌋🛰️⚓
 * 
 * RBN 3.0: Powers all insert, update, and delete operations.
 */
trait CrudTrait
{
    /**
     * Insert a new record into the database 🧱
     */
    public function insert(array $data): int
    {
        $keys = implode(", ", array_map(fn($k) => "`{$k}`", array_keys($data)));
        $placeholders = ":" . implode(", :", array_keys($data));
        $sql = "INSERT INTO `{$this->table}` ({$keys}) VALUES ({$placeholders})";

        // [B-24] Yonlendirme KAPSAMDA: is bitince aktif baglanti geri alinir.
        return $this->onConnection(function () use ($sql, $data): int {
            $this->db->query($sql, $data);
            return (int) $this->db->getLastInsertId();
        });
    }

    /**
     * Insert multiple records in one go 🚀⚡
     *
     * [D-18] HETEROJEN SATIR NORMALİZASYONU.
     *
     * Önceden kolon listesi YALNIZCA ilk satırdan alınıyordu; sonraki satırlarda
     * farklı anahtar kümesi sessizce bozuluyordu:
     *   - eksik anahtar → değer hiç yazılmıyor (statement geçersiz veya yanlış),
     *   - fazladan anahtar → `PDOException 21S01 Column count doesn't match
     *     value count at row N` (tüm toplu iş kayboluyordu).
     *
     * Artık kolon listesi TÜM satırların birleşimidir (ilk görülme sırası korunur)
     * ve eksik alanlar BAĞLANAN `null` ile doldurulur. Böylece hiçbir satır
     * verisi kaybolmaz, hizalama bozulmaz ve `DEFAULT`/null kolon kısıtı gerekiyorsa
     * bu HAPLLA MySQL'den gelen açık hata olarak yansır (sessiz bozulma yok).
     */
    public function insertBatch(array $data): bool
    {
        if (empty($data)) return true;

        // Sütun birleşimi: ilk görülme sırası korunur.
        $keys = [];
        foreach ($data as $item) {
            if (!is_array($item)) {
                throw new \InvalidArgumentException('insertBatch(): every row must be an array.');
            }
            foreach (array_keys($item) as $k) {
                if (!in_array($k, $keys, true)) {
                    $keys[] = $k;
                }
            }
        }
        if ($keys === []) {
            throw new \InvalidArgumentException('insertBatch(): no columns found in the given rows.');
        }

        $columns = implode(", ", array_map(fn($k) => $this->wrapColumn((string) $k), $keys));

        $values = [];
        $params = [];
        $i = 0;
        foreach ($data as $item) {
            $placeholders = [];
            foreach ($keys as $k) {
                $paramKey = "b_{$i}_{$k}";
                $placeholders[] = ":{$paramKey}";
                // Eksik anahtar: null. Değer YOKSA `array_key_exists` ile ayrılır,
                // yoksa null değer de null'a çevrilir (aynı sonuç, kasıtsız yol yok).
                $params[$paramKey] = array_key_exists($k, $item) ? $item[$k] : null;
            }
            $values[] = "(" . implode(", ", $placeholders) . ")";
            $i++;
        }

        $sql = "INSERT INTO `{$this->table}` ({$columns}) VALUES " . implode(", ", $values);
        return (bool) $this->onConnection(fn(): mixed => $this->db->query($sql, $params));
    }

    /**
     * Update multiple records in one go (Ultra Fast) 🚀⚡
     *
     * [D-07] KIMLIK DEGERI ARTIK INTERPOLASYONLA YAZILMIYOR.
     * Eskiden `WHERE ... IN (" . implode(', ', array_map(fn($id) => is_numeric($id)
     * ? $id : "'{$id}'", $ids)) . ")"` yaziliyordu; tirnak icine gomulen
     * deger escape EDILMEDIGI icin `id = "1 OR 1=1"` girdisi sunucuya
     * oyle gidiyordu ve **kati olmayan `sql_mode`'da** MySQL bunu DOUBLE'a
     * cast edip `id = 1` satirini YANLISLIKLA guncelliyordu (sessiz veri
     * bozulmasi — olculdu). Artik her kimlik degeri ADLANDIRILMIS yer tutucuya
     * gider; PDO degeri parametre olarak baglar, sunucu hicbir cast yapmaz.
     *
     * Kolon adlari da [D-06] ile backtick kacisli gelir.
     */
    public function updateBatch(array $data, string $index = 'id'): bool
    {
        if (empty($data)) return true;

        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $index)) {
            throw new \InvalidArgumentException("Invalid batch index column: {$index}");
        }

        $cases = [];
        $idPlaceholders = [];
        $params = [];

        foreach ($data as $i => $row) {
            if (!array_key_exists($index, $row)) {
                throw new \InvalidArgumentException("updateBatch(): row {$i} has no index column '{$index}'");
            }
            $id = $row[$index];

            // YALNIZCA TAM SAYI KIMLIK: PDO degeri baglasa bile MySQL, tam sayi
            // sutuna metin karsilastirmasinda bagli degeri DOUBLE'a CAST EDER
            // ("1 OR 1=1" -> 1) ve kati olmayan sql_mode'da YANLIS SATIR yine
            // guncellenir. Bu yuzden cast'i kaynakta kesiyoruz: tam sayi olmayan
            // kimlik reddedilir (istisna) -> HICBIR SATIR yazilmaz.
            if (is_bool($id) || !(is_int($id) || (is_string($id) && preg_match('/^-?\d+$/', $id)))) {
                throw new \InvalidArgumentException(
                    "updateBatch(): index '{$index}' must be an integer, got " . gettype($id) . " (row {$i})"
                );
            }

            $idParamKey = "bid_{$i}";
            $idPlaceholders[] = ":{$idParamKey}";
            $params[$idParamKey] = $id;

            foreach ($row as $key => $val) {
                if ($key === $index) continue;
                $valParamKey = "v_{$i}_{$key}";

                $cases[$key][] = "WHEN `{$index}` = :{$idParamKey} THEN :{$valParamKey}";

                $params[$valParamKey] = $val;
            }
        }

        if (empty($cases)) {
            return true;
        }

        $sql = "UPDATE `{$this->table}` SET ";
        foreach ($cases as $column => $caseArray) {
            $sql .= $this->wrapColumn($column) . " = CASE " . implode(' ', $caseArray) . " END, ";
        }
        $sql = rtrim($sql, ', ') . " WHERE `{$index}` IN (" . implode(', ', $idPlaceholders) . ')';

        return (bool) $this->onConnection(fn(): mixed => $this->db->query($sql, $params));
    }

    /**
     * Update existing records in the database 🛰️
     */
    public function update(array $data): bool
    {
        // [FW-DB-1 / B-26] Artik ETKILENEN SATIR SAYISI uzerinden
        // calisiyor. Donus degeri yine bool'dur ve 0 satirda da `true` doner
        // (ESKI DAVRANIS KORUNUR: `update()` hicbir zaman `false` donmezdi);
        // yalnizca sayiyi almak isteyenler `updateAffected()` kullanir.
        $this->updateAffected($data);

        return true;
    }

    /**
     * [B-3] Tek bir `UPDATE` yapar ve ETKILENEN SATIR SAYISINI dondurur.
     *
     * NEDEN GEREKLI? `update()` yalniz `true` donuyordu, bu yuzden
     * "kosullu muhhurleme" (compare-and-set) yapmak mumkun degildi:
     * `WHERE ... AND used_at IS NULL` yazan bir UPDATE'in kac satiri degistirdigi
     * OLCULEMIYORDU. Tek-kullanimlik sozlesmesi (A0-6) ancak bu sayiyla
     * kapatilir: 0 satir -> baska bir istek tokendi, 1 satir -> biz kazandik.
     *
     * [B-26] WHERE ZORUNLULUGU: kosulsuz cagrildiginda BUTUN TABLOYU gunceller,
     * hata vermez ve sabit `true` donerdi — `delete()` ile ASIMETRIK koruma
     * olususturuyordu (delete() WHERE'siz istisna atiyor). Artik `update()` ve
     * `delete()` ayni sozlesmeyi paylasir: kosulsuz cagri istisna firlatir ve
     * HICBIR SATIR YAZILMAZ. Toplu guncelleme gercekten isteniyorsa cagiran
     * ACIK bir kosul yazmalidir (`->whereIn(pk, $ids)` gibi).
     *
     * @return int Etkilenen satir sayisi (0 = eslesen satir yoktu).
     */
    public function updateAffected(array $data): int
    {
        if (empty($this->where)) {
            throw new \Exception("Safety: UPDATE requires WHERE conditions. Use rawQuery() or an explicit where() for bulk updates.");
        }

        $set = implode(", ", array_map(fn($k) => "`{$k}` = :u_{$k}", array_keys($data)));
        $sql = "UPDATE `{$this->table}` SET {$set}" . $this->buildWhere();

        $updateParams = [];
        foreach ($data as $k => $v) {
            $updateParams["u_{$k}"] = $v;
        }

        $finalParams = array_merge($this->params, $updateParams);
        $stmt = $this->onConnection(fn(): mixed => $this->db->query($sql, $finalParams));

        return $stmt instanceof \PDOStatement ? $stmt->rowCount() : 0;
    }

    /**
     * Delete records from the database ⚠️
     */
    public function delete(): int
    {
        $sql = "DELETE FROM `{$this->table}`";
        if (empty($this->where)) {
            throw new \Exception("Safety: DELETE requires WHERE conditions. Use truncate() for empty table.");
        }

        $sql .= $this->buildWhere();
        $stmt = $this->onConnection(fn(): mixed => $this->db->query($sql, $this->params));
        return $stmt->rowCount();
    }

    /**
     * Increment a column's value 🚀
     *
     * [D-02] ASILAR:
     *  1) SET kismindaki miktar KONUMSAL `?` idi ve where parametreleri
     *     `array_values()` ile duzlestirilerek araya katiliyordu. SQL'de
     *     konumsal + isimli yer tutucu KARISTIRILINCA PDO `SQLSTATE[HY093]`
     *     veriyor ve sayac hicbir zaman degismiyordu (D-35: model katmani
     *     WHERE'yi dogru koydugu halde her sayac oluyordu).
     *     Artik miktar da ADLANDIRILMIS yer tutucu (`:inc_amount`) ile gider;
     *     SET/WHERE parametreleri ADLA eşleşir, sira karışmaz.
     *  2) WHERE'siz cagri TUM TABLOYU artiriyordu. `update()` ile ayni
     *     sozlesme: kosulsuz cagri istisna firlatir, hicbir satir yazilmaz.
     */
    public function increment(string $column, int $amount = 1): bool
    {
        return $this->shiftCounter($column, $amount, '+');
    }

    /**
     * Decrement a column's value 🛰️
     *
     * [D-02] `increment()` ile aynı parametre karıştırma ve WHERE zorunluluğu geçerlidir.
     */
    public function decrement(string $column, int $amount = 1): bool
    {
        return $this->shiftCounter($column, $amount, '-');
    }

    /**
     * [D-02] `increment()` / `decrement()` ortak çekirdeği.
     *
     * @param string $column  Sayac sutunu.
     * @param int    $amount  Degisim miktari.
     * @param string $isaret  '+' veya '-'.
     * @throws \Exception WHERE kosulu yoksa (toplu sayac guvenlik gecidi).
     * @throws \InvalidArgumentException Sutun bos veya gecersizse.
     */
    protected function shiftCounter(string $column, int $amount, string $isaret): bool
    {
        if (empty($this->where)) {
            throw new \Exception("Safety: {$isaret} COUNTER requires WHERE conditions. Use rawQuery() or an explicit where() for bulk updates.");
        }
        if ($column === '' || !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $column)) {
            throw new \InvalidArgumentException("Invalid counter column: {$column}");
        }

        $wrappedColumn = $this->wrapColumn($column);
        $params = $this->params;
        $params['counter_amount'] = $amount;
        $sql = "UPDATE `{$this->table}` SET {$wrappedColumn} = {$wrappedColumn} {$isaret} :counter_amount"
            . $this->buildWhere();

        return (bool) $this->onConnection(fn(): mixed => $this->db->query($sql, $params));
    }

    /**
     * Empty the entire table 🌋
     */
    public function truncate(): bool
    {
        $sql = "TRUNCATE TABLE `{$this->table}`";
        return (bool) $this->onConnection(fn(): mixed => $this->db->rawExecute($sql));
    }
}
