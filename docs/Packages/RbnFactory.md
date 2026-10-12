# Packages/RbnFactory — içerik fabrikası ayarı ve VPS sözleşmesi

> **Kapsam:** Bu sayfa yalnız fabrika ayar dosyasındaki `direction` bloğunun VPS'e giden alanlarını anlatır. Paketin tam dosya envanteri henüz yazılmadı.
> **Kaynak klasör:** `Packages/RbnFactory/` · ayar örneği: `Packages/RbnFactory/factory-settings.example.php`

## `direction.site_domain` — sitenin kendi alan adı

| Ayar | Jobs yanıtı alanı | Eşleme |
|---|---|---|
| `direction.site_domain` | `site_alan_adi` | `Models/FactoryContract.php` (`FIELDS`), değer `Services/FactoryService.php` içinde `trim` edilerek yazılır |

* **Biçim:** yalnız alan adı, ör. `site.com` (şemasız, `www` öneksiz).
* **Ne işe yarar:** VPS, yazının CTA ve bağlantılarında yalnız bu alan adını bilir; başka bir alan adı içeren yazıyı **reddeder**. Böylece model alan adı uyduramaz.
* **Boş bırakılırsa:** `site_alan_adi` boş gider; VPS tarafında alan adı kuralı uygulanmaz (eski davranış). Canlı sitede doldurmanız önerilir.
* **Örnek:**

```php
'direction' => [
    'site_note'   => 'Sitenin konusu ve bölgesi hakkında kısa not',
    'location'    => '',
    'site_domain' => 'site.com',
],
```

Sürüm notu: [`.github/UPGRADING.md`](../../.github/UPGRADING.md) ve [`.github/CHANGELOG.md`](../../.github/CHANGELOG.md) `[Unreleased]`.
