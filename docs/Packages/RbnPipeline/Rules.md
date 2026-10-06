# Packages/RbnPipeline/Rules — prompt kuralları (11 kural sınıfı)

> **Doğrulanan kod tabanı:** `d49b4413` (dal `feat/fw-license-master`) · **Tarih:** 2026-10-05 · **Yayın:** 0.9.5 = bu commit + sonrası; belge yalnız doğrulama anındaki kodu anlatır
> **Kaynak klasör:** `Packages/RbnPipeline/Rules/` — **11 `*.php`**
> (`Blog/` 3 + `Prompt/` 7 + `Youtube/` 1).
> **Envanter:** 11 dosyanın **11'i** aşağıda anlatıldı.
> Üst belge: [../RbnPipeline.md](../RbnPipeline.md).

## 1. Ne işe yarar, kim kullanır

Her kural, yapay zekâ istemine eklenen **tek bir talimat bloğunu**
(`compileInstructions()`) ve o yanıt üzerinde çalışan **tek bir temizleme
adımını** (`sanitizeResponse()`) temsil eder. Kural, `PromptRuleInterface`
uygular; `PromptBuilder` onları sırayla derler.

**Kimler çağırır:** `Packages/RbnPipeline/Builders/PromptBuilder.php`
(`json()`, `seo()`, `image()`, `writingTone()`, `identity()`, `youtube()`,
`innerLinking()`, `blogStructure()`, `contentQuality()` kısayolları) ve
`Presets/*` sınıfları.

## 2. Klasör/dosya envanteri (11/11)

| Dosya | Görev | Önemli public yöntemler |
|---|---|---|
| `Prompt/JsonResponseRule.php` | JSON biçimlendirme ve otonom onarım merkezi; şema alanlarını isteme basar. | `setSchema(array $schema)`, `compileInstructions(array $context = [])`, `sanitizeResponse(string $response, array $context = [])` |
| `Prompt/SeoMetaRule.php` | Standart SEO meta gereksinimleri, anahtar öbek kuralı ve otonom yedek üretimi. | `getSeoSchemaFields()`, `compileInstructions(array $context = [])`, `sanitizeResponse(string $response, array $context = [])` |
| `Prompt/ImagePromptRule.php` | Imagen 3 için **İngilizce ve Türkçe karakterden arındırılmış** görsel prompt üretimi. | `compileInstructions(array $context = [])`, `sanitizeResponse(string $response, array $context = [])` |
| `Prompt/ImageSafetyRule.php` | Görsel prompt güvenlik sınırları ve yasaklı kelimeler (yardımcı, doğrudan kural değil). | `getPromptAlphabetInstructions()`, `getSafetyInstructions()` |
| `Prompt/ImageCompilerRule.php` | Standart stil şablonlarıyla temiz görsel prompt derlemesi (Imagen hedefli). | `compileInstructions(array $context = [])`, `sanitizeResponse(string $response, array $context = [])` |
| `Prompt/WritingToneRule.php` | Projeye özel yazım tonu, anlatıcı bakış açısı, marka kişiliği rehberi. | `compileInstructions(array $context = [])`, `sanitizeResponse(string $response, array $context = [])` |
| `Prompt/IdentityRule.php` | Proje kimliği ve özel persona kurallarını **dinamik** kurar ve uygulatır. | `compileInstructions(array $context = [])`, `sanitizeResponse(string $response, array $context = [])` |
| `Blog/BlogStructureRule.php` | Blog makalesi için standart HTML yapısı, uzunluk, alıntı ve SSS gereksinimleri. | `compileInstructions(array $context = [])`, `sanitizeResponse(string $response, array $context = [])` |
| `Blog/ContentQualityRule.php` | Makale kalitesi, **"AI slop"** karşıtı ve konuya sadakat için ana zorlayıcı. | `compileInstructions(array $context = [])`, `sanitizeResponse(string $response, array $context = [])` |
| `Blog/InnerLinkingRule.php` | Katı üst sınırlı dinamik iç bağlantılandırma (SEO gereksinimi). | `compileInstructions(array $context = [])`, `sanitizeResponse(string $response, array $context = [])` |
| `Youtube/YoutubeScriptRule.php` | YouTube senaryoları için ek standartlar. | `compileInstructions(array $context = [])`, `sanitizeResponse(string $response, array $context = [])` |

**Kapsama:** 11/11.

## 3. Akış

```
PromptBuilder::compile()
 ├─ her kayıtlı kural için: $rule->compileInstructions($context)
 └─ birleştirilmiş istem metni + görev metni (task())

PromptBuilder::sanitize($response)
 └─ her kayıtlı kural için: $rule->sanitizeResponse($response, $context)
     ├─ JsonResponseRule      → JSON sarmalayıcı sökme, iç içe çözme, onarım
     │                          (Concerns\SanitizesResponseTrait üzerinden)
     ├─ BlogStructureRule     → HTML yapısı, SSS, alıntı
     ├─ ContentQualityRule    → kalite/sadakat süzgeci
     └─ InnerLinkingRule      → bağlantı sayısı/şekli
```

`ImageSafetyRule` bu zincirin **elemanı değildir**; `ImagePromptRule` ve
`ImageCompilerRule` onun talimatlarını `getPromptAlphabetInstructions()` /
`getSafetyInstructions()` ile çağırarak kullanır.

## 4. Yapılandırma / ayar anahtarları

| Anahtar | Yer | Not |
|---|---|---|
| `JsonResponseRule::setSchema($schema)` | `Prompt/JsonResponseRule.php` | Şema verilmezse kuralın kendi varsayılanı kullanılır |
| `image_prompt_mode` | `Builders/PromptBuilder.php:62` | `'metadata'` — `imagePrompt()` bu değeri context'e ekler |
| Proje kimliği / persona | `Prompt/IdentityRule.php` | Proje ayarlarından gelir; kural metni dinamik üretir |

## 5. Tuzaklar ve kurallar

1. **Kural tekilleştirme `PromptBuilder`'da yapılır**, kural sınıfında değil
   (`Builders/PromptBuilder.php:28-39`); iki kısayol aynı kuralı iki kez
   ekleyemez.
2. **`sanitizeResponse()` sırası önemlidir:** JSON onarımı diğer kural
   temizlemelerinden **önce** çalışmalıdır, aksi halde metin tabanlı
   temizleme bozuk JSON'u bozar (`JsonResponseRule` önce eklenmelidir).
3. **`ImageSafetyRule` tek başına kural değildir**; doğrudan
   `PromptBuilder` kısayolu yoktur.
4. **`ContentQualityRule` en ağır kuraldır** (171 satır) — üretilen metni
   olduğu gibi yayınlamak bu kuralın varlık nedenidir; atlanırsa "AI slop"
   içerik yayına girer.

## 6. Örnek (gerçek koddan)

```php
$builder = $this->builder('prompt')
    ->json(['baslik' => 'string', 'icerik' => 'string'])
    ->seo()
    ->blogStructure()
    ->contentQuality();

$metin = $builder->sanitize($aiYanit);
```

## 7. İlgili belgeler

* [../RbnPipeline.md](../RbnPipeline.md) — builder'lar, preset'ler, servisler
* Sözleşmeler: `Packages/RbnPipeline/Contracts/PromptRuleInterface.php` (kaynak kod; belge §3.4)
