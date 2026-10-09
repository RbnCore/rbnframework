<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Base\Data\Traits\Model\Engine;

/**
 * CrudModelTrait - Model Mutation & Persistence 🖊️🛰️
 */
trait CrudModelTrait
{
    /**
     * Store new record 📦
     */
    public function create(array $data): int|bool
    {
        // 📊 FW-BASE-1 T2: korumali alan LOG-ONLY sayaci (davranisma dokunmaz)
        if (method_exists($this, 'logProtectedFieldAttempt')) {
            $this->logProtectedFieldAttempt($data, 'create');
        }

        // 🛡️ FW-BASE-1 T1: beyaz/kara liste (bayrak `off` iken DAVRANIS DEGISMEZ)
        if (method_exists($this, 'applyMassAssignmentGuard')) {
            $data = $this->applyMassAssignmentGuard($data);
        }

        // 🎼 RBN Framework: [SMART PK GUARD] 🛡️⚓
        // If primary key exists and is empty, remove it to allow DB auto-increment.
        $pk = $this->getPrimaryKey();
        if (isset($data[$pk]) && (is_null($data[$pk]) || $data[$pk] === '')) {
            unset($data[$pk]);
        }

        // 🛡️ RBN Framework + FW-BASE-1 T3 (B-20): [MULTI-TENANT SCOPED INJECTION] 📦🔑
        // `project_key` daima SUNUCU BAĞLAMINDAN yazilir; kullanicinin gonderdigi
        // deger EZILIR. Tek istisna: `writeAsProject()` kacis kapisi.
        // Sira bilincli: kapsam enjekteyonu toplu atama suzgecinden SONRA gelir
        // (kiraci izolasyonu son soz soyleyen kuralladir).
        if (method_exists($this, 'applyProjectKeyScope')) {
            $data = $this->applyProjectKeyScope($data);
        }

        if (method_exists($this, 'prepareTimestampForStorage')) {
            $data = $this->prepareTimestampForStorage($data, true);
        }

        if (method_exists($this, 'prepareJsonForStorage')) {
            $data = $this->prepareJsonForStorage($data);
        }

        return $this->query()->insert($data);
    }

    /**
     * Update existing record ⚙️
     */
    public function update($id, array $data): bool
    {
        // 📊 FW-BASE-1 T2: korumali alan LOG-ONLY sayaci (davranisma dokunmaz)
        if (method_exists($this, 'logProtectedFieldAttempt')) {
            $this->logProtectedFieldAttempt($data, 'update');
        }

        // 🛡️ FW-BASE-1 T1: beyaz/kara liste (bayrak `off` iken DAVRANIS DEGISMEZ)
        if (method_exists($this, 'applyMassAssignmentGuard')) {
            $data = $this->applyMassAssignmentGuard($data);
        }

        // 🛡️ FW-BASE-1 T3 (B-20): kaydi baska kiraciya TASIMA yolu `update()`
        // uzerinden de kapatildi — `project_key` sunucu baglamindan yazilir.
        if (method_exists($this, 'applyProjectKeyScope')) {
            $data = $this->applyProjectKeyScope($data);
        }

        if (method_exists($this, 'prepareTimestampForStorage')) {
            $data = $this->prepareTimestampForStorage($data, false);
        }

        if (method_exists($this, 'prepareJsonForStorage')) {
            $data = $this->prepareJsonForStorage($data);
        }

        // 🛡️ FW-GECE-BASE B-55: "Kac satir gercekten degisti?" sorusu artik
        // `updateAffected()` ile olculuyor. Builder'in `update()` metodu geriye
        // uyum sozlesmesi geregi DAIMA `true` donuyordu; 0 satirlik bir UPDATE
        // "basarili" sayiliyordu.
        //
        // DIKKAT: MySQL `rowCount()` DEGISEN satiri sayar (PDO_MYSQL_ATTR_FOUND_ROWS
        // kapali). Degeri zaten ayni olan bir alan yazilirsa 0 doner. Bu bir
        // HATA degil; bu yuzden 0 durumunda hedefin varligi bir kez daha
        // dogrulanir: kayit yoksa FALSE, kayit var ama deger ayniysa TRUE.
        $builder = $this->query()->where($this->getPrimaryKey(), $id);

        if (!method_exists($builder, 'updateAffected')) {
            return (bool) $builder->update($data);
        }

        if ($builder->updateAffected($data) > 0) {
            return true;
        }

        return (bool) $this->query()->where($this->getPrimaryKey(), $id)->exists();
    }

    /**
     * Delete a single record 🗑️
     */
    public function destroy($id): bool
    {
        return (bool) $this->query()->where($this->getPrimaryKey(), $id)->delete();
    }

    /**
     * Standard Save (Smart Insert or Update) 🚀
     */
    public function save(array $data): bool|int
    {
        $id = $data[$this->getPrimaryKey()] ?? null;

        // B-22: `if ($id)` falsy idi — `'0'` ve `0` birincil anahtarları
        // VAR OLMAYAN kayıt sanılıp `create()` (INSERT) yoluna düşüyordu.
        // Artık yalnız id GERÇEKTEN yoksa/boşsa insert yapılır.
        $idDolu = $id !== null && (!is_string($id) || trim($id) !== '');

        if ($idDolu) {
            unset($data[$this->getPrimaryKey()]);
            return $this->update($id, $data);
        }
        return $this->create($data);
    }

    /**
     * Standard Toggle Status (Atomic Switch) 🔄
     */
    public function toggleStatus($id, string $field = 'is_active'): bool
    {
        $record = $this->query()->where($this->getPrimaryKey(), $id)->first();
        if (!$record)
            return false;

        $newStatus = !((bool) ($record[$field] ?? 0));
        return (bool) $this->update($id, [$field => (int) $newStatus]);
    }

    /**
     * Atomic increment 🆙
     */
    public function increment($id, string $column, int $amount = 1)
    {
        return $this->query()->where($this->getPrimaryKey(), $id)->increment($column, $amount);
    }

    /**
     * Atomic decrement 🔽
     */
    public function decrement($id, string $column, int $amount = 1)
    {
        return $this->query()->where($this->getPrimaryKey(), $id)->decrement($column, $amount);
    }
}
