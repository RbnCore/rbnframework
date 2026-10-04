<?php

namespace Rbn\Framework\Core\Http\Engine\Traits\Request;

use Rbn\Framework\Core\Base\Services\BaseService;
use Rbn\Framework\Core\Http\Validator;
use Rbn\Framework\Core\System\Config\Config;
use Rbn\Framework\Core\Support\Bridges\Helpers\Library\LogThrottle;

/**
 * ValidationTrait - The Security Shield 🛡️⚓
 * 
 * RBN 3.5: Masterpiece Standard (Shield Hub Integrated).
 * Centralized request validation logic with automated security guards.
 */
trait ValidationTrait
{
    /**
     * FW-ALTYAPI-2 / B (adım 5): `form()` girdi kipi bayrağı.
     *
     * `off|log|enforce`. VARSAYILAN `enforce`: `form()` yalnız doğrulanmış
     * alanları döndürür. `off` yalnız ACİL KAPAMA anahtarıdır.
     */
    public const FORM_INPUT_MODE_FLAG = 'security.form_input_mode';

    /** @var string Bayrak okunamazsa/bozuksa uygulanacak kip (fail-closed). */
    public const FORM_INPUT_MODE_VARSAYILAN = 'enforce';

    /** `log` kipinde düşen alanların kayıt kodu. */
    public const FORM_INPUT_UNVALIDATED_KODU = 'FORM_INPUT_UNVALIDATED_FIELD';

    /** `log` kipindeki kaydın saatlik throttle etiketi. */
    public const FORM_INPUT_LOG_ETIKETI = 'form-input-unvalidated';

    /**
     * RbnShield Güvenlik Kalkanını Aktifleştirir
     */
    public function shield(array $options = []): self
    {
        $this->shieldActive = true;
        $this->shieldOptions = $options;
        return $this;
    }

    /**
     * Request doğrulaması yap (Eğer shield aktifse önce güvenlik taraması yapar)
     */
    public function validate(array $rules, array $messages = []): array
    {
        // 1. SHIELD KONTROLÜ (Aktif edildiyse)
        if ($this->shieldActive) {
            $this->applyShieldConfigurations();
        }

        // 1.5. POST_MAX_SIZE KONTROLÜ
        if ($this->isPost() && empty($this->post) && empty($this->files) && ($this->server['CONTENT_LENGTH'] ?? 0) > 2048) {
            $limit = ini_get('post_max_size');

            // RBN 3.5: Masterpiece Global Shield Gateway 🛡️✨
            shield()->validation(['post_max_size' => 'Limit exceeded'], "Form verisi gönderilemedi. Gönderilen veri boyutu PHP limitini ({$limit}) aşmış olabilir.");
        }

        // 2. YAPISAL DOĞRULAMA (Validator)
        $validator = Validator::make($this->all(), $rules, $messages);

        if ($validator->fails()) {
            $this->handleFailedValidation($validator);
        }

        $validatedData = $validator->validated();

        // 3. CUSTOM DOĞRULAMA (afterValidate Kancası)
        if (method_exists($this, 'afterValidate')) {
            $hookResult = $this->afterValidate($validatedData);
            if (isset($hookResult['success']) && !$hookResult['success']) {
                $this->handleHookFailure($hookResult['message'] ?? 'Özel doğrulama hatası');
            }
        }

        return $validatedData;
    }

    /**
     * Kural TANIMLAMADAN ham gövdeyi al — yazma yolunun açık yolu 📦🛡️
     *
     * FW-ALTYAPI-2 / B (adım 2). `form([])` (kuralsız çağrı) bugün iki işi
     * birden yapıyordu: (a) kalkan zincirini çalıştırıyordu (method kontrolü +
     * CSRF + origin + bot + hız sınırı), (b) ham gövdeyi döndürüyordu.
     * Bu metot (b)'yi açık adla sunar ve **(a)'yı AYNEN korur** — koruma
     * zincirini atlamak bir güvenlik gerilemesi olurdu.
     *
     * KURALLA İLGİSİ YOK: alan doğrulaması yapılmaz; `form()`'un döndürdüğü ham
     * karışımın `all()` kısmıdır. Doğrulanmış alan isteyen çağrılar `form()`
     * kullanmalıdır.
     *
     * @param array $options `form()` ile aynı seçenekler (`csrf`, `method`, …).
     */
    public function rawAll(array $options = []): array
    {
        // 🛡️ Aynı kalkan zinciri: `form()`'un yaptığı method kontrolü + shield.
        $this->applyFormGuard($options);

        // Denetimler `validate()` içinde çalışır; `rawAll()` doğrulama yapmadığı
        // için `validate()`'a GİRMEZ — kalkan burada AÇIKÇA çalıştırılır.
        if ($this->shieldActive) {
            $this->applyShieldConfigurations();
        }

        return $this->rawInput();
    }

    /**
     * RBN Masterpiece "Form" - Security, Validation and Data Retrieval 🛡️
     *
     * FW-ALTYAPI-2 / B (adım 5) — `security.form_input_mode`:
     *   `enforce` (VARSAYILAN): yalnız doğrulanmış (kuralda tanımlı VE gönderilmiş)
     *                          alanları döndürür. Ham gövde sızmaz.
     *   `log`                 : bugünkü karışım döner, düşecek alan ADLARI
     *                          `FORM_INPUT_UNVALIDATED_FIELD` olarak kaydedilir.
     *   `off`                 : bugünkü davranış (acil kapama anahtarı).
     *
     * `form([])` + `enforce` -> `InvalidArgumentException`: kuralsız yazma
     * yolu kapatılır; açık ham yol `rawAll()`'dır (bkz. `rawAll()`).
     */
    public function form(array $rules, array $options = []): array
    {
        $this->applyFormGuard($options);

        $mod = $this->formInputMode();

        if ($mod === 'enforce' && $rules === []) {
            throw new \InvalidArgumentException(sprintf(
                '%s::form() kural dizisi BOŞ: doğrulanacak alan tanımlanmamış. '
                . 'Kuralsız (ham) yazma yolu kapalıdır — açık ham yol `rawAll()` '
                . 'metodudur. Acil eski davranış için `security.form_input_mode = off`.',
                static::class
            ));
        }

        $validated = $this->validate($rules);

        if ($mod === 'enforce') {
            $data = $validated;
        } else {
            // FW-ALTYAPI-2 / B (adım 1): ham gövde temizliği `rawInput()`'a taşındı.
            $data = array_merge($this->rawInput($rules), $validated);

            if ($mod === 'log') {
                $this->logUnvalidatedFormFields(array_keys(array_diff_key($data, $validated)));
            }
        }

        // 🧼 Otomatik Nullable / Boş Veri Temizleyici:
        // Eğer kuralda 'nullable' veya 'optional' varsa ve gelen değer boş string ("") ise null yap / veya temizle
        foreach ($rules as $field => $ruleset) {
            $ruleStr = is_array($ruleset) ? implode('|', $ruleset) : (string) $ruleset;
            if (str_contains($ruleStr, 'nullable') || str_contains($ruleStr, 'optional')) {
                if (isset($data[$field]) && is_string($data[$field]) && trim($data[$field]) === '') {
                    $data[$field] = null;
                }
            }
        }

        return $data;
    }

    /**
     * `security.form_input_mode` bayrağını normalize eder 🏷️
     *
     * `off|log|enforce` kabul eder; okunamayan/bozuk değer `enforce`'a düşer
     * (fail-closed: kapatılamayan bir bozuk ayar zorlamayı sessizce kaldırmasın).
     *
     * @return string `off`|`log`|`enforce`
     */
    protected function formInputMode(): string
    {
        try {
            $bayrak = Config::get(self::FORM_INPUT_MODE_FLAG, self::FORM_INPUT_MODE_VARSAYILAN);
        } catch (\Throwable $e) {
            return self::FORM_INPUT_MODE_VARSAYILAN;
        }

        if (!is_string($bayrak)) {
            return self::FORM_INPUT_MODE_VARSAYILAN;
        }

        $bayrak = strtolower(trim($bayrak));

        return in_array($bayrak, ['off', 'log', 'enforce'], true)
            ? $bayrak
            : self::FORM_INPUT_MODE_VARSAYILAN;
    }

    /**
     * Kural dışı gelen alan ADLARINI kaydeder (ölçüm; karar DEĞİŞMEZ) 📜
     *
     * DEĞER YAZILMAZ — yalnız alan adı. Parola/girdi sızıntısı kapatılmıştır.
     * `LogThrottle::once()` saatlik kapı kullanır: 162 isteklik duman koşusunda
     * bu satırların günlüğü doldurması engellenir (bkz. FW-LOG-GURULTU).
     *
     * @param string[] $alanlar Düşecek alan adları.
     */
    protected function logUnvalidatedFormFields(array $alanlar): void
    {
        if ($alanlar === []) {
            return;
        }

        try {
            if (!LogThrottle::once(self::FORM_INPUT_LOG_ETIKETI . ':' . static::class)) {
                return;
            }
            error_log(sprintf(
                '[RBN][%s] form(): %d alan kural dizisinde DEGIL ve dogrulanmis ciktiya girmeyecek. alanlar=%s',
                self::FORM_INPUT_UNVALIDATED_KODU,
                count($alanlar),
                implode(',', $alanlar)
            ));
        } catch (\Throwable $e) {
            // Kayıt yazılamazsa istek ASLA bozulmaz (ölçüm katmanı karar değiştirmez).
        }
    }

    /**
     * RBN Masterpiece "Filter" - Filter and validate GET/Query parameters safely 🛡️🔎
     */
    public function filter(array $rules, array $options = []): array
    {
        $defaults = [];
        $formattedRules = [];

        foreach ($rules as $key => $ruleOrValue) {
            if (is_int($key)) {
                // E.g. ['mode', 'status']
                $formattedRules[$ruleOrValue] = 'optional';
            } else {
                // E.g. ['mode' => 'topics', 'status' => '0', 'faqs' => [], 'suggestions' => []]
                $formattedRules[$key] = 'optional';
                $defaults[$key] = $ruleOrValue;
            }
        }

        $options['method'] = $options['method'] ?? 'GET';
        $data = $this->form($formattedRules, $options);

        // Apply defaults for missing or empty inputs
        foreach ($defaults as $key => $defaultValue) {
            if (!isset($data[$key]) || $data[$key] === null || $data[$key] === '') {
                $data[$key] = $defaultValue;
            }
        }

        return $data;
    }


    /**
     * FORM GİRİŞ KAPISI — method kontrolü + shield aktivasyonu 🛡️
     *
     * FW-ALTYAPI-2 / B (adım 2): `form()` ile `rawAll()` bu adımı ORTAKLAŞIR.
     * Ayrı ayrı yazılsaydı, ikisinden birinin `method`/`csrf`/`rateLimitEnabled`
     * seçeneklerini unutması sessiz bir güvenlik gerilemesi olurdu.
     *
     * DİKKAT (çift çalıştırma tuzağı): burada yalnız **method kontrolü** ve
     * **shield bayrağı** kurulur. Denetimlerin kendisi
     * (`applyShieldConfigurations()`) `validate()` İÇİNDE çalışır; `form()`
     * yalnız `validate()` çağırdığı için ikinci kez çağırmak denetimleri
     * (hız sınırı sayacı, F-10 sayacı, CSRF) İKİ KEZ işletirdi.
     * `rawAll()` doğrulama yapmadığı için `validate()`'a girmez; onun için
     * denetimler `rawAll()` gövdesinde AÇIKÇA çağrılır.
     *
     * @param array $options `method` (varsayılan `POST`) + shield seçenekleri.
     */
    protected function applyFormGuard(array $options = []): void
    {
        // 🛡️ Masterpiece Automated Guard: Ensure correct HTTP method (Post-only by default)
        $method = $options['method'] ?? 'POST';
        if (!$this->isMethod($method)) {
            alert()->error($options['error_message'] ?? 'Geçersiz İstek (Method Mismatch)');
            exit;
        }

        if (!empty($options) || !$this->shieldActive) {
            $this->shield($options);
        }
    }

    protected function applyShieldConfigurations(): void
    {
        $formSecurity = BaseService::get()->service('form');
        if (!$formSecurity)
            return;

        $securityResult = $formSecurity->runSecurityChecks($this->post, $this->shieldOptions);

        if (!$securityResult['success']) {
            $this->handleFailedShield($securityResult['message']);
        }
    }

    protected function handleFailedShield(string $message): void
    {
        // RBN 3.5: [MASTERPIECE SHIELD LOGIC] 🛡️🛰️⚓
        // Add fallback redirect for non-ajax requests to prevent white pages.
        alert()->error($message, $_SERVER['HTTP_REFERER'] ?? '/');
        exit;
    }

    protected function handleHookFailure(string $message): void
    {
        $_SESSION['_old_input'] = $this->all();

        // RBN 3.5: [MASTERPIECE SHIELD LOGIC] 🛡️🛰️⚓
        alert()->error($message, $_SERVER['HTTP_REFERER'] ?? '/');
        exit;
    }

    protected function handleFailedValidation(Validator $validator): void
    {
        $errors = $validator->errors();
        $firstError = reset($errors);
        $message = is_array($firstError) ? reset($firstError) : $firstError;

        $_SESSION['_errors'] = $errors;
        $_SESSION['_old_input'] = $this->all();

        // RBN 3.5: [MASTERPIECE SHIELD LOGIC] 🛡️🛰️⚓
        alert()->error((string) $message, $_SERVER['HTTP_REFERER'] ?? '/', null, ['errors' => $errors]);
        exit;
    }
}
