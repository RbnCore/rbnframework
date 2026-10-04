<?php
declare(strict_types=1);

namespace Rbn\Framework\Core\Services\Gatekeepers\Handlers;

use Rbn\Framework\Core\Base\BaseComponent;
use Rbn\Framework\Core\Database\Models\Master\MasterIpBlocksModel;
use Rbn\Framework\Core\Database\Repositories\Common\ShieldSettingsRepository;
use Rbn\Framework\Core\Http\Security\MachineApiRegistry;

/**
 * IpGuardHandler - The Gatekeeper Actor 🛡️🏮
 *
 * RBN 3.5: Atomic actor for validating IP blacklist and whitelist status.
 *
 * [A0-1 — LOG-ONLY MOD] 🎚️
 * Bu katman **ölüydü**: `provider('shieldDbSettings')` kayıtlı olmadığı için
 * `:22`'deki `getSetting()` çağrısı `Error` fırlatıyor, `:91 catch (\Throwable)`
 * onu yutuyor ve WAF/beyaz liste/kara liste hiç çalışmıyordu.
 *
 * A0-1 iki şeyi birlikte yapar:
 *  1. Okuma zincirini ayağa kaldırır (`shieldDbSettings` provider + FQCN alias).
 *  2. İlk sürümde **ENGELLEMEZ** — kararı üretir ve
 *     `Storage/logs/ipguard/*.jsonl` içine `would-block` olarak yazar.
 *
 * [FW-110 / 97-1 — ANAYASA §8 DÜZELTMESİ] 🏛️
 * A0-1'in okuma zinciri bir **Provider** kaydıyla çözülüyordu, ama o sınıf
 * (`ShieldDbSettingsProvider`) HİÇBİR DIŞ SERVİSE BAĞLANMIYORDU — tek işi
 * `repository('common.shieldSetting')` çağırmaktı. Anayasa §8 kural 1: "DB'ye
 * dokunan sınıf Provider adıyla/klasörüyle YAZILMAZ; Repository olur."
 * Provider **kaldırıldı**; bu katman artık doğrudan Repository'yi kullanır.
 * Mod ve kill-switch normalizasyonu TEK merkezde yaşar
 * (`ShieldSettingsRepository::normalizeIpGuardMode()` / `::normalizeSwitch()`).
 *
 * Neden log-only (ONARIM-PLANI §5.1): IP katmanı aylarca ölüydü, dolayısıyla
 * `ip_blocks` tablosundaki kayıtlar ESKİ; `enforce` açılsaydı meşru ziyaretçi
 * ve Google/bing botu engellenirdi. 48 saat log toplandıktan sonra gerçek yanlış
 * pozitif oranı çıkarılacak; `enforce` **AYRI bir yama/ayar**dır.
 *
 * Mod tek yerdedir: `shield_ip_guard_mode = log_only|enforce`
 * (varsayılan `log_only`; **yalnız tam `'enforce'` metni** zorlama sayılır —
 *  `1`/`true`/`on` log_only'ya düşer, güvenlik incelemesi 104 A0-1a).
 * Ayrıca `shield_ip_guard` ana kill-switch'i olarak çalışmaya devam eder.
 *
 * [A0-2'YE DOKUNULMADI] 🚫
 * Fail-closed'e çevirme (`:91`'de `DiagnosticException` re-throw) ve
 * `RBN_GUARD_FAILCLOSED` gölge kill-switch'i **ayrı görevdir**. Buradaki
 * `catch` kendi alanının hatasını **loglar** (sessiz yutma yok) ama
 * yeniden fırlatmaz — A0-1'in amacı 18 siteyi 500'e düşürmemektir.
 *
 * [B-10 / FW-BANU-BULGULAR-2] Ayar okuma HATASI artık fail-OPEN DEĞİL.
 * `check()` içindeki ayar okuma kendi `try/catch`'ine alındı: okunamazsa
 * mod = `log_only` (A0-1) + kill-switch = AÇIK varsayılır, `settings_unreadable`
 * kararı kaydedilir ve katman ÇALIŞMAYA DEVAM EDER. Ölçüm: `olc_b10.php`.
 *
 * @return array Karar kayıtları (log-only kanıtı + kabul testi ölçümü)
 */
class IpGuardHandler extends BaseComponent
{
    /**
     * Executes the IP based access check. 🛡️
     *
     * Dönüş değeri: karar kayıtlarının listesi. Her kayıt:
     *   ['rule' => string, 'decision' => allow|would-block|blocked|skipped|error,
     *    'ip' => string, 'project_id' => int, 'mode' => string, 'detail' => string]
     *
     * Çağıranlar (ShieldSentinel, IpGuardService) dönüş değerini yok sayar —
     * geriye dönük uyum bozulmaz.
     */
    public function check(): array
    {
        $decisions = [];
        $currentIp = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
        $projectId = function_exists('project_id') ? (int) project_id() : 0;

        try {
            // 0. Ayarları oku (artık NULL DEĞİL) + modu belirle 🎚️
            try {
                /** @var ShieldSettingsRepository $settings */
                $settings = $this->repository('common.shieldSetting');
                // [FW-110 / 97-1] Anayasa §8: bu katman DB'ye dokunan tek sinf
                // Repository'dir. `shieldDbSettings` PROVIDER'i kaldirildi (dIs servise
                // baglanmiyordu; §8 kural 1 ihlali). Mod + kill-switch normalizasyonu
                // TEK merkezde: ShieldSettingsRepository::normalizeIpGuardMode() /
                // ::normalizeSwitch() (fail-closed).
                //
                // [B-10 / FW-BANU-BULGULAR-2] AYARIN OKUNAMASI FAIL-OPEN OLMAZ.
                // Ölçüldü (`olc_b10.php`, gerçek `check()` çalıştırıldı): `repository()`
                // DB'ye ulaşamayınca (Connection refused) istisna `catch (\Throwable)`
                // düşüyor ve katman `['ip_guard_error']` ile SESSİZCE hiçbir koruma
                // üretmeden dönüyordu — kill-switch'e bile bakılmadan, `would-block`
                // bile yazılmadan. Yani "kapatma değeri okunamayınca koruma devre dışı".
                // Düzeltme: okuma kendi çerçevesinde; hata olursa **varsayılan**
                // kararlar korunur (mod = A0-1 `log_only`, kill-switch = AÇIK) ve
                // katman ÇALIŞMAYA DEVAM EDER — en azından 48 saatlik ölçüm
                // (`would-block` logları) kaybolmaz.
                $mode = ShieldSettingsRepository::normalizeIpGuardMode(
                    $settings->getSetting(ShieldSettingsRepository::KEY_IP_GUARD_MODE, ShieldSettingsRepository::MODE_LOG_ONLY)
                );
                $killSwitchAcik = ShieldSettingsRepository::normalizeSwitch(
                    $settings->getSetting(ShieldSettingsRepository::KEY_IP_GUARD, true),
                    true
                );
            } catch (\Rbn\Framework\Core\Support\Exceptions\DiagnosticException $e) {
                throw $e;
            } catch (\Throwable $e) {
                $mode = ShieldSettingsRepository::normalizeIpGuardMode(null); // = log_only
                $killSwitchAcik = true;
                $decisions[] = $this->decision(
                    'settings_unreadable',
                    'fallback',
                    $currentIp,
                    $projectId,
                    $mode,
                    'ayar okunamadi; varsayilan koruma DEVAM: ' . get_class($e) . ': ' . $e->getMessage()
                );
                $this->logWarning('settings_unreadable', [
                    'exception' => get_class($e),
                    'message'   => $e->getMessage(),
                    'mode'      => $mode,
                    'not'       => 'kill-switch ACIK varsayildi (fail-OPEN YOK); katman log_only ile calisiyor.',
                ]);
            }
            $isEnforce = ($mode === ShieldSettingsRepository::MODE_ENFORCE);

            // 0.b Global Kill-Switch (`shield_ip_guard`) 🛡️
            if (!$killSwitchAcik) {
                $decisions[] = $this->decision('kill_switch', 'skipped', $currentIp, $projectId, $mode, 'shield_ip_guard kapali');
                return $decisions;
            }

            // IpGuardService Satellites
            $ipGuardService = $this->service('ipGuard');
            if (!$ipGuardService) {
                $decisions[] = $this->decision('service', 'skipped', $currentIp, $projectId, $mode, 'ipGuard service cozulemedi');
                return $decisions;
            }

            if ($currentIp === '') {
                $decisions[] = $this->decision('remote_addr', 'skipped', $currentIp, $projectId, $mode, 'REMOTE_ADDR bos');
                return $decisions;
            }

            // 0.4. Whitelist Check (Highest Priority) — kayitli alias 🕊️
            //
            // [FW-IP-ENFORCE-HAZIRLIK · Ö-4 · 2026-10-03 · zeki-6eb7f5] SIRA
            // DUZELTMESI: bu kontrol `bot_user_agent` kuralindan SONRA
            // calisiyordu. Sonuc: beyaz listedeki IP `sitebot` UA ile
            // geldiğinde bot kurali onu `would-block` (enforce'te 403 + ban)
            // ediyordu — beyaz liste bu IP'yi `ip_blacklist`'ten koruyor ama
            // BOT kuralindan korumuyordu (tutarsizlik; yonetici karari olan
            // "guvenilir" tanimi yarida uygulaniyordu).
            //
            // KARAR (brif oneri + gerekce): beyaz liste = "guvenilir" (yonetici
            // karari) -> `bot_user_agent` ve `ip_blacklist` kurallarindan
            // MUAF. **WAF MUAF DEGILDIR**: WAF bir IP-itibar kurali degil,
            // istek YUZEYI taramasidir; guvenilir bir IP'den (ofis/CI/
            // ele gecirilmis makine) gelen SQLi/XSS/traversal denemesi de
            // yakalanmalidir. Bu yuzden akis `return` etmez, WAF adimi
            // calismaya DEVAM eder.
            //
            // [A0-1] `model(FQCN)` calismaz: NamespaceResolver FQCN'yi
            // 'Rbn\Framework\Core\Database\Models\Master\...' olarak bolup
            // hicbir katmana isletmiyor -> NULL. Bu yuzden ONARIM-PLANI'nin
            // "FQCN kullan (SystemGuardHandler:70 gibi)" onerisi YANLISTIR;
            // olculen yol kayitli alias. (Bkz. rapor "YENI BULGU".)
            $isWhitelisted = $this->model('master.ipWhitelist')
                ->where('ip_address', '=', $currentIp)
                ->exists();

            if ($isWhitelisted) {
                $decisions[] = $this->decision('whitelist', 'allow', $currentIp, $projectId, $mode, 'beyaz listede');
            }

            // 0.5. Bot Access Prevention Check (Early Security Layer) 🤖⛔
            // Beyaz listedeki IP muaftir (0.4 karari).
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            if (!$isWhitelisted && $ipGuardService->isBlockedBot($userAgent)) {
                if (!$isEnforce) {
                    $decisions[] = $this->wouldBlock('bot_user_agent', $currentIp, $projectId, $mode, 'UA: ' . substr((string) $userAgent, 0, 120));
                    return $decisions;
                }
                $decisions[] = $this->decision('bot_user_agent', 'blocked', $currentIp, $projectId, $mode, 'UA: ' . substr((string) $userAgent, 0, 120));
                header('HTTP/1.1 403 Forbidden');
                echo "Access Denied: Crawler blocked by security policy.";
                exit;
            }

            // 0.6. AJAN UCU MUAFIYETI 🤖🔓
            //
            // [FW-IP-ENFORCE-HAZIRLIK · Ö-2 · 2026-10-03 · zeki-6eb7f5]
            // `/api/agent/*` ve `/api/telegram/webhook` uclari kendi kimlik
            // dogrulamasi ve kendi hiz siniriyla geliyor; IP katmani ban/WAF
            // uyguladiginda ajan SUSUR (B-1 form-encoded govde, B-4 ban
            // kaydi). Muafiyet yalniz bu iki yol grubundadir ve
            // `IpGuardService::isAgentEndpoint()` tarafindan normalize edilir
            // (yol hilesiyle BYPASS edilemez).
            //
            // BOT KURALI (0.5) DOKUNULMADI: `BLOCKED_BOTS` listesinde Go UA
            // yok, ajan yolunda anlamsiz.
            //
            // [FW-APIGUARD · TASARIM GOREV 1 · 2026-10-03 · zeki-6eb7f5]
            // MUAFIYET ARTIK BEYANDAN BESLENIR: once `MachineApiRegistry`
            // (beyan tablosu, `machine-api`), sonra ESKI EMNIYET olarak sabit
            // `isAgentEndpoint()` listesi. Sabit liste SILINMEDI: yeni ajan
            // henuz beyan etmeden once mevcut davranis bit-bit ayni kalir
            // ("beyan yoksa eski davranis" kurali).
            // Ikisi de ayni normalize kurallarini paylasir (`..`, `//`, `\`,
            // buyuk harf, sorgu dizesi hilesi) -> bypass testleri ayni sonucu
            // vermeye devam eder.
            $beyan = MachineApiRegistry::match((string) ($_SERVER['REQUEST_URI'] ?? ''));
            $beyanMuaf = ($beyan !== null && (bool) ($beyan['ip_exempt'] ?? false));
            $isAgentEndpoint = $beyanMuaf
                || $ipGuardService->isAgentEndpoint((string) ($_SERVER['REQUEST_URI'] ?? ''));
            if ($isAgentEndpoint) {
                $decisions[] = $this->decision(
                    'agent_endpoint',
                    'skipped',
                    $currentIp,
                    $projectId,
                    $mode,
                    'muaf: ajan ucu (kendi kimlik dogrulamasi + hiz siniri var)'
                    . ($beyan !== null
                        ? '; beyan kapsami: ' . (string) $beyan['scope']
                        : '; beyan yok, sabit liste')
                );
            } elseif ($beyan !== null) {
                // [FW-APIGUARD · madde 5] Beyanli ama muafiyeti HENUZ VERILMEMIS
                // uc (crew/worker). Kayit yazilir, koruma KALIR: muafiyet
                // anahtari tek satirlik bir karardir ve gozlem verisi
                // toplanmadan acilmaz (tasarim raporu §4).
                $decisions[] = $this->decision(
                    'agent_endpoint',
                    'skipped',
                    $currentIp,
                    $projectId,
                    $mode,
                    'beyanli makine ucu (' . (string) $beyan['scope'] . ') ama IP muafiyeti ACILMADI: '
                        . 'WAF/ban kararlari bu yolda OLDUGU GIBI uygulanir (A-11 gözlemi bekliyor)'
                );
            }

            // 1. WAF (Web Application Firewall) Security Scan 🛡️🔥
            // [Ö-4] Beyaz liste WAF'tan MUAF DEGILDIR (0.4 gerekcesi): guvenilir
            // IP'ye ozel bir erisim/sifirla kurali degildir, istek yuzeyi
            // taramasidir.
            $attackType = $ipGuardService->detectAttack();
            if ($attackType && !$isAgentEndpoint) {
                // LOG-ONLY: ban YAZILMAZ. Yazmak, 48 saatlik gözlemi
                // kendi verisiyle bozardi (yeni kayit = gecmis hata).
                if (!$isEnforce) {
                    $decisions[] = $this->wouldBlock('waf_attack', $currentIp, $projectId, $mode, $attackType);
                    return $decisions;
                }

                // [G-07 · 2026-10-03 · zeki-6eb7f5] CEZA ARTIK KADEMELI VE
                // PROJE KAPSAMLI. Onceki hali:
                //     blockIpAddress($ip, "WAF ...", 10080, 0)
                // yani **7 GUN + GLOBAL** ban. Iki ayri sorun birden:
                //   (1) KAPSAM: ban sorgusu `project_id = 0 VEYA = <aktif
                //       proje>` oldugu icin `0` yazmak ayni NAT IP'sindeki TUM
                //       projeleri bu IP'ye kapatiyordu — saldirgan degil KURBAN
                //       (ofis/CI/hotspot) banlaniyordu. FormGuard yolu bunu
                //       2026-10-03'te duzeltti (O-3, `fd6b635`); IP katmani
                //       **hala eskiydi**.
                //   (2) SURE: 10080 = 7 GUN kalici. Tek bir yanlis pozitif
                //       (`/blog/order by 1`, `/destek/alert(acilir)` gibi meşru
                //       bir URL) 7 günluk site kapatmaya yetiyordu.
                // Duzeltme: kademe `IpGuardService::progressiveBlockMinutes()`
                // (aktif ban sayisindan; 15 dk / 1 saat / 24 saat), kapsam
                // AKTIF PROJE. Proje kimligi cozulemezse 0 yazilir (fail-closed
                // = eski davranis; sessiz daraltma olmaz).
                // KATEGORILER KORUNUR: yasak yine `master.ipBlock` +
                // `common.ipBlock`e yazilir; yalniz SURE ve KAPSAM degisti.
                $dakika = $ipGuardService->progressiveBlockMinutes($currentIp);
                $projeId = function_exists('project_id') ? (int) project_id() : 0;

                $ipGuardService->blockIpAddress(
                    $currentIp,
                    "WAF Saldırı Engelleme: " . $attackType . " (kademeli ceza)",
                    $dakika,
                    $projeId
                );

                $decisions[] = $this->decision(
                    'waf_attack',
                    'blocked',
                    $currentIp,
                    $projectId,
                    $mode,
                    $attackType . ' | kademeli ceza: ' . $dakika . ' dk, proje kapsamli (proje_id=' . $projeId . ')'
                );
                header('HTTP/1.1 403 Forbidden');
                echo "Access Denied: Dangerous request payload detected by firewall.";
                exit;
            }

            // 2. Blacklist Check (Global or Local) — kayitli alias 🚫
            // [Ö-4] Beyaz listedeki IP bu kuraldan da muaftir (0.4 karari).
            if ($isWhitelisted) {
                $decisions[] = $this->decision('ip_blacklist', 'allow', $currentIp, $projectId, $mode, 'beyaz listede (ban muaf)');
                return $decisions;
            }

            /** @var MasterIpBlocksModel $model */
            $model = $this->model('master.ipBlock');

            // [A0-1 YENI BULGU] `->group(callable)` QueryBuilder'da YOKTUR;
            // ic ice kosul API'si `where(Closure)`'dir (ConditionTrait.php:15).
            // Eski `->group(...)` cagrisi undefined-method hatasi veriyordu ve
            // `catch (\Throwable)` onu yutup kara liste kontrolunu HICBIR
            // calistirmiyordu. Bu, G-01'in 3. kok nedeni (alias'larin yaninda).
            $blockedRow = $model->where('ip_address', '=', $currentIp)
                ->where('blocked_until', '>', now())
                ->where(function ($q) use ($projectId) {
                    $q->where('project_id', '=', 0) // Global
                        ->orWhere('project_id', '=', $projectId); // Local
                })
                ->first();

            if ($blockedRow && $isAgentEndpoint) {
                // [Ö-2] Ajan ucu: ban ENGELLENMEZ, yalniz muaflik izi yazilir.
                $decisions[] = $this->decision(
                    'ip_blacklist',
                    'skipped',
                    $currentIp,
                    $projectId,
                    $mode,
                    'muaf: ajan ucu (ban satiri var ama uzerinde durulmadi)'
                );
            } elseif ($blockedRow) {
                $detail = 'reason: ' . (string) (is_array($blockedRow) ? ($blockedRow['reason'] ?? '') : ($blockedRow->reason ?? ''))
                    . ' | blocked_until: ' . (string) (is_array($blockedRow) ? ($blockedRow['blocked_until'] ?? '') : ($blockedRow->blocked_until ?? ''))
                    . ' | kayit_proje_id: ' . (string) (is_array($blockedRow) ? ($blockedRow['project_id'] ?? '') : ($blockedRow->project_id ?? ''));

                if (!$isEnforce) {
                    $decisions[] = $this->wouldBlock('ip_blacklist', $currentIp, $projectId, $mode, $detail);
                    return $decisions;
                }

                $decisions[] = $this->decision('ip_blacklist', 'blocked', $currentIp, $projectId, $mode, $detail);

                // [A0-1 YENI BULGU] `$this->shield` yalnizca
                // `BaseDiscoveryContext::triggerDiagnostic()` icinde tembel
                // atanir; normal akista NULL kalir. Eski kod `$this->shield->
                // diagnostic(...)` yaziyordu -> enforce modunda "Call to a
                // member function diagnostic() on null" -> catch -> sessiz
                // gecis, YANI ENGELLEME YINE TUTMADI. Global `shield()`
                // fabrikasi her zaman guvenli (system_helpers.php:11).
                $hub = $this->shield ?? (function_exists('shield') ? shield() : null);
                if ($hub) {
                    $hub->diagnostic(
                        'Erişim Engellendi',
                        "IP adresiniz ({$currentIp}) sistem tarafından güvenlik gerekçesiyle engellenmiştir.",
                        'Eğer bunun bir hata olduğunu düşünüyorsanız, lütfen sistem yöneticisi ile iletişime geçin.'
                    );
                }
                return $decisions;
            }

            $decisions[] = $this->decision('ip_blacklist', 'allow', $currentIp, $projectId, $mode, 'aktif ban yok');

            return $decisions;

        } catch (\Rbn\Framework\Core\Support\Exceptions\DiagnosticException $e) {
            throw $e;
        } catch (\Throwable $e) {
            // [A0-2 DEĞİL] Sessiz yutma kaldırıldı: hata ARTIK kayda girer.
            // Yeniden fırlatma A0-2'nin işi (gölge kill-switch ile).
            $decisions[] = $this->decision(
                'ip_guard_error',
                'error',
                $currentIp,
                $projectId,
                ShieldSettingsRepository::MODE_LOG_ONLY,
                get_class($e) . ': ' . $e->getMessage()
            );
            $this->logError('ip_guard_error', [
                'exception' => get_class($e),
                'message'   => $e->getMessage(),
                'file'      => $e->getFile() . ':' . $e->getLine(),
                'not'       => 'A0-2 fail-closed ayri gorev; burada yalnizca KAYIT',
            ]);

            return $decisions;
        }
    }

    /**
     * 'would-block' kararını üretir ve Storage/logs/ipguard altına yazar. 📜🎚️
     *
     * IP maskelenmez: bunlar KENDİ sunucularımızın logları ve 48 saatlik
     * ölçümün amacı yanlış-pozitif oranını doğru çıkarmaktır; maskeleme
     * ölçümü bozar. (Ayrıntı görevte: "IP'yi maskele? yerel IP'ler olduğu gibi".)
     */
    private function wouldBlock(string $rule, string $ip, int $projectId, string $mode, string $detail): array
    {
        // [FW-IPKATMAN-ON · 2026-10-03 · zeki-6eb7f5] LOOPBACK MUAFIYETI.
        // 48 saatlik olcumun amaci GERCEK ziyaretci yanlis-pozitif oranini
        // cikarmaktir; yerel dongu trafigi (duman testi / curl / ayni sunucudan
        // gelen saglik-cron) o orani kendi verisiyle bozuyordu (bir dosyada 724
        // satirin 724'u 127.0.0.1). Loopback burada `would-block` YAZMAZ.
        // KAPSAM: YALNIZ 127.0.0.0/8 + ::1 + IPv4-eslemeli karsiliklari
        // (`PreBoot::isLoopbackAddress()`); RFC1918/link-local/ULA MUAF DEGILDIR.
        // Loopback disinda her sey oldugu gibi calisir -> olcum bozulmaz.
        // MOD MANTIGI DEGISMEDI (`$mode` ve log_only/enforce ayrimi ayni).
        if (\Rbn\Framework\Core\System\Kernel\Base\PreBoot::isLoopbackAddress($ip)) {
            return $this->decision($rule, 'skipped', $ip, $projectId, $mode, 'loopback muafiyeti: ' . $detail);
        }

        $this->logWouldBlock($rule, $ip, $projectId, $mode, $detail);
        return $this->decision($rule, 'would-block', $ip, $projectId, $mode, $detail);
    }

    /**
     * @return array Tek karar kaydı
     */
    private function decision(string $rule, string $decision, string $ip, int $projectId, string $mode, string $detail): array
    {
        return [
            'rule'       => $rule,
            'decision'   => $decision,
            'ip'         => $ip,
            'project_id' => $projectId,
            'mode'       => $mode,
            'detail'     => $detail,
            'ts'         => date('c'),
        ];
    }

    /**
     * 'would-block' satırını yazar: hangi IP, hangi kural, hangi proje. 📜
     */
    private function logWouldBlock(string $rule, string $ip, int $projectId, string $mode, string $detail): void
    {
        $this->logWarning('would-block', [
            'rule'       => $rule,
            'ip'         => $ip,
            'project_id' => $projectId,
            'project'    => function_exists('project_key') ? (string) project_key() : '',
            'mode'       => $mode,
            'detail'     => $detail,
            'not'        => 'LOG-ONLY: istek ENGELLENMEDI. 48 saatlik olcum icin kayit.',
        ]);
    }

    private function logWarning(string $message, array $context): void
    {
        try {
            $this->logs()?->channel('ipguard')->warning($message, $context);
        } catch (\Throwable) {
            // Log yazimi guvenlik kararini degistirmez; sessizce gecilmez ama kirilmaz.
        }
    }

    private function logError(string $message, array $context): void
    {
        try {
            $this->logs()?->channel('ipguard')->error($message, $context);
        } catch (\Throwable) {
            // Ayni gerekce.
        }
    }
}
