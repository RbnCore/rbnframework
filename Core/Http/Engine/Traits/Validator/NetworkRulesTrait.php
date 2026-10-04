<?php
/**
 * RBN Framework 3.0: High-Performance Http Engine 🎻📡
 */
namespace Rbn\Framework\Core\Http\Engine\Traits\Validator;

use Rbn\Framework\Core\Base\Services\BaseService;

/**
 * NetworkRulesTrait - Complex Data & Network Rules 📡
 * 
 * Handles email (DNS), IP, URL, and specialized phone formats.
 */
trait NetworkRulesTrait
{
    /**
     * Email address with syntax and Blacklist check 📧
     *
     * [A-07] CANLI DNS (MX) SORGUSU KALDIRILDI.
     *
     * SORUN: bu kural dogrudan `checkdnsrr($domain, 'MX')` cagiriyordu. PHP
     * `checkdnsrr` zaman asimi parametresi TUTMAZ (`default_socket_timeout`
     * UYGULANMAZ); cozucu yanit vermezse istek, `email` kuralina kadar — yani
     * kayit / giris formunun KRITIK YOLUNDA — asili kalir. Ayrica canli DNS'e
     * bagli bir karar kendi basina bir ZAMANLAMA KANALI (bilinen alan adlarina
     * gore daha hizli/reddedilmis) ve kullanici yazim hatasi ile gercek
     * "domain yok" ayrimini yapilamaz hale getiriyordu (asagidaki olcum).
     *
     * COZUM (secenek 1): karar canli DNS'e BAGLANMAZ. E-posta FORMATI +
     * alan adi SOZDIZIMI + kara liste (gecici/spam) + supheli desen yeterli.
     * Gercek teslimat dogrulamasi zaten SMTP gonderimi sirasinda yapilir; SMTP
     * kendi zaman asimi olan bir katmandir ve kullaniciyi bekletmez.
     */
    protected function validateEmail(string $field, $value): void
    {
        if (empty($value)) return;

        $email = preg_replace('/\s+/', '', trim(strtolower($value)));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, 'email');
            return;
        }

        // Domain Syntax & Blacklist Check (no live DNS — bkz. A-07 notu yukarida)
        $parts  = explode('@', $email);
        $domain = (string) end($parts);

        if (in_array($domain, $this->validation('validation.TEMP_DOMAINS') ?? [], true)) {
            $this->addError($field, 'email_temp');
            return;
        }

        if (!$this->isDomainSyntaxValid($domain)) {
            $this->addError($field, 'email_domain');
        }
    }

    /**
     * Domain syntax check (offline, no DNS) 🏷️
     *
     * [A-07] Alan adinin SOZDIZIMINI dogrular; canli DNS'e gitmez.
     * Kural: etiketler `[A-Za-z0-9-]` ile, bos olamaz, icinde bosluk/egik
     * cizgi olamaz; en az bir nokta; TLD yalniz harf, en az 2 karakter.
     * Yerel gelistirme alan adlari (`*.test`, `localhost`) gecerli sayilir.
     */
    protected function isDomainSyntaxValid(string $domain): bool
    {
        $domain = trim($domain);

        if ($domain === '' || str_contains($domain, '..')) {
            return false;
        }

        if ($domain === 'localhost' || str_ends_with($domain, '.test')) {
            return true;
        }

        $parts = explode('.', $domain);
        if (count($parts) < 2) {
            return false;
        }

        foreach ($parts as $part) {
            if ($part === '' || !preg_match('/^[A-Za-z0-9]([A-Za-z0-9-]*[A-Za-z0-9])?$/', $part)) {
                return false;
            }
        }

        // TLD: yalniz harf, en az 2 karakter (sayisal TLD yok)
        $tld = (string) end($parts);

        return (bool) preg_match('/^[A-Za-z]{2,}$/', $tld);
    }

    /**
     * IP address check 🌐
     */
    protected function validateIp(string $field, $value): void
    {
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_IP)) {
            $this->addError($field, 'ip');
        }
    }

    /**
     * URL format check 🔗
     */
    protected function validateUrl(string $field, $value): void
    {
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
            $this->addError($field, 'url');
        }
    }

    /**
     * Date format check 📅
     */
    protected function validateDate(string $field, $value): void
    {
        if (!empty($value) && strtotime($value) === false) {
            $this->addError($field, 'date');
        }
    }

    /**
     * Turkish GSM phone number format check 📲
     */
    protected function validatePhoneTr(string $field, $value): void
    {
        if (empty($value)) return;

        $digits = preg_replace('/\D+/', '', $value);
        if (strpos($digits, '90') === 0) $digits = substr($digits, 2);
        if (strlen($digits) === 10) $digits = '0' . $digits;

        if (strlen($digits) !== $this->validation('validation.TURKISH_PHONE_LENGTH')) {
            $this->addError($field, 'phone_tr');
            return;
        }

        $prefix = substr($digits, 1, 3);
        if (!in_array($prefix, $this->validation('validation.TURKISH_PHONE_PREFIXES'), true)) {
            $this->addError($field, 'phone_tr');
        }
    }
}
