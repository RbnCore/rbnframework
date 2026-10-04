<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Base;

use Rbn\Framework\Core\Base\Concerns\ConcernsContextTrait;
use Rbn\Framework\Core\Base\Services\BaseService;
use Rbn\Framework\Core\System\Discovery\Clusters\Logic\Component\ComponentResolver;
use Rbn\Framework\Core\Support\Bridges\Helpers\Library\LogThrottle;

/**
 * BaseComponent - The Absolute Root DNA 🧬⚓
 * 
 * RBN 3.5: Centralized orchestrator for discovery, context and properties.
 * Masterpiece Refactoring: Now 100% trait-driven and non-redundant.
 * 
 * @method array|mixed getRouteConfig(?string $projectKey = null, ?string $key = null)
 * @method string targetProjectUrl(string $projectKey, string $path = '')
 * @method mixed resolveProjectData(string $key, ?string $projectKey = null)
 * @method \Rbn\Framework\Core\Database\Engine\DatabaseEngine db()
 * @method \Rbn\Framework\Core\Services\System\Managers\SessionManager session()
 * @method \Rbn\Framework\Core\System\Storage\Providers\SessionProvider sessions()
 * @method \Rbn\Framework\Core\System\Storage\Providers\CacheProvider cache()
 * @method \Rbn\Framework\Core\System\Storage\Providers\LogProvider logs()
 */
#[\AllowDynamicProperties]
abstract class BaseComponent implements \ArrayAccess
{
    /**
     * Unified Global Context Hub 🪐🧬
     * Contains: HttpContext, ServicesContext, StorageContext and ErrorHandling
     */
    use ConcernsContextTrait;

    /** @var array Cached Property Suffix Resolution 🧠⚡ */
    private static array $resolvedSuffixes = [];

    /**
     * @var int Son ek cozumleme onbelleginin ust siniri 🧠🛡️
     *
     * B-07: onbellek suregi (CLI kervani, uzun omurlu surecler) boyunca
     * SINIRSIZ buyuyordu. Cozumleme SONUCU degismez; yalniz onbellek
     * sinirlandirilir ve sinira ulasinca tamamen bosaltilir.
     * Yeni metot adi `__` ile DEGIL, Turkce olmayan Ingilizce adiyla eklendi
     * (Anayasa §10: mevcut adlara dokunulmadi).
     */
    private const RESOLVED_SUFFIX_CACHE_LIMIT = 512;

    /**
     * Son ek cozumlemesini onbellege yazar (sinirli onbellek) 🧠🛡️
     *
     * @param string $name  Cozulmus ozellik adi
     * @param array  $cozum [type, target]
     */
    private static function rememberResolvedSuffix(string $name, array $cozum): void
    {
        if (!isset(self::$resolvedSuffixes[$name]) && count(self::$resolvedSuffixes) >= self::RESOLVED_SUFFIX_CACHE_LIMIT) {
            self::$resolvedSuffixes = [];
        }
        self::$resolvedSuffixes[$name] = $cozum;
    }

    /** @var int Global Call Depth Tracker (Recursion Guard) 🛡️ */
    private static int $callDepth = 0;

    /**
     * [B-92 · FW-KARAR-2-B] DI YOK SAYILAN CONSTRUCTOR UYARISI (LOG-ONLY) 📣
     *
     * `__construct(?BaseService $rbn = null)` imzası **korundu** (K-1 kuralı:
     * geriye uyumlu kal, imza daraltma). Parametre gövdede hiç kullanılmıyor;
     * bir bileşene verilen DI nesnesi sessizce düşüyordu. Parametre SİLİNMEZ
     * (alt sınıfların imzası kırılır), bunun yerine **saatte bir, sınıf başına
     * TEK** uyarı yazılır.
     *
     * GÜRÜLTÜ DÜZELTMESİ (FW-LOG-GURULTU, ölçüm): bu satır `HttpContextTrait`
     * üzerinden **HER İSTEKTE** çağrılıyordu (`new RemoteRequest($hub)` →
     * `new ViewEngine($hub)`). PHP-FPM'de "süreç başına bir kez" koruması
     * işe YARAMAZ (her istek yeni süreç). Ölçülen: 162 isteklik duman koşusunda
     * 596 satır (RemoteRequest 369 + ViewEngine 227). Koruma artık
     * `LogThrottle::once()` (saatlik, süreçler arası).
     *
     * @see \Rbn\Framework\Core\Support\Bridges\Helpers\Library\LogThrottle
     */
    public const B92_UYARI_ETIKETI = 'b92-di-yok-sayildi';

    /**
     * Construct the Component (Sync with Hub) ⚓
     */
    public function __construct(?BaseService $rbn = null)
    {
        // 📣 [B-92] DI verilmiş ama kullanılmıyor → görünürlük (log-only).
        // Sessiz düşen DI, "bileşen neden $rbn'ı görmüyor?" sorusunun
        // cevabını günlüklerde aramayı zorlaştırıyordu.
        //
        // GÜRÜLTÜ SINIRI: süreç içi dizi YETERSİZDİR (PHP-FPM'de her istek yeni
        // süreç). `LogThrottle::once()` saatlik + süreçler arası koruma sağlar;
        // depolama kullanılamazsa fail-closed devreye girer (satır yazılmaz,
        // istek bozulmaz).
        if ($rbn !== null && LogThrottle::once(self::B92_UYARI_ETIKETI . ':' . static::class)) {
            // İsimsiz (anonim) sınıfların adında NUL baytı vardır; log
            // yazımı NUL'da KIRPILIR ve satır yarım kalır. Temizleniyor.
            $ad = str_replace("\0", '', static::class);
            error_log('[RBN] [B-92] UYARI: ' . $ad
                . '::__construct(?BaseService $rbn) parametresine verilen DI nesnesi'
                . ' YOK SAYILDI (gerçek DI bağı kurulmadı).'
                . ' İmza geriye uyum için korundu; DI bekleniyorsa'
                . ' "BaseService::get()" singleton kullanılmalı.');
        }

        // 🎼 RBN 3.5: [SOVEREIGN BEFORE BOOT] 🛫
        $this->beforeBoot();

        // 🎼 RBN 3.5: DNA Kökünü (Concerns) ayağa kaldır 🚀🔋
        $this->bootConcernsContext();

        // 🎼 RBN 3.5: [SOVEREIGN AFTER BOOT] 🚀
        $this->afterBoot();
    }

    /**
     * Masterpiece Lifecycle Hook: Pre-initialization 🛫
     */
    protected function beforeBoot(): void
    {
        // To be implemented by child classes.
    }

    /**
     * Masterpiece Lifecycle Hook: Post-initialization 🚀
     */
    protected function afterBoot(): void
    {
        // To be implemented by child classes.
    }

    /**
     * Sovereign Debug Hub 🕵️‍♂️🔬⚓
     * 
     * RBN 3.5: Prevents massive recursive exhausting dumps during development.
     * Unsets the global singletons (rbn, discover) from the dump list.
     */
    public function __debugInfo(): array
    {
        $info = get_object_vars($this);

        // 🎼 Mastperiece: Hide heavy engines from dumps to ensure DX sükunet 🛡️
        unset($info['rbn'], $info['discover']);

        $info['DNA_Active'] = ($this->module ? "[{$this->module}]" : "[Core]") . " Sovereign Identity";

        return $info;
    }

    /**
     * Universal Component Resolver 🛰️🏙️⚓
     * 
     * RBN 3.5: Strategic Proxy to the ComponentResolver Discovery cluster.
     */
    protected function component(string $type, ?string $target = null, bool $mandatory = true): ?object
    {
        // 🛡️ RBN 3.5: [GLOBAL SHOCK SENSOR] - Instant Loop Terminal 🏛️🚀⚓
        self::$callDepth++;

        if (self::$callDepth > 20) {
            // 🛡️ B-06: `rbn_panic` yalnızca `Core/Support/Bridges/Helpers/rbn_helpers.php`
            // içinde tanımlıdır ve composer `autoload_files`'ta DEĞİLDİR.
            // BaseComponent tek başına (framework bootstrapu olmadan) yüklenirse
            // tanımsız çağrı PHP `Error` (fatal) veriyordu. Tanımsızsa aynı
            // anlamı taşıyan \RuntimeException fırlatılır; tanımlıysa eski
            // davranış (panic ekranı) BİREBİR korunur.
            $mesaj = "Framework component depth limit exceeded (20). Last Request: [{$type}] -> [{$target}]";

            if (function_exists('rbn_panic')) {
                // NOT: `rbn_panic` void doner ve zaten panic aktifse erken cikar;
                // eski kodda da akis buradan DEVAM ediyordu. O yuzden `return`
                // EKLENMEDI - akis birebir korunur.
                rbn_panic($mesaj, "Global Recursion Detected");
            } else {
                throw new \RuntimeException($mesaj . ' (Global Recursion Detected)');
            }
        }

        try {
            return ComponentResolver::resolve($this, $type, $target, $mandatory);
        } finally {
            self::$callDepth--;
        }
    }

    /**
     * Magic Satellite Discovery (Lazy Loading) 🪄✨
     */
    public function __get(string $name)
    {
        if (isset(self::$resolvedSuffixes[$name])) {
            [$type, $target] = self::$resolvedSuffixes[$name];
            $value = $this->component($type, $target);
            if ($value !== null) {
                $this->{$name} = $value;
            }
            return $value;
        }

        // 🎼 RBN 3.5: Masterpiece Suffix Discovery (Delegated to ComponentTypes Single Source of Truth) 🎭
        $typeMap = \Rbn\Framework\Core\Support\Definitions\System\ComponentTypes::typeMap();

        foreach ($typeMap as $suffix => $type) {
            if (str_ends_with($name, $suffix)) {
                $target = substr($name, 0, -strlen($suffix));
                self::rememberResolvedSuffix($name, [$type, $target]);
                $value = $this->component($type, $target);
                if ($value !== null) {
                    $this->{$name} = $value;
                }
                return $value;
            }
        }

        return null;
    }
}
