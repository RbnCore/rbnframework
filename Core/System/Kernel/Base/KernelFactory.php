<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\System\Kernel\Base;

use Rbn\Framework\Core\System\Kernel\Kernel;
use Rbn\Framework\Core\System\Kernel\Stages\Autoload;
use Rbn\Framework\Core\System\Kernel\Stages\ShieldSentinel;
use Rbn\Framework\Core\System\Kernel\Stages\DatabaseGuardStage;
use Rbn\Framework\Core\System\Kernel\Stages\ComponentRegistry;
use Rbn\Framework\Core\System\Kernel\Stages\SessionSandboxStage;
use Rbn\Framework\Core\System\Kernel\Stages\Routing;

/**
 * KernelFactory - Standard Boot-loader Orchestrator 🏛️🚀⚓
 */
class KernelFactory
{
    /**
     * Creates and configures a fully-equipped Kernel instance.
     *
     * ─────────────────────────────────────────────────────────────────────────
     * [S-10] AŞAMA SIRASI VE BAĞIMLILIKLARI (kodda belgelenmiş)
     *
     * Sıra bir KAZARA değil, birbirinin üzerine yığılma zorunluluğudur.
     * Aşağıdaki listede her aşamanın "neye bağımlı" olduğu YAZILIDIR; sıra
     * değişirse hangi adımın kırıldığı buradan okunur.
     *
     *  1. `Autoload`
     *     Bağımlılık: YOK (her şeyden önce).
     *     Composer + proje PSR-4 kayıtlarını kurar. Bu aşama atlanır/sonraya
     *     alınırsa sonraki aşamaların `use` ile çözülen sınıfları YÜKLENMEZ.
     *
     *  2. `ShieldSentinel`
     *     Bağımlılık: 1 (otoload).
     *     Zorunlu sağlık/güvenlik denetimi; başarısızsa erken çıkış üretir.
     *     `Autoload`'dan ÖNCE gelemez (denetlediği sınıflar henüz yok).
     *
     *  3. `DatabaseGuardStage`
     *     Bağımlılık: 1, 2.
     *     Ortamı ve aktif projeyi DB üzerinden çözer (`project_data` burada
     *     dolar). `ShieldSentinel`'den SONRA gelir: proje bilgisi olmadan
     *     çalışacak güvenlik kararı veremez.
     *
     *  4. `ComponentRegistry`
     *     Bağımlılık: 1, 2, 3 (dolaylı: `BaseService` + proje bağlamı hazır).
     *     Exception servisini kaydeder ve sınıf alias'larını kurar.
     *     `DatabaseGuardStage`'den ÖNCE gelirse alias'lar eksik bağlanır ve
     *     istisna işleme sessizce devre dışı kalır.
     *
     *  5. `SessionSandboxStage`
     *     Bağımlılık: 3, 4.
     *     Oturum çerezi bayrakları, oturum ömrü ve "proje kutusu" senkronu.
     *     Proje bağlamı (3) ve servis/alias katmanı (4) hazır olmadan
     *     `session_name()` doğru proje için ayarlanamaz.
     *
     *  6. `Routing`
     *     Bağımlılık: 1-5 (tümü).
     *     Rotaları yükler (`Route::loadRoutes()`), bakım modunu ve ön-eki
     *     denetler. Ardından `Bootstrap` çağrısı `Route::run()` ile isteği
     *     `RedirectManager` → `dispatch` hattına bırakır; `RedirectManager`
     *     bu noktada rota koleksiyonunu HAZIR bulur (R-02 tuzak kontrolü
     *     buna dayanır).
     *
     * ÖZET: Autoload -> Diagnostics -> DB -> Services -> Session -> Route
     *       (Türkçe: Oto yükleme -> Tanılama -> Veritabanı -> Servisler ->
     *        Oturum -> Yönlendirme)
     *
     * Bu liste bir AŞAMA ENVANTERİDİR; yeni aşama eklenirse aynı biçimde
     * "neye bağımlı" satırı EKLENMEK zorundadır.
     * ─────────────────────────────────────────────────────────────────────────
     */
    public static function create(string $publicPath, array $config = []): Kernel
    {
        $kernel = new Kernel($publicPath, $config);

        // 🎼 Orchestrate the RBN Framework Pipeline 🏺⚖️🛡️⚓
        // Logic: Autoload -> Diagnostics -> DB -> Services -> Session -> Route
        // Sıra ve bağımlılık gerekçeleri: yukarıdaki [S-10] bloğu.
        $kernel->addStage(new Autoload())
               ->addStage(new ShieldSentinel())
               ->addStage(new DatabaseGuardStage())
               ->addStage(new ComponentRegistry())
               ->addStage(new SessionSandboxStage())
               ->addStage(new Routing());

        return $kernel;
    }
}
