<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Base\Patterns;

use Rbn\Framework\Core\Support\Contracts\Base\BaseProxyInterface;
use Rbn\Framework\Core\Base\Patterns\Traits\Proxy\DiscoveryProxyTrait;
use Rbn\Framework\Core\System\Discovery\Engine\DiscoveryEngine;
use Rbn\Framework\Core\Base\BaseComponent;
use Rbn\Framework\Core\Base\Services\BaseService;
use Rbn\Framework\Core\Support\Bridges\Helpers\Library\LogThrottle;

/**
 * BaseProxy - Unified entry gate for dynamic gateways 🪐🦾⚓
 * 
 * RBN 3.5: [MASTERPIECE EVOLUTION]
 * Artık bir BaseComponent mirasçısıdır. Tüm DNA yeteneklerine sahip,
 * ancak performans için "Lightweight" önyükleme kullanan zeki bir geçittir.
 */
class BaseProxy extends BaseComponent implements BaseProxyInterface
{
    use DiscoveryProxyTrait {
        __get as private proxyGet;
    }

    /**
     * Yutulan kesif hatası throttle anahtarı etiketi (proxy sınıfı eklenir).
     *
     * @see \Rbn\Framework\Core\Support\Bridges\Helpers\Library\LogThrottle
     */
    public const DISCOVERY_SWALLOWED_THROTTLE_TAG = 'proxy-discovery-swallowed';

    /**
     * KARAR 8 / AŞAMA 1 (GÖRÜNÜRLÜK) — yutulan kesif hatasının kaydı. 📊
     *
     * Ölü keşif yolu HER çağrıda tekrarlanır; sayaç olmadan log şişer.
     *
     * @var array<string, true>
     */
    private static array $discoverySwallowedLogged = [];

    /** Bu süreçte yutulan kesif hatası zaten kaydedildi mi? (ölçüm, davranış değil) */
    private function discoverySwallowRecorded(): bool
    {
        if (isset(self::$discoverySwallowedLogged[static::class])) {
            return true;
        }
        self::$discoverySwallowedLogged[static::class] = true;
        return false;
    }

    /**
     * Create a new proxy gate ⚓🧬
     * 
     * RBN 3.5: Optimized Component Initialization.
     * BaseComponent'in ağır boot sürecinden kaçınmak için constructor override edilir.
     * DNA (rbn, discover) lazy-load olarak BaseContextTrait üzerinden çözülür.
     * 
     * @param string $type The discovery target type
     */
    public function __construct(string $type)
    {
        // 🛡️ FW-GECE-BASE B-65: `parent::__construct()` BILINCLI cagrilmiyor
        // (Lightweight onyukleme; tam boot her proxy icin kimlik yansimasi
        // tetiklerdi). Ama DNA'nin koku (`$this->rbn`) hicbir zaman atanmiyordu:
        // dokumandaki "DNA (rbn, discover) lazy-load" vaadi tutmuyordu.
        // `discover()` zaten kendi null kontrolunu yapiyor; `rbn` icin ayni
        // minimal atama burada yapilir.
        $this->rbn = BaseService::get();

        // 🎼 Proxy-specific initialization (Discovery Engine Start)
        $this->initializeProxy($type);
    }

    /**
     * Unified Magic Discovery Hub 🪄✨
     * Merges Proxy Context Discovery with BaseComponent Satellite Discovery.
     *
     * 🛡️ KARAR 8 / AŞAMA 1 — GÖRÜNÜRLÜK (yalnız ölçüm).
     *
     * ÖLÇÜLEN GERÇEK: `DiscoveryProxyTrait` `ComponentRegistry`'yi
     * `System\Registries\...` namespace'inden çağırıyor; gerçek sınıf
     * `Kernel\Stages\...` ve onda `locate()` YOK. Bu yüzden `proxyGet()`
     * HER çağrıda `Error: Class ... not found` fırlatıyor ve burada sessizce
     * yutuluyor — yani **birincil proxy keşfi hiç çalışmıyor** ve çalışıyor
     * gibi görünüyor.
     *
     * AŞAMA 1'in tek işi görünürlük: yutulan hata **süreç başına, proxy sınıfı
     * başına BİR KEZ** `security` kanalına düşer (sınıf adı + mesaj; STACK
     * YAZILMAZ — stack her seferinde aynı ve dosya yolu sızdırır).
     *
     * GERİYE UYUM: dönüş değeri ve iki aşamalı çözümleme sırası BİREBİR AYNIDIR.
     * Bu satır hiçbir şeyi değiştirmez, yalnız görünür kılar.
     *
     * AŞAMA 2 (gerçek düzeltme — yanlış namespace'i bağlama) BİLİNÇLİ OLARAK
     * YAPILMADI: `AssetProxy` `BaseProxy`'i miras alıp `__get`/`__call`'u eziyor;
     * birincil yolu bağlamak TÜM asset çözümlemesini değiştirirdi. Ayrı karar.
     */
    public function __get(string $name)
    {
        try {
            // 1. [PRIMARY] Try Proxy-specific discovery (e.g. $asset->framework)
            return $this->proxyGet($name);
        } catch (\Throwable $e) {
            // 📊 AŞAMA 1: yutulan keşif hatasını BİR KEZ kaydet (davranış aynı).
            if (!$this->discoverySwallowRecorded()) {
                $this->reportSwallowedDiscovery($name, $e);
            }
            // 2. [SECONDARY] Fallback to BaseComponent satellites (e.g. $asset->AuthHandler)
            return parent::__get($name);
        }
    }

    /**
     * Yutulan keşif hatasını kaydet — yalnız ölçüm, hiçbir kontrolü değiştirmez. 📊
     *
     * `security` kanalı kullanılamazsa `error_log`'a düşer; ikisinde de yazma
     * BAŞARISIZ OLURSA çözümleme yine sessizce devam eder (fail-open, ölçüm katmanı).
     *
     * @param string     $name Çözülemeyen hedef adı (hassas veri DEĞİLDİR).
     * @param \Throwable $e    Yutulan hata (yalnız sinif + mesaj kullanilir).
     */
    private function reportSwallowedDiscovery(string $name, \Throwable $e): void
    {
        // [FW-LOG-GURULTU] Ölü keşif yolu HER İSTEKTE tekrarlanır; süreç içi
        // dizi PHP-FPM'de sıfırlandığı için saatlik kapı gerekir. Depolama
        // kullanılamazsa fail-closed: satır yazılmaz, istek bozulmaz.
        if (!LogThrottle::once(self::DISCOVERY_SWALLOWED_THROTTLE_TAG . ':' . static::class)) {
            return;
        }

        $satir = 'PROXY_DISCOVERY_SWALLOWED proxy=' . static::class
            . ' type=' . ($this->type ?? '?')
            . ' target=' . $name
            . ' error=' . get_class($e) . ': ' . $e->getMessage();

        try {
            $this->logs()?->channel('security')->notice('PROXY_DISCOVERY_SWALLOWED', [
                'proxy'  => static::class,
                'type'   => $this->type ?? null,
                'target' => $name,
                'error'  => get_class($e) . ': ' . $e->getMessage(),
                'mode'   => 'log_only',
            ]);
            return;   // kanal yazdirdi, error_log'a gerek yok
        } catch (\Throwable $kanalYok) {
            // Kanal acilamadi: asagida error_log'a dusulur.
        }

        // `security` kanali her zaman acilabilir degil (kapali proje, erken boot).
        // Olcum kaybi olmasin diye error_log'a da dusulur — daima acilir.
        error_log('[RBN] ' . $satir);
    }

    /**
     * Internal Bridge: Resolve discovery request via the Engine Singleton. 🌉🛰️
     */
    protected function resolveDiscovery(string $type, string $name)
    {
        return DiscoveryEngine::instance()->{$type}($name);
    }
}
