<?php

namespace Rbn\Framework\Core\Http\Engine\Traits\Request;

/**
 * InputTrait - The Data Collector 📥⚓
 */
trait InputTrait
{
    /**
     * Tüm input verilerini al (GET + POST + JSON) 🛰️⚡ (Memoized)
     */
    public function all(): array
    {
        if ($this->mergedInput === null) {
            $json = method_exists($this, 'getJsonData') ? $this->getJsonData() : ($this->json ?? []);
            $this->mergedInput = array_merge($this->get, $this->post, $json);
        }
        return $this->mergedInput;
    }

    /**
     * Ham gövdenin KURALLARLA TEMİZLENMİŞ hâli (korumasız çekirdek) 🧼
     *
     * FW-ALTYAPI-2 / B (adım 1). `form()` bugün `array_merge($this->all(), $validated)`
     * ile **tüm** `GET + POST + JSON` gövdesini döndürüyordu; kural dizisinde
     * olmayan alanlar da servise/modele geçebiliyordu. Bu metot, o karışımın
     * **yalnız `all()` kısmını** verir.
     *
     * KALAN YOK: method kontrolü, shield/CSRF ve hız sınırı **burada**
     * çalışmaz. Dışarıdan çağrılamaz (`protected`); güvenli giriş noktası
     * `ValidationTrait::rawAll()`'dır — o, `form()` ile **aynı** kalkan
     * zincirini çalıştırır.
     *
     * @param array $rules `project` alanının korunup korunmayacağını belirler
     *                     (`form()` ile aynı sözleşme; verilmezse `project` düşer).
     */
    protected function rawInput(array $rules = []): array
    {
        $data = $this->all();

        // 🛡️ Framework iç anahtarları servise/DB'ye sızmaz.
        unset($data['csrf_token'], $data['repeat_password'], $data['_method'], $data['submit']);

        if (!isset($rules['project'])) {
            unset($data['project']);
        }

        return $data;
    }

    /**
     * Spesifik bir değeri al (JSON önceliklı)
     */
    public function input(string $key, $default = null)
    {
        $all = $this->all();
        return $all[$key] ?? $default;
    }

    /**
     * Sadece GET parametrelerini al 🔎
     */
    public function query(?string $key = null, $default = null)
    {
        if (is_null($key)) return $this->get;
        return $this->get[$key] ?? $default;
    }

    /**
     * Değer var mı? ✅
     */
    public function has(string $key): bool
    {
        $all = $this->all();
        return isset($all[$key]);
    }

    /**
     * Değer var mı ve dolu mu? ✨
     */
    public function filled(string $key): bool
    {
        $value = $this->input($key);
        return !empty($value) && !is_null($value);
    }

    /**
     * Değer eksik mi? ❌
     */
    public function missing(string $key): bool
    {
        return !$this->has($key);
    }

    /**
     * Sadece belirli anahtarları al
     */
    public function only(array $keys): array
    {
        $all = $this->all();
        return array_intersect_key($all, array_flip($keys));
    }

    /**
     * Belirli anahtarlar hariç al
     */
    public function except(array $keys): array
    {
        $all = $this->all();
        foreach ($keys as $key) {
            unset($all[$key]);
        }
        return $all;
    }
}
