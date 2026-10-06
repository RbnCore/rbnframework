# Core/Support — Ortak sabitler, sözleşmeler, yardımcılar ve istisnalar

> **Doğrulanan kod tabanı:** `d508f5e1` (dal `feat/fw-license-master`) · **Tarih:** 2026-10-05 · **Yayın:** 0.9.4 = bu commit + sonrası; belge yalnız doğrulama anındaki kodu anlatır
> **Kaynak klasör:** `Core/Support/` — 82 `*.php`. Kökte `*.php` yoktur.
> **Envanter:** 82 dosyanın 82'si alt belgelerde anlatıldı (aşağıdaki tablo).

## 1. Ne işe yarar, kim kullanır

Çatının **bağımlılığı en az, kullanımı en yaygın** katmanıdır: iş mantığı olmayan sabit tabloları (`Definitions`, `Blueprints`), imza sözleşmeleri (`Contracts`), istisna türleri (`Exceptions`) ve her yerden çağrılan yardımcılar (`Bridges`). `Core/System` (özellikle `Discovery`, `Kernel`, `Paths`, `Storage`), `Core/Http`, `Core/Render`, `Core/Routes`, `Core/Services` ve tüm `Bundles/Packages` buraya bağımlıdır. Tersi de vardır ama dardır: ölçüme göre (`Framework\Core\(Base|Database|Http|Render|Routes|Services|System)` geçişi) `Core/Support` içinde **20 dosya** başka bir `Core` katmanına atıf yapar — çoğu global işlev dosyaları (`http_helpers`, `project_helpers`, `system_helpers`), `Definitions` (`FolderMatrix`, `NamespaceMap`, `RouteBlueprint`), `AssetProxy`, `LogThrottle` ve `PathHelper`; saf sabit/doğrulama dosyaları (`Blueprints/*`) hiçbirine bağlanmaz.

## 2. Alt dallar ve belgeleri

| Klasör | `*.php` | Belge | İçerik |
|---|---:|---|---|
| `Blueprints/` | 11 | [Blueprints.md](Blueprints.md) | `Constants/` (2) + `Validations/` (9): doğrulama kuralları, hız sınırları, kara/beyaz listeler |
| `Bridges/` | 32 | [Bridges.md](Bridges.md) | Global işlevler (4+1), `Library/` yardımcı sınıfları (15), ikon veri/sınıfları (9), `AssetProxy`, 2 özellik |
| `Contracts/` | 21 | [Contracts.md](Contracts.md) | 21 interface (`Base` 7, `Http` 3, `Database` 3, …) |
| `Definitions/` | 10 | [Definitions.md](Definitions.md) | `System/` (ad alanı, klasör, bileşen türü, kimlik), `Route/`, `Render/` (varlık) |
| `Exceptions/` | 8 | [Exceptions.md](Exceptions.md) | 8 istisna sınıfı |
| **Toplam** | **82** | | `find Core/Support -name '*.php' | wc -l` = 82 |

## 3. Katmanlar arası ilişki

```
Definitions ──(Definition::get, kategori adı)──► Discovery/DefinitionResolver   (Core/System)
Blueprints/Validations ──(ValidationResolver)──► validation('kategori.anahtar')
Contracts ◄── implements ── BaseService / BaseModel / Request / Kernel aşamaları
Exceptions ◄── throw ── Kernel / Gatekeepers / Cron / Doğrulama
Bridges/Helpers/Global ──(rbn_helpers.php, Paths::init içinde)──► tüm uygulama
```

## 4. Tuzaklar (dallar arası, ölçülmüş)

1. **`NamespaceMap::COMPONENT_REGISTRY` var olmayan sınıfı gösterir** ([Definitions §5.1](Definitions.md)).
2. **`Definition::get('identity', …)` marka sabitlerini vermez** (`identity` kategorisi varlık proxy ayarına ayrılmıştır; [Definitions §5.2](Definitions.md)).
3. **Global `version_compare()` ölü koddur** (PHP yerleşiği öncelikli; [Bridges §5.2](Bridges.md)).
4. **`CryptoHelper` kimlik doğrulamasız CBC** ([Bridges §5.1](Bridges.md)).
5. **Ölü sözleşmeler:** `BaseConfigInterface`, `IpGuardProviderInterface` ([Contracts §5.2](Contracts.md)).
6. **Yüklenmeyen veri dosyaları:** `Icons/Internal/{devicons,payment_icons,social_brands,tabler_icons,unicode_emojis}.php` ([Bridges §5.11](Bridges.md)).
7. **Doğrulama kategori adı dosya adından türer** ([Blueprints §5.5](Blueprints.md)).

## 5. Örnek (gerçek koddan)

```php
// Core/Support/Blueprints/Validations/RateLimitValidations.php: canon() → getLimit()
RateLimitValidations::canon('login');   // 'frontend_login'
```

## 6. İlgili belgeler

* [Core/System](../System/README.md) · [Kavram: mimari harita](../../kavramlar/01-mimari-harita.md)
* [Açık sorular](../../acik-sorular.md)
