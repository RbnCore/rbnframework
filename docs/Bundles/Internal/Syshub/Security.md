# Bundles/Internal/Syshub/Security — Güvenlik merkezi (firewall, IP blok, whitelist, rate limit)

> **Doğrulanan kod tabanı:** `d49b4413` (dal `feat/fw-license-master`) · **Tarih:** 2026-10-05 · **Yayın:** 0.9.4 = bu commit + sonrası; belge yalnız doğrulama anındaki kodu anlatır
> **Kaynak klasör:** `Bundles/Internal/Syshub/Controllers/Security/` (5 `*.php`) +
> ilgili 3 provider + 1 handler + 5 görünüm.
> **Envanter:** 5 controller dosyasının 5'i anlatıldı.

## 1. Ne işe yarar, kimler kullanır

`syshub/security/*` altındaki dört bağımsız güvenlik yüzeyini yönetir:
**güvenlik panosu** (genel bakış), **firewall anahtarları**, **IP engelleme**,
**güvenilir IP listesi** ve **hız sınırlama kayıtları**. Hepsinin verisi
`cm_sys_settings_shield` ve `cm_sys_ip_blocks` / `cm_sys_rate_limits` tablolarında,
IP whitelist'in master karşılığı `master.ipWhitelist`'tedir.

**Kimler çağırır:** geliştirici paneli. Alt modüller `WebhubMap`/`SyshubMap`
karşılığında `SyshubMap.php:32-41` alt alt modül olarak tanımlıdır; rota grubu
`ModuleData.php:125-158`'de.

## 2. Klasör/dosya envanteri

### 2.1 `Controllers/Security/` (5)

| Dosya | Görev | Önemli public yöntemler |
|---|---|---|
| `SecurityController.php` | `#[SubModule(entity:'security', service:'syshub', handler:'syshub')]`. Tek satırlık zayıf kontrolör: tüm veri derleme handler'a devredilir. | `index(): void` (:22) |
| `FirewallController.php` | `#[SubModule(entity:'firewall', service:'syshub', handler:'syshubSecurity')]`. Firewall anahtarlarının okunması ve **toplu veya tekli** yazılması. | `index(): void` (:24), `updatesetting(): void` (:39) |
| `IpBlockController.php` | `#[SubModule(entity:'security/ipblock', …, modal:'Security/Partials/modal_ip_block')]`. Manuel IP engeli listesi ve ekleme/temizleme. | `index(): void` (:25), `store(): void` (:48), `clearAll(): void` (:70) |
| `IpWhitelistController.php` | `#[SubModule(entity:'security/ipwhitelist', …, modal:'Security/Partials/modal_whitelist')]`. Güvenilir IP listesi. | `index(): void` (:25), `store(): void` (:47), `destroy(string $ip): void` (:67) |
| `RateLimitsController.php` | `#[SubModule(entity:'security/ratelimits', …)]`. Hız sınırı kayıtları (hatalı giriş denemeleri). | `index(): void` (:24), `deleteIP(int $id): void` (:44), `bulkDeleteIP(): void` (:53), `clearAll(): void` (:68) |

### 2.2 Veri katmanı (3 provider + 1 handler, bu alt dalın dışında ama ona hizmet eder)

| Dosya | Bu alt daldaki rolü |
|---|---|
| `Providers/SyshubFirewallProvider.php` | `firewall_enabled`, `firewall_mode` anahtarlarının tek yazma yolu + önbellek düşürme |
| `Providers/SyshubWhitelistProvider.php` | `master.ipWhitelist` ∪ `cm_sys_settings_shield.maintenance_ips` birleşimi |
| `Providers/SyshubRateLimitProvider.php` | `common.rateLimit` kayıtları, istatistikler, silme |
| `Handlers/SyshubSecurityHandler.php` | Panel istatistik ağacı + **`enrich()` GeoIP zenginleştirmesinin tek yeri** |

## 3. Akış

```
GET syshub/security
  → SecurityController::index()                       SecurityController.php:22
      → $this->service->security()  ⇒ SyshubSecurityHandler      :26
          → handler('syshub')->getDashboardStats()['security']    SyshubSecurityHandler.php:30
          → buildStatsTree():  whitelist>count, rateLimit>getStats,
                                repository('common.ipBlock')->all()  :51-53
          → getEnrichedRecentAttempts(): son 5 kayıt + enrich()  :75-78
      → render('Security/index', $dashboardData)      :29

POST syshub/security/ipblock/create
  → IpBlockController::store()                        IpBlockController.php:48
      → request->form([ip_address|reason|duration => required])
      → $this->service->ipBlock() ⇒ repository('common.ipBlock')   SyshubService.php:64
      → IpBlockRepository::block($ip,$reason,(int)$duration)        IpBlockRepository.php:36
      → handleResult($result, "IP ({$ip})")

POST syshub/security/ipwhitelist/create
  → IpWhitelistController::store()                    IpWhitelistController.php:47
      → whitelist()->add($ip,$label)                  SyshubWhitelistProvider.php:41
          → model('common.shieldSetting')->where('setting_key','maintenance_ips')
          → yoksa create(), varsa update()  (virgüllü liste olarak saklanır)
```

`updatesetting()` iki senaryoyu tek uçta toplar (`FirewallController.php:42-58`):
form `settings` dizisi gönderdiyse **toplu**, `id`+`value` gönderdiyse **tekli**
güncelleme. Tekli dalda `handleResult`'a 4. parametre olarak `'status'` açıkça
verilir; aksi hâlde `ActionControllerTrait`'in sezgisel fiil eşlemesi
`updatesetting` adını `update` olarak yorumlar (`ActionControllerTrait.php:159-177`).

## 4. Yapılandırma / ayar anahtarları

| Anahtar | Yer | Varsayılan | Not |
|---|---|---|---|
| `firewall_enabled` | `cm_sys_settings_shield` | `'0'` | `SyshubFirewallProvider.php:90`; `'1'` dışında her şey kapalı sayılır |
| `firewall_mode` | aynı | `'standard'` | Serbest metin; panel yalnız gösterir |
| `maintenance_ips` | aynı | `''` | Virgüllü liste; **bakım modu izinleri ve bu ekranın whitelist'i aynı satırdır** |
| Değer tipi | aynı | — | `performUpdate()` dizi gelirse `json_encode` + `value_type='json'`; tekil değerde `value_type='boolean'` **her zaman** yazılır (`SyshubFirewallProvider.php:57,67`) — bu bir tutarsızlık, `boolean` şeması karşılığı olmayan değerler için yanlış etiketlenir |

## 5. Tuzaklar ve kurallar

1. **İki ayrı IP kaynağı, tek liste.** `getList()` master kayıtlarını `source='Global (Master)'`,
   ayar kayıtlarını `source='Project Settings'` ve **sanal id** `s_<index>` ile işaretler
   (`SyshubWhitelistProvider.php:29,114,117`). Bu sanal id'ler gerçek kayıt id'si olmadığından
   `destroy()` yalnız ayar tarafını siler (`IpWhitelistController::destroy` → `remove()`), master
   kaydı kalır. Ekranda tek "sil" butonu iki farklı etki gösterir.
2. **`add()` yinelenen IP'de `true` döner.** `in_array($ip,$ipList)` isabetinde metod başarıyla
   çıkar (`SyshubWhitelistProvider.php:50-52`); kullanıcı "eklendi" sanar, kayıt değişmez.
3. **`remove()` boş listeye izin verir.** `array_filter` sonrası boş string `implode` ile
   `setting_value`'ya yazılır (`SyshubWhitelistProvider.php:88`); bir sonraki `add()` bunu
   `explode` ile `['']`e böler, boş eleman `getIpsFromSettings()`da elenir (:110-112).
4. **Önbellek iki yerden düşürülüyor.** `performUpdate()` (`SyshubFirewallProvider.php:73`) ve
   `SyshubMaintenanceProvider::save()` (:92) aynı `ShieldSettingsRepository::settingsCacheKey()`
   anahtarını siler. İkisi de `try/catch` içinde; düşmezse panel bir sonraki istekte eski değeri görür.
5. **GeoIP zenginleştirmesi iki yerde tekrarlanıyor.** `SyshubRateLimitProvider::getRecords()`
   kayıt döngüsünde kendi eşlemesini yapar (:39-47), `SyshubSecurityHandler::enrich()` aynı işi
   yapar (:85-95). `IpBlockController` ve `IpWhitelistController` kayıtları `enrich()` ile
   geçirir; `RateLimitsController` **yalnız** `getRecords()`'a güvenir. Tek nokta değil.
6. **`repository('common.ipBlock')` bir repository döndürür, model değil.** `SyshubService::ipBlock()`
   `provider()` değil `repository()` çağırır (`SyshubService.php:64`); bu yüzden
   `IpBlockController` üzerinde `getBlocks()/block()/getStats()/clearAll()` vardır ama
   `getRecords()/getStats():array` imzaları `IpBlockRepository`'den gelir
   (`IpBlockRepository.php:26,36,56,64`).
7. **`buildStatsTree()` bir alanı yanlış okuyor.** `$ipStats = repository('common.ipBlock')->all()`
   bir **dizi**, ama `$ipStats['active_blocks']` bir **nesne anahtarı** olarak okunuyor
   (`SyshubSecurityHandler.php:53,65`) → her zaman 0 döner.
8. **`security` rotasının yazma ucu trait'ten gelir.** `Route::post('save','update')`
   (`ModuleData.php:134`) `SecurityController`'da `update()` yoktur; `CrudControllerTrait::update()`
   devreye girer. Aynı şekilde `modal/{id?}` ucu `ActionControllerTrait::modal()` (:251).
9. **`Route::post('delete/{id}','delete')` ucu controller'da yok.** `IpBlockController` yalnız
   `clearAll()` tanımlar (`IpBlockController.php:70`); `delete` eylemi trait'ten gelir.

## 6. Örnek (gerçek koddan)

```php
// Bundles/Internal/Syshub/Controllers/Security/FirewallController.php:42-58 (özetlenmiş)
if ($this->request->has('settings')) {
    $data   = $this->request->form(['settings' => 'required|array']);
    $result = $this->service->firewall()->updatesetting($data['settings']);
    $this->handleResult($result, 'Güvenlik ayarları', 'security/firewall');
    return;
}

$data   = $this->request->form(['id' => 'required', 'value' => 'nullable']);
$result = $this->service->firewall()->updatesetting((string) $data['id'], $data['value'] ?? null);

// 'status' açıkça veriliyor: handleResult'ın sezgisel fiil eşlemesi
// 'updatesetting' adını 'update' olarak yorumlar.
$this->handleResult($result, 'Güvenlik ayarı', 'security/firewall', 'status');
```

## 7. İlgili belgeler

* [Syshub/README.md](README.md) — modül haritası, rota tablosu, diğer alt dallar
* [Core/Http/README.md](../../../Core/Http/README.md) — `request->form()`, `handleResult()`
* [kavramlar/03-veritabani-ve-kiracilik.md](../../../kavramlar/03-veritabani-ve-kiracilik.md)
* [acik-sorular.md](../../../acik-sorular.md)