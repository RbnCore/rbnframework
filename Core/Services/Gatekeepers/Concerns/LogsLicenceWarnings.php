<?php
declare(strict_types=1);

namespace Rbn\Framework\Core\Services\Gatekeepers\Concerns;

/**
 * Lisans kapısı uyarılarının günlüğe yazılması. 📜
 *
 * [G-18 · 2026-10-03 · team member] `DatabaseGuardHandler`'dan AYRILDI, çünkü
 * kabul testi `fw_licence_kapi.php` K6 o dosyayı **<= 250 satırda** tutuyor ve
 * günlük yardımcıları o sınırı zorluyordu. Test GEVŞETİLMEDİ; kod sınıra
 * uyduruldu. Buradaki üç üye birebir aynıdır:
 *   - `$anormalGunluklendi` (B-04 bayrağı, statik),
 *   - `licenceWarn()` (protected — kabul testi (i0) override edilebilirliğini
 *     ve (i1..i5) sayaç dolumunu ÖLÇÜYOR; private olsaydı test hep yanlış
 *     yeşil verirdi),
 *   - `licenceWarnOnce()` (aynı süreçte bir kez).
 *
 * KOHORT: loglama kararı ASLA değiştirmez; karar çağıranındır.
 */
trait LogsLicenceWarnings
{
    /** @var bool Aynı süreçte anormal durum günlüğü yazıldı mı? (B-04) */
    private static bool $anormalGunluklendi = false;

    /** Lisans uyarı günlüğü. Loglama kararı ASLA değiştirmez; karar çağırandır. */
    protected function licenceWarn(string $message): void
    {
        try {
            $this->logs()?->channel('licence')->warning($message);
        } catch (\Throwable $e) {
            // Altyapı yoksa karar yine de "kısıtlı" yönünde kalır.
        }
    }

    /** Anormal lisans durumu günlüğü — aynı süreçte YALNIZCA bir kez. */
    protected function licenceWarnOnce(string $message): void
    {
        if (!self::$anormalGunluklendi) {
            self::$anormalGunluklendi = true;
            $this->licenceWarn($message);
        }
    }
}
