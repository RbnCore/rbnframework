<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Base\Services\Traits\Provider\Engine;

/**
 * CrudProviderTrait - Strategic CRUD Automation for Providers 🛰️⚙️
 * 
 * RBN 3.5: Bridges the Provider to the Model's Persist and Read traits.
 * Eliminates repetitive code in strategic hub providers.
 */
trait CrudProviderTrait
{
    /**
     * The primary model target for this provider. 🛰️🎡
     * RBN 3.5: Renamed to targetModel for discovery symmetry.
     */
    protected $targetModel = null;

    /**
     * Standard Save (Insert or Update) 💾
     */
    public function save(array $data): bool|int
    {
        $model = $this->component('model');
        if (!$model)
            return false;

        $res = $model->save($data);

        // 🎼 RBN 3.5: [AUTONOMOUS INSERT ID GUARANTEE] 🏛️⚓
        // Return real Insert ID on creation for seamless downstream processing.
        if (empty($data['id']) && method_exists($model, 'getLastInsertId')) {
            $lastId = (int) $model->getLastInsertId();
            if ($lastId > 0) {
                return $lastId;
            }
        }

        return $res;
    }

    /**
     * Standard Create (Explicit Insert) 📦
     */
    public function create(array $data): int|bool
    {
        $model = $this->component('model');
        if (!$model)
            return false;

        return $model->create($data);
    }

    /**
     * Standard Update (Explicit Update by ID) ⚙️
     *
     * 🛡️ D-37 (geriye uyum): bu metot **AYNEN korunur**; 19 projede
     * `$provider->update($id, $data)` doğrudan çağrılıyor ve hiçbir imza
     * değiştirilmedi. Servis katmanındaki `CrudServiceTrait::update(array $data)`
     * ile aynı ada sahip olması D-37'nin ölçtüğü ÇELİŞKİnin kaynağıdır.
     *
     * YENİ kod için anlamı açık ad: `updateById()`.
     */
    public function update(int $id, array $data): bool
    {
        $model = $this->component('model');
        if (!$model)
            return false;

        return $model->update($id, $data);
    }

    /**
     * [D-37] BELİRSİZ `update` ADININ AÇIK KARŞILIĞI — `updateById()` 🎯⚓
     *
     * Sorun (ölçüldü): `CrudServiceTrait::update(array $data)` tek array ister,
     * `CrudProviderTrait::update(int $id, array $data)` ise iki argüman ister.
     * Aynı ad, iki katmanda iki FARKLI SÖZLEŞME. Servis tek array gönderirse
     * sağlayıcıya yanlış konumda gider (D-37 3 numaralı tespit).
     *
     * Çözüm (geriye uyumlu): sağlayıcıya **yeni, anlamı açık bir ad** verilir.
     * Bu metot yalnız YENİ bir giriştir; mevcut `update()` **SİLİNMEDİ** ve
     * **İMZASI DEĞİŞMEDİ** — 19 projedeki 10 doğrudan çağrı hiçbir değişiklik
     * olmadan çalışmaya devam eder (D37-20 kilidi).
     *
     * Varsayılan uygulama mevcut `update()`e **DELEGE EDER**. Bu bilinçlidir:
     * sağlayıcı alt sınıfları `update()`u kendi ezmeleri halinde yeni ad onu
     * yine çağırır (D37-18) — yani 19 projede hiçbir şey değişmez.
     *
     * @param int   $id   Hedef kaydın birincil anahtarı.
     * @param array $data  Yazılacak alanlar.
     */
    public function updateById(int $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    /**
     * Standard Destroy (Delete by ID) 🗑️
     */
    public function destroy(int $id): bool
    {
        $model = $this->component('model');
        if (!$model)
            return false;

        return (bool) $model->destroy($id);
    }

    /**
     * [B-06 / FW-ALTYAPI-1] BELİRSİZ `toggleStatus` ADININ AÇIK KARŞILIĞI 🎯⚓
     *
     * Sorun (ölçüldü): `toggleStatus` ÜÇ katmanda ÜÇ farklı sözleşme taşıyor:
     *   - `CrudModelTrait::toggleStatus($id, string $field)`  → 2. parametre ALAN ADI
     *   - `CrudProviderTrait::toggleStatus(int $id, string $field)` → 2. parametre ALAN ADI
     *   - `CrudServiceTrait::toggleStatus(?int $id, $status, $field)` → 2. parametre DEĞER
     * Aynı ad, iki katmanda iki FARKLI ANLAM. Servis katmanındaki istenen
     * değer, sağlayıcıya giderken **düşüyordu** (`toggleStatusCompat` bunu
     * saatlik logluyordu — D-37'in açık kalan maddesi).
     *
     * Çözüm (imza daraltmadan): burada **anlamı açık bir ad** var. `$status`
     * boş/`null` ise oku-değiştir-yaz yapılır (ESKİ `toggleStatus()` ile
     * BİREBİR aynı); doluysa **istenen değer birebir yazılır**.
     *
     * @param int         $id     Hedef kaydın birincil anahtarı.
     * @param string      $field  Hedef durum alanı.
     * @param bool|null   $status İstenen değer; `null` = "değiştir" (toggle).
     */
    public function setStatusById(int $id, string $field, ?bool $status = null): bool
    {
        $model = $this->component('model');
        if (!$model)
            return false;

        if ($status === null) {
            // Oku-degistir-yaz: eski yolla BIREBIR ayni.
            return (bool) $model->toggleStatus($id, $field);
        }

        $pk = method_exists($model, 'getPrimaryKey') ? $model->getPrimaryKey() : 'id';

        $builder = $model->query()->where($pk, $id);
        if (method_exists($builder, 'updateAffected')) {
            return $builder->updateAffected([$field => $status ? 1 : 0]) > 0;
        }

        return (bool) $builder->update([$field => $status ? 1 : 0]);
    }

    /**
     * Standard Toggle Status (is_active switch) 🔄
     *
     * @deprecated [B-06] Belirsiz ad. Yeni kod `setStatusById()` kullanmalıdır.
     *             Bu metot **SİLİNMEDİ** ve **İMZASI DEĞİŞMEDİ** —
     *             `PolicyProvider`/`SettingsRepository` gibi alt sınıflar bu
     *             imzayı birebir kullanıyor (PHP LSP: daraltma yapılamaz).
     *             Kullanımı saatlik `LogThrottle` ile görünür.
     */
    public function toggleStatus(int $id, string $field = 'is_active'): bool
    {
        $this->logDeprecatedToggleStatus(__FUNCTION__);

        return $this->setStatusById($id, $field, null);
    }

    /**
     * [B-06] Eski ada yapılan çağrının görünürlük satırı 📊
     *
     * `LogThrottle::once()` saatlik + süreçler arası kapıdır: bir panel
     * ekranı yüzlerce kez çağırsa bile günlüğe saatte bir satır düşer.
     * Değer/PII YOK; yalnız sınıf adı (Anayasa §9).
     */
    private function logDeprecatedToggleStatus(string $metot): void
    {
        try {
            if (!\Rbn\Framework\Core\Support\Bridges\Helpers\Library\LogThrottle::once(
                'b06-deprecated-toggle-status:' . static::class
            )) {
                return;
            }
        } catch (\Throwable $e) {
            // Ölçüm katmanı kararı bozamaz.
        }

        error_log('[RBN] B-06: ' . static::class . '::' . $metot . '() kullanildi; '
            . 'belirsiz addir. Yeni kod setStatusById($id, $field, $status) kullanmali '
            . '(null = degistir, deger = yaz).');
    }

    /**
     * Atomic Increment 🆙
     */
    public function increment(int $id, string $column, int $amount = 1)
    {
        $model = $this->component('model');
        return $model ? $model->increment($id, $column, $amount) : false;
    }

    /**
     * Atomic Decrement 🔽
     */
    public function decrement(int $id, string $column, int $amount = 1)
    {
        $model = $this->component('model');
        return $model ? $model->decrement($id, $column, $amount) : false;
    }
}
