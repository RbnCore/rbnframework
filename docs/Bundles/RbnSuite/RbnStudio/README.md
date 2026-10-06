# RbnStudio — İçerik yönetimi (makale, haber, kategori, taslak)

> **Doğrulanan kod tabanı:** `d49b4413` (dal `feat/fw-license-master`) · **Tarih:** 2026-10-05 · **Yayın:** 0.9.4 = bu commit + sonrası; belge yalnız doğrulama anındaki kodu anlatır
> **Kaynak klasör:** `Bundles/RbnSuite/RbnStudio/` — **16 `*.php`**
> (Controllers 5 · Models 2 · Views 9)
> **Envanter:** 16 php dosyasının **16'sı** aşağıdaki tabloda anlatıldı.
> Bu paket 20 eşiğinin altında olduğu için **tek belge** yeterlidir.

## 1. Ne işe yarar, kimler kullanır

RbnStudio, panelin **içerik üretim** yarısıdır: yayınlanmış makaleler, yayınlanmış
haberler, içerik kategorileri ve yapay zekâ destekli taslak/fikir havuzu. Dört alt
modül (`posts`, `news`, `categories`, `drafts`) vardır ve her biri kendi rotası,
kontrolörü ve görünümleriyle ayrıdır.

Bu paket RbnAdmin'ın **yanında**, aynı `admin` middleware grubu altında yüklenir
(`Core/Routes/Mappings/core.php:80-86`).

**Önemli mimari ayrım:** RbnStudio'nın **kendi veri modeli/tablosu yoktur.**
Tüm içerik, projelerin tanımladığı servisler (`content.blog.service`,
`content.news.service`) ve evrensel `app.contentCategory` / `app.contentDraft`
depoları üzerinden çalışır. Bu yüzden `Models/` klasöründe yalnız iki **yapılandırma**
sınıfı vardır (`ModuleData`, `StudioMap`); veri modelleri
`Core/Database/Repositories/Project/` altındadır.

## 2. Dosya envanteri (16 dosya)

### 2.1 `Controllers/` — 5

| Dosya | Görev | Önemli public yöntemler (imza) |
|---|---|---|
| `RbnStudioController.php` | Paket kökü. `#[Module(name:'studio', data: ModuleData::class, context:'backend')]` (`:14-18`). Alt modüllerin **ortak motoru** | korumalı: `getContentConfig(?string $key=null, ?string $projectKey=null): mixed` (`:24`), `hasBlog/hasNews/hasCategory/hasDraft/hasSocial(?string $projectKey=null): bool` (`:38-73`), `resolvePostService/resolvePostModel/resolveCategoryModel/resolveCategoryService/resolveDraftModel/resolveDraftService/resolveNewsService/resolveNewsModel` (`:78-157`), `executeGenerateImage(string $type='blog')` (`:163`), `executeRewrite(string $type='blog')` (`:225`), `getActiveSocialPlatforms(?string $projectKey=null): array` (`:267`), `executeSocialModal(string $type, int\|string\|null $id=null): mixed` (`:318`), `executeSocialShare(string $type='blog')` (`:382`) |
| `PostsController.php` | Yayınlanmış makaleler. `#[SubModule(module:'studio', entity:'posts')]` (`:13`) | `index()`, `save()`, `delete($id)`, `edit(?int $id=null)`, `rewrite()`, `generateImage()`, `socialModal(?int $id=null)`, `socialShare()` |
| `NewsController.php` | Yayınlanmış haberler. `#[SubModule(module:'studio', entity:'news')]` (`:13`) | `index()`, `save()`, `delete($id)`, `edit(?int $id=null)`, `generateImage()`, `rewrite()`, `socialModal(?int $id=null)`, `socialShare()` |
| `CategoriesController.php` | İçerik kategorileri. `#[SubModule(module:'studio', entity:'categories', model:'app.contentCategory', repository:'app.contentCategory')]` (`:13`) | `index()`, `save()`, `delete($id)`, `status()`, `modal($id=null, ?string $view=null)` |
| `DraftsController.php` | Taslak/fikir havuzu. `#[SubModule(module:'studio', entity:'drafts', model:'app.contentDraft', repository:'app.contentDraft')]` (`:13`) | `index()`, `save()`, `delete($id)`, `generate(int $id)`, `modal($id=null, ?string $view=null)` |

**Gözlem:** `PostsController` ve `NewsController` özniteliklerinde `service`/`repository`
**yoktur**; içerik servisi çalışma anında `getRouteConfig()['content']` üzerinden
çözülür (`:26` → `RbnStudioController::resolvePostService()`). `CategoriesController` ve
`DraftsController` ise `repository` verdiği için `activeService` **kurauluşta**
çözülür (`ComponentHydratorTrait.php:69-70`).

### 2.2 `Models/` — 2

| Dosya | Görev | Önemli üyeler |
|---|---|---|
| `ModuleData.php` | Rota tanımı (tüm 4 alt modül burada). `#[Bundle(name:'studio', context:'panel', map: StudioMap::MAP)]` (`:15-19`) | `registerMap(): array` → **boş diziler** (`:25-31`), `registerRoutes(): void` (`:36-86`) |
| `StudioMap.php` | Menü ağacı | `MAP` (`:15-41`): `module_name`, `title`, `icon`, `sub_modules` (4 dal) |

### 2.3 `Views/` — 9

| Dosya | Render eden | Beklediği değişkenler |
|---|---|---|
| `Posts/index.rbn.php` | `PostsController::index()` (`Controllers/PostsController.php:89`) | `posts`, `pager`, `categories`, `categoryMap`, `categorySlugMap`, `activeTab`, `counts`, `rewrittenCount`, `hasCategory`, `hasSocial`, `socialPlatforms` |
| `Posts/form.rbn.php` | `PostsController::edit()` (`:166`) | `post`, `id`, `categories`, `projectKey`, `hasCategory`, `hasSocial` |
| `Posts/Partials/social_modal.rbn.php` | `RbnStudioController::executeSocialModal()` (`:365`) | `post`, `id`, `type`, `platforms`, `publishedUrl`, `imageUrl`, `previewImageUrl`, `projectKey`, `ajax` (`:1-8`) |
| `News/index.rbn.php` | `NewsController::index()` (`Controllers/NewsController.php:95`) | `Posts/index` ile aynı küme, `rewrittenCount` **yok** |
| `News/form.rbn.php` | `NewsController::edit()` (`:170`) | `Posts/form` ile aynı küme |
| `Categories/index.rbn.php` | `CategoriesController::index()` (`Controllers/CategoriesController.php:56`) | `categories`, `pager`, `totalCount`, `currentType`, `hasBlog`, `hasNews`, `blogCount`, `newsCount` |
| `Categories/Partials/modal.rbn.php` | `CategoriesController::modal()` (`:141`) | `@var array\|null $category`, `@var int\|null $id`, `@var bool $isEdit`, `@var bool $hasBlog`, `@var bool $hasNews`, `@var string $currentType` (`:1-6`) |
| `Drafts/index.rbn.php` | `DraftsController::index()` (`Controllers/DraftsController.php:46`) | `drafts`, `pager`, `categories`, `categoryMap`, `totalCount` |
| `Drafts/Partials/modal.rbn.php` | `DraftsController::modal()` (`:157`) | `@var array\|null $draft`, `@var int\|null $id`, `@var bool $isEdit`, `@var array $categories` (`:1-4`) |

## 3. Rotalar (`ModuleData::registerRoutes()`, 4 grup / 33 uç)

| Grup | Prefix | Kontrolör | Uç sayısı | Grup satırı |
|---|---|---|---:|---|
| Categories | `studio/categories` | `CategoriesController` | 7 | `:39-47` |
| Drafts | `studio/drafts` | `DraftsController` | 7 | `:50-58` |
| Posts | `studio/posts` | `PostsController` | 10 | `:61-72` |
| News | `studio/news` | `NewsController` | 9 | `:75-85` |

**Toplam: 33 uç** (ölçüldü: `Select-String "Route::(get|post)\("` → 33 eşleşme;
bunların **33'ü de** `->name('admin.studio.…')` ile adlandırılmış, adsız uç yok).

RbnAdmin'ın aksine bu rotaların **tamamı** `->name('admin.studio.…')` ile
adlandırılmıştır — `handleResult()`'ın `returnPath()`'i adlandırılmış rotaları
doğru çözer.

Üç grup `Route::get('delete/{id:[0-9]+}')` **ve** `Route::post('delete/{id:[0-9]+}')`
olarak **iki kez** kayıt etmiştir (`:43-44`, `:52-53`, `:63-64`, `:78-79`) ve ikisine
**aynı isim** vermiştir (`admin.studio.categories.delete` iki kez). Bu, düzeltilmiş
bir hatanın kalıntısı gibi görünüyor ve iki riski var: (a) aynı isimli iki rota
adlandırma defterinde çakışır, (b) **GET `delete` bir CSRF yüzeyidir** — kurbanın
tarayıcısında `<img src="…/studio/posts/delete/12">` kaydı siler.
RbnAdmin'daki karşılıklarının **tamamı** POST'tur.

## 4. Akışlar

### 4.1 Konfigürasyon çözümleme — paketin kalbi

RbnStudio hiçbir şeyi "açık/kapalı" kendi bilmez; her yetenek
`project-routemap.php` içindeki `view_mapping[<proje>]['content']` altında
tanımlanan bir anahtara bağlıdır:

```
RbnStudioController::getContentConfig($key)                 :24-33
  → getRouteConfig($projectKey, 'content')                  :31
      → ResolvesProjectConfigTrait::getRouteConfig()        Core/Base/Concerns/Data/ResolvesProjectConfigTrait.php:71
        → Paths::project()->configs('project-routemap.php') (statik olarak bir kez require edilir, :74-76)
        → $map['view_mapping'][$projectKey]                 :109

hasBlog()   → !empty(content.blog)      :38-41
hasNews()   → !empty(content.news)      :46-49
hasCategory()→ !empty(content.category) :54-57
hasDraft()  → !empty(content.draft)     :62-65
hasSocial() → !empty(content.social)    :70-73
```

Bu yüzden üç alt modül `index()` başında **kendi varlıklarını doğrular** ve yoksa
`/admin/studio/posts`'e yönlendirir: `NewsController::index()` (`:23-26`),
`CategoriesController::index()` (`:23-26`), `DraftsController::index()` (`:23-26`).
**`PostsController::index()` bunu yapmaz** — blog kapalıysa boş liste basar
(`:26` servisi null döner, `:52` boş dizi verir).

### 4.2 Makale kaydetme

```
POST /admin/studio/posts/save  → ModuleData.php:62 → PostsController::save()  :107
  → $this->request->input(...) ile 11 alan toplanır        :112-124
      (doğrulama YOK — service katmanına bırakılmış)
  → hasSocial() ise social_summary + social_hashtags eklenir :126-129
  → resolvePostService()->savePost($data)                  :131-132
  → handleResult($result, 'Makale işlemi', 'posts', $context)  :134-135
```

`hasSocial()` **false** ise `social_summary`/`social_hashtags` alanları hiç
gönderilmez; servis tarafında "silme" değil, **"yazma"** olur — yani sosyal medya
alanı bir kez yazıldıktan sonra sosyal medya kapalısa **kalıcı olarak kalır**.
`savePost()` bu alanları doğrulamaz.

### 4.3 Listeleme ve sekme rozetleri

`PostsController::index()` (`:19-102`) ve `NewsController::index()` (`:19-107`)
**aynı mantığı iki kez** uygular (gövde neredeyse satır satır aynıdır;
tek fark `News`'in `rewrittenCount`'u render verisine koymaması, `:95-106`).
Her ikisi de rozet sayıları için **tüm kayıtları ikinci kez çeker**
(`:56`, `NewsController.php:63`) ve sayıyı PHP içinde döngüyle hesaplar — yani
`getPosts()` üç kez çağrılır (sorgu + rozet + sayfalama). Bu, veri büyüdükçe
üç katına çıkan bir sorgudur.

### 4.4 AI görsel üretimi

```
POST /admin/studio/posts/generate-image → executeGenerateImage('blog')   RbnStudioController.php:163
  ├─ id / title / custom_prompt / project_key okunur                    :164-167
  ├─ prompt = custom_prompt ?: title                                     :169
  ├─ service('api')->gemini('image', $prompt, ['project_key'=>…, 'id'=>…]) :175-178
  ├─ service('image')->base64Image($base64, "images/{$type}/".date('Y/m'),
  │                                'public', ['extension'=>'webp'])     :189-194
  ├─ $result['url'|'image_url'|'image'] = '/' . path                    :203-206
  └─ id > 0 ise savePost(['id'=>…, 'image'=>…, 'image_prompt'=>…])      :208-217
```

`type` değeri yalnız klasör yolu (`images/blog/` ↔ `images/news/`) ve hangi
servisin güncelleneceğini belirler (`:209`); `news` dışındaki her değer `blog`
gibi davranır.

### 4.5 İçerik yeniden yazma

```
POST /admin/studio/posts/rewrite → executeRewrite('blog')   :225
  ├─ id boşsa 400-benzeri JSON hata                       :229-231
  ├─ local_model = type==='news' ? resolveNewsModel() : resolvePostModel()  :235
  ├─ category_model = resolveCategoryModel()  (varsayılan 'app.contentCategory')  :236,102
  └─ builder('task.content_rewrite')->autopilot([… 'force'=>true])           :238-247
```

Builder'ın kaydı framework'tedir:
`task.content_rewrite → RbnPipeline\Builders\Tasks\ContentRewriteTaskBuilder`
(`Packages/PackageData.php:95`, sınıf `Packages/RbnPipeline/Builders/Tasks/ContentRewriteTaskBuilder.php:18`).

### 4.6 Taslaktan makale üretimi

```
GET /admin/studio/drafts/generate/{id} → DraftsController::generate(int $id)   :125
  → service('blogAutopilot')->generateArticle(
        $id, 'Ssblogs.post', 'app.contentDraft', 'app.contentCategory',
        $projectKey, ['has_author_comment'=>true])                       :128-135
```

⚠️ **`blogAutopilot` servisi framework içinde **yoktur**.** Ölçüldü:
`grep blogAutopilot E:\localhost` → **tek** eşleşme, bu satırın kendisi.
`'Ssblogs.post'` görünüm adı da tek eşleşme. Bu uç, ilgili servis ve görünüm
**yalnız belirli bir projede** tanımlıysa çalışır; aksi halde 500 verir.
Ayrıntı: §5.2.

### 4.7 Sosyal medya paylaşımı

```
GET  social-modal/{id} → executeSocialModal($type,$id)   :318
  ├─ hasSocial() değilse 403                             :323-325
  ├─ post yoksa 404                                      :330-332
  ├─ platformlar = getActiveSocialPlatforms()            :334
  │    → apiManager->resolveApiKey('instagram'|'facebook', $pk)
  │      + apiKeys[INSTAGRAM_ACCESS_TOKEN|…] yedeği       :281-283, 297-299
  ├─ kategori slug → yayın URL'si: {haber|blog}/{slug}/{post.slug}  :348-350
  ├─ görsel: mutlak URL → targetProjectUrl(), önizleme → '/' . path  :357-363
  └─ render('Posts/Partials/social_modal', [… 'ajax'=>true])          :365-375

POST social-share → executeSocialShare($type)             :382
  ├─ caption + hashtag + "Detaylar: <url>" birleşimi      :431-432
  ├─ facebook: görsel varsa postPhoto(), yoksa postFeed()  :446-449
  ├─ instagram: görsel YOKSA paylaşım yapılamaz           :474-486
  └─ sonuçlar platform başına toplanır, mesaj <br> ile birleşir  :495-505
```

## 5. Tuzaklar (kodda ölçülmüş)

1. **`deleteDraft()` çağrısı var, metot yok — PHP fatal.** ⚠️
   `DraftsController::delete()` (`:118`)
   `$this->repository('app.contentDraft')->deleteDraft($id)` çağırıyor; ancak
   `ContentDraftRepository` bu metodu **taşımıyor**, gerçek adı `destroyDraft()`
   (`Core/Database/Repositories/Project/ContentDraftRepository.php:91`).
   **Ölçüldü:** `method_exists(ContentDraftRepository::class, 'deleteDraft')` →
   **`false`**. Depo katmanında `__call` yoktur (`BaseRepository` yalnız
   `ActionProviderTrait`, `ContentQueryTrait`, `ContentCacheTrait` kullanır,
   `Core/Base/Data/BaseRepository.php:20`; hiçbirinde `__call` yok), bu yüzden PHP
   `Error: Call to undefined method` fırlatır → HTTP 500.
   Bu paketin **en ciddi** bulgusudur: `GET/POST /admin/studio/drafts/delete/{id}`
   ucu her çağrıldığında 500 verir.

2. **`blogAutopilot` servisi ve `Ssblogs.post` görünümü framework'te yok.**
   `DraftsController::generate()` (`:128-130`) ikisini de kullanıyor.
   Ölçüldü: tüm workspace taramasında `blogAutopilot` ve `Ssblogs` için her biri
   **tek** eşleşme (yani çağıranın kendisi). Bu uç, servis ve görünüm yalnız
   **belirli projelerde** tanımlıysa çalışır; tanımlı değilse 500 verir.
   Framework paketi olduğu için en azından **yapılandırılabilir bir servis adı**
   olması beklenirdi — şu an projeye gömülü bir bağımlılık. Açık soru maddesi:
   [acik-sorular §2.10](../../../acik-sorular.md).

3. **`Route::get('delete/{id}')` üç grupta da kayıtlı — CSRF yüzeyi.**
   Dört grupta da **ardışık GET+POST çifti** vardır ve ikisi de aynı ada bağlıdır:
   categories `:43`(GET)/`:44`(POST), drafts `:52`/`:53`,
   posts `:63`/`:64`, news `:78`/`:79` — hepsi `admin.studio.<x>.delete` adıyla.
   Yani adlandırma defterinde **dört çift çakışma** vardır.
   GET sürümü `CrudControllerTrait`/kontrolör mantığına gider; kalıcı tehlike
   düzeyi `destroyDraft`/`deletePost` çağrılarına bağlı (tuzak 1 nedeniyle zaten
   500 veriyor), ama `drafts` dışındaki gruplarda `deleteCategory()` gibi gerçek
   silme yollarına ulaşabilir. RbnAdmin'daki karşılıklarının tamamı POST'tur.

4. **`registerMap()` iki anahtarı da boş döndürüyor** (`:25-31`):
   `services` ve `providers` boş dizi. Bu, RbnStudio'nın **kendi servis veya
   provider'ı olmadığı** anlamına gelir — doğru ve kasıtlı (içerik servisleri
   projeye aittir). Ama `Route::load()` bu metodu çağırmaya devam eder, yani
   her istekte boş dizi üretilir (`:26-31`).

5. **`resolveCategoryModel()` / `resolveDraftModel()` sabit `project_key`'e
   bakmaz.** `getContentConfig()` içindeki `project_key` **aktif** projedir
   (`:26`); taslak ve kategori modelleri evrenseldir (`app.contentCategory`,
   `app.contentDraft`) ve her projede aynıdır. Bu doğru bir sadeleştirmedir.

6. **`getActiveSocialPlatforms()` her çağrıda `getPlatform()` ile provider'ın
   durumunu değiştirir** (`SocialMediaProvider` durumlu bir nesnedir,
   bkz. [RbnAdmin Providers §5.9](../RbnAdmin/Providers.md)). Bu metot
   `getPlatform('instagram')` **ve** `getPlatform('facebook')` çağırır (`:286,303`);
   ikinci çağrı birincinin `activeData`'sını siler ama birincinin değerleri
   **zaten kopyalanmıştır** (`:287-293`), yani bu kullanım güvenlidir.

7. **`executeSocialModal()` `$type` değerini doğrulamaz.** `'news'` dışındaki her
   değer `blog` gibi davranır (`:327`, `:348`) — yani `/studio/news/social-modal/1`
   ucu bir hata vermez, **yanlış** içerik gösterir.

8. **`executeSocialShare()` istisna mesajını kullanıcıya sızdırır.**
   `catch (\Throwable $e) { $results['facebook'] = ['success'=>false, 'message'=>$e->getMessage()]; }`
   (`:463`, `:489`). Bu, RbnAuth'ın giriş akışında **kapatılmış** sızıntı
   deseninin (`AuthController.php:54-70`) panel tarafındaki açık karşılığıdır:
   ham istisna metni (SQLSTATE, dosya yolu) JSON gövdesine gider.

9. **Sosyal medya alanları kapatılınca temizlenmiyor.** §4.2. `hasSocial()` false
   ise `social_summary`/`social_hashtags` gönderilmez; servis alanı yok saydığı için
   eski değer kalır.

10. **`DraftsController::save()` toplu eklemede sonuç sayısını kendisi uyduruyor.**
    `$savedCount++` (`:93`) her döngüde artar, döngü sonunda
    `"{$savedCount} adet yeni taslak başarıyla eklendi."` basılır (`:97`) —
    **`saveDraft()` dönüş değeri denetlenmez**. Depo yazma başarısız olsa bile
    "başarıyla eklendi" denir. Tekli eklemede (`:102-109`) sonuç denetlenir.

11. **`DraftsController::save()` toplu eklemede `id` dalını atlar.**
    `if ($id)` dalı önce çalışır (`:65-74`), sonra `bulk_mode` (`:76-98`).
    Yani `id` **ve** `bulk_mode` birlikte gönderilirse toplu ekleme **sessizce
    yok sayılır** ve tek kayıt güncellenir.

12. **`CategoriesController::save()` varsayılan tipi `getRouteConfig` ile
    çözüyor, `getContentConfig` ile değil.** `:89-90`:
    `$this->getRouteConfig($projectKey, 'has_blog')` — ama `hasBlog()` (§4.1)
    `content.blog` anahtarını okur. İki farklı anahtar ailesidir; biri
    tanımlıysa diğeri değilse tip yanlış çözülür. Aynı desen
    `CategoriesController::modal()` (`:137-138`) ve `index()` (`:31-32`) içinde de
    vardır — üç kez.

13. **Liste ekranları veriyi üç kez çekiyor.** §4.3. `getPosts()` sorgu + rozet +
    sayfalama için üç kez çağrılır (`PostsController.php:52,56`). `NewsController`
    da aynı (`:59,63`).

14. **`StudioMap::MAP` ile `ModuleData` arasındaki bağ `#[Bundle(map: …)]`
    özniteliğidir.** `ModuleData.php:15-19`. `ModuleDataDriver` bu haritadan
    `sub_modules`'ı alır (`Core/System/Discovery/Engine/Drivers/ModuleDataDriver.php:169`).

15. **`DraftsController::index()` kategori tipini `blog`'a sabitler**
    (`:43`, `:155`): `getCategories(['type'=>'blog', …])`. Taslaklar haber
    kategorileriyle ilişkilendirilemez — bilinçli bir kısıt.

## 6. Örnek (gerçek koddan)

Yeteneğin varlığına göre yönlendirme — paketin tipik kalıbı:

```php
// Bundles/RbnSuite/RbnStudio/Controllers/NewsController.php:21-26
$projectKey = $this->activeProjectKey();

if (!$this->hasNews($projectKey)) {
    $this->response->redirect('/admin/studio/posts');
    return;
}
```

ve konfigürasyondan servis çözümleme:

```php
// Bundles/RbnSuite/RbnStudio/Controllers/RbnStudioController.php:78-82
protected function resolvePostService(?string $projectKey = null): ?object
{
    $conf = $this->getContentConfig('blog', $projectKey);
    return !empty($conf['service']) ? $this->service($conf['service']) : null;
}
```

## 7. İlgili belgeler

* [RbnAdmin](../RbnAdmin/README.md) — panelin bu paketle ortak rotaları, `SocialMediaProvider`
* [RbnAuth](../RbnAuth/README.md) — rotaların `admin` middleware'i
* [Core/Database/Repositories](../../../Core/Database/Repositories.md) — `ContentCategoryRepository`, `ContentDraftRepository`
* [Packages/RbnPipeline](../../../Packages/RbnPipeline.md) — `task.content_rewrite` builder'ı (⏳ ayrı görev)
* [Packages/RbnApi](../../../Packages/RbnApi.md) — `service('api')->gemini()` (⏳ ayrı görev)
* [acik-sorular](../../../acik-sorular.md)
