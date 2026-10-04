<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Base\Web\Traits\Controller\Engine;

use Exception;

/**
 * CrudControllerTrait - Standard CRUD actions for RBN 3.5 Controllers 💎🎡
 * 
 * RBN 3.5: Renamed to ControllerTrait to maintain layer-specific standards.
 * 
 * @property string|null $module
 * @property string|null $sub_module
 * @property string|null $entityName
 * @property string|null $modalView
 */
trait CrudControllerTrait
{
    /**
     * Standard Record Creation Endpoint 🆕
     */
    public function create(): void
    {
        // FW-ALTYAPI-2 / B (adım 2): `form([])` (kuralsız) → `rawAll()`.
        // T5 tasarımı gereği ham gövde doğrudur; asıl koruma `crudInput()`'ın
        // model beyaz listesidir. `rawAll()` aynı girdiyi verir (form'ın yaptığı
        // iç anahtar temizliği dahil) — kalkan zinciri `form()` ile aynıdır.
        $data = $this->crudInput($this->request->rawAll());
        $result = $this->activeService->create($data);

        // 🎼 RBN 3.5: [EXPLICIT DISPATCH] 🆕🛰️⚓
        $this->handleResult($result, null, null, 'create');
    }

    /**
     * Standard Record Update Endpoint 📝
     */
    public function update(): void
    {
        // FW-ALTYAPI-2 / B (adım 2): `form([])` (kuralsız) → `rawAll()`.
        $data = $this->crudInput($this->request->rawAll());
        $result = $this->activeService->update($data);

        // 🎼 RBN 3.5: [EXPLICIT DISPATCH] 📝🛰️⚓
        $this->handleResult($result, null, null, 'update');
    }

    /* ==========================================================================
       [ FW-BASE-3 / T5 ] CRUD GİRİŞ KAPISI — model beyaz listesine bağlı 🛡️
       ========================================================================== */

    /**
     * Ham istek verisini MODEL BEYAZ LİSTESİNE bağlar 🎯
     *
     * T5'in giriş kapısı. `request->rawAll()` kuralsız **ham** gövdeyi
     * döndürür (CSRF/iç anahtarlar düşülmüş haliyle) ve bu veri doğrudan
     * servise/modele akıtılırdı. Burada veri, hedef modelin `$fillable`
     * listesine göre BUDANIR.
     *
     * GERİ UYUMLULUK KİLİDİ (T1 kuralı, aynen):
     *   - hedef model çözülemiyorsa        -> veri DOKUNULMAZ
     *   - model `$fillable` TANIMLAMIYORSA -> veri DOKUNULMAZ
     * Yani beyaz listesi olmayan bir modelin davranışı bu değişiklikle
     * BİREBİR AYNI kalır.
     *
     * @param array $data `request->rawAll()` / `request->form(...)` çıktısı.
     * @return array Beyaz listeye göre budanmış veri.
     */
    protected function crudInput(array $data): array
    {
        $model = $this->crudTargetModel();
        if (!is_object($model) || !method_exists($model, 'getFillableFields')) {
            return $data;
        }

        $fillable = $model->getFillableFields();
        if ($fillable === []) {
            return $data;   // beyaz liste tanımlı değil → davranış değişmez
        }

        // Birincil anahtar daima geçer: update hedefini taşır (ve "satırı başka
        // kayda taşı" yolunu kapatır). Sunucu tarafından üretilir.
        if (method_exists($model, 'getPrimaryKey')) {
            $fillable[] = (string) $model->getPrimaryKey();
        }

        $beyaz = array_flip(array_values(array_unique($fillable)));
        $suzulen = array_intersect_key($data, $beyaz);
        $dusen = array_values(array_diff(array_keys($data), array_keys($beyaz)));

        if ($dusen !== []) {
            $this->logCrudInputDrops($model, $dusen);
        }

        return $suzulen;
    }

    /**
     * CRUD yazma hedefi olan modeli bulur (beyaz liste kaynağı) 🔎
     *
     * Sıra: kontrolörün uyanık modeli (`#[SubModule(model: …)]`) → aktif
     * servisin kendi modeli. İkisi de yoksa `null` döner (kilit devrede).
     */
    protected function crudTargetModel(): ?object
    {
        $adaylar = [];

        if (isset($this->model) && is_object($this->model)) {
            $adaylar[] = $this->model;
        }

        $servis = $this->activeService ?? null;
        if (is_object($servis) && isset($servis->model) && is_object($servis->model)) {
            $adaylar[] = $servis->model;
        }

        foreach ($adaylar as $aday) {
            if (method_exists($aday, 'getFillableFields')) {
                return $aday;
            }
        }

        return null;
    }

    /**
     * Beyaz listeden düşen alanları ÖLÇER (log-only; yazma davranışına dokunmaz) 📜
     *
     * Log satırı **değer taşımaz** — yalnız model sınıfı ve alan ADLARI
     * (parola sızıntısı riski kapatılmıştır).
     *
     * @param string[] $fields Düşen alan adları.
     */
    protected function logCrudInputDrops(object $model, array $fields): void
    {
        try {
            $this->logs()->channel('security')->warning('CRUD_INPUT_FIELD_DROPPED', [
                'model'   => get_class($model),
                'fields'  => $fields,
                'source'  => 'CrudControllerTrait',
                'mode'    => 'fillable_whitelist',
            ]);
        } catch (\Throwable $e) {
            // Log yazılamazsa YAZMA ENGELLENMEZ.
        }
    }

    /**
     * Standard Record Deletion
     */
    public function delete($id): void
    {
        $id = is_numeric($id) ? (int) $id : $id;
        $service = $this->activeService;

        // 1. service->destroy($id) or service->delete($id) 
        if (method_exists($service, 'destroy')) {
            $result = $service->destroy($id);
        } else if (method_exists($service, 'delete')) {
            $result = $service->delete($id);
        } else {
            throw new Exception("Service for CrudControllerTrait must implement destroy() method.");
        }

        // 🎼 RBN 3.5: [EXPLICIT DISPATCH] 🗑️🛰️⚓
        $this->handleResult($result, null, null, 'delete');
    }

    /**
     * Standard Status Toggle (AJAX)
     */
    public function status(): void
    {
        $id = (int) $this->request->input('id');
        $value = $this->request->input('value') ?? $this->request->input('status');
        $status = ($value == '1' || $value === true || $value == 'true' || $value == 'on');

        $service = $this->activeService;

        // 🛡️ B-06 (imza politikası): önce ANLAMI AÇIK ad denenir; eski ad
        // yalnız CAĞIRICI olarak kalır (bulunmazsa `method_exists` koruması
        // eski altyapılı servisleri çalıştırır). İstenen DEĞER artık her iki
        // katmanda da birebir yazılır — `value`'yu "değiştir" diye okuyan
        // sağlayıcı yolu kapatıldı.
        if (method_exists($service, 'setStatus')) {
            $result = $service->setStatus($id, $status);
        } else if (method_exists($service, 'toggleStatus')) {
            $result = $service->toggleStatus($id, $status);
        } else if (method_exists($service, 'update')) {
            $result = $service->update(['id' => $id, 'is_active' => $status ? 1 : 0]);
        } else {
            throw new Exception("Service for CrudControllerTrait must implement setStatus()/toggleStatus() or update() method.");
        }

        // 🎼 RBN 3.5: [EXPLICIT DISPATCH] 🚥🛰️⚓
        $this->handleResult($result, null, false, 'status');
    }
}
