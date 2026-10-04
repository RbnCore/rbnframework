<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\System\Kernel\Stages;

use Rbn\Framework\Core\System\Kernel\Kernel;
use Rbn\Framework\Core\System\Kernel\Base\BaseStage;
use Rbn\Framework\Core\System\Registries\SystemRegistry;
use Rbn\Framework\Core\Support\Bridges\Helpers\Library\LogThrottle;

/**
 * ComponentRegistry - Exception handlers, Aliases, Helpers, and Storage 🏛️🎭⚓
 * 
 * RBN 3.5 Stage: Hierarchically resolves framework components via Triple Gates.
 * Now purely leverages the Universal map() Orchestrator for total autonomy.
 */
class ComponentRegistry extends BaseStage
{
    /**
     * [S-09] Tanılama günlüğü saatlik kapı etiketleri (tek merkez).
     *
     * Bu kapılar yalnız GÖRÜNÜRLÜK içindir: hiçbir yolu düşürmez, istisna
     * fırlatmaz, yan etki yaratmaz. Anahtar yalnız sabit etiket + sınıf/öğe
     * adı taşır (değer, proje anahtarı veya istek verisi YOK — Anayasa §9).
     */
    private const THROTTLE_STAGE_SERVICE = 'S09_STAGE_SERVICE_UNRESOLVED';
    private const THROTTLE_EXCEPTION_SERVICE = 'S09_EXCEPTION_SERVICE_UNREGISTERED';
    private const THROTTLE_ALIAS_ORIGINAL = 'S09_ALIAS_ORIGINAL_MISSING';
    private const THROTTLE_ALIAS_TARGET = 'S09_ALIAS_TARGET_ALREADY_EXISTS';

    public function handle(Kernel $kernel): void
    {
        // 0. Initialize Orchestration 🛰️⚙️
        $services = $this->getStageService();
        if (!$services) {
            // [S-09] Bu dal normalde ERİŞİLEMEZ (`BaseService::get()` her zaman
            // bir nesne döner). Yine de sessizce geçmiyordu: çözülemediği
            // görünür kılınır, istek DÜŞÜRÜLMEZ (PreflightException YOK).
            $this->reportUnresolved(self::THROTTLE_STAGE_SERVICE, 'stage-service');

            return;
        }
        $kernel->set('services', $services);

        // 1. Exception Service Orchestration 🔱🧬⚡
        $exceptionService = $services->service('exception');
        if ($exceptionService && method_exists($exceptionService, 'register')) {
            $exceptionService->register();
        } else {
            // [S-09] `exception` servisi çözülemedi/yok (`register` metodu
            // yok). Önceden hiçbir iz bırakmıyordu; teşhisi zorlaştırıyordu.
            // Yalnız log: istek akışı DEĞİŞMEZ.
            $this->reportUnresolved(self::THROTTLE_EXCEPTION_SERVICE, 'exception-service');
        }

        // 2. Aliases (Unified Fusion via Root DNA) 🎭⚓
        $this->registerAliases();
    }

    /**
     * Registers cross-framework class aliases 🎭🛰️
     */
    private function registerAliases(): void
    {
        // 🥇 RBN 3.5: Use the Universal Map Orchestrator from SystemRegistry
        // (This already consolidates System VIP + Modular Aliases via inheritance) 🧬🏛️
        $aliases = (new SystemRegistry())->registerMap()['aliases'] ?? [];

        foreach ($aliases as $original => $aliasList) {
            // [RBN SECURITY BARRIER] 🛡️⚓
            // Only attempt aliasing if the original class actually exists!
            if (!is_string($original) || !class_exists($original)) {
                // [S-09] Kayıt VAR ama kaynak sınıf yok → alias üretilemez.
                // Önceden tamamen sessizdi; şimdi saatte bir görünür.
                $this->reportUnresolved(self::THROTTLE_ALIAS_ORIGINAL, (string) $original);

                continue;
            }

            if (is_string($aliasList)) {
                $aliasList = [$aliasList];
            }

            foreach ($aliasList as $alias) {
                if (!is_string($alias)) {
                    continue;
                }

                if (class_exists($alias)) {
                    // [S-09] Hedef ad zaten KULLANIMDA: `class_alias` bilinçli
                    // olarak atlanıyordu (meşru durum: başka bir sınıf adı
                    // önceden tanımlanmış olabilir). Sessiz geçiş yerine iz.
                    $this->reportUnresolved(self::THROTTLE_ALIAS_TARGET, $original . '->' . $alias);

                    continue;
                }

                class_alias($original, $alias);
            }
        }
    }

    /**
     * [S-09] Sessiz no-op'ları görünür kılar (yalnız `error_log`, saatlik kapı).
     *
     * SÖZLEŞME:
     *   - İstisna FIRLATMaz, yan etki YOKTUR, istek düşmez.
     *   - `LogThrottle::once()` false dönerse (TTL dolu veya depolama yok)
     *     HİÇBİR şey yazılmaz → gürültü artmaz.
     *   - Yazılacak satır yalnız sabit etiket + sınıf/öğe adı içerir; proje
     *     adı, anahtar, kullanıcı/IP/istek verisi ASLA girmez (Anayusa §9).
     */
    private function reportUnresolved(string $tag, string $subject): void
    {
        try {
            if (!LogThrottle::once($tag . ':' . static::class)) {
                return;
            }
        } catch (\Throwable) {
            // Kapı katmanı bozulursa yine de sessizce geç (ölçüm katmanı
            // kararı bozamaz); aşağıdaki `error_log` yedeği çalışır.
        }

        $satir = 'COMPONENT_REGISTRY_UNRESOLVED tag=' . $tag
            . ' class=' . static::class
            . ' subject=' . $subject;

        if (function_exists('error_log')) {
            @error_log('[RBN][S-09] ' . $satir);
        }
    }
}
