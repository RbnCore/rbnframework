# Packages/RbnApi — dış servis kapısı (API sağlayıcıları + servisler)

> **Doğrulanan kod tabanı:** `d49b4413` (dal `feat/fw-license-master`) · **Tarih:** 2026-10-05 · **Yayın:** 0.9.3 = bu commit + sonrası; belge yalnız doğrulama anındaki kodu anlatır
> **Kaynak klasör:** `Packages/RbnApi/` — **27 `*.php`**
> (1 kök + `Managers/` 3 + `Models/` 3 + `Providers/` 10 + `Services/` 10).
> **Envanter:** 27 dosyanın **27'si** aşağıda anlatıldı.
> Kardeş belgeler: [PackageData.md](PackageData.md) (paket kayıt haritası),
> [RbnEmail.md](RbnEmail.md), [RbnFile.md](RbnFile.md),
> [RbnPipeline.md](RbnPipeline.md), [RbnUtility.md](RbnUtility.md).

## 1. Ne işe yarar, kim kullanır

Tüm dış servis çağrılarının **tek katmanıdır**. Üç katman vardır ve
her katman tek yönlü çağrılır:

```
Controllers / Tasks / Services
  → RbnApi\Services\*        (yüksek seviye, iş bilgisi taşır)
     → RbnApi\Providers\*    (düşük seviye HTTP + URL + anahtar)
        → ApiManager::resolveApiKey()  (anahtar çözümü)
           → RemoteRequest (Core/Http/RemoteRequest.php) → cURL
```

**Kimler çağırır:** `Packages/PackageData.php:24-34, 100-110` bunları
`services.*` ve `providers.*` anahtarlarıyla kaydeder; herhangi bir controller
`$this->service('telegram')`, `$this->manager('api')` gibi adlarla erişir.
`Packages/RbnPipeline` sosyal paylaşımda doğrudan
`FacebookService`/`InstagramService` çağırır
(`RbnPipeline/Services/AutoTaskManager.php:457, 491`).

## 2. Klasör/dosya envanteri (27/27)

### 2.1 Kök (1)

| Dosya | Görev | Önemli public yöntemler |
|---|---|---|
| `ApiService.php` | Birleşik API geçidi (`app.gemini` / `app.google` tipi servisler). | `gemini(string $type, string $task, array $context = [])`, `google(string $type, array $params = [], ?string $projectKey = null)` |

### 2.2 `Managers/` (3)

| Dosya | Görev | Önemli public yöntemler |
|---|---|---|
| `Managers/ApiManager.php` | Anahtar çözümleme ve API loglama. `#[Component(alias:'api', type:'manager')]`. | `resolveApiKey(string $apiName, ?string $projectKey = null, array $context = []): string\|array\|null`, `resolveApiKeyType(...)`, `log(string $service, string $level, string $message, array $context = [], ?string $projectKey = null)`, `getFlattenedKeys()`, `getLabelForKey(string $key)` |
| `Managers/AiUsageManager.php` | AI kullanım kaydı, maliyet hesabı ve rapor. | `recordUsage(string $model, array $usageMetadata, ?string $projectKey = null, ?string $taskKey = null, string $requestType = 'text')`, `calculateCost(string $model, int $promptTokens, int $outputTokens)`, `getCurrencyRates()`, `getFilteredReport(array $filters = [], int $page = 1, int $perPage = 20)`, `clearLogs(?string $projectKey = null)` |
| `Managers/GeminiAppManager.php` | Merkezî AI orkestratörü: proje anahtarı, modül, prompt handler, persona birleştirme, yanıt biçimleme. | `generateText(string $typeOrTitle, string\|array $titleOrContext = '', array $context = [])`, `generateImage(string $topic, ?string $projectKey = null, bool\|string $save = false, array $extraContext = [])`; protected: `runTaskOrchestration(...)` `:137`, `parseTextArgs(...)` `:202`, `prepareContext(...)` `:219`, `resolvePromptModule(...)` `:236` |

### 2.3 `Models/` (3)

| Dosya | Görev | Önemli public yöntemler |
|---|---|---|
| `Models/ApiKeysRegistry.php` | **Anahtar beyaz listesi**: API adı → ayar anahtarı adı; ücretli anahtar listesi. | `const MAP` (`:16-48`), `const PAID_KEYS` (`:53-57`), `static isPaidKey(string $apiName): bool` (`:62`) |
| `Models/AiData.php` | Gemini model/fiyat/kabiliyet sözlüğü (`const MODELS`, `:17`). | `get(string $model)`, `getPricing(string $model)`, `getByProvider(string $provider = 'google')`, `getPricingMap()` |
| `Models/OpenAiPricingRegistry.php` | OpenAI GPT model fiyat referansı (yalnız sabitler). | — |

### 2.4 `Providers/` (10)

| Dosya | Görev | Önemli public yöntemler |
|---|---|---|
| `Providers/GeminiProvider.php` | Google Gemini AI bağlantı düğümü; `RemoteRequest` üzerinden çağrır. | `call(string $model, array $payload, bool $isImage = false, ?string $projectKey = null, array $context = [])` |
| `Providers/GoogleMapsProvider.php` | Google Places API iş yeri detayı. | `getPlaceDetails(string $placeId, string $referer, ?string $projectKey = null)` |
| `Providers/GoogleAnalyticsProvider.php` | GA4 Data API; servis hesabı JSON'u ile token üretir. | `getPropertyId(string $projectKey)`, `isActive(string $projectKey)`, `getServiceAccountConfig(string $projectKey)`, `getReports(string $projectKey, ?string $startDate = null, ?string $endDate = null)`, `getTurkeyCityReports(...)` |
| `Providers/FacebookProvider.php` | Meta Facebook Graph API çağrı düğümü. | `call(string $method, array $params = [], string $httpMethod = 'GET', ?string $projectKey = null)` |
| `Providers/InstagramProvider.php` | Meta Graph API çağrı düğümü. | `call(string $method, array $params = [], string $httpMethod = 'GET', ?string $projectKey = null)` |
| `Providers/TwitterProvider.php` | X/Twitter API çağrı düğümü. | `call(string $method, array $params = [], string $httpMethod = 'POST', bool $isUpload = false, ?string $projectKey = null)` |
| `Providers/YoutubeProvider.php` | YouTube Data API v3 çağrı düğümü. | `call(string $endpoint, array $params = [], ?string $projectKey = null)`, `getVideo(...)`, `getChannel(...)`, `getPlaylistItems(...)`, `getLiveStreams(...)`, `getPopularVideos(...)` |
| `Providers/ShopierProvider.php` | Shopier REST çağrı düğümü. | `call(string $method, array $params = [], string $httpMethod = 'GET', ?string $projectKey = null)` |
| `Providers/TmdbProvider.php` | TMDB çağrı düğümü ve kısayolları. | `call(string $endpoint, array $params = [], ?string $projectKey = null)`, `search(...)`, `getMovie(...)`, `getSeries(...)`, `getPopular(...)`, `getPerson(...)`, `discoverTmdb(...)` |
| `Providers/TelegramProvider.php` | Telegram Bot API düğümü: token/username/webhook secret `z_settings_api` üzerinden `SettingsApiRepository` ile okunur (`:124,146,162,269`); `secret_token` `hash_equals` ile doğrulanır. | `botToken()`, `botUsername()`, `apiBase()`, `isBotConfigured()`, `isValidChatId(...)`, `isValidStartCode(...)`, `generateStartCode()`, `parseStartCode(...)`, `buildStartLink(...)`, `webhookSecret(bool $persist = true)`, `verifyWebhookSecret(string $given)`, `call(...)`, `sendMessage(...)`, `setWebhook(...)`, `getWebhookInfo()`, `deleteWebhook()`, `getMe()`, `sanitizeText(...)`, `escape(...)`, `cleanDescription(...)` |

### 2.5 `Services/` (10)

| Dosya | Görev | Önemli public yöntemler |
|---|---|---|
| `Services/GeminiService.php` | Gemini yüksek seviye arayüzü (`@property GeminiProvider $gemini`). | `call(...)`, `ask(string $prompt, ?string $model = null, ?string $projectKey = null, array $context = [])`, `askImage(...)` |
| `Services/GoogleService.php` | Google iş yeri + Analytics yüksek seviye arayüzü. | `getCompanyGoogleData(string $placeId, string $referer, ?string $projectKey = null)`, `isAnalyticsActive(?string $projectKey = null)`, `getAnalyticsReport(?string $projectKey = null, ?string $startDate = null, ?string $endDate = null, string $type = 'standard')` |
| `Services/FacebookService.php` | Sayfa yayınlama ve işlemler. | `call(...)`, `getPageInfo(string $pageId = 'me', array $fields = [...], ?string $projectKey = null)`, `postFeed(string $pageId, string $message, ?string $link = null, ?string $projectKey = null)`, `postPhoto(string $pageId, string $imageUrl, string $caption = '', ?string $projectKey = null)` |
| `Services/InstagramService.php` | Medya konteyneri + yayın akışı. | `call(...)`, `getProfile(...)`, `createMediaContainer(...)`, `publishMedia(...)`, `publishPhoto(...)` |
| `Services/TwitterService.php` | X gönderi paylaşımı. | `call(...)`, `send(string $text, ?string $imagePath = null, ?string $projectKey = null)`, `shareContent(string $title, string $url, ?string $imagePath = null, string $suffix = '', ?string $projectKey = null)` |
| `Services/YoutubeService.php` | Video/kanal/oynatma listesi okuma. | `call(...)`, `getVideo(...)`, `getChannel(...)`, `getPlaylistItems(...)`, `getLiveStreams(...)`, `getPopularVideos(...)` |
| `Services/TmdbService.php` | TMDB yüksek seviye + fragman URL çıkarma. | `call(...)`, `search(...)`, `getMovie(...)`, `getSeries(...)`, `getPopular(...)`, `extractTrailerUrl(array $videos)`, `getPerson(...)`, `discoverTmdb(...)` |
| `Services/ShopierService.php` | Ödeme bağlantısı üretimi ve sipariş doğrulama. | `generatePaymentUrl(string $orderNo, string $productName, float $totalPrice, ?string $imageUrl = null, ?string $projectKey = null)`, `verifyOrderOnline(string $productId, ?string $projectKey = null)` |
| `Services/TelegramService.php` | Telegram bot geçidi; proje bağımsız kısım (olay kuralları, cooldown, `telegram_state` **projede kalır**). | `botToken()`, `botUsername()`, `apiBase()`, `isBotConfigured()`, `isValidChatId(...)`, `isValidStartCode(...)`, `generateStartCode()`, `parseStartCode(...)`, `buildStartLink(...)`, `webhookSecret(bool $persist = true)`, `verifyWebhookSecret(...)`, `sendMessage(...)`, `setWebhook(...)`, `getWebhookInfo()`, `deleteWebhook()`, `getMe()`, `call(...)`, `sanitizeText(...)`, `escape(...)`, `cleanDescription(...)` |
| `Services/IndexNowService.php` | Arama motoru IndexNow bildirimi (doğrulama dosyası yazar). | `ping(string $domain, string $projectKey, array $urls)`, `getStats(string $projectKey, int $urlsCount)`, `pingAll(...)`, `pingSingleUrl(string $projectKey, string $path)` |

**Kapsama:** 27/27.

## 3. Akış

### 3.1 API anahtarı çözümleme (ücretsiz / ücretli ayrımı)

```
Provider::call(...)
 └─ ApiManager::resolveApiKey('gemini', $projectKey)        ApiManager.php:21
     ├─ ApiKeysRegistry::MAP['gemini'] → 'GEMINI_API_KEY'   :24
     ├─ resolveApiKeyType() → 'free' | 'paid'              :32
     ├─ FREE  → önce proje önbelleği: api_keys['GEMINI_API_KEY_FREE']   :36-41
     │        → yoksa Master DB: key_type='free' AND is_active=1       :45-51
     └─ PAID  → SIFIR DB SORGUSU, yalnız project_data('api_keys')      :57-60
                (dizi tipindeki anahtarlar alan alan çözülür :61-70)
```

### 3.2 Gemini metin üretimi

```
$geminiApp->generateText($type, $title, $context)
 └─ GeminiAppManager::generateText()        GeminiAppManager.php:?
     ├─ parseTextArgs()   → geri uyumlu (title,type) imzası    :202
     ├─ prepareContext()  → proje/modül/persona birleştirme     :219
     ├─ resolvePromptModule() → taskMap + kural listesi         :236
     └─ runTaskOrchestration() → GeminiService::call()
         └─ GeminiProvider::call()
             ├─ ApiManager::resolveApiKey('gemini')
             └─ $this->remote->post(...)  (Core/Http/RemoteRequest.php)
```

### 3.3 IndexNow bildirimi

```
IndexNowService::ping($domain, $projectKey, $urls)   IndexNowService.php:24
 ├─ url listesi boşsa → hata döner                        :28-30
 ├─ extractDomain() ile host temizlenir (port/şema atılır)  :34
 ├─ is_local() ise ping YAPILMAZ, success döner            :37-40
 ├─ hashToken($projectKey.'_indexnow') → deterministik anahtar  :43
 ├─ public köke "<key>.txt" doğrulama dosyası yazılır        :46-53
 └─ POST https://api.indexnow.org/indexnow                  :56-64
     {host, key, keyLocation, urlList}
```

### 3.4 Sosyal paylaşım (RbnPipeline'dan)

```
AutoTaskManager::dispatchSocialShares()      RbnPipeline/Services/AutoTaskManager.php:386
 ├─ caption birleştirme (özet + hashtag + URL)                :433
 ├─ FacebookService::postFeed(pageId, caption, url)           :457
 └─ InstagramService::publishPhoto(accountId, imageUrl, caption)  :491
```

## 4. Yapılandırma / ayar anahtarları

| Anahtar | Yer | Varsayılan / not |
|---|---|---|
| `ApiKeysRegistry::MAP` | `Models/ApiKeysRegistry.php:16-48` | Beyaz liste. Listede olmayan API adı `resolveApiKey()`'dan `null` döner (`ApiManager.php:24-27`) — yani isimsiz anahtar **reddedilir**, tahmin edilmez. |
| `ApiKeysRegistry::PAID_KEYS` | `:53-57` | `gemini`, `google`, `openai`. Bu listedekiler **sıfır DB sorgusu** ile proje önbelleğinden okunur (`ApiManager.php:57`) |
| `project_data('api_keys')` | `ApiManager.php:37, 58` | Proje keşif önbelleği; `*_FREE` anahtarları yalnız `free` dalında aranır |
| `z_settings_api` | `Providers/TelegramProvider.php:124,146,162,269` | Telegram token/username/webhook secret tek kaynağı (`SettingsApiRepository`) |
| `is_local()` | `Services/IndexNowService.php:37` | Yerelde ping atlanır (dış ağa çıkmaz) |

## 5. Tuzaklar ve kurallar

1. **Anahtar sızdırmama kuralı:** Telegram token'ı `botToken()` dışına
   çıkmaz; yalnız `call()` içinde URL'e girer, hiçbir loga/yazmaya yazılmaz
   (`TelegramProvider.php` sınıf dokümanı). Token yoksa **fail-closed**:
   sahte "gonderdim" dönmez.
2. **Katman ayrımı kuralı:** iş kuralı (hangi olay kime gider, cooldown,
   `telegram_state`) `RbnApi`'de **yoktur**; proje kendi servisinde yazar
   (`TelegramService` sınıf dokümanı, "KAPSAM DIŞI" listesi).
3. **Beyaz liste dışı API reddi sessiz değildir ama da hata değildir:**
   `resolveApiKey()` `null` döner, çağıran kendi hatasını üretir.
4. **IndexNow yerelde sessizce atlar** ve `success` döner — log'dan
   ayrılırsa "kaç kez pingledi" sayacı yanıltır (`IndexNowService.php:37-40`).
5. **`recordUsage()` maliyet için `AiData`/`OpenAiPricingRegistry` fiyat
   haritasını kullanır**; model bilinmiyorsa `calculateCost()` para birimi
   bilgisi olmadan çalışır (`getCurrencyRates()` ayrıdır).
6. **Google Analytics sağlayıcı kimlik bilgisi JSON ile gelir**
   (`getServiceAccountConfig()`, `:57`); bu değer proje ayarındadır, koda
   gömülü değildir.

## 6. Örnek (gerçek koddan)

```php
// Telegram mesajı (proje bağımsız kısım)
$telegram = $this->service('telegram');
$code = $telegram->generateStartCode();
$link = $telegram->buildStartLink($code);
$telegram->sendMessage($chatId, $text);

// API anahtarı çözümleme (doğrudan)
$key = $this->manager('api')->resolveApiKey('gemini', $projectKey);
```

## 7. İlgili belgeler

* [PackageData.md](PackageData.md) — `services.*` / `managers.*` / `providers.*` kayıt haritası
* [RbnPipeline.md](RbnPipeline.md) — bu servisleri tüketen otomasyon hattı
* [../Core/Http/README.md](../Core/Http/README.md) — `RemoteRequest` (tüm dış çağrıların taşıyıcısı)
* [../Core/Http/Security.md](../Core/Http/Security.md) — dışarıdan **gelen** trafiğin koruması (bu paket dışarıya **giden** trafiği yönetir)
* [../Core/System/Config.md](../Core/System/Config.md) — `project_data()` ve ayar önceliği
* [../kavramlar/02-yapilandirma.md](../kavramlar/02-yapilandirma.md)
