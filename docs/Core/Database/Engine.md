# Core/Database/Engine — bağlantı yönetimi, sorgu üretimi, şema ve koleksiyon

> **Doğrulanan kod tabanı:** `1d89c431` (dal `feat/fw-license-master`) · **Tarih:** 2026-10-05 · **Yayın:** 0.9.6 = bu commit + sonrası; belge yalnız doğrulama anındaki kodu anlatır
> **Kaynak klasör:** `Core/Database/Database.php` + `Core/Database/Engine/` — 16 `*.php`.
> **Envanter:** 16 dosyanın 16'sı aşağıda anlatıldı.

## 1. Ne işe yarar, kim kullanır

`Database` tekil nesnesi bağlantı havuzunu tutar; `MySqlProvider` her aile
için PDO'yu açar; `QueryBuilder` + 7 trait SQL'i üretir; `SchemaBuilder`
tabloyu inceler ve DDL çalıştırır; `Collection` sonuç kümesini sarar.

**Kimler çağırır:** `BaseModel::query()`, `BaseModel::getDb()`, tüm
repository'ler (`$this->db->raw(...)`), `DatabaseService`, CLI handler'ları.

## 2. Dosya envanteri

| Dosya | Görev | Önemli public yöntemler |
|---|---|---|
| `Database.php` | Bağlantı orkestratörü (4 trait). Singleton, klonlanamaz. | `static getInstance(): self`, `transaction(callable $callback): mixed` |
| `Engine/Collection.php` | Sonuç sarmalayıcı: `ArrayAccess`, `Countable`, `IteratorAggregate`, `JsonSerializable`. | `__construct($items=[])`, `static make($items=[]): self`, `all(): array`, `where(string $key,$operator,$value=null): self`, `map/filter(callable): self`, `pluck(string): self`, `sortBy/sortByDesc(string): self`, `groupBy(string): self`, `first()/last()`, `count(): int`, `merge($items): self`, `add/push($item): self`, `toArray(): array`, `offset*`, `getIterator()`, `jsonSerialize()` |
| `Engine/Providers/MySqlProvider.php` | `database_project/master/common` için PDO sağlayıcısı; ayar değişiminde ve CLI'da ölü bağlantıda kendini yeniler. | `__construct(string $name='database_project')`, `getPdo(): PDO`, `beginTransaction/commit/rollback/inTransaction(): bool`, `getLastInsertId(): int` |
| `Engine/Providers/QueryBuilder.php` | Sorgu kurucu; 7 trait'i kullanır, sorguyu **kendi bağlantısında** çalıştırır. | `__construct(?Database $db=null,?string $table=null)`, `setModelClass(string): self`, `connection(string $name): static`, `onConnection(callable): mixed` (protected) |
| `Engine/Providers/SchemaBuilder.php` | Tablo inceleme + DDL (drop/truncate/optimize/convert/rename). | `__construct(Database $db,string $table)`, `connection(string): static`, `drop/truncate/optimize(): bool`, `convert(string $collation): bool`, `rename(string $newName): bool`, `exists(): bool`, `getColumnListing(): array`, `getDatabaseName(): string`, `getPrimaryKey(): string`, `getCreateSql(): string`, `getStatus(?string $table=null): array`, `getTableName(): string` |
| `Engine/Traits/ConnectionTrait.php` | Bağlantı adı doğrulama, sağlayıcı önbelleği, kapsamlı geçiş. | `normalizeConnectionName(string): string`, `connection(?string $name=null): self|string`, `connectionScoped(string $name, callable $islem): mixed`, `getPdo(): PDO`, `getProvider(string $name): DbProviderInterface`, `setPdo(PDO $pdo,string $name='default'): void`, `reconnect(?string $name=null): void` |
| `Engine/Traits/ExecutionTrait.php` | PDO çalıştırma, hazırlanmış ifade önbelleği, 2006/2013 otomatik yeniden bağlanma, boot döngü sayacı. | `resetBootQueryCount(): void`, `query(string $query,array $params=[]): \PDOStatement\|false`, `prepare(string $query): \PDOStatement`, `execute(string $query,array $params=[]): bool`, `raw(string $sql,array $params=[]): array`, `rawFirst(string $sql,array $params=[]): ?array`, `rawExecute(string $sql,array $params=[]): bool` |
| `Engine/Traits/TransactionTrait.php` | Transaction yüzeyi. | `beginTransaction/commit/rollback/inTransaction(): bool` |
| `Engine/Traits/QueryBridgeTrait.php` | `Database` → builder köprüsü. | `table(string $table): QueryBuilder`, `schema(string $table): SchemaBuilder`, `getLastInsertId(): int` |
| `Engine/Traits/Query/ConditionTrait.php` | `WHERE` üretimi ve koşul yardımcıları. | `where($column,$operator=null,$value=null): static`, `orWhere(...)`, `whereIn/whereNotIn(string,array): static`, `whereNull/orWhereNull/whereNotNull/orWhereNotNull(string): static`, `whereRaw/orWhereRaw(string,array=[]): static`, `bindRawParams(string,array): string`, `buildWhere()/buildWhereOnly(): string`, `when($condition,callable,?callable): static` |
| `Engine/Traits/Query/SelectionTrait.php` | `SELECT`, sıralama, gruplama, limit/offset, eager load listesi. | `select(string $columns='*'): static`, `with($relations): static`, `getEagerLoads(): array`, `orderBy(string,$direction='ASC'): static`, `orderByRaw(string): static`, `groupBy(string): static`, `having(string $condition,array $params=[]): static`, `bindAggregateParams(string,array): string`, `limit(int): static`, `offset(int): static` |
| `Engine/Traits/Query/JoinTrait.php` | `JOIN` çeşitleri. | `join(string $table,string $first,string $operator,string $second,string $type='INNER'): static`, `leftJoin/rightJoin/innerJoin(string,string,string,string): static` |
| `Engine/Traits/Query/AggregateTrait.php` | `count/sum/avg/exists`. | `count(): int`, `sum(string $column): float`, `avg(string $column): float`, `exists(): bool`, `aggregateQuery(string $selectSql): mixed`, `wrapAggregateColumn(string): string` |
| `Engine/Traits/Query/CrudTrait.php` | `INSERT/UPDATE/DELETE` (batch dâhil) ve sayaç artırma. | `insert(array $data): int`, `insertBatch(array $data): bool`, `updateBatch(array $data,string $index='id'): bool`, `update(array $data): bool`, `updateAffected(array $data): int`, `delete(): int`, `increment/decrement(string $column,int $amount=1): bool`, `truncate(): bool` |
| `Engine/Traits/Query/ExecutionTrait.php` | Sorguyu koşturur, hidrasyon ve `Collection` sarmalaması yapar. | `get(): mixed`, `rows(): array`, `first(): mixed`, `toSql(): string` |
| `Engine/Traits/Query/SqlHelperTrait.php` | Kimlik sarma, alias ayrıştırma, `IN` parametreleme. | `wrapColumn(string): string`, `wrapTable(string): string`, `isAliased(string): bool`, `extractAlias(string): ?string`, `parameterize(array $values): string` |

**Kapsama:** 16/16.

## 3. Akış

### 3.1 Bağlantı çözümleme

```
Database::getProvider('database_master')            ConnectionTrait.php:149
 ├─ elle kaydedilmiş sağlayıcı? (setPdo köprüsü) → aynen döner   :153-155
 ├─ ham ad geçersizse error_log (görünürlük)                    :163-168
 ├─ normalizeConnectionName(): 'default'→database_project, diğer geçersiz → istisna  :173 / :68-83
 ├-$connections önbelleğinde var? → aynen                        :175-177
 ├─resolving[bağlantı] işaretliyse → PreflightException (döngü)  :180-190
 └─new MySqlProvider($ad)                                        :196
     └─getPdo(): Config değiştiyse pdo=null; CLI'da 10 sn kuralı; yoksa connect()  MySqlProvider.php:60-102
```

### 3.2 Sorgu çalıştırma

```
$builder->where('x',1)->get()                     Query\ExecutionTrait.php:17
 ├─ toSql()
 ├─ onConnection(fn) → Database::connectionScoped($ad, fn)   QueryBuilder.php:97
 │   └─ Database::query($sql,$params)             Engine/Traits/ExecutionTrait.php:46
 │       ├─ RBN_PANIC_ACTIVE → false (bellek freni)   :49-51
 │       ├─ CLI dışında boot sorgu sayacı >100 → rbn_panic   :53-62
 │       ├─ prepare(): md5(sql + bağlantı + spl_object_id(pdo)) ile ifade önbelleği (LRU)  :94-118
 │       └─ 2006/2013 → reconnect(activeConnection) + bir kez yeniden dene  :73-84
 └─ modelClass varsa satırlar model örneğine çevrilir, yoksa collect()  :36-54
```

## 4. Ayarlar

| Sabit | Yer | Not |
|---|---|---|
| `KNOWN_CONNECTIONS` | `ConnectionTrait.php:29` | üç aile |
| `CONNECTION_ALIASES` | `ConnectionTrait.php:43` | `default` → `database_project` |
| `STMT_CACHE_LIMIT` | `Engine/Traits/ExecutionTrait.php` | ifade önbelleği üst sınırı (LRU) |
| `bootQueryCount > 100` | `:56-61` | CLI dışında panic eşiği |
| CLI ölü bağlantı eşiği `> 10.0` sn | `MySqlProvider.php:83` | yalnız CLI |

## 5. Tuzaklar ve kurallar

1. **`prepare()` önbelleği statiktir.** Anahtar **PDO nesne kimliğini** de
   içerir; `setPdo()` ile değişen PDO'da bayat ifade kullanılmaz
   (`Engine/Traits/ExecutionTrait.php:96-103`).
2. **`reconnect()` bağlantı adı almalıdır.** Parametresiz çağrı yalnız o an
   aktif bağlantıyı düşürür ve 2006/2013 kalıcı hale gelebilir
   (`:74-80`).
3. **`first()` `LIMIT 1` kalıcı yazmaz.** Builder kopyalanır; kullanıcının
   kendi `limit()`'i varsa korunur (`Query/ExecutionTrait.php:80-92`).
4. **Panik freni asimetrisiz olmamalıdır.** `Database::query()` `false`
   dönerse `get()` boş koleksiyon döner, `fetchAll()` hatası üretmez
   (`Query/ExecutionTrait.php:25-32`).
5. **`get()` dizi DEĞİLDİR.** `Collection` döner, model atanmışsa öğeleri model
   nesnesidir. `array` dönüş tipli yöntemde `return ...->get();` TypeError verir;
   `->get() ?: []` hiç devreye girmez (nesne her zaman doğrudur) ve
   `Collection::toArray()` model nesnelerini dizileştirmez. Satır dizisi gereken
   yerde `rows(): array` kullanılır (`Query/ExecutionTrait.php:66-75`).
6. **İç içe transaction dış yüzeyi kullanmaz** (`Database.php:57-60`).
7. **`connectionScoped` istisnayı yutmaz**, `finally` ile bağlantıyı geri alır
   (`ConnectionTrait.php:123-133`).
8. **Ayrıştırıcı boş döner.** `RBN_PANIC_ACTIVE` tanımlıysa `query()`
   `false` döner; çağıran taraf bunu denetlemek zorundadır.
9. **`SchemaBuilder` tanımlayıcıları sarar** (`ident()`, `:29`); tablo adı
   güvenlik açısından buradan geçer.

## 6. Örnek (gerçek koddan)

```php
// DatabaseService üzerinden ham okuma-yazma (Service.php:132, 158)
$rows = $this->db->raw('SELECT id, title FROM z_app_pages WHERE is_active = ? LIMIT 10', [1]);

// Ham SQL'i kendi bağlantısında çalıştıran builder (QueryBuilder.php:97)
$qb = $this->db->table('z_users')->connection('database_master');
$list = $qb->where('is_active', 1)->orderBy('id', 'DESC')->limit(20)->get();
```

## 7. İlgili belgeler

* [../README.md](README.md) — katman haritası ve tuzaklar
* [Models.md](Models.md), [Repositories.md](Repositories.md)
* [../Base/Data.md](../Base/Data.md) — `BaseModel` ve query sarmalayıcıları
* [../../kavramlar/03-veritabani-ve-kiracilik.md](../../kavramlar/03-veritabani-ve-kiracilik.md)