# 04 — SÜRÜMLEME VE YAYIN

> **Bu belge hangi commit'e göre yazıldı:** `d4af18d` (dal `feat/fw-license-master`)
> **Son doğrulama tarihi:** 2026-10-05
> **Yayın tabanı:** 0.9.5 = bu commit + sonrası; belge yalnız doğrulama anındaki kodu anlatır
> **Kapsam:** Sürüm kuralı, tek kaynak, `rbn version:*`, CHANGELOG/UPGRADING biçimi

---

## 1. Biçim kuralı (tek kural, `A.B.C`)

Kaynak: `.agents/rules/versioning.md`.

| Basamak | Aralık | Not |
|---|---|---|
| **A** | `0` … **sınırsız** (`9`'dan sonra `10`, `11`…) | tıkanmaz |
| **B** | `0` … `9` | **asla `10` olmaz** |
| **C** | `0` … `9` | **asla `10` olmaz** |

* Geçerli desen: `^(0|[1-9][0-9]*)\.[0-9]\.[0-9]$`
  (`Core/Support/Bridges/Helpers/Library/Version.php:28`).
* `0.10.0`, `1.2.10`, `1.2.3.4` **geçersizdir** (`versioning.md:25-27`).
* Yeni uygulamalar **`0.1.1`** ile başlar (`Version::INITIAL`, `Version.php:31`;
  `versioning.md:12`).

### 1.1 Sonraki sürüm = sayaç

`versioning.md:14-22`:

1. `C < 9` → `C + 1` (`0.7.3` → `0.7.4`)
2. `C = 9` ve `B < 9` → `C = 0`, `B + 1` (`0.7.9` → `0.8.0`)
3. `C = 9` ve `B = 9` → `C = 0, B = 0, A + 1` (`0.9.9` → `1.0.0`)

Kod karşılığı: `Version::next(string $version): string` (`Version.php:68`).

> **"major/minor/patch" yorumu YOKTUR.** Her yayın sıradaki sürümü alır.
> Bilinçli atlama (`A+1.0.0`) ancak **patron kararıyla** ve CHANGELOG'da
> gerekçeyle yapılır (`versioning.md:20`).

Yardımcılar (`Core/Support/Bridges/Helpers/Global/support_helpers.php`):
`next_version()` (`46`), `version_is_valid()` (`56`), `version_compare()` (`66`),
`version_parse()` (`78`), `version_initial()` (`88`).

**Kural:** Sürüm numarasını artırmak için **elle sayı yazılmaz**
(`versioning.md:31`). Geçersiz değer `Version` içinde `assertValid()` ile
**`\InvalidArgumentException`** fırlatır (`Version.php:118-123`).

---

## 2. Sürüm tek kaynakları

| Sürüm | Tek kaynak | Türev alan |
|---|---|---|
| **Framework** | `FrameworkIdentity::FRAMEWORK_VERSION` (`Core/Support/Definitions/System/FrameworkIdentity.php:26`) | `README.md:5`, `CITATION.cff`, `CHANGELOG.md` "Son sürüm" başlığı |
| **CLI** | `FrameworkIdentity::FRAMEWORK_CLI_VERSION = '2.3.0'` (`FrameworkIdentity.php:57`) | `php rbn list` başlığı ("CLI v2.3.0") |
| **RbnShield** | `FrameworkIdentity::SHIELD_VERSION = '2.1.0'` (`FrameworkIdentity.php:64`) | — |
| **RBN Admin Pro** | `FrameworkIdentity::ADMIN_VERSION = '1.2.0'` (`FrameworkIdentity.php:68`) | Panel alt bilgisi |
| **RbnAuth** | `FrameworkIdentity::AUTH_VERSION = '2.2.0'` (`FrameworkIdentity.php:73`) | — |
| **Proje (uygulama)** | **master DB `projects.version`** | BootCache → `project_data('version')` → `app_version()` ve `APP_VERSION` sabiti |

**Kopyalama yasağı:** Sürüm **TEK** yerde tutulur, diğer yerler oradan okur
(`versioning.md:30`). `version:check` kopyaların **framework sürümüyle aynı**
olmasını denetler (bkz. §4.1 satır 3 ve 4).

---

## 3. Proje sürümü: `projects.version` → `APP_VERSION`

### 3.1 Uygulama zamanlaması (kritik)

`APP_VERSION` sabiti **`PreBoot::detectEnvironment()` içinde TANIMLANMAZ**
(`PreBoot.php:356-362`). Gerekçe kodun içinde yazılıdır:

* `detectEnvironment()` `orchestrate()` içinde **0. adımda** çalışır
  (`PreBoot.php:423`);
* proje veri önbelleği ise **0.5. adımda**
  (`ProjectDiscovery::getProjectData()`, `PreBoot.php:426-427`) dolar;
* yani 0. adımda `projects.version` **henüz okunamaz**.

Sabit, `Paths` hazır olduktan **sonra** `PreBoot::defineAppVersion()` ile tanımlanır:
adım **1** `initPaths()`, adım **1.5** `defineAppVersion()`
(`PreBoot.php:438, 444`, açıklama `440-443`).

**Giriş noktalarında `define('APP_VERSION', …)` yazmak da aynı sebepten yasaktır**
(`PreBoot.php:356-362`; `.github/UPGRADING.md:46-50`).

### 3.2 Çözümleme zinciri

```
master DB  projects.version
   └─ ProjectDataMapper::build() → assemble()
        └─ BootCacheProvider::set()  →  <workspace>/.cache/project_<key>.json
             └─ Bootstrap::setAppContext('project_data')
                  ├─ project_data('version')
                  ├─ app_version()  → ProjectVersionResolver::resolve()
                  └─ APP_VERSION   → ProjectVersionResolver::resolveFromCache()
```

* `app_version()` (`Core/Support/Bridges/Helpers/Global/project_helpers.php:107-112`)
  → `ProjectVersionResolver::resolve(project_data('version'))`.
* `ProjectVersionResolver::resolve(mixed $raw): string`
  (`Core/Support/Bridges/Helpers/Library/ProjectVersionResolver.php:41`);
  `resolveFromCache()` (`64`) `Bootstrap::getAppContext('project_data')`'ten okur.
* Değer yoksa/geçersizse **standart başlangıç sürümü `0.1.1`** kullanılır;
  iki parçalı `1.0` gibi geçersiz bir varsayılan **asla yazılmaz**
  (`PreBoot.php:370-374`; `project_helpers.php:102-105`).
* `APP_VERSION` **önceden tanımlanmışsa ezilmez**
  (`PreBoot.php:384-386`, `398-400`).

### 3.3 Etkilenen çıktılar

`.github/UPGRADING.md:33-39`:

| Çıktı | Önce | Sonra |
|---|---|---|
| `{{APP_VERSION}}` (şablon) | `3.5.0` | projenin `projects.version` değeri |
| `module-version` (`<meta name="module-version">`) | `1.0` | aynı değer |
| `siteVersion` (giriş ekranı) / panel `app_version` | `1.0` / `1.0` | aynı değer |
| RbnShield / RBN Admin Pro / RbnAuth / CLI | `v2.1` / `1.2` / `2.2` / `2.3` | `2.1.0` / `1.2.0` / `2.2.0` / `2.3.0` |

Şablon ifadesi `{{ … }}` → `htmlspecialchars((string)(expr ?? ""), ENT_QUOTES, "UTF-8")`
(`Core/Render/ViewEngine.php:182`); güvenlik denetimi
`TemplateExpressionGuard::assertSafe()` (`ViewEngine.php:181`).
`{!! … !!}` ham çıktı verir (`ViewEngine.php:191`) ve **aynı** denetimden geçer (`190`).

**Tanımsız sabit uyarısı:** Denetim bir **kara listedir** (yasaklı desenler,
`include`/`require` benzeri deyimler, yasaklı işlev çağrıları —
`TemplateExpressionGuard.php:141-169`); sabit **çözümlemesi yapmaz**. Bu yüzden
`{{TANIMSIZ_SABIT}}` denetimden geçer ve **çalışma anında** PHP `Error` verir —
`?? ""` koruması yalnız **tanımlı** sabitler için işe yarar (`ViewEngine.php:182`).
Bu yüzden `{{APP_VERSION}}` yazan şablon, sabit tanımlanmadığı bir bağlamda hata
verir; sabit `PreBoot::defineAppVersion()` ile **tanımlanmış olmalıdır** (bkz. §3.1).

### 3.4 Veri geçişi (canlıda elle, sıra önemli)

`.github/UPGRADING.md:55-62`:

```bash
php rbn master:migrate          # 1) yedek + 0.1.1'e çevir
php rbn cache:clear             # 2) keşif önbelleğini temizle
php rbn version:check           # 3) doğrula (çıkış kodu 0 olmalı)
php rbn master:migrate --rollback   # GERİ ALMA: yedekteki değerler geri yazılır
```

* Yedek tablo: **`projects_version_backup`** (geri alma sonrası **silinmez**)
  (`.github/UPGRADING.md:62`; `db:tables --master` ile doğrulandı).
* **Şema değişmedi:** `projects.version` `varchar(20) DEFAULT '1.0'` olarak durur
  (ölçüldü); yalnız satır değerleri değişmiştir. Kolon `DEFAULT`'unu `0.1.1` yapmak
  **ayrı bir şema kararıdır** (patron onayı gerekir) (`.github/UPGRADING.md:63-66`).
* Migration sınıfı: `Core/Database/Migrations/Master/NormalizeProjectVersions.php`.

---

## 4. `rbn version:*`

İki komut `Core/Services/Console/Handlers/VersionHandlers.php` içindedir;
ikisi de master DB'ye bağlanır (`rbn:67-72`).

### 4.1 `version:check` — SALT-OKUNUR

`VersionHandlers::versionCheck()` (`VersionHandlers.php:43-101`):

| # | Kaynak | Satır |
|---|---|---|
| 1 | `FrameworkIdentity::FRAMEWORK_VERSION` | `48-51` |
| 2 | `FRAMEWORK_CLI_VERSION`, `SHIELD_VERSION`, `ADMIN_VERSION`, `AUTH_VERSION` | `54-62` |
| 3 | `CITATION.cff` — framework sürümüyle **aynı olmalı** | `64-67` |
| 4 | `CHANGELOG.md` "Son sürüm" başlığı — framework sürümüyle **aynı olmalı** | `69-72` |
| 5 | Master DB'deki proje ve uygulama sürümleri — hepsi `A.B.C` olmalı | `74-78` |

Çıkış kodu: sapma yoksa `0`, varsa `1` (`VersionHandlers.php:101`).
Kaynak 3 ve 4 **kopya** olduğu için "framework ile aynı mı" sorusudur; kaynak 1, 2, 5
yalnız **geçerlilik** (`Version::isValid()`) sorusudur.

**Ölçülen çıktı** (yerel master, 2026-10-06, 0.9.5 ağacında yeniden ölçüldü): framework `0.9.5` OK; CLI `2.3.0`,
Shield `2.1.0`, Admin `1.2.0`, Auth `2.2.0` OK; `CITATION.cff` `0.9.5` OK;
`CHANGELOG.md` `0.9.5` OK; master'daki **19 proje** kaydının **19'u** `0.1.1` OK.
Sonuç: *"Tüm sürümler tutarlı ve geçerli (A.B.C)."*

### 4.2 `version:next <project_key>` — varsayılan KURU KOŞU

`VersionHandlers::versionNext()` (`VersionHandlers.php:108-157`).

* **`--apply` yoksa hiçbir şey yazmaz** (kuru koşu) ve çıkış kodu `1`'dir
  (`.github/UPGRADING.md:51-53`; `VersionHandlers.php:124, 130, 140`).
* Yazma yolu `Version::next()` ile hesaplanır; **elle sayı yazılmaz**.
* Hedef tablo yoksa / kayıt yoksa hata verip çıkar (`VersionHandlers.php:124, 130`).
* `--master` **otomatik** açılır (`rbn:67-69`).

**Kural:** Master hub → proje kaydındaki `version` alanını **elle güncelleme**;
`rbn version:next <project_key> --apply` kullan (`.github/UPGRADING.md:51-54`).

---

## 5. CHANGELOG biçimi

`.github/CHANGELOG.md` ve `.github/UPGRADING.md` **Türkçedir** (kök `README.md:166`).

* Sürüm başlığı `## [A.B.C]` ve etiket `vA.B.C` **aynı değeri** taşır
  (`versioning.md:31`).
* `version:check` "Son sürüm" başlığını okur (`VersionHandlers.php:222`
  → `changelogSurumu()`); yani CHANGELOG'daki en son sürüm framework sürümüyle
  **eşleşmek zorundadır**.
* **Güvenlik girdileri tarafsız yazılır**; sömürüm adımı, payload, PoC ve
  dosya/satır ayrıntısı bu depoda yayınlanmaz (`.github/SECURITY.md:140-144`).
* Proje/müşteri/kişi adı **yazılmaz**: "X Telegram test router'ı" ➡️
  "Telegram test router'ı" (`.agents/rules/core-architecture.md` §9.4).

## 6. UPGRADING biçimi

`.github/UPGRADING.md:780-788` — dosyanın varlık sebebi: framework tek dosya
güncellemesiyle tüm siteleri etkiler; yanlış sıra tüm siteleri aynı anda düşürür.

Yapı:

1. **`## [Unreleased]`** en üstte (`UPGRADING.md:1`) — yayınlanmamış birikim.
2. Sürüm başlığı + **kısa liste** (yalnız sırayı gösterir, ayrıntı aşağıda)
   (`.github/UPGRADING.md:5-25`).
3. Her başlık: **ne değişti** → **yapmanız gerekenler** → **etkilenen davranış**
   → **geri alma**.
4. **Kırıcı değişiklikler** açıkça `KIRICI` etiketiyle işaretlenir
   (`.github/UPGRADING.md:226-286, 486-546, 547-569, 670-702`).
5. Ölçüm sonucu **sayısal** verilir (örn. 65/65 kolon, 26 `UNIQUE`, 9 çakışan grup)
   (`.github/UPGRADING.md:361-372`).
6. Geri alma hem **kullanıcı** (komut) hem **yönetici** (`ALTER TABLE …`) düzeyinde
   yazılır (`.github/UPGRADING.md:55-62, 423-433`).
7. Gizli değerler / canlı veritabanı adları **bu dosyada taşınmaz**
   (`.github/UPGRADING.md:438`).

## 7. `CITATION.cff`

Atıf dosyası framework sürümünün bir **kopyasıdır** ve `version:check` bunu denetler
(`VersionHandlers.php:64-67, 217`). Depo kökünde `LICENSE` (MIT) ve `CITATION.cff`
bulunur. Paketleme/dağıtımda `LICENSE` metni **kalmalıdır** (kök `README.md:174-177`).

---

## 8. Yayın öncesi kontrol listesi

1. `php rbn version:check` → **çıkış kodu 0**.
2. `FrameworkIdentity::FRAMEWORK_VERSION` = `CITATION.cff` = CHANGELOG "Son sürüm"
   (denetim bunu zaten yapar).
3. **Kırıcı** değişiklikler UPGRADING'de etiketli ve geri alınmış.
4. `.github/SECURITY.md` desteklenen sürüm tablosu güncel
   (`.github/SECURITY.md:20-34`).
5. Sürüm **elle artırılmamış**, `Version::next()` ile üretilmiş.
6. Proje sürümleri master DB'de `A.B.C` (`rbn version:check` bunu denetler).
7. Framework değişikliğiyle **ilgili belge güncellendi** (bkz. `docs/README.md`).
8. Sürüm **yayın sonraki güncellemeyle** yapılır; docs-only değişiklikler sürüm
   **artırmaz**.

---

## 9. Bilinmeyenler / dikkat

* `CITATION.cff` içindeki sürüm alanının **adı** (`version:` mı `cff-version` mı)
  bu belgede okunmadı; `version:check` yalnız sonucu doğruladı.
* `version:next`'in proje **kaydı yoksa** hangi mesajı verdiği
  (`VersionHandlers.php:124, 130, 140` erken çıkışları) **kuru koşuyla
  ölçülmedi**.
* `version:check` `applications` tablosunu da denetler
  (`VersionHandlers.php:74-78` → `masterSurumleri()`), ancak yerel master'da
  `applications` **boş** olduğu için bu dal ölçümde **görünmedi**.
