# Packages/RbnPipeline — yapay zekâ içerik hattı (prompt kuralları, preset'ler, otomasyon)

> **Doğrulanan kod tabanı:** `d49b4413` (dal `feat/fw-license-master`) · **Tarih:** 2026-10-05 · **Yayın:** 0.9.4 = bu commit + sonrası; belge yalnız doğrulama anındaki kodu anlatır
> **Kaynak klasör:** `Packages/RbnPipeline/` — **31 `*.php`**
> (3 `Builders/` + 3 `Builders/Tasks/` + 4 `Concerns/` + 2 `Contracts/` +
> 6 `Presets/` + 3 `Rules/Blog/` + 7 `Rules/Prompt/` + 1 `Rules/Youtube/` + 2 `Services/`).
> **Envanter:** 31 dosyanın **31'i** aşağıda anlatıldı.
> Bu paket spek eşiğini (≥20 php) aştığı için alt dal belgeleri de üretildi:
> [Rules.md](RbnPipeline/Rules.md).

## 1. Ne işe yarar, kim kullanır

"RSS/Trends'ten haber al → yapay zekâ ile metin üret → görsel üret → taslak
kaydet → yayınla → sosyal medyaya dağıt" zincirinin tamamıdır. Tasarım
**kural tabanlıdır**: her beceri (`Rules/`) küçük bir sınıftır, her hazır
kombinasyon (`Presets/`) bu kuralları birleştirir, `PromptBuilder` hepsini
tek metne derler.

**Kimler çağırır:** cron/queue motoru → `Builders/Tasks/*` (görev sınıfları) →
`AbstractTaskBuilder::autopilot()` → `Services/AutoTaskManager.php` ve
`Services/ExternalFetchManager.php` (`PackageData.php:44-45`).

## 2. Alt klasör haritası

| Alt klasör | `*.php` | İçerik |
|---|---|---|
| `Builders/` | 3 | `PromptBuilder` + iki soyut taban |
| `Builders/Tasks/` | 3 | Somut görev inşacıları |
| `Concerns/` | 4 | Paylaşılan trait'ler (çözümleme, temizleme, yaşam döngüsü) |
| `Contracts/` | 2 | İki arayüz |
| `Presets/` | 6 | Hazır kural kombinasyonları |
| `Rules/` | 11 | Kural sınıfları (`Blog/` 3, `Prompt/` 7, `Youtube/` 1) — ayrıntı [Rules.md](RbnPipeline/Rules.md) |
| `Services/` | 2 | `AutoTaskManager`, `ExternalFetchManager` |

## 3. Klasör/dosya envanteri (31/31)

### 3.1 `Builders/` (3)

| Dosya | Görev | Önemli public yöntemler |
|---|---|---|
| `Builders/PromptBuilder.php` | Sıvı (fluent) inşacı: kuralları + görev metnini derler ve yanıtı temizler. | `reset()`, `applyRule(PromptRuleInterface $rule)`, `json(?array $schema = null)`, `seo()`, `image()`, `imagePrompt()`, `innerLinking()`, `blogStructure()`, `writingTone()`, `identity()`, `contentQuality()`, `preset(string $name, array $context = [])`, `youtube()`, `task(string $taskInstruction)`, `withContext(array $context)`, `compile()`, `sanitize(string $response)` |
| `Builders/AbstractPresetBuilder.php` | Ortak şema ve zenginleştirme sağlayan preset tabanı. | — (soyut; alt sınıflar `apply()` uygular) |
| `Builders/AbstractTaskBuilder.php` | Tüm otomatik görev inşacılarının şablonu: yaşam döngüsü doğrulaması, cron zaman eşleşmesi, günlük kota, AI/görsel/yayın kısayolları. | `autopilot(array $params = [])` (`:37`), `abstract protected executeAutopilot(array $params): array` (`:29`), protected: `getProjectKey(): string` (`:75`), `resolveCandidate(array $params = []): array` (`:87`), `buildContext(array $extraContext = []): array` (`:142`), `generateText(...)` (`:249`), `savePost(...)` (`:274`), `generateImage(...)` (`:288`), `finalizePost(...)` (`:308`), `finishTask(...)` (`:350`) |

### 3.2 `Builders/Tasks/` (3)

| Dosya | Görev | Önemli public yöntemler |
|---|---|---|
| `Builders/Tasks/BaseContentTaskBuilder.php` | Tüm metin/ içerik görevlerinin 6 adımlı standart boru hattı. | — (soyut; `executeAutopilot()` uygular) |
| `Builders/Tasks/ContentTaskBuilder.php` | Standart içerik görevi (en ince somut sınıf). | — |
| `Builders/Tasks/ContentRewriteTaskBuilder.php` | Yeniden yazım (rewrite) görevi. | — |

### 3.3 `Concerns/` (4)

| Dosya | Görev | Önemli public yöntemler |
|---|---|---|
| `Concerns/AutoTaskTrait.php` | Tüm otomatik görevler için ortak yaşam döngüsü: proje anahtarı, günlük yayın sayacı, yayınlama, önbellek temizleme, yayın bağlantısı çözümü. | `getProjectKey(?string $key = null, bool $throwOnEmpty = false)`, `getTodayPublishedCount(array $params)`, `publishPost(int $postId, string $modelAlias)`, `clearSitemapCache()`, `clearFeedCache()`, `clearContentCache()`, `resolvePublishedPostLink(?string $projectKey = null, string $typePrefixOrUrl = '', string $postSlug = '', string $categorySlug = '', bool $asHtmlLink = true)` |
| `Concerns/PipelineResolverTrait.php` | Kaynak kipi çözümü, taslak/RSS havuzu, kategori eşleme, dengeli aday seçimi, kara liste GUID çözümü. | `resolveCategories(mixed $categoriesOrModel = null, mixed $selectedId = null, ?array $candidateRes = null, bool $returnSlug = false)`, `resolveRecentPosts(mixed $postModelOrName = null, int $limit = 50)`, `fetchDraftCandidate(array $params)`, `getNextBalancedItem(array $candidates, array $posts = [], array $categories = [])`, `resolveGuidsForBlacklist(array $candidateRes, array $newsData = [])` |
| `Concerns/PipelineSanitizerTrait.php` | AI yanıtı normalizasyonu, SSS temizleme, HTML temizliği, mevcut bağlantıların AI için biçimlenmesi. | `normalizeAiResponse(array $aiResult)`, `formatFaqs($faqsRaw)`, `formatExistingLinksForAi(array $rawPosts, array $categoryMap = [], string $type = 'blog')` |
| `Concerns/SanitizesResponseTrait.php` | Markdown JSON sarmalayıcı sökme, iç içe çözme, kontrol karakteri temizliği, kaçırılmamış tırnak onarımı, dizi çözme. | `cleanJsonWrapper(string $rawText)`, `repairJson(string $json)`, `extractBalancedJson(string $text)` |

### 3.4 `Contracts/` (2)

| Dosya | Görev | Önemli public yöntemler |
|---|---|---|
| `Contracts/PromptRuleInterface.php` | Modüler kural sözleşmesi. | `compileInstructions(array $context = []): string`, `sanitizeResponse(string $response, array $context = [])` |
| `Contracts/PromptPresetInterface.php` | Yeniden kullanılabilir preset sözleşmesi. | `apply(PromptBuilder $builder, array $context = [])` |

### 3.5 `Presets/` (6)

| Dosya | Görev | Önemli public yöntemler |
|---|---|---|
| `Presets/BlogPreset.php` | Blog/makale standart kural kümesi. | `apply(PromptBuilder $builder, array $context = [])` |
| `Presets/NewsPreset.php` | Haber / RSS standart kümesi. | `apply(PromptBuilder $builder, array $context = [])` |
| `Presets/TrendsPreset.php` | Google Trends tabanlı üretim kümesi. | `apply(PromptBuilder $builder, array $context = [])` |
| `Presets/YoutubePreset.php` | YouTube senaryosu kümesi. | `apply(PromptBuilder $builder, array $context = [])` |
| `Presets/ImagePreset.php` | Doğrudan görsel üretimi için temiz küme. | `apply(PromptBuilder $builder, array $context = [])` |
| `Presets/CustomPreset.php` | Anında uygulanan özel kural kümesi. | `apply(PromptBuilder $builder, array $context = [])` |

### 3.6 `Rules/` (11) — ayrıntılı liste [Rules.md](RbnPipeline/Rules.md)

| Dosya | Görev (tek cümle) |
|---|---|
| `Rules/Prompt/JsonResponseRule.php` | JSON biçimlendirme ve otonom onarım kuralı. |
| `Rules/Prompt/SeoMetaRule.php` | SEO meta alanları, anahtar öbekleri ve otonom yedek üretimi. |
| `Rules/Prompt/ImagePromptRule.php` | Imagen 3 için İngilizce/Türkçesiz karakterden arındırılmış görsel prompt'u. |
| `Rules/Prompt/ImageSafetyRule.php` | Görsel prompt güvenlik sınırları ve yasaklı kelimeler. |
| `Rules/Prompt/ImageCompilerRule.php` | Standart stil şablonlarıyla temiz görsel prompt derlemesi. |
| `Rules/Prompt/WritingToneRule.php` | Projeye özel yazım tonu, anlatıcı bakış açısı, marka kişiliği. |
| `Rules/Prompt/IdentityRule.php` | Proje kimliği ve özel persona kurallarını dinamik kurar. |
| `Rules/Blog/BlogStructureRule.php` | Blog makalesi için standart HTML yapısı, uzunluk, alıntı, SSS. |
| `Rules/Blog/ContentQualityRule.php` | Makine kalitesi ("AI slop") karşıtı ana kalıcı. |
| `Rules/Blog/InnerLinkingRule.php` | Katı üst sınırlı dinamik iç bağlantılandırma. |
| `Rules/Youtube/YoutubeScriptRule.php` | YouTube senaryosu standartları. |

### 3.7 `Services/` (2)

| Dosya | Görev | Önemli public yöntemler |
|---|---|---|
| `Services/AutoTaskManager.php` | Yayın hattının yürütücüsü: metin üretimi, taslak kaydetme, görsel üretimi, yayınlama, sosyal dağıtım. | `generateText(string $type, string $title, array $context = [], $taskLog = null)` (`:23`), `saveDraftPost(string $modelAlias, array $postData, ?array $draft = null, $taskLog = null, array $taskParams = [])` (`:89`), `generateAndSavePostImage(string $prompt, string $type, $taskLog = null, array $context = [], ?int $postId = null, ?string $modelAlias = null)` (`:194`), `finalizeAndPublishPost(int $postId, string $modelAlias, string $categorySlug, string $postSlug, string $typePrefix, $taskLog = null, $guids = null, ?string $projectKey = null)` (`:250`), `dispatchSocialShares(int $postId, string $modelAlias, string $publishedUrl, $taskLog = null, ?string $projectKey = null)` (`:386`) |
| `Services/ExternalFetchManager.php` | Dış kaynak (Trends/RSS) aday toplama, tekilleştirme, kara liste ve mevcut içerik karşılaştırması. | `fetchTrendsCandidate(array $params)`, `fetchRssCandidate(array $params)`, `filterUniqueCandidates(array $candidates, string $projectKey, string $modelAlias, string $type = 'generic')`, `getBlacklistedGuids(string $projectKey)`, `getExistingGuids(string $modelAlias, string $projectKey)`, `getPublishedTitles(string $modelAlias, int $limit = 200)`, `blacklistCandidate(string $projectKey, string $guid, string $title = '')` |

**Kapsama:** 31/31.

## 4. Akış

### 4.1 Otomatik görev (cron → yayın)

```
Cron/queue motoru → ContentTaskBuilder::autopilot($params)   AbstractTaskBuilder.php:37
 ├─ 1) category_type türetilir (params → preset adı)          :45-51
 ├─ 2) ZORUNLU parametre denetimi                             :54-59
 │     project_key yok  → CronTaskException::missingParameter
 │     local_model yok  → CronTaskException::missingParameter
 ├─ 3) executeAutopilot($taskParams)                          :62
 │     ├─ resolveCandidate()                        (1. adım) :87
 │     │    ├─ source 'trends' → fetchTrendsCandidate()       :105-106
 │     │    ├─ source 'rss'    → fetchRssCandidate()          :107-108
 │     │    ├─ hiçbiri değilse fetchDraftCandidate()          :111-113
 │     │    └─ aday yoksa → CronTaskException::candidateNotFound  :115-121
 │     │        (taskLog->skipped() ile "atlandı" olarak loglanır)
 │     ├─ buildContext()                             (2. adım) :142
 │     │    taskParams + generation_options + categories + eski haberler
 │     ├─ generateText()   → service('app.gemini')            :249
 │     │    AutoTaskManager::generateText()                   AutoTaskManager.php:23
 │     │    ├─ yayınlanmış yazılar + kategoriler bağlama eklenir  :41-61
 │     │    ├─ $geminiApp->generateText($type,$title,$context)   :64
 │     │    ├─ normalizeAiResponse()                              :73
 │     │    └─ formatFaqs()                                       :76
 │     ├─ generateImage()  → FileService (Packages/RbnFile)  :288
 │     ├─ savePost()       → taslak kayıt                    :274
 │     ├─ finalizePost()   → publishPost()                   :308
 │     │    ├─ publishPost()                                   AutoTaskManager.php:263
 │     │    ├─ clearSitemapCache() / clearFeedCache() / clearContentCache()  :316-318
 │     │    └─ resolvePublishedPostLink(..., asHtmlLink=false)               :371
 │     └─ dispatchSocialShares() → Facebook + Instagram       :376, 386
 └─ CronTaskException yakalanır → success=true + status döner  :63-69
```

**Kritik davranış:** `CronTaskException` **hata değildir**; görev "atlandı"
olarak başarıyla kapanır (`success=true`, `status` dolu) — böylece cron
motoru bunu hata saymaz (`:63-69`).

### 4.2 Prompt derleme

```
PromptBuilder
 ├─ json($schema)?  → JsonResponseRule (aynı sınıf ikinci kez eklenmez)
 │                    applyRule() sınıf bazlı tekilleştirme yapar  PromptBuilder.php:28-39
 ├─ seo() / image() / innerLinking() / …  → ilgili kural sınıfı
 ├─ preset('blog') → BlogPreset::apply($this)   (kuralları tek tek ekler)
 ├─ task('...')    → görev metni
 ├─ withContext([...]) → context
 ├─ compile()      → kuralların compileInstructions() + görev metni
 └─ sanitize($response) → kuralların sanitizeResponse() zinciri
```

### 4.3 Dış kaynak aday toplama

```
ExternalFetchManager::fetchRssCandidate($params)
 ├─ service('rssParser')->parse($url)            RbnUtility
 ├─ getBlacklistedGuids($projectKey)              → daha önce işlenenler
 ├─ getExistingGuids($modelAlias, $projectKey)    → sitede yayınlananlar
 ├─ getPublishedTitles($modelAlias, 200)          → başlık benzerliği
 └─ filterUniqueCandidates($candidates, ...)      → kalan adaylar
```

## 5. Yapılandırma / ayar anahtarları

| Anahtar / parametre | Yer | Varsayılan / not |
|---|---|---|
| `project_key` | `AbstractTaskBuilder.php:54` | **Zorunlu**; yoksa `CronTaskException::missingParameter` |
| `local_model` | `:57` | **Zorunlu**; "tek ve gerçek model parametresi" standardı (`:53`) |
| `category_type` / `content_type` | `:45-51` | Verilmezse `preset`/`type` adından türetilir |
| `source` | `:100` | Varsayılan `'draft'`; `trends` ve `rss` özel dallar |
| `generation_options` | `:144` | `buildContext()` içinde görev parametreleriyle birleştirilir |
| `image_prompt_mode` | `PromptBuilder.php:62` | `'metadata'` — `imagePrompt()` bu context'i ekler |
| Günlük kota | `AutoTaskTrait::getTodayPublishedCount()` | `autopilot()` yaşam döngüsü denetiminde kullanılır |

## 6. Tuzaklar ve kurallar

1. **Aynı kural iki kez eklenmez.** `applyRule()` sınıf adına göre tekilleştirir
   (`PromptBuilder.php:30-35`) — `image()` ve `imagePrompt()` aynı
   `ImagePromptRule` sınıfını ekler, ikinci çağrı **yok sayılır**.
2. **Aday bulunamazsa hata fırlatılmaz.** `CronTaskException::candidateNotFound`
   atılır ve `autopilot()` bunu `success=true` + `status` ile karşılar
   (`:115-121`, `:63-69`). Bu, "bugün yeni içerik yok" durumunu hata gibi
   göstermemenin yoludur.
3. **Zorunlu parametre denetimi baştan yapılır** (`:54-59`); yarım kalan
   görev yarım içerik üretmez.
4. **Kategori hiyerarşisi:** görev içinde `category_type` **açıkça** verilmişse
   o kazanılır; verilmemişse preset adından türetilir (`:45-51`).
5. **Yayın sonrası üç önbellek temizlenir** — sitemap, feed, içerik
   (`AutoTaskManager.php:316-318`). Biri unutulursa yeni yazı sitemap'te
   görünmez.
6. **`resolvePublishedPostLink(..., $asHtmlLink)` bayrağı belirleyicidir:**
   `false` ile ham URL alınır ve sosyal caption'a konur (`:371`).
7. **JSON onarımı merkezidir:** `SanitizesResponseTrait::cleanJsonWrapper/
   repairJson/extractBalancedJson` tüm framework'te tek kopyadır; kural
   sınıfları kendi kopyasını yazmaz, bu trait'i kullanır.
8. **`ContentQualityRule` "AI slop" karşıtıdır**; metin üretimi sonrası
   sanitize aşamasında devreye girer — üretilen içeriği olduğu gibi
   yayınlamak bu kuralın varlık nedenidir.

## 7. Örnek (gerçek koddan)

```php
// Prompt derleme
$prompt = $this->builder('prompt')
    ->json(['baslik' => 'string', 'icerik' => 'string'])
    ->seo()
    ->blogStructure()
    ->identity()
    ->preset('blog', $context)
    ->task('Türkçe bir blog yazısı yaz.')
    ->withContext($context)
    ->compile();

$metin = $prompt->sanitize($aiYanit);
```

## 8. İlgili belgeler

* [RbnPipeline/Rules.md](RbnPipeline/Rules.md) — 11 kural sınıfının ayrıntılı listesi
* [PackageData.md](PackageData.md) — `rules.*`, `presets.*`, `builders.*` kayıt adları
* [RbnApi.md](RbnApi.md) — `app.gemini`, `FacebookService`, `InstagramService`, `rssParser` tüketicisi
* [RbnUtility.md](RbnUtility.md) — RSS ve Google Trends kaynakları
* [RbnFile.md](RbnFile.md) — görsel üretimi sonrası depolama
* Core/Services (`../Core/Services/`) — cron/queue motoru (⏳ ayrı görev; belgesi henüz yazılmadı)
