<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnEmail\Handlers;

use Rbn\Framework\Core\Base\BaseComponent;
use Rbn\Framework\Core\Support\Blueprints\Validations\EmailValidations;

/**
 * EmailGuardHandler - The Outbound Sentry 🛡️🎻⚓
 * 
 * RBN 3.5 "Masterpiece": Specialized Security Handler for email dispatching.
 * Protects SMTP reputation by validating formats, DNS, and blacklists.
 */
class EmailGuardHandler extends BaseComponent
{
    /**
     * Perform a comprehensive security check on the recipient address. ⚔️🛡️
     * 
     * @param string $email
     * @return array Standard RBN Result
     */
    public function check(string $email): array
    {
        $email = trim(strtolower($email));

        // 1. Syntax Check 🧬
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->sendError("Geçersiz e-posta formatı: [{$email}]", ['code' => 'invalid_format']);
        }

        $parts  = explode('@', $email);
        $user   = $parts[0];
        $domain = end($parts);

        // 2. Blacklist Check (Temp/Spam Domains) 🛡️⚓
        if (in_array($domain, EmailValidations::TEMP_DOMAINS, true)) {
            return $this->sendError("Geçici e-posta servislerine gönderim yapılamaz.", ['code' => 'temp_email']);
        }

        if (in_array($domain, EmailValidations::SPAM_DOMAINS, true)) {
            return $this->sendError("Bu domain [{$domain}] kara listede bulunuyor.", ['code' => 'spam_email']);
        }

        // 3. Suspicious & Disposable Pattern Match 🧬🔍
        $allPatterns = array_merge(EmailValidations::SUSPICIOUS_PATTERNS, EmailValidations::DISPOSABLE_PATTERNS);
        foreach ($allPatterns as $pattern) {
            if (preg_match($pattern, $email)) {
                return $this->sendError("Bu e-posta adresi [{$email}] sistem politikası gereği engellendi.", ['code' => 'suspicious_identity']);
            }
        }

        // 4. Domain Syntax Check (offline) 🏷️
        //
        // [A-07] Burada ONCEDEN `checkdnsrr($domain, 'MX')` vardi: canli DNS,
        // zaman asimsiz, ve gonderimden hemen ONCE (yani kullaniciyi bekleten)
        // bir cagri. PHP `checkdnsrr` zaman asimi kabul etmez -> yanit vermeyen
        // cozucu istegi asili birakir. Karar artik YALNIZCA soz dizimine
        // dayanir; gercek teslimat dogrulamasi SMTP tarafinda (kendi zaman asimi
        // olan katman) yapilir.
        if (!$this->isDomainSyntaxValid($domain)) {
            return $this->sendError("Bu domain [{$domain}] gecerli gorunmuyor.", ['code' => 'domain_syntax']);
        }

        return $this->sendSuccess("Identity verified.");
    }

    /**
     * Offline domain syntax check — canli DNS CAGIRMAZ 🏷️
     *
     * @param string $domain
     * @return bool
     */
    public function isDomainSyntaxValid(string $domain): bool
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

        return (bool) preg_match('/^[A-Za-z]{2,}$/', (string) end($parts));
    }
}
