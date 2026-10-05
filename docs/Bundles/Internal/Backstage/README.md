# Bundles/Internal/Backstage — Panel çekirdeği: sidebar, e-posta ve ayar mimarisi

> **Doğrulanan kod tabanı:** `d49b4413` (dal `feat/fw-license-master`) · **Tarih:** 2026-10-05 · **Yayın:** 0.9.3 = bu commit + sonrası; belge yalnız doğrulama anındaki kodu anlatır
> **Kaynak klasör:** `Bundles/Internal/Backstage/` — 24 `*.php` + 12 `*.rbn.php` görünüm.
> **Envanter:** 24 php dosyasının 24'ü bu belgede anlatıldı.
> **Belge tek dosyadır** çünkü hiçbir alt dal 20 `*.php` eşiğini geçmez
> (SPEC §1 "alan büyükse (≥20 php)" kuralı; en büyük alt dal `Controllers/` = 4).

## 1. Ne işe yarar, kimler kullanır

Backstage, **panelin kendisini ayarlayan** modüldür — sitenin içeriğini değil,
yönetim arayüzünü yönetir:

1. **Sidebar** — panel menülerinin kategori + hiyerarşik menü ağacı (rol süzgeçli).
2. **E-posta** — SMTP/gönderen/genel e-posta ayarları ve test e-postası gönderimi.
3. **Ayar Mimarisi** — `z_settings` ve `z_setting_groups` tablolarının **yapısal**
   yönetimi: alan ekleme, tip/etiket/rol/yardım metni düzenleme.

Modül diğer modüllerin ayar verisini de okur (`BackstageService::getEmailSettings()`
`repository('project.settings')` üzerinden `email` grubunu çeker), ama **yazma
yetkisi yalnız kendi alanlarındadır**.

**Kimler çağırır:** geliştirici paneli (`#[Module(panel:'developer', context:'panel')]`,
`BackstageController.php:15-20`).

## 2. Klasör/dosya envanteri (24 php)

### 2.1 `Controllers/` (4)

| Dosya | Görev | Önemli public yöntemler |
|---|---|---|
| `BackstageController.php` | Modül kökü. `#[Module(name:'backstage', data:ModuleData::class, panel:'developer', context:'panel')]` — **`service:` bildirimi yok**, bu yüzden `$this->service` alt modüllerde `SubModule` attribute'inden gelir. | `index()` (:23) |
| `SidebarController.php` | `#[SubModule(entity:'sidebar', service:'sidebar', provider:'sidebar')]`. Kategori ve menü yönetimi; rol listesini `AuthRole::ROLES`'ten alır. | `index(): void` (:20), `getModalData($id): array` (:35 *protected*), `categorySave(): void` (:65), `menuSave(): void` (:78) |
| `EmailController.php` | `#[SubModule(entity:'email', service:'backstage', provider:'backstage', model:'Settings')]`. E-posta ayarları, test gönderimi, iki senaryolu güncelleme. | `index()` (:18), `manage()` (:28), `getModalData($id)` (:39 *protected*), `testMail()` (:63), `updateMailSettings()` (:91) |
| `SettingsArchitectController.php` | `#[SubModule(entity:'settings', service:'backstage', provider:'backstage')]`. Ayar grubu ve ayar alanı yönetimi; tek kişilik modülde en çok uç. | `index(): void` (:17), `group($id): void` (:27), `getModalData($id)` (:48 *protected*), `createSetting(): void` (:87), `updateSetting(): void` (:112), `createGroup(): void` (:134), `updateGroup(): void` (:148) |

### 2.2 `Services/` (2)

| Dosya | Görev | Önemli public yöntemler |
|---|---|---|
| `BackstageService.php` | Ayar servisi (`$targetModel='Settings'`). Okuma tarafı **repository** üzerinden; yazma tarafı `CrudSettingsProvider`'a devredilir. `getEmailSettings()` ayarları beş kümeye böler. | `getEmailSettings(): array` (:21), `getGroups(): array` (:59), `getSettingsRaw(string $groupKey): array` (:69), `getGroupWithSettings(int $id): array` (:80), `createSetting(array $data)` (:106), `updateSetting(int $id,array $data)` (:110), `createGroup(array $data)` (:114), `updateGroup(int $id,array $data)` (:118), `bulkUpdateSettings(array $settings)` (:122), `remove(string $entity,int $id): bool` (:143) |
| `SidebarService.php | Sidebar servisi (`$targetModel='SidebarMenus'`). Yazma sonrası önbellek düşürür; `clearSidebarCache()` **tüm rollerin** anahtarlarını toplu siler. | `categories(): array` (:24), `getStructure(string $userRole): array` (:33), `tree(): array` (:41), `saveCategory(array $data): bool\|int` (:49), `saveMenu(array $data): bool\|int` (:61), `clearSidebarCache(): self` (:73) |

### 2.3 `Providers/` (3)

| Dosya | Görev | Önemli public yöntemler |
|---|---|---|
| `BackstageProvider.php` | Ayar/grup okuma uzmanı. `target(string $modelName): self` hedef modeli çalışma anında değiştirir (akıcı). `getSettingsGroup()` `z_settings` ⨝ `z_setting_groups` birleştirir. | `target(string $modelName): self` (:27), `getGroups(): array` (:37), `findGroup(int $id): ?array` (:45), `getSettingsGroup(string $type,?string $role=null,bool $onlyActive=true): array` (:54) |
| `CrudSettingsProvider.php` | Ayar **tek yazma kapısı**. Her metotta `redirect_url`, `view`, `id` alanlarını siler ("parazit koruması"). | `createSetting(array $data): int\|bool` (:18), `updateSetting(int $id,array $data): bool` (:40), `createGroup(array $data): int\|bool` (:55), `updateGroup(int $id,array $data): bool` (:64), `delete(string $entity,int $id): bool` (:74) |
| `SidebarProvider.php` | Sidebar sorgu uzmanı. `afterBoot()` **URI'den hedef modeli seçer** (`/category` ⇒ `SidebarCategories`). `getSidebarStructure()` rol bazlı önbellek kullanır. | `afterBoot(): void` (:23 *protected*), `getSidebarStructure(string $userRole): array` (:37), `getMenusForCategory(int,string,string): array` (:81 *protected*), `getTree(int $parentId=0,int $depth=0,?array $elements=null): array` (:117), `getCategories(): array` (:141), `getParentMenus(): array` (:150), `saveCategory(array $data): bool\|int` (:162), `saveMenu(array $data): bool\|int` (:177), `checkRoleAccess(?string $required,string $userRole): bool` (:192 *protected*) |

### 2.4 `Handlers/` (1)

| Dosya | Görev | Önemli public yöntemler |
|---|---|---|
| `SidebarHandler.php` | Sidebar yazma uzmanı: slug türetme, **atomik sıralama (transaction)**, alt öğe korumalı silme. | `saveCategory(array $data,?int $id=null): bool` (:20), `saveMenu(array $data,?int $id=null): bool` (:32), `reorder(string $type,array $ids): bool` (:44), `deleteCategory(int $id): bool` (:66), `deleteMenu(int $id): bool` (:74) |

### 2.5 `Models/` (2)

| Dosya | Görev | Önemli public yöntemler |
|---|---|---|
| `ModuleData.php` | `#[Bundle(name:'backstage', context:'developer', map:BackstageMap::MAP)]`. 2 servis + 1 handler + 3 provider alias'ı; 4 rota grubu (dashboard, sidebar, email, settings). | `registerMap(): array` (:26), `registerRoutes(): void` (:48) |
| `BackstageMap.php` | Menü haritası: 1 kimlik + 3 alt modül; `sidebar` ve `settings` alt modüllerinin alt dalları yalnız `controller` bilgisi taşır (başlık/iikon yok). | `const MAP` (:19) |

### 2.6 `Views/` (12)

| Görünüm | Sağlayan uç | Görev |
|---|---|---|
| `backstage_dash.rbn.php` | `BackstageController::index` | Modül ana ekranı |
| `Sidebar/index.rbn.php` | `SidebarController::index` | Kategori + menü ağacı |
| `Sidebar/Partials/modal_category.rbn.php` | `SidebarController::modal` (`view=modal_category`) | Kategori ekle/düzenle |
| `Sidebar/Partials/modal_menu.rbn.php` | aynı (`view=modal_menu`) | Menü ekle/düzenle (ikon seti `menu`, üst menü listesi) |
| `Emails/index.rbn.php` | `EmailController::index` | SMTP / gönderen / genel kartları |
| `Emails/manage.rbn.php` | `EmailController::manage` | E-posta alan şeması + sıralama |
| `Emails/Partials/modal.rbn.php` | `EmailController::modal` | E-posta alanı ekle/düzenle |
| `Emails/Partials/modal_role.rbn.php` | aynı | Alan erişim rolü seçimi |
| `Settings/index.rbn.php` | `SettingsArchitectController::index` | Ayar grubu listesi |
| `Settings/group_settings.rbn.php` | `SettingsArchitectController::group` | Bir grubun alanları |
| `Settings/Partials/modal.rbn.php` | `SettingsArchitectController::modal` | Ayar alanı ekle/düzenle |
| `Settings/Partials/modal_group.rbn.php` | aynı (`view=modal_group`) | Grup ekle/düzenle (ikon seçici `popular`) |

**Kapsama:** 24/24.

## 3. Akış

### 3.1 Panel sidebar'ı üretimi

```
bir panel isteği
  → SidebarService::getStructure($userRole)              SidebarService.php:33
      → SidebarProvider::getSidebarStructure($userRole)  SidebarProvider.php:37
          → cache()->remember("sidebar_{$activeProject}_{$userRole}", …)   :53
              ├─ SidebarCategoriesModel: is_active=1, order_num ASC          :54-58
              ├─ her kategori için getMenusForCategory()                     :63
              │    ├─ SidebarMenusModel: category_id + parent_id=0 + is_active=1
              │    │    + order_num ASC                                     :86-92
              │    ├─ checkRoleAccess($menu['required_role'], $userRole)     :96
              │    └─ children: parent_id = $menu['id'], is_active=1         :101-106
              └─ sonuç: [{name, icon, menus:[…]}]                            :66-70
```

Rol kapısı (`SidebarProvider.php:192-204`):

```
required_role boşsa           → true
userRole === 'developer'      → true (geliştirici her şeyi görür)
aksi halde: AuthRole::ROLES[$userRole]['level'] >= AuthRole::ROLES[$required]['level']
```

### 3.2 Sidebar yazma ve atomik sıralama

```
POST backstage/sidebar/category/save
  → SidebarController::categorySave()                SidebarController.php:65
      → request->form(['category_name' => 'required'])
      → $this->service->saveCategory($data)           SidebarService.php:49
          → SidebarProvider::saveCategory()           SidebarProvider.php:162
              ├─ category_slug yoksa turkishSlug(category_name)   :165-167
              ├─ BaseModel::fillProjectKeyIfMissing($data)        :169
              └─ SidebarCategoriesModel->save($data)              :171
          → başarılıysa clearCache()                   SidebarService.php:53

POST backstage/sidebar/menu/reorder  → 'bulkOrder' (BulkControllerTrait)
  → SidebarHandler::reorder('menu', $ids)             SidebarHandler.php:44
      ├─ field = 'category' ? 'category_order' : 'menu_order'     :46-47
      ├─ beginTransaction()                            :49
      ├─ her id için update(id, [field => index+1]); başarısızsa rollBack + false  :52-57
      └─ commit() / catch → rollBack + false           :58-63
```

### 3.3 E-posta ayarları ve test gönderimi

```
GET backstage/email
  → EmailController::index()                          EmailController.php:18
      → BackstageService::getEmailSettings()          BackstageService.php:21
          → repository('project.settings')->fetch([group_key=>'email',
                                                    project_key=>active_project_key()])   :24-27
          → beş kümeye ayrıştırır: smtp / sender / general / enabled / mapped   :29-52
      → render('Emails/index', array_merge($emailData))         EmailController.php:22

POST backstage/email/test-mail
  → EmailController::testMail()                       EmailController.php:63
      → settings = service('settings')->read('email')             :65
      → targetEmail = settings['email_admin_address'] ?? null      :66
      → service('rbnEmail')->sendTemplate('test_connection', $targetEmail, $data)  :78
          $data = ['time' => now('d.m.Y H:i:s'),
                   'site_name' => service('seo')?->get('site_title') ?? 'RBN Framework']  :73-76
      → Route->handleResult($result, [success_message, error_message])  :81-84
```

### 3.4 Ayar mimarisi

```
GET backstage/settings/group/{id}
  → SettingsArchitectController::group($id)           SettingsArchitectController.php:27
      → BackstageService::getGroupWithSettings($id)   BackstageService.php:80
          → fetch(['target'=>'groups','id'=>$id]) → ilk kayıt
          → fetch(['group_id'=>$id]) → alanlar        :93-95
      → render('Settings/group_settings', ['group'=>…, 'settings'=>…])   :36

POST backstage/settings/createSetting
  → createSetting()                                   :87
      → request->form([11 kural])                     :91-103
      → BackstageService::createSetting()             BackstageService.php:106
          → CrudSettingsProvider::createSetting()     CrudSettingsProvider.php:18
              ├─ unset(redirect_url, view, id)        :21
              ├─ setting_key yoksa: turkishSlug(label_tr) → '-' yerine '_'   :23-26
              ├─ is_active yoksa 1                    :27
              ├─ field_options boşsa null             :30-32
              └─ SettingsModel->create($data)         :34
```

## 4. Yapılandırma / ayar anahtarları ve sabitler

| Öğe | Yer | Değer | Not |
|---|---|---|---|
| Sidebar önbellek anahtarı | `SidebarProvider.php:53` | `"sidebar_{$activeProject}_{$userRole}"` | **[FW-ALTYAPI-3 / H · G4] düzeltmesi:** anahtar önceki hâlinde kiracıyı içermiyordu, bir proje başka projenin kenar çubuğunu gösteriyordu (yorum :44-48) |
| Servis önbellek anahtarı | `SidebarService.php:16` | `navigation_sidebar` | `clearSidebarCache()` bunu da siler (:77) |
| `clearSidebarCache()` kapsamı | `SidebarService.php:75-79` | `AuthRole::ROLES` anahtarları → `sidebar_{rol}` + `navigation_sidebar` | Rol sayısı artarsa yeni rolün önbelleği otomatik kapsama girer |
| E-posta grupları | `BackstageService.php:44,46` | `smtp_host/port/username/password` → `smtp`; `email_from_name/address` → `sender` | Diğer aktif alanlar `general`'a düşer |
| E-posta anahtarı | `EmailController.php:66` | `email_admin_address` | `testMail()` hedefi; yoksa hata verir (:69) |
| Yeni e-posta alanı varsayılanları | `EmailController.php:50-53` | `group_id=3`, `required_role='developer'`, `field_type='text'` | **Sabit grup id** |
| Yeni ayar varsayılanları | `SettingsArchitectController.php:72-74` | `group_id` istekten, `required_role='developer'`, `field_type='text'` | Burada grup id **isteğe bağlıdır** |
| Toplu e-posta güncelleme önbelleği | `BackstageService.php:130-131` | `settings_group_seo`, `settings_group_system` | **İki anahtar sabit**; başka grup kazanılırsa önbelleği düşmez |
| Sidebar sıralama alanları | `SidebarHandler.php:47` | `category_order` / `menu_order` | `order_num` değil — **okuma** sırası `order_num`, **yazma** sırası bu alanlara gider |
| Panel sırası okuma alanı | `SidebarProvider.php:57,:90` | `order_num` | |
| `AuthRole::ROLES` | `Bundles/RbnSuite/RbnAuth/Models/AuthRole` | — | Backstage bu sabiti doğrudan kullanır (`SidebarController.php:41`, `SidebarProvider.php:199`, `SidebarService.php:75`) |

## 5. Tuzaklar ve kurallar (kodda görülen, ölçülmüş)

1. **Kök `Module` attribute'inde `service:` yok.** `BackstageController`'da
   `#[Module(name:'backstage', data:…, panel:'developer', context:'panel')]`
   (`BackstageController.php:15-20`). `SyshubController` ve `WebhubController` `service:` bildiriyor.
   Kök controller `$this->service` kullanmadığı için sorun çıkmıyor; alt modüller
   `SubModule` attribute'inden çözüyor (`ComponentContext.php:52-58`).
2. **`SidebarProvider::afterBoot()` URI'ye bakar.** `str_contains($uri, '/category')` ⇒
   `SidebarCategories`, aksi halde `/menu` ⇒ `SidebarMenus` (`SidebarProvider.php:25-30`).
   URI ikisini de içermiyorsa `SidebarMenus` kalır. Bu, **aynı provider'ın iki modeli
   karıştırmasına** dayanır — `getCategories()/getParentMenus()` açık model adı kullandığı
   için etkilenmez.
3. **Sıralama alanı ikiye ayrılmış.** Okuma `order_num` (`SidebarProvider.php:57,:90`),
   yazma `menu_order`/`category_order` (`SidebarHandler.php:47`). Sıralama arayüzü ikinci alanı
   doldurduğunda panelde **hiçbir görünüm değişmez**; tersi de doğrudur.
4. **Alt öğe koruması sessiz `false` döner.** `deleteCategory()` menüsü olan kategoriyi
   silmez (`SidebarHandler.php:68-69`), `deleteMenu()` alt menüsü olanı silmez (:76-77).
   `Webhub`'taki `FrontendMenuProvider::destroy()` ile aynı desen. Kullanıcıya neden
   söylenmediği için controller mesajı geneldir.
5. **`reorder()` transaction'ı model üzerinden yürütüyor.** `$model->beginTransaction()`
   (`SidebarHandler.php:49`) — **model nesnesi üzerinden**, yani bağlantı bu modelin
   bağlandığı PDO'da. `update()` çağrıları aynı modelde olduğu için tutarlıdır; farklı bir
   bağlantıya kaçarsa commit/rollback etkisiz kalır (kodda böyle bir çağrı yok).
6. **Yazma iki farklı servise dağılmış durumda.** `SidebarService::saveCategory()` →
   `SidebarProvider::saveCategory()`; `SidebarHandler::saveCategory()` ise **ayrı** bir
   yol (`SidebarHandler.php:20`). Handler, `ModuleData.php:34`'te `handlers.sidebar` olarak
   kayıtlı olsa da **hiçbir controller'dan çağrılmıyor** — kayıtlı ama kullanılmıyor.
7. **`BackstageService` okumada repository, yazmada provider kullanıyor.** `getGroups()` ve
   `getSettingsRaw()` → `repository('project.settings')->fetch()` (:61,:71);
   `createSetting()` vb. → `provider('CrudSettingsProvider')` (:108,:111,:116,:120,:145).
   Bu, Anayasa §8'in repository kuralıyla uyumlu tek yazma kapısıdır.
8. **`fetch()` sözleşmesi: `target` anahtarı hedef tabloyu seçer.** `fetch(['target'=>'groups'])`
   grup tablosunu, `fetch(['group_id'=>$id])` ayar tablosunu okur (`BackstageService.php:58,:94`).
9. **`getSettingsGroup()` JOIN'de tablo ön ekini zorlar.** `select('z_settings.*')` ve
   `join('z_setting_groups', …)` (`BackstageProvider.php:57-58`) — **tablo ön eki kodda
   gömülü**; ön ek değişirse bu sorgu sessizce bozulur.
10. **`CrudSettingsProvider` "parazit koruması" her metotta tekrarlanır.**
    `unset($data['redirect_url'], $data['view'], $data['id'])` (:21,:42,:57,:66) —
    `id` de silindiği için `updateSetting()` çağırdığı `$id` **argümandan** gelir; veri
    dizisindeki `id` yok sayılır.
11. **`bulkUpdateSettings()` önbellek anahtarları eksik.** Yalnız `settings_group_seo` ve
    `settings_group_system` düşürülür (:130-131). `email` grubu toplu güncellendiğinde
    önbellek düşmez — `EmailController::updateMailSettings()` tam olarak bu yolu kullanır
    (`EmailController.php:97`).
12. **`testMail()` `email_admin_address` yoksa hiç göndermez**, ayar önce kaydedilmelidir
    (`EmailController.php:68-70`). `site_name` kaynak olarak `service('seo')?->get('site_title')`
    kullanılır (:75) — bu anahtar Webhub'ın yazdığı `meta-title`/`site-title` ile aynı değildir.
13. **`EmailController::updateMailSettings()` iki senaryo, iki doğrulama.** `settings` dizisi
    varsa **doğrulamasız** toplu yazma (:96-99); yoksa 8 kurallu yapısal güncelleme
    (:102-113). Ham `request->input('settings')` kullanıldığı için toplu yolda beyaz liste yok.

## 6. Örnek (gerçek koddan)

```php
// Bundles/Internal/Backstage/Handlers/SidebarHandler.php:44-64
public function reorder(string $type, array $ids): bool
{
    $model = ($type === 'category') ? $this->SidebarCategoriesModel : $this->SidebarMenusModel;
    $field = ($type === 'category') ? 'category_order' : 'menu_order';

    $model->beginTransaction();

    try {
        foreach ($ids as $index => $id) {
            if (!$model->update((int) $id, [$field => $index + 1])) {
                $model->rollBack();
                return false;
            }
        }
        $model->commit();
        return true;
    } catch (Exception $e) {
        $model->rollBack();
        return false;
    }
}
```

```php
// Bundles/Internal/Backstage/Providers/CrudSettingsProvider.php:18-35
public function createSetting(array $data): int|bool
{
    // 🛡️ [PARASITE PROTECTION] - Veri tabanında olmayan UI alanlarını temizle
    unset($data['redirect_url'], $data['view'], $data['id']);

    if (empty($data['setting_key']) && !empty($data['label_tr'])) {
        $slug = $this->helper('text')->turkishSlug($data['label_tr']);
        $data['setting_key'] = str_replace('-', '_', $slug);
    }
    $data['is_active'] = $data['is_active'] ?? 1;

    // 🛡️ [JSON PROTECTION] - Boş gelen JSON alanlarını null yap
    if (isset($data['field_options']) && empty($data['field_options'])) {
        $data['field_options'] = null;
    }

    return $this->SettingsModel->create($data);
}
```

## 7. İlgili belgeler

* [Core/Base/README.md](../../../Core/Base/README.md) — `BaseComponent`, uydurma özellik çözümü
* [Core/Base/Services.md](../../../Core/Base/Services.md) — `BaseService` / `BaseProvider` sözleşmeleri
* [Core/Database/Repositories.md](../../../Core/Database/Repositories.md) — `SettingsRepository::fetch()`
* [kavramlar/02-yapilandirma.md](../../../kavramlar/02-yapilandirma.md) — `z_settings` / `z_setting_groups` şeması
* [acik-sorular.md](../../../acik-sorular.md)