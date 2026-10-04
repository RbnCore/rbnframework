<?php

namespace Rbn\Framework\Core\Http;

use Rbn\Framework\Core\Http\Request;

/**
 * FormRequest - Dedicated class for form validation and authorization.
 */
abstract class FormRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    abstract public function rules(): array;

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [];
    }

    /**
     * Define the shield protection layer for the request.
     * Override this method in child classes to enable automatic FormService verification.
     */
    public function withShield(): array
    {
        return [];
    }

    /**
     * Validate the class instance.
     */
    public function validateResolved(): array
    {
        if (!$this->authorize()) {
            $this->failedAuthorization();
        }

        // Otomatik Shield Yüklemesi
        $shieldOptions = $this->withShield();
        if (!empty($shieldOptions)) {
            $this->shield($shieldOptions);
        }

        return $this->validate($this->rules(), $this->messages());
    }

    /**
     * Handle a failed authorization attempt.
     *
     * [http DÜŞÜK-4] Önceden `header()` + düz metin `echo` + `exit` idi:
     * kullanıcı framework'ün standart 403 sayfasını görmüyor, ham metin
     * basılıyordu (bilgi sızdırmıyordu, yalnız GÖRÜNTÜ/kozmetik eksiği).
     * Artık TEK merkezden (`shield()->forbidden()` → `UserErrorProvider`,
     * RbnShield 403 şablonu) basılır — diğer tüm 403 yollarıyla aynı sayfa.
     *
     * KIRILMA YOK:
     *   - HTTP durumu yine 403 (durum kodu `UserErrorProvider` içinde yazılır).
     *   - `shield()` yardımcısı yoksa (çok erken boot / birim testi) eski
     *     düz metin davranışına düşülür → istek ASLA askıda kalmaz.
     */
    protected function failedAuthorization(): void
    {
        if (function_exists('shield')) {
            // `Shield::forbidden()` RbnShield 403 sayfasını basar ve `exit`
            // yapar. Beklenmedik biçimde dönse bile aşağıdaki yedek 403'ü
            // basmaya devam eder (istek ASLA 200/boş dönmez).
            shield()->forbidden('Bu istek için yetkiniz bulunmuyor.');
        }

        header('HTTP/1.1 403 Forbidden');
        echo "403 Forbidden";
        exit;
    }
}

