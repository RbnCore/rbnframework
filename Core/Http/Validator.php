<?php

namespace Rbn\Framework\Core\Http;

use Rbn\Framework\Core\Http\Engine\Traits\Validator\HelperTrait;
use Rbn\Framework\Core\Http\Engine\Traits\Validator\CoreRulesTrait;
use Rbn\Framework\Core\Http\Engine\Traits\Validator\DatabaseRulesTrait;
use Rbn\Framework\Core\Http\Engine\Traits\Validator\NetworkRulesTrait;
use Rbn\Framework\Core\Base\Concerns\Contexts\ServicesContextTrait;

/**
 * Validator - High-Performance Validation Engine
 * 
 * Manages validation rules using a Trait-Driven Architecture.
 * Decomposes massive rule sets into specialized structural units.
 */
class Validator
{
    use HelperTrait, CoreRulesTrait, DatabaseRulesTrait, NetworkRulesTrait, ServicesContextTrait;

    protected array $data;
    protected array $rules;
    protected array $messages;
    protected array $errors = [];

    /**
     * [F-06] Karşılığı olmayan / uygulanamayan kurallar (gözlem için).
     * @var array<string, true>
     */
    protected array $unknownRules = [];

    /**
     * Initialize the validation engine 🧱
     */
    public function __construct(array $data, array $rules, array $messages = [])
    {
        $this->data     = $data;
        $this->rules    = $rules;
        $this->messages = $messages;
        $this->initServicesContext();
    }

    /**
     * Static factory for fluent creation 🛰️
     */
    public static function make(array $data, array $rules, array $messages = []): self
    {
        return new self($data, $rules, $messages);
    }

    /**
     * Check if validation failed ❌
     */
    public function fails(): bool
    {
        $this->validate();
        return !empty($this->errors);
    }

    /**
     * Get the current error collection 📋
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Get validated data (only keys present in rules) 🛡️
     */
    public function validated(): array
    {
        $validatedData = [];
        foreach ($this->rules as $field => $ruleset) {
            if (isset($this->data[$field])) {
                $validatedData[$field] = $this->data[$field];
            }
        }
        return $validatedData;
    }

    /**
     * Run the validation process 🕹️
     */
    public function validate(): array
    {
        $this->errors = [];

        foreach ($this->rules as $field => $ruleset) {
            $rules = is_string($ruleset) ? explode('|', $ruleset) : $ruleset;
            $value = $this->data[$field] ?? null;

            foreach ($rules as $rule) {
                $this->applyRule($field, $value, $rule);
            }
        }

        return $this->errors;
    }

    /**
     * Dynamically apply a rule to a field 🛰️
     */
    protected function applyRule(string $field, $value, $rule): void
    {
        $params = [];

        // Handle Array-based rules
        if (is_array($rule)) {
            $ruleName = $rule['type'] ?? $rule[0] ?? '';
            $params   = $rule['params'] ?? $rule[1] ?? [];
            $rule     = $ruleName;
        } else {
            // Handle String-based rules (e.g., 'min:2')
            if (strpos($rule, ':') !== false) {
                [$rule, $paramString] = explode(':', $rule, 2);
                $params = explode(',', $paramString);
            }
        }

        if (empty($rule)) return;

        $methodName = 'validate' . str_replace('_', '', ucwords($rule, '_'));

        if (method_exists($this, $methodName)) {
            $this->$methodName($field, $value, $params);
            return;
        }

        // [F-06 · 2026-10-03 · team member] Bilinmeyen kural ARTIK sessizce gecilmiyor.
        //
        // TABAN: `method_exists()` false ise dongu SESSIZCE biterdi. Boylece yazim hatali
        // bir kural ("acccepted", "requred") veya framework'te unutulmus bir kural hicbir
        // iz birakmadan geciyordu -> guvenlik kurali sandigimiz sey uygulanmiyordu
        // (canli ornek: `accepted` -> KVKK acik riza onay kutusu dogrulanmiyordu).
        //
        // KARAR: `throw` DEGIL, "kayit + uyari logu + devam".
        // Gerekce: framework+projects+domains taramasinda (2338 dosya / 92 kural baglami)
        // kural dizisine yanlislikla ROL DEGERI konmus 2 canli kullanim var
        // (Bundles/Internal/Webhub/Controllers/{Identity,Seo}Controller.php ->
        // 'required_role' => 'developer'); fail-closed bu 2 ucu kirardi.
        // Bu kalemler ayri karara birakilmistir (rapor).
        $this->unknownRules[$field . '.' . $rule] = true;

        error_log(sprintf(
            '[RBN][Validator] bilinmeyen kural UYGULANMADI: alan=%s kural=%s aranan-metot=%s',
            $field,
            $rule,
            $methodName
        ));
    }

    /**
     * [F-06] Uygulanamayan (karşılığı olmayan) kural adlarını döndür 🔎
     *
     * @return string[] "alan.kural" anahtarlari
     */
    public function unknownRules(): array
    {
        return array_keys($this->unknownRules);
    }
}
