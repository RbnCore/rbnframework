# Bundles/Internal/Syshub/DbConsole — Veritabanı konsolu, SQL terminali, tablo gezgini

> **Doğrulanan kod tabanı:** `d49b4413` (dal `feat/fw-license-master`) · **Tarih:** 2026-10-05 · **Yayın:** 0.9.4 = bu commit + sonrası; belge yalnız doğrulama anındaki kodu anlatır
> **Kaynak klasör:** `Bundles/Internal/Syshub/Controllers/DbConsole/` (3 `*.php`) +
> `Handlers/SyshubDbConsoleHandler.php` + `Providers/SyshubDbConsoleProvider.php` +
> `Services/SyshubDbConsoleService.php` + 4 görünüm.
> **Envanter:** 3 controller dosyasının 3'ü anlatıldı.

## 1. Ne işe yarar, kimler kullanır

Veritabanını panelden yönetir: **DB özeti** (tablo sayısı/boyut/kayıt),
**SQL terminali** (tek ifadeli ham SQL), **tablo gezgini** (satır görüntüleme,
silme, collation değiştirme, tablo düşürme, SQL/CSV/ZIP dışa aktarma),
**toplu işlemler** ve **temiz kurulum**.

**Kimler çağırır:** geliştirici paneli (`syshub/dbconsole/*`,
`ModuleData.php:87-122`). Veri katmanı `schemaDoctor` modelidir
(`Core\Database\Models\SchemaDoctorModel.php`).

## 2. Klasör/dosya envanteri

### 2.1 `Controllers/DbConsole/` (3)

| Dosya | Görev | Önemli public yöntemler |
|---|---|---|
| `DbConsoleController.php` | `#[SubModule(entity:'database', service:'dbConsole')]`. DB özeti ekranı ve dört sistem eylemi. | `index(): void` (:23), `seedAdmin(): void` (:37), `cleanInstall(): void` (:46), `optimize(): void` (:55), `convertAllCollations(): void` (:64) |
| `SqlConsoleController.php` | Aynı `SubModule` kimliği, farklı controller. SQL terminali; `index()` ayrıca `@fw/RbnCommon/css/rbn-terminal.css` varlığını hazırlar (:27). | `index(): void` (:23), `executeQuery(): void` (:40), `getDatabaseInfo(): void` (:54), `getTableList(): void` (:66), `getTableData(): void` (:78) |
| `TableExplorerController.php` | Tablo listesi, satır gezme, satır/tablo silme, dışa aktarma (ayrı `.sql` dosyaları veya tek ZIP). | `index(): void` (:23), `browse(): void` (:42), `changeCollation(): void` (:67), `deleteRow(): void` (:81), `bulkDeleteRows(): void` (:95), `bulkExportRows(): void` (:109), `dropTable(): void` (:125), `bulkDropTables(): void` (:135), `exportSQL(): void` (:151), `bulkExportSQL(): void` (:168), `bulkExportZip(): void` (:189) |

### 2.2 Zincirin geri kalanı

| Dosya | Görev |
|---|---|
| `Handlers/SyshubDbConsoleHandler.php` | Tüm yıkıcı DB eylemleri ve **güvenlik kapıları** (`isRawSqlRole`, `hasMultipleStatements`, `maskForAudit`) |
| `Providers/SyshubDbConsoleProvider.php` | Salt okuma keşfi: `getInfo/getTables/getRows/getMetadata` |
| `Services/SyshubDbConsoleService.php` | 12 düz proxy; `seedAdmin()` → `service('masterAccount')->seedAdmin()` (:86) |
| `Views/DbConsole/index.rbn.php` | DB özet kartları |
| `Views/DbConsole/console.rbn.php` | Terminal arayüzü + gömülü JS (`RbnSqlConsole` nesnesi, :193) |
| `Views/DbConsole/tables.rbn.php` | Tablo listesi + arama + sayfalama |
| `Views/DbConsole/browse.rbn.php` | Satır tablosu + kolon başlıkları |

## 3. Akış — SQL terminali ve tablo gezgini

```
POST syshub/dbconsole/console/execute
  → SqlConsoleController::executeQuery()            SqlConsoleController.php:40
      → request->form(['query'=>'required|string'], ['action'=>'sql_console_execute'])  :41-43
      → SyshubDbConsoleService::executeQuery()        :46
          → handler('syshubDbConsole')                ModuleData.php:37
          → SyshubDbConsoleHandler::executeQuery()    :23
              ├─ rol kapısı      isRawSqlRole()       :31 / :68
              ├─ tek ifade       hasMultipleStatements()  :42 / :81
              ├─ denetim         auditSql()           :47 / :200
              └─ model('schemaDoctor')->executeRaw()  :50
      → Route->handleResult($result)                  :48

GET syshub/dbconsole/tables
  → TableExplorerController::index()                  :23
      → service->getTables()  ⇒ provider->getTables() ⇒ schemaDoctor->getTableStatus()
      → paginate($allTables, 15)                       :29
      → render('DbConsole/tables', [tables, database_info, total, search, pagination])  :31-39

POST syshub/dbconsole/tables/bulk-export-zip
  → TableExplorerController::bulkExportZip()          :189
      → her tablo için exportSql($name)  ⇒ prepareTempZip()  :193-194
      → respondDownload($file, …)                     :195
```

## 4. Yapılandırma / ayar anahtarları ve sabitler

| Öğe | Yer | Değer | Not |
|---|---|---|---|
| `CLEANUP_ALLOWED_TABLES` | `Definition::get('database_project', 'CLEANUP_ALLOWED_TABLES')` | — | `cleanInstall()` **yalnız bu listedeki** tabloları truncate eder (`SyshubDbConsoleHandler.php:239-244`). Liste boşsa hiçbir şey yapılmaz, metot yine `true` döner. |
| Hedef collation | `DbConsoleController::convertAllCollations()` | `utf8mb4_unicode_ci` | `changeCollation('*', …)` — joker tablo adı modele bırakılır (`DbConsoleController.php:65`) |
| `LIMIT` (hızlı tablo verisi) | `SqlConsoleController::getTableData()` | 100 | Sabit kodlu, istenemez (:78) |
| `getRows()` varsayılanı | `SyshubDbConsoleProvider::getRows()` | 100 | `TableExplorerController::browse()` bu varsayılanı kullanır, sonra **elle** sayfalar (`TableExplorerController.php:46-47`) |
| Dışa aktarma başlığı | `SyshubDbConsoleHandler::exportSql()` | `-- RBN Framework SQL Export` | `DROP TABLE IF EXISTS` + şema + INSERT'ler; değerler `$pdo->quote()` ile kaçırılır (:284-296) |
| Terminal asset | `SqlConsoleController::index()` | `@fw/RbnCommon/css/rbn-terminal.css` | Panel kontekstinde hazırlanır (:27) |

## 5. Tuzaklar ve kurallar

1. **`'*'` tablo adı bir sözleşmedir.** `convertAllCollations()` `changeCollation('*', …)` çağırır;
   jokerin `convertCollation()` içinde yorumlanması modele bağlıdır. `SyshubDbConsoleHandler::optimize()`
   ise joker kullanmaz — `array_column($provider->getTables(), 'name')` ile açık tablo listesi alır (:224).
2. **`bulkExportSQL` ve `bulkExportZip` ayrı uçlar, aynı rota grubu.** `ModuleData.php:118-120`
   kebab-case yollar (`bulk-export-sql`, `bulk-export-zip`, `bulk-drop-tables`) kayıt eder; diğer
   toplu uçlar camelCase'dir (`bulkDeleteRows`). Görünüm hangisine post ediyorsa o çalışır.
3. **Geçici dosya yazımı diske gider.** `prepareTempFile()` / `prepareTempZip()` çıktıyı bir geçici
   dosyaya yazar, `respondDownload()` onu indirir. `bulkExportSQL()` her tablo için **ayrı** indirme
   URL'si üretip `respondMultiDownload()` ile toplu tetikler (:176-181) — tarayıcı çoklu indirme
   izni isteyebilir.
4. **Görünüm değişkeni uyuşmazlığı (`browse.rbn.php`).** Controller `items`, `table_metadata`,
   `primary_key` gönderir (`TableExplorerController.php:52-61`) ama görünüm `$records`, `$columns`,
   `$page`, `$limit` bekler (`browse.rbn.php:5-9`, kullanım :92,103,109-111). `$records`/`$columns`
   **tanımsız değişken** olarak okunur; bu yüzden tablo gövdesi boş kalır.
5. **`getTableData()` tablo adını doğrulamaz.** `` "SELECT * FROM `{$form['table']}` LIMIT 100" ``
   (`SqlConsoleController.php:78`) — `required|string` yeterli; adres/kaçış kontrolü yok, sorgu
   `executeQuery()`'den geçerken yalnız "tek ifade" kuralı uygulanır.
6. **`prepareTempFile()` dosya adı girdiden gelir.** `bulkExportRows()` dosya adını
   `` "{$form['table']}_rows_" . date('Ymd_His') . ".sql" `` olarak kurar (:114); tablo adı
   yol ayırıcı içerirse beklenmedik dizin oluşabilir.
7. **`cleanInstall()` başarısızlığı bildirmez.** Döngüde `truncateTable()` dönüş değeri **denetlenmez**
   (`SyshubDbConsoleHandler.php:241-243`), metot koşulsuz `true` döner.
8. **Aynı `SubModule` kimliği üç controller'da.** `entity: 'database'`, `service: 'dbConsole'` üçünde
   de aynı (`DbConsoleController.php:14`, `SqlConsoleController.php:14`, `TableExplorerController.php:14`).
   Keşif alias'ı (`SyshubService::dbConsole()`) tekildir; ayrım yalnız rotadan gelir.

## 6. Örnek (gerçek koddan)

```php
// Bundles/Internal/Syshub/Handlers/SyshubDbConsoleHandler.php:236-249
public function cleanInstall(): bool
{
    $model   = $this->model('schemaDoctor');
    $tables  = Definition::get('database_project', 'CLEANUP_ALLOWED_TABLES') ?? [];

    foreach ($tables as $t) {
        $model->truncateTable($t);
    }

    return true;
}
```

```php
// Bundles/Internal/Syshub/Controllers/DbConsole/TableExplorerController.php:189-196
$form  = $this->request->form(['ids' => 'required|array']);
$files = [];

foreach ($form['ids'] as $name) {
    $files[$name . '.sql'] = $this->service->exportSql($name);
}

$file = $this->service->prepareTempZip('db_pack_' . date('Ymd_His') . '.zip', $files);
$this->respondDownload($file, 'Seçili tablolar ZIP arşivi içinde hazırlandı.');
```

## 7. İlgili belgeler

* [Syshub/README.md](README.md) — modül haritası, kayıt haritası, tuzak 10 (SQL rol kapısı)
* [Core/Database/Engine.md](../../../Core/Database/Engine.md) — `executeRaw`, `getTableStatus`
* [kavramlar/03-veritabani-ve-kiracilik.md](../../../kavramlar/03-veritabani-ve-kiracilik.md)
* [acik-sorular.md](../../../acik-sorular.md)