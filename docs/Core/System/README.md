# Core/System — Çatının çekirdeği (açılış, yapılandırma, keşif, yol, kayıt, depolama)

> **Doğrulanan kod tabanı:** `d508f5e1` (dal `feat/fw-license-master`) · **Tarih:** 2026-10-05 · **Yayın:** 0.9.6 = bu commit + sonrası; belge yalnız doğrulama anındaki kodu anlatır
> **Kaynak klasör:** `Core/System/` — 93 `*.php` (+ `Config/` içinde 3 `README.md`). Kökte `*.php` yoktur; her dosya altı alt dalın birindedir.
> **Envanter:** 93 php dosyasının 93'ü alt belgelerde anlatıldı (aşağıdaki tablo).

## 1. Ne işe yarar, kim kullanır

Framework'ün **her istekte ilk çalışan** ve **her katmanın altında duran** kodudur. Dört soruya cevap verir: *Hangi ortamdayım ve hangi proje?* (`Kernel`, `Config`), *Neyin nerede olduğunu nasıl bulurum?* (`Paths`, `Discovery`), *Bu ada hangi sınıf karşılık gelir?* (`Registries`, `Discovery`), *Geçici/kalıcı dosya verisini nereye yazarım?* (`Storage`).

Bağımlılık iki yönlüdür (ölçüm: `Framework\Core\(Base|Database|Http|Render|Routes|Services)` geçişi): `Core/System` içindeki **33 dosya** bu altı katmandan birine atıf yapar (en sık `Base` taban sınıfları — `BaseService`, `BaseConfig`, `BaseComponent` — ve `Routes`/`Services` — `Route`, `BootSentinel`, `PreflightProvider`); öte yandan bu katmanların hepsi kendi çözümlerini `Core/System` (`Paths`, `Discovery`, `Registries`, `Storage`) üzerinden yapar. Bu yüzden açılış kodu döngüleri `isResolving`-türü bayraklarla keser (§4).

## 2. Alt dallar ve belgeleri

| Klasör | `*.php` | Belge | Kısa görev |
|---|---:|---|---|
| `Config/` | 22 | [Config.md](Config.md) | `Config` (dosya ayarı), `Secrets` (sır dosyası + `app` ortam bayrağı), DB profil çözücüleri |
| `Discovery/` | 24 | [Discovery.md](Discovery.md) | Ad → sınıf/yol çözümü, modül keşfi, keşif önbellekleri, proje verisi toplama |
| `Kernel/` | 19 | [Kernel.md](Kernel.md) | `Bootstrap`, `PreBoot`, 6 aşamalı `Kernel`, bekçiler |
| `Paths/` | 4 | [Paths.md](Paths.md) | Çatı/proje/modül yol kayıt defteri |
| `Registries/` | 8 | [Registries.md](Registries.md) | Ad → sınıf haritası (193 kayıt), takma adlar |
| `Storage/` | 16 | [Storage.md](Storage.md) | Dosya tabanlı önbellek, oturum, günlük, trafik, önyükleme önbelleği |
| **Toplam** | **93** | | `find Core/System -name '*.php' | wc -l` = 93 |

## 3. Bir isteğin yolu (özet)

```
index.php → Bootstrap::run()
  → PhpVersionGate (≥ 8.3)
  → PreBoot::orchestrate():  ortam sabitleri (RBN_DEV…) → ProjectDiscovery (BootCache / ProjectDataMapper)
                              → Paths::init → APP_VERSION → BootSentinel → vendor/autoload → hızlı varlık
  → KernelFactory::create():  Autoload → ShieldSentinel → DatabaseGuardStage
                              → ComponentRegistry → SessionSandboxStage → Routing
  → Route::run() → Kernel::terminate()
```

Her ok için dosya:satır kanıtı ilgili alt belgededir ([Kernel §3](Kernel.md)).

## 4. Dallar arası bağımlılık (kodda görülen)

| Kimden → Kime | Nasıl |
|---|---|
| `Kernel` → `Config`, `Paths`, `Discovery`, `Storage` | `PreBoot` `Secrets`'i erken yükler (`app.environment`); `Paths::init`; `ProjectDataMapper`/`BootCacheProvider`; `SessionSandboxStage` → `StorageManager::sessions()` |
| `Discovery` → `Registries`, `Paths`, `Config` | `NamespaceResolver` → `SystemRegistry::locate()`; `Definition::get('database_*')` → `Config/Definitions/DbProfiles`; `ProjectDataMapper` → `DbProfileResolver` |
| `Config` → `Discovery` | `ConfigResolver::resolveContextSurvival` → `Definition::get(..., 'ENV_FILE')`; `DatabaseConfig` → `Definition::get(..., 'KEYS_MAP')` |
| `Paths` → `Discovery` | `FrameworkContext`/`ProjectContext` `FolderContext`'ten türer; `Paths::module()` → `ModuleDiscoveryDriver` |
| `Storage` → `Paths`, `Config` (dolaylı) | tüm sağlayıcılar `Paths::project()->…` ile dizin bulur |

Döngüsel bağımlılıklar `isResolving`/`$resolving` bayraklarıyla kesilir: `Config::get`, `Definition::get`, `DiscoveryEngine::resolveDiscovery`, `NamespaceResolver::find`, `DiscoveryConfigTrait::$isLocating`.

## 5. Tuzaklar (dallar arası, ölçülmüş)

1. **Yol matrisi tuzağı:** `FolderMatrix`'te olmayan anahtar yola BÜYÜK harfle yazılır; Windows'ta fark edilmez, Linux üretimde yanlış yol üretir ([Paths §5](Paths.md)).
2. **Kayıt defterinde 5 ölü kayıt** (193 kayıttan): `handlers.storage`, `handlers.localization`, `handlers.seo`, `metadata.KIT_`, `metadata.SEO_` ([Registries §5](Registries.md)).
3. **`Config::get()` bütünlük bekçisini atlar** ([Config §5.1](Config.md)).
4. **Ortam kararı (RBN_DEV) istemciye bağlı, DB profili sunucuya bağlıdır:** ikisi bilerek ayrıdır; ölçüm tablosu [Config §5](Config.md)'te.
5. **Önyükleme önbelleği düz metin JSON** (`<workspace>/.cache`) ve proje verisini taşır; TTL'i yoktur ([Storage §5](Storage.md)).

## 6. Örnek (gerçek koddan)

```php
// Core/System/Kernel/Base/KernelFactory.php:81-86
$kernel->addStage(new Autoload())->addStage(new ShieldSentinel())->addStage(new DatabaseGuardStage())
       ->addStage(new ComponentRegistry())->addStage(new SessionSandboxStage())->addStage(new Routing());
```

## 7. İlgili belgeler

* [Core/Support](../Support/README.md) (sabitler, sözleşmeler, yardımcılar)
* [Kavram: mimari harita](../../kavramlar/01-mimari-harita.md) · [Kavram: yapılandırma](../../kavramlar/02-yapilandirma.md) · [Kavram: veritabanı ve kiracılık](../../kavramlar/03-veritabani-ve-kiracilik.md)
* [Açık sorular](../../acik-sorular.md)
