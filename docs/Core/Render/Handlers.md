# Core/Render/Handlers — veri hazırlayıcılar ve üç güvenlik kapısı

> **Doğrulanan kod tabanı:** `c23b431f` · **Tarih:** 2026-10-05 · **Yayın:** 0.9.6 = bu commit + sonrası; belge yalnız doğrulama anındaki kodu anlatır
> **Kaynak klasör:** `Core/Render/Handlers/` — **6 `*.php`** = 3 kök + `UI/` altında 3.
> **Envanter:** 6 dosyanın **6'sı** anlatıldı.
> **Doğrulama platformu:** Windows + PHP 8.3, `TemplateExpressionGuard` ve `RawHtmlGate` gerçekten çalıştırılarak.

## 1. Ne işe yarar, kim kullanır

İki farklı iş bir arada:

* **`UI/` altındaki 3 sınıf (Handler adı yanıltıcı):** bunlar veri hazırlamaz,
  `BaseRender::prepare()` sözleşmesini **boş doldurur** — `RenderService::prepareContext()`
  her render'da `$this->handler($type)->prepare(...)` çağırır, UI handler'ları
  `$data`'ya kendiliğinden ekleme yapar ve veriyi olduğu gibi döndürür.
* **Kök 3 sınıf:** güvenlik kapıları. `TemplateExpressionGuard` **derleme
  zamanında** ifade denetler, `RawHtmlGate` **çalışma zamanında** ham HTML'i
  temizler, `SchemaPreset` şema ön ayarını uygular.

## 2. Klasör/dosya envanteri (6/6)

| Dosya | Görev | Önemli public yöntemler |
|---|---|---|
| `TemplateExpressionGuard.php` (219) | Şablon ifadesi güvenlik kapısı (R-03). Yasaklı yapı listesiyle (izin listesi **değil**) fail-closed denetim yapar. | `assertSafe(string $ifade,string $baglam='sablon'): void` (hata fırlatır), `isSafe(string $ifade): bool`, `static onbellegiTemizle(): void` · korumalı: `denetle()`, `uriTehlikeliMi` benzeri yok · sabitler: `DENIED_FUNCTIONS`, `DENIED_KEYWORDS`, `DENIED_STATEMENTS`, `DENIED_PATTERNS` (hepsi `private`) |
| `RawHtmlGate.php` (149) | Veritabanından gelen ham HTML/JS snippet'lerini temizler: inline olay handler'ları ve tehlikeli URI şemalarını nötrleştirir. | `sanitize(string $html): string`, `hasDangerousMarkup(string $html): bool` · korumalı: `uriTehlikeliMi(string $deger): bool`, `dongusuzUygula(string $html,callable $adim): string` · sabitler: `EVENT_ATTR_PATTERN`, `URI_ATTR_PATTERN`, `DANGEROUS_SCHEMES` |
| `SchemaPreset.php` (86) | `SchemaBuilder`'a hazır şema ön ayarı uygular (kayıt: `presets['schema']`). | `execute(object $manager,string $type,array $data=[]): void` |
| `UI/FrontendHandler.php` (49) | `frontend` render tipi için `prepare()` sözleşmesi. | `prepare(string $type,?string $view,array $options=[]): array` |
| `UI/AuthHandler.php` (94) | `auth` (giriş/kayıt) ekranları için kimlik çözümü yapan `prepare()`. | `prepare(string $type,?string $view,array $options=[]): array` · kullanır: `AuthIdentity` (RbnAuth), `FrameworkIdentity` |
| `UI/PanelHandler.php` (131) | Panel ekranları için panel kimliği/önek çözümleyen `prepare()`. | `prepare(string $type,?string $view,array $options=[]): array` · kullanır: `PanelIdentity` (RbnAdmin), `ConfigMap` |

## 3. Akış — `TemplateExpressionGuard` (derleme zamanı kapısı)

`ViewEngine::parse()` her `{{ }}` ve `{!! !!}` ifadesi için
`$guard->assertSafe($m[1], $dosyaAdi)` çağırır (`ViewEngine.php:181, :190`).

```
ViewEngine::parse()
  └─ TemplateExpressionGuard::assertSafe($ifade, $dosyaAdi)   Handlers/TemplateExpressionGuard.php:114
     ├─ boş ifade → RuntimeException                                   :117-121
     ├─ statik önbellek: self::$sonucOnbellegi[$ifade]                 :123-129
     └─ denetle($ifade, $baglam)                                       :141-197
        1. DENIED_PATTERNS — '`', '<?', '?>', '/*', '$$', '${'         :143-149
        2. DENIED_STATEMENTS — include/require (word-boundary)           :152-159
        3. DENIED_FUNCTIONS — 60+ islev, `islev\s*\(` deseni           :164-171
        4. DENIED_KEYWORDS — new/echo/print/exit/…                      :175-182
        5. değişken-fonksiyon: yalnız $\GLOBALS…( gibi sistem çağrısı   :187-196
```

### 3.1 Ölçülen sınırlar (17 ifade, `DOC-AGAC-3-dogrula3.php` V2)

| İfade | Sonuç | Neden |
|---|---|---|
| `$getEmoji($url)`, `$cleanPhone($p)` | ✅ **geçer** | Değişken-fonksiyon çağrısı meşru; guard yalnız süperglobal çağrısını yasaklar (`:191`) |
| `system_errors`, `$required_role` | ✅ geçer | `DENIED_FUNCTIONS`/`KEYWORDS` word-boundary kullanır (`system(`, `system` değil) |
| `$GLOBALS["x"]` | ✅ geçer | Çağrı **değil** dizin erişimi; `$(...)` deseni tutmuyor |
| `$a ?? "b"`, `count($items) > 2` | ✅ geçer | Yasaklı kalıp yok |
| `str_contains($a,"?x=/admin")` | ✅ geçer | Metin içinde `include`/`require` kelimesi geçse bile word-boundary + `()` şartı kurtarır |
| `` `ls` `` | ❌ red | `DENIED_PATTERNS['`']` |
| `system("ls")`, `eval("1")` | ❌ red | `DENIED_FUNCTIONS` |
| `file_put_contents("/x","y")`, `file_exists("/x")`, `array_map("x",$y)` | ❌ red | `DENIED_FUNCTIONS` (dosya sistemi + geri çağırma tabanlı) |
| `new X()` | ❌ red | `DENIED_KEYWORDS['new']` |
| `foo /* yorum`, `${x}`, `$$x` | ❌ red | `DENIED_PATTERNS` |

**Tuzak (tasarım, kod yorumu `:20-26`):** Bu **izin listesi değil**, yasaklı-yapı
listesidir. Korpusta meşru değişken-fonksiyon çağrıları var; yasaklama onları
kırardı. Sonuç: **guard, "bu ifade güvenli mi" değil "bu ifade bu yasaklı kalıplardan
birini içeriyor mu" sorusuna cevap verir.** `DENIED_FUNCTIONS` listesinde
`file_exists`/`is_dir` **varken**, listedeki `file_get_contents` de var — yani
bir şablon diskteki dosya varlığını bile yoklayamaz.

**Önbellek sınırı yok:** `self::$sonucOnbellegi` (`:106`) yalnız
`onbellegiTemizle()` ile temizlenir (`:215`). Uzun süreli süreçlerde (cron kervanı)
bellek sınırsız büyür; `TemplateExpressionGuard.php:213` yorumu bunu kabul eder.

## 4. Akış — `RawHtmlGate` (çalışma zamanı kapısı)

`FrontendProvider::safeSnippet()` DB'den gelen 4 snippet'i bu kapıdan geçirir
(`Providers/UI/FrontendProvider.php:124-136`): `google_analytics_code`,
`google_adsense_code`, `head_scripts`, `body_scripts`.

```
RawHtmlGate::sanitize($html)      Handlers/RawHtmlGate.php:61
  ├─ EVENT_ATTR_PATTERN  → on*=="..." nitelikleri kaldırılır     :42, :65-98
  ├─ URI_ATTR_PATTERN    → href/src/action/… değerleri denetlenir :50, :115-137
  │    └─ DANGEROUS_SCHEMES eşleşirse 'about:blank#rbn-blocked' :53
  └─ dongusuzUygula() — özyineleme yok, tek geçiş (bounded)      :138
```

Ölçülen çıktı (V9):

| Girdi | `hasDangerousMarkup` | `sanitize` |
|---|---|---|
| `<a href="javascript:alert(1)">x</a>` | `true` | `<a href="about:blank#rbn-blocked">x</a>` |
| `<img src=x onerror=alert(1)>` | `true` | `<img src=x>` |
| `<a href="data:image/svg+xml;base64,AAA">x</a>` | `true` | `<a href="about:blank#rbn-blocked">x</a>` |
| `<a href="/safe">x</a>` | `false` | değişmez |
| `<svg><script>alert(1)</script></svg>` | **`false`** | **değişmez** |

**Bulgu (yeni, ölçüldü):** `hasDangerousMarkup()` ve `sanitize()` **`<script>`
etiketini hiç ele almıyor** — `DANGEROUS_SCHEMES` yalnız URI şemaları
(`javascript:`, `vbscript:`, `livescript:`, `mocha:`, `data:text/html`,
`data:application/xhtml`, `data:image/svg+xml` — `:53`) ve olay niteliklerini
kapsıyor. Meşru snippet'lerde `<script>` **olmalıdır** (analytics/AdSense tam
olarak `<script>` ile gelir), bu yüzden kaldırılamaz; ancak `<script>alert(1)</script>`
gibi **ham `<script>` gövdesi** de geçiyor. `sanitize()` yalnız `on*=` ve şema
taraması yaptığı için **inline script içeriği korunur** — bu, `FrontendProvider`
yorumunda (`:111-123`) bilinçli bir seçimdir ("meşru snippet'ler AYNEN korunur")
ama XSS yüzeyi `on*=` dışında kapatmaz.
Ayrıntı: [acik-sorular.md §1.9](../../acik-sorular.md).

## 5. Akış — UI handler'ları

`RenderService::prepareContext()` (`Services/RenderService.php:68-83`) her render'da
`$this->handler($type)->prepare($type, $view, $data)` çağırır:

```
RenderService::prepareContext('frontend', 'home', $data)
  └─ handler('frontend') → FrontendHandler::prepare('frontend','home',$data)  UI/FrontendHandler.php:20
     └─ $data döner (BaseRender::prepare varsayılanı ile aynı)
```

* **Frontend** (49 satır): yalnız sözleşme; gerçek veri toplama
  `FrontendProvider::render()` içinde yapılır (settings okuma, SEO, asset).
* **Auth** (94 satır): `AuthIdentity` üzerinden oturumdaki kimliği `$data`'ya
  bağlar. Panel/frontend `BaseRender::bootHarmony()`'de kullanıcı verisini zaten
  enjekte eder (`Core/Base/Web/BaseRender.php:79-95`); bu handler **kimlik
  nesnesi** tarafını tamamlar.
* **Panel** (131 satır): `PanelIdentity` + `ConfigMap` ile panel öneki ve panel
  kimliğini çözer.

**Tuzak:** Bu üç sınıf `BaseRender`'ı genişletir, `BaseComponent`'i değil; bu
yüzden `provider()`/`resolver()` yardımcıları da kullanılabilir.

## 6. Yapılandırma

`Handlers/` altında **sabit tanımlayan Config sınıfı yoktur** — güvenlik
listeleri sınıf içi `private const`'tur:

| Sabit | Kapsam | Kaynak |
|---|---|---|
| `TemplateExpressionGuard::DENIED_FUNCTIONS` | ~60 islev: kod çalıştırma, dosya sistemi, geri çağırma, durum, ağ | `TemplateExpressionGuard.php:44-66` |
| `::DENIED_KEYWORDS` | 13 dil yapısı (`new`, `echo`, `print`, `exit`, `die`, `goto`, `yield`, `function`, `declare`, `namespace`, `instanceof`, `clone`, `unset`) | `:75-78` |
| `::DENIED_STATEMENTS` | 4 (`include`, `include_once`, `require`, `require_once`) | `:87-89` |
| `::DENIED_PATTERNS` | 6 karakter/seyrek kalıp | `:96-103` |
| `RawHtmlGate::DANGEROUS_SCHEMES` | 7 şema | `RawHtmlGate.php:53` |

## 7. Örnek (gerçek koddan)

```php
// Core/Render/Handlers/UI/PanelHandler.php:25
public function prepare(string $type, ?string $view, array $options = []): array
```

```php
// Ölçülen çıktı — guard'ın meşru değişken-fonksiyon çağrısını geçirmesi
(new TemplateExpressionGuard)->isSafe('$getEmoji($url)')   // true
(new TemplateExpressionGuard)->isSafe('system("ls")')       // false
```

## 8. İlgili belgeler

* [Core/Render genel](README.md) · [Builders](Builders.md) · [Providers](Providers.md)
* [Core/Base/Web.md](../Base/Web.md) (`BaseRender::prepare`/`bootHarmony`) ·
  [Core/System/Kernel.md](../System/Kernel.md) (panic/`RBN_PANIC_ACTIVE`) ·
  [Core/Support/Exceptions.md](../Support/Exceptions.md)
* [Açık sorular §1.9](../../acik-sorular.md) (`RawHtmlGate` `<script>` kapsamı)