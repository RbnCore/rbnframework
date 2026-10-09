<?php

namespace Rbn\Framework\Core\Base\Services\Traits\Service\Engine;

/**
 * CrudServiceTrait - Content Mutation ✍️
 */
trait CrudServiceTrait
{
    /**
     * KARAR 11 — uygulanmayan `toggleStatus` değeri logunun DETAY sınırı. 📊
     *
     * Bu çağrı sayısı, istenen değerin sağlayıcıda karşılığı olmadığı için
     * bir "hata" değil, kalıcı bir sözleşme farkıdır (D-37'nin açık kalan
     * maddesi). Bu yüzden HER çağrıda satır yazmak gürültüdür: tek bir panel
     * ekranı onlarca satır üretebilir.
     *
     * Kural: ilk N çağrı DETAYLI, sonrası YALNIZCA sayaç, kapanışta TEK özet.
     */
    public const TOGGLE_COMPAT_DETAIL_LIMIT = 3;

    /** Kapanış özet satırının işareti (sayma/ayıklama için sabit metin). */
    public const TOGGLE_STATUS_COMPAT_SUMMARY = 'TOGGLE_STATUS_COMPAT_SUMMARY';

    /**
     * KARAR 11 — süreç geneli sayaç (yalnız LOG ÖLÇÜMÜ).
     *
     * Bu trait'i kullanan TEK kök sınıf `BaseService` olduğu için statik
     * özellik tüm servisler arasında PAYLAŞILIR; yani sayaç gerçekten süreç
     * genelindedir (D37-32).
     *
     * @var int
     */
    protected static int $toggleStatusCompatCount = 0;

    /**
     * Kapanış özeti ZATEN kaydedildi mi? (aynı sebeple tek seferlik)
     *
     * @var bool
     */
    protected static bool $toggleStatusCompatSummaryRegistered = false;

    /**
     * [D-37] Katmanlar arası imza adaptörü — konum bazlı uyum 🧩
     *
     * ÇELİŞKİ (ölçüldü): `CrudServiceTrait::toggleStatus(?int $id, $status,
     * string $field)` → 2. parametre **değerdir** (`bool`), ama
     * `CrudProviderTrait::toggleStatus(int $id, string $field)` → 2. parametre
     * **alan adıdır** (`string`). Yani aynı pozisyon, farklı anlam.
     *
     * Düzeltme YALNIZCA bu çağrı noktasındadır: sağlayıcıya **alan adı**
     * gönderilir. Dış imza, dönüş tipi ve sağlayıcı imzası **aynen korunur**
     * (imza kırıcı değişiklik yapılmaz — sağlayıcı alt sınıfları imzayı
     * daralttığı için PHP LSP kuralı gereği gevşetilemez).
     *
     * Kalan AÇIK fark: sağlayıcı sözleşmesi yalnız "değiştir" (`toggle`)
     * anlar; çağıranın verdiği **değer** (`$status`) orada karşılıksızdır.
     * Bu durum sessizce yutulmaz, **SINIRLI** loglanır (karar 11):
     * ilk {@see TOGGLE_COMPAT_DETAIL_LIMIT} çağrı detaylı, sonrası sayaç,
     * kapanışta tek özet satırı.
     *
     * @param object $provider Çağrılacak sağlayıcı.
     * @param int    $id       Hedef kayıt birincil anahtarı.
     * @param mixed  $status   İstenen durum değeri (bool|null).
     * @param string $field    Hedef durum alanı.
     */
    private function toggleStatusCompat(object $provider, int $id, $status, string $field): bool
    {
        if ($status !== null) {
            $this->logToggleStatusCompat($status, $field);
        }

        // Sağlayıcı 2. parametresi ALAN ADI'dır — konum uyumu burada sağlanır.
        return (bool) $provider->toggleStatus($id, $field);
    }

    /**
     * KARAR 11 — "istenen değer uygulanmadı" logunu SINIRLA. 📊
     *
     * DAVRANIŞ DEĞİŞMEZ: burada yalnız sayaç artar ve log satırı yazılır.
     * Sağlayıcı çağrısı, alan adı ve dönüş değeri bu metottan ETKİLENMEZ.
     */
    private function logToggleStatusCompat($status, string $field): void
    {
        self::$toggleStatusCompatCount++;
        $no = self::$toggleStatusCompatCount;

        if ($no <= self::TOGGLE_COMPAT_DETAIL_LIMIT) {
            error_log('[RBN] toggleStatus adaptoru: saglayici yalnizca "degistir" '
                . 'semantiği destekliyor; istenen deger (' . var_export($status, true)
                . ') uygulanmadi, alan="' . $field . '". (detay ' . $no . '/'
                . self::TOGGLE_COMPAT_DETAIL_LIMIT . ')');
        }

        $this->registerToggleStatusCompatSummary();
    }

    /**
     * Kapanışta TEK özet satırı kaydet (yalnız sayaçta kalan çağrılar için). 📊
     *
     * `register_shutdown_function` yalnız BİR KEZ kaydedilir; aksi hâlde her
     * servis örneği kendi kaydını ekler ve kapanışta aynı özet N kez yazılır.
     */
    private function registerToggleStatusCompatSummary(): void
    {
        if (self::$toggleStatusCompatSummaryRegistered) {
            return;
        }
        self::$toggleStatusCompatSummaryRegistered = true;

        register_shutdown_function(static function (): void {
            $toplam = self::$toggleStatusCompatCount;
            if ($toplam <= self::TOGGLE_COMPAT_DETAIL_LIMIT) {
                return;   // yalnız 3 veya daha az: zaten hepsi detaylı
            }
            error_log('[RBN] ' . self::TOGGLE_STATUS_COMPAT_SUMMARY
                . ': toplam=' . $toplam
                . ' detayli=' . self::TOGGLE_COMPAT_DETAIL_LIMIT
                . ' sadece_sayac=' . ($toplam - self::TOGGLE_COMPAT_DETAIL_LIMIT)
                . ' (istenen deger hicbir projede uygulanmiyor)');
        });
    }
    /**
     * Create New Record
     */
    public function create(array $data): self
    {
        $result = $this->save(null, $data);
        $this->lastResult = (bool) $result;

        if ($this->lastResult) {
            $this->clearCache();
        }
        return $this;
    }

    /**
     * Update Existing Record
     */
    public function update(array $data): self
    {
        // 🛡️ B-54: `($this->targetId) ? ... : ...` truthiness kontrolüydü.
        // PHP'de `0` FALSY olduğu için `targetId === 0` iken kod sessizce
        // `$data['id']` üzerine düşüyor ve BAŞKA bir satırı güncelliyordu
        // (ölçüldü: targetId=0 + id=42 -> update(42)). Artık varlık `null`
        // kontrolüyle yapılır; `0` da geçersiz kimlik olarak aşağıdaki
        // `if (!$id)` korumasında reddedilir.
        $id = ($this->targetId !== null) ? $this->targetId : ($data['id'] ?? null);

        if (!$id) {
            $this->lastResult = false;
            return $this;
        }

        $result = $this->save($id, $data);
        $this->lastResult = (bool) $result;

        if ($this->lastResult) {
            $this->clearCache();
        }
        return $this;
    }

    /**
     * [D-37] BELİRSİZ `update` ADININ AÇIK KARŞILIĞI — `updateById()` 🎯⚓
     *
     * Bu metot **yeni** bir giriştir; `update(array $data)` AYNEN korunur, hiçbir
     * mevcut çağıran kırılmaz. Farkı: hedef kimlik AÇIKÇA verilir, dolayısıyla
     * hem model hem sağlayıcı yolunda "hangi satır" sorusu cevapsız kalmaz.
     *
     * Akış (mevcut hiçbir yol DEĞİŞTİRİLMEZ):
     *   1) model varsa → `$model->update($id, $data)`
     *   2) model yoksa ve sağlayıcı `updateById` sunuyorsa → sağlayıcı yeni ada
     *      gider (D-37 kararı 3: sağlayıcı tarafındaki belirsiz ad burada biter)
     *   3) sağlayıcı `updateById` sunmuyorsa → ESKİ `save($id, $data)` yoluna
     *      düşülür; böylece `CrudProviderTrait` KULLANMAYAN elle yazılmış
     *      sağlayıcılar da kırılmaz.
     *
     * NOT: `save()` yolu BILINCLI OLARAK değiştirilmedi — dört sağlayıcı
     * (`FrontendMenuProvider`, `PolicyProvider`, `WebhubProvider`,
     * `SyshubMaintenanceProvider`) `save()`u kendi eziyor; yolu değiştirmek
     * onların özel davranışını atlamak olurdu.
     *
     * @param int   $id   Hedef kaydın birincil anahtarı (0 ve altı geçersiz).
     * @param array $data Yazılacak alanlar.
     */
    public function updateById(int $id, array $data = []): self
    {
        // 🛡️ B-54 ile aynı sözleşme: `0` geçerli bir kimlik DEĞİLDİR.
        if ($id <= 0) {
            $this->lastResult = false;
            return $this;
        }

        try {
            $model = $this->component('model');

            if ($model && method_exists($model, 'update')) {
                $this->lastResult = (bool) $model->update($id, $data);
            } elseif (isset($this->provider) && method_exists($this->provider, 'updateById')) {
                $this->lastResult = (bool) $this->provider->updateById($id, $data);
            } else {
                // Sağlayıcı yeni adı bilmiyorsa mevcut yol AYNEN kullanılır.
                $this->lastResult = (bool) $this->save($id, $data);
            }

            if ($this->lastResult) {
                $this->clearCache();
            }
        } catch (\Exception $e) {
            $this->lastResult = false;
            $this->storage->logs()->channel('system')->error("Smart Service UpdateById Error: " . $e->getMessage());
        }

        return $this;
    }

    /**
     * Destroy Record
     */
    public function destroy(?int $id = null): self
    {
        $id = $id ?? $this->targetId;

        try {
            if (!$id) {
                throw new \Exception("Smart Service Destroy failed: ID not provided.");
            }

            // 🎯 RBN Framework: Strategic Delegation (Avoid triggering magic __get provider)
            $model = $this->component('model');
            if ($model) {
                $this->lastResult = (bool) $model->destroy($id);
            } elseif (isset($this->provider) && method_exists($this->provider, 'destroy')) {
                $this->lastResult = (bool) $this->provider->destroy($id);
            } else {
                throw new \Exception("Smart Service Destroy failed: Main model not found.");
            }

            if ($this->lastResult) {
                $this->clearCache();
            }
        } catch (\Exception $e) {
            $this->lastResult = false;
            $this->storage->logs()->channel('system')->error("Smart Service Destroy Error: " . $e->getMessage());
        }

        return $this;
    }

    /**
     * [B-06 / FW-ALTYAPI-1] BELİRSİZ `toggleStatus` ADININ AÇIK KARŞILIĞI 🎯⚓
     *
     * İki katman, aynı ad, iki farklı anlam:
     *   - servis: 2. parametre **DEĞER** (`bool|null`)
     *   - sağlayıcı: 2. parametre **ALAN ADI** (`string`)
     * Bu yüzden servis katmanındaki "istenen değer" sağlayıcıya giderken
     * düşüyordu. Yeni metot adı ne yaptığını söyler ve DEĞERİ taşır:
     *   - `$status === null` → "değiştir" (oku-değiştir-yaz; eski yolla aynı)
     *   - `$status` dolu    → istenen değer **birebir** yazılır
     *
     * Model yolu `toggleStatus()` ile BİREBİR AYNI gövdedir (B-55 `updateAffected`
     * ölçümü korunur). Sağlayıcı yolu önce `setStatusById()` dener; eski
     * sağlayıcılarda `toggleStatusCompat()` yedeği AYNEN çalışır.
     */
    public function setStatus(?int $id = null, $status = null, string $field = 'is_active'): self
    {
        $id = $id ?? $this->targetId;

        try {
            if (!$id) {
                throw new \Exception("Smart Service Toggle failed: ID not provided.");
            }

            // 🎯 RBN Framework: Strategic Delegation
            $model = $this->component('model');
            if ($model) {
                // 🛡️ FW-GECE-BASE B-55: birincil anahtar varsa o kullanilir
                // (sabit 'id' varsayimi kiriliyordu).
                $pk = method_exists($model, 'getPrimaryKey') ? $model->getPrimaryKey() : 'id';

                if ($status === null) {
                    // Oku-degistir-yaz: kayit YOKSA hicbir sey yazilmamali.
                    $record = $model->where($pk, $id)->first();
                    if (!$record) {
                        $this->lastResult = false;
                        $this->storage->logs()->channel('system')
                            ->warning("Smart Service Toggle: kayit bulunamadi (id=" . (int) $id . ')');
                        return $this;
                    }
                    $status = !(bool) ($record[$field] ?? 0);
                }

                // B-55: builder `update()` daima `true` donuyordu; burada
                // ETKILENEN SATIR sayisi olculur. DIKKAT: bilerek builder
                // uzerinden yaziliyor; `$model->update()` toplu atama
                // luzgecinden gectigi icin `is_active` dusebilir ve
                // "basarisiz toggle" yaratirdi.
                $builder = $model->query()->where($pk, $id);
                if (method_exists($builder, 'updateAffected')) {
                    $this->lastResult = $builder->updateAffected([$field => $status ? 1 : 0]) > 0;
                } else {
                    $this->lastResult = (bool) $builder->update([$field => $status ? 1 : 0]);
                }
            } elseif (isset($this->provider)
                && method_exists($this->provider, 'setStatusById')) {
                // 🛡️ B-06: yeni, anlamı açık ad. İstenen DEĞER artık düşmüyor.
                $this->lastResult = (bool) $this->provider->setStatusById(
                    (int) $id,
                    $field,
                    $status === null ? null : (bool) $status
                );
            } elseif (isset($this->provider) && method_exists($this->provider, 'toggleStatus')) {
                // 🛡️ D-37: eski hâli `$this->provider->toggleStatus($id)` idi ve
                // ALAN ADI (`$field`) sessizce düşüyordu. Adaptör konum uyumunu
                // kurar; dış imza ve dönüş tipi değişmez. (YENİ sağlayıcı
                // `setStatusById()` tanımlıysa bu dala hiç girilmez.)
                $this->lastResult = $this->toggleStatusCompat($this->provider, (int) $id, $status, $field);
            } else {
                throw new \Exception("Smart Service Toggle failed: Main model not found.");
            }

            if ($this->lastResult) {
                $this->clearCache();
            }
        } catch (\Exception $e) {
            $this->lastResult = false;
            $this->storage->logs()->channel('system')->error("Smart Service Toggle Error: " . $e->getMessage());
        }

        return $this;
    }

    /**
     * Toggle Status (AJAX Friendly)
     *
     * @deprecated [B-06] Belirsiz ad (2. parametre katmanlar arası DEĞER de
     *             ALAN ADI da olabiliyor). **SİLİNMEDİ, imzası DEĞİŞMEDİ**:
     *             `BaseServiceInterface` bu imzayı kilitler ve alt sınıflar
     *             (`ProductCategoryService`) bu adı kendilerine ezmış olabilir.
     *             Yeni çağrılar `setStatus()` kullanmalıdır; bu metot ona
     *             devreder ve kullanımı saatlik `LogThrottle` ile görünür.
     */
    public function toggleStatus(?int $id = null, $status = null, string $field = 'is_active'): self
    {
        $this->logDeprecatedToggleStatus(__FUNCTION__);

        return $this->setStatus($id, $status, $field);
    }

    /**
     * [B-06] Eski ada yapılan çağrının görünürlük satırı 📊
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

        // Anonim sinif adi NUL bayt tasir; error_log() orada keser.
        error_log('[RBN] B-06: ' . explode("\0", static::class, 2)[0] . '::' . $metot . '() kullanildi; '
            . 'belirsiz addir (2. parametre katmanlar arasinda hem DEĞER hem ALAN ADI). '
            . 'Yeni kod setStatus($id, $status, $field) kullanmali.');
    }

    /**
     * Core Persistence Logic
     *
     * 🛡️ D-37 (geriye uyum): İlk parametre `mixed` yapıldı.
     *
     * ÇELİŞKİ (ölçüldü): `CrudProviderTrait::save(array $data)` TEK array
     * ister; bu metot ise `save(?int $id, array $data)` ZORUNLU iki argüman
     * istiyordu. Tek argümanlı `save($data)` çağrısı `TypeError` veriyordu.
     * `WebhubService` bu yüzden aynı imzayı **elle** gevşetmişti (satır 60) —
     * yani uyum zaten istenmiş, sadece trait'e taşınmamıştı.
     *
     * Güvenlik: Bu GEVSETTİRMEDİR (PHP LSP yön B) ve `WebhubService`'in mevcut
     * imzasıyla **birebir aynı**dır; hiçbir alt sınıf DARALTTIĞI için kırılma
     * yoktur. Eski `save($id, $data)` ve `save(null, $data)` çağrıları biten
     * davranışıyla çalışmaya devam eder. Dizi tek argüman gelirse veri olarak
     * yorumlanır (`WebhubService` ile aynı uzlaşma).
     */
    public function save(mixed $id = null, array $data = [])
    {
        // 🛡️ D-37: Tek argüman dizi geldiyse normalizasyon (WebhubService ile aynı).
        if (is_array($id)) {
            $data = $id;
            $id = null;
        }
        $id = $id !== null ? (int) $id : null;

        try {
            // 🎯 RBN Framework: Strategic Delegation
            $model = $this->component('model');
            if ($model) {
                if ($id) {
                    $result = $model->update($id, $data);
                } else {
                    $result = $model->create($data);
                }

                // 🛡️ B-56: `save()` `lastResult`'a dokunmuyordu; oysa
                // `ActionServiceTrait` varsayılanı `true`. Dogrudan
                // `$service->save(...)` çağıran kod hata olsa bile
                // "başarılı" görüyordu. Kardeş metotlar (`destroy`,
                // `toggleStatus`, `truncate`) zaten güncelliyordu — asimetri
                // kapatıldı.
                // NOT: `create()`/`update()` save() sonrası zaten
                // `(bool)$result` yazıyor → SONUC BİREBİR AYNI.
                $this->lastResult = (bool) $result;

                return $result;
            }

            if (isset($this->provider) && method_exists($this->provider, 'save')) {
                if ($id)
                    $data['id'] = $id;
                $providerSonuc = $this->provider->save($data);
                $this->lastResult = (bool) $providerSonuc;
                return $providerSonuc;
            }

            throw new \Exception("Smart Service Save failed: Main model not found.");
        } catch (\Exception $e) {
            $this->lastResult = false;
            $this->storage->logs()->channel('system')->error("Smart Service Save Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Tabloyu Sıfırla (Truncate) 🧹 
     * High-performance data reset engine.
     */
    public function truncate(?string $modelName = null): self
    {
        try {
            // Get model (main or explicit)
            $model = $modelName ? (isset($this->rbn) ? $this->rbn->model($modelName) : $this->component('model')) : $this->component('model');

            if (!$model) {
                $this->lastResult = false;
                return $this;
            }

            $this->lastResult = (bool) $model->query()->truncate();
            if ($this->lastResult) {
                $this->clearCache();
            }
        } catch (\Exception $e) {
            $this->lastResult = false;
            $this->storage->logs()->channel('system')->error("Crud Service Error: " . $e->getMessage());
        }

        return $this;
    }
}
