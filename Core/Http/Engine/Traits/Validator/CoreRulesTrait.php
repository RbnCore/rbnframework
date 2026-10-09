<?php
/**
 * High-Performance Http Engine 🎻🛡️
 */
namespace Rbn\Framework\Core\Http\Engine\Traits\Validator;

/**
 * CoreRulesTrait - The Foundational Validator Rules 🧱
 * 
 * Handles basic constraints like required, length, numeric, and regex types.
 */
trait CoreRulesTrait
{
    /**
     * Required field check ⛓️
     */
    protected function validateRequired(string $field, $value): void
    {
        if (is_null($value) || (is_string($value) && trim($value) === '') || (is_array($value) && empty($value))) {
            $this->addError($field, 'required');
        }
    }

    /**
     * Minimum length check 📏
     */
    protected function validateMin(string $field, $value, array $params): void
    {
        $min = (int) ($params[0] ?? 0);
        if (!empty($value) && mb_strlen((string) $value) < $min) {
            $this->addError($field, 'min', [$min]);
        }
    }

    /**
     * Maximum length check 🛡️
     */
    protected function validateMax(string $field, $value, array $params): void
    {
        $max = (int) ($params[0] ?? 255);
        if (!empty($value) && mb_strlen((string) $value) > $max) {
            $this->addError($field, 'max', [$max]);
        }
    }

    /**
     * Numeric value check 🔢
     */
    protected function validateNumeric(string $field, $value): void
    {
        if (!empty($value) && !is_numeric($value)) {
            $this->addError($field, 'numeric');
        }
    }

    /**
     * Confirmation matching (e.g., password_confirmation) 🤝
     */
    protected function validateConfirmed(string $field, $value): void
    {
        $confirmationField = $field . '_confirmation';
        $confirmationValue = $this->data[$confirmationField] ?? null;

        if ($value !== $confirmationValue) {
            $this->addError($field, 'confirmed');
        }
    }

    /**
     * Custom regular expression check 🔍
     */
    protected function validateRegex(string $field, $value, array $params): void
    {
        if (!empty($value) && !empty($params) && !preg_match($params[0], $value)) {
            $this->addError($field, 'regex');
        }
    }

    /**
     * Alphabetic character check (and Turkish characters) 🔡
     */
    protected function validateAlpha(string $field, $value): void
    {
        if (!empty($value) && !preg_match('/^[a-zA-ZçÇğĞıİöÖşŞüÜ\s]+$/u', $value)) {
            $this->addError($field, 'alpha');
        }
    }

    /* == [F-06] 2026-10-03 · team member ==================================
     *
     * Asagidaki kurallar framework+projects+domains taramasinda
     * KULLANILIYORDU ama framework'te karsiligi YOKTI -> `applyRule()`
     * `method_exists()` false dedigi icin sessizce geciliyordu.
     * (tarama: _tarama_kurallar3.php, 2338 dosya / 92 kural baglami)
     *
     * Taranan kullanim sayilari:
     *   optional 161 | nullable 146 | string 48 | array 15 | in 4 | accepted 4 | same 2
     *
     * BOS DEGER KORUMASI: bu trait'teki her kural `!empty($value)` ile
     * korunur; ozel (zorunlu) `required` disinda hicbir kural bos degeri
     * reddetmez. Yeni kurallar da ayni korumayi tasi.
     * ================================================================ */

    /**
     * Onay kutusu (checkbox) kabulü ✅
     *
     * KVKK md.5 / acik riza: sunucu tarafı ZORUNLU kontrol. Onaylanmayan
     * kutu gonderilmez (alan hic gelmez -> null) veya bos string gelir;
     * ikisi de REDDEDILIR. Onceki surumlerde bu kontrolu birkac denetleyici
     * elle yazmak zorunda kalmisti.
     *
     * Kabul kumesi: on / yes / 1 / true / accepted
     * (HTML `value` ozniteligi olmayan checkbox tarayicida "on" gonderir;
     *  JSON/API istemcileri true, 1, "1", "yes", "accepted" gonderebilir)
     */
    protected function validateAccepted(string $field, $value): void
    {
        if (is_bool($value)) {
            $ok = $value;
        } elseif (is_int($value) || is_float($value)) {
            $ok = ((string) $value) === '1';
        } elseif (is_string($value)) {
            $ok = in_array(strtolower(trim($value)), ['on', 'yes', '1', 'true', 'accepted'], true);
        } else {
            $ok = false;   // null / dizi / nesne -> kabul edilmez
        }

        if (!$ok) {
            $this->addError($field, 'accepted');
        }
    }

    /**
     * `nullable` isaretleyicisi — alan bos olabilir 🚫
     *
     * BILINCLI KARAR: bu kural `validate()` dongusunu KESMEZ; yalnizca
     * taninmis/isaretleyici olur. Sebep: bu trait'teki her kural zaten
     * `!empty($value)` korumasiyla bos degerde atliyor, yani `nullable`
     * eklemek davranista HICBIR degisiklik yapmaz. Kesme yapsaydik
     * `nullable|required` kombinasyonunda `required` gecersiz kilinirdi
     * (tabandaki davranis degisir, canli formlar kirilir).
     */
    protected function validateNullable(string $field, $value): void
    {
        // isaretleyici — bileserek bos bir implementasyon
    }

    /**
     * `optional` isaretleyicisi — `nullable` ile ayni sozlesme 🏷️
     * (`ValidationTrait::filter()` her anahtari 'optional' yapar.)
     */
    protected function validateOptional(string $field, $value): void
    {
        // isaretleyici — bileserek bos bir implementasyon
    }

    /**
     * Metin (string) tur denetimi 🔤
     *
     * Tarama: `'table' => 'required|string'`, `'char_name' => 'required|string|min:2'`.
     * Dizi/Nesne GIDER (form alanlarina dizi basmak tip hatasi veya
     * parametre kirlilmesi olur); sayisal degerler kabul edilir, cunku
     * HTML formu her sayiyi metin olarak gonderir ve mevcut 48 canli
     * kullanimda sayisal degerler surekli gelir.
     */
    protected function validateString(string $field, $value): void
    {
        if (!empty($value) && (is_array($value) || is_object($value))) {
            $this->addError($field, 'string');
        }
    }

    /**
     * Dizi (array) tur denetimi 📚
     *
     * Tarama: DbConsole toplu islemler (`'ids' => 'required|array'`).
     * Istemci `ids[]` yerine `ids` scalar gonderirse islem hata verir.
     */
    protected function validateArray(string $field, $value): void
    {
        if (!empty($value) && !is_array($value)) {
            $this->addError($field, 'array');
        }
    }

    /**
     * Sabit liste uyeligi (`in:a,b,c`) 🎯
     *
     * Karsilastirma BUYUK/KUCUK HARF DUYARSIZ: canli kullanimda liste
     * elle yazilmis (`in:china,europe,ch,eu,CH,EU`) ve form degeri
     * kucuk/buyuk harf karisik gelebiliyor; karsi lastigi degil, esit
     * degil kontroludur, bu yuzden duyarsiz karsilastirma hem guvenli
     * hem canli formlari kirmaz.
     */
    protected function validateIn(string $field, $value, array $params): void
    {
        if (empty($value) || empty($params)) {
            return;
        }

        $izin = array_map(
            static fn($p) => strtolower(trim((string) $p)),
            $params
        );

        if (!in_array(strtolower(trim((string) $value)), $izin, true)) {
            $this->addError($field, 'in');
        }
    }

    /**
     * Esitlik (`same:alan`) — parola/teyit ve benzeri 🔗
     *
     * Tarama: RbnAdmin `updatePassword()`:
     *   'new_password'    => 'required|min:8'
     *   'repeat_password' => 'required|same:new_password'
     * Karsilastira metinlerde `hash_equals` ile (zamanlama sizintisi yok).
     */
    protected function validateSame(string $field, $value, array $params): void
    {
        if (empty($value) || empty($params)) {
            return;
        }

        $other = $this->data[$params[0]] ?? null;

        if (is_array($value) || is_array($other)) {
            if ($value != $other) {
                $this->addError($field, 'same');
            }
            return;
        }

        if (!hash_equals((string) $value, (string) $other)) {
            $this->addError($field, 'same');
        }
    }
}
