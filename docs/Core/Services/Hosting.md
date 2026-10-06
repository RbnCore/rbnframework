# Core/Services/Hosting — cPanel API köprüsü (5 dosya)

> **Doğrulanan kod tabanı:** `c23b431f` · **Tarih:** 2026-10-05 · **Yayın:** 0.9.4 = bu commit + sonrası; belge yalnız doğrulama anındaki kodu anlatır
> **Kaynak klasör:** `Core/Services/Hosting/` — **5 `*.php`** = 1 kök + `Data/` 1 +
> `Handlers/` 2 + `Providers/` 1.
> **Envanter:** 5 dosyanın **5'i** anlatıldı.
> **Doğrulama platformu:** Windows + PHP 8.3. **`secrets.php` açılmadı, cPanel
> API çağrısı yapılmadı** (gece görevi kuralı: sır dosyası yasak).

## 1. Ne işe yarar, kim kullanır

cPanel UAPI'sine **HTTP köprüsü**. 5 sınıf, üç katman:

```
CPanelService (servis)                    ← kayıt: logicMap['services']['cpanel']
  └─ bridge() → CPanelProvider (provider)  ← kayıt YOK (elle tutulan bağımlılık)
       ├─ call($module, $function, $params)   ← tüm UAPI çağrıları buradan
       └─ getSsoUrl($target, $email) / redirect()
  └─ mail()    → CPanelMailHandler       ← kayıt: logicMap['handlers']['cpanelMail']
  └─ domains() → CPanelDomainsHandler    ← kayıt: logicMap['handlers']['cpanelDomains']
  └─ host() / sso()
Data\CpanelData                            ← sabitler (kota, SSO hedefleri)
```

**Kimler çağırır:** yönetim panelindeki e-posta ve alan adı ekranları
(RouteBlueprint üzerinden). Bu çalışmada çağıran örnek bulunamadı
(`CPanelService` dışında `service('cpanel')` referansı tarandı, panel
kaynakları `Bundles/RbnSuite/RbnAdmin` altında ve bu göreve dahil değil).

## 2. Klasör/dosya envanteri (5/5)

| Dosya | Görev | Önemli public yöntemler |
|---|---|---|
| `CPanelService.php` (68) | **İnce servis kabuğu** — provider ve handler'lara yönlendirir. | `bridge()`, `call(string $module,string $function,array $params=[]): array`, `host(): string`, `mail()`, `sso()`, `domains()` |
| `Providers/CPanelProvider.php` (229) | **Asıl köprü.** Tembel kimlik bilgisi, UAPI çağrısı, SSO URL üretimi, yönlendirme. | `afterBoot(): void` (korumalı), `host(): string`, `isConfigured(): bool`, `call(string $module,string $function,array $params=[]): array`, `getSsoUrl(string $target,?string $email=null): array`, `redirect(string $target,?string $email=null): void` · korumalı: `credentials(): array` |
| `Handlers/CPanelMailHandler.php` (121) | **E-posta hesabı** işlemleri (8 uç). | `list(string $domain): array`, `create(string $email,string $password,string $domain,int $quota=0): array`, `delete(string $email,string $domain): array`, `getWebmailSession(string $email): array`, `changePassword(string $email,string $password,string $domain): array`, `listAll(): array`, `editQuota(string $email,string $domain,int $quota): array` |
| `Handlers/CPanelDomainsHandler.php` (24) | **Alan adı** listeleme (tek metot, en küçük sınıf). | `list(): array` |
| `Data/CpanelData.php` (75) | `final` veri/sabit sınıfı. | `const DEFAULT_QUOTA = 1024` (MB), `const DESTINATIONS` (7 hedef) |

## 3. Akış — `CPanelProvider::call()` (Providers/CPanelProvider.php:80-108)

```
call('Email', 'list_pops', ['domain'=>'…'])
 ├─ credentials()                                    :84 → :43-57
 │    · cache'lenmiş $host/$user/$token döner         :45-47
 │    · Secrets::section('cpanel') → $c             :50   ← fail-closed
 │    · RuntimeException: bölüm yoksa/eksikse        :41 yorumu
 │    · 'sessiz fallback YOK'                        :83
 ├─ !isConfigured() → ['status'=>0, 'errors'=>['cPanel configuration is missing or invalid.']]  :86-88
 ├─ URL: https://{host}:2083/execute/{module}/{function}                    :90
 ├─ remote->request('GET', $url, $params, [], false, [
 │      connect_timeout: 5, timeout: 15,
 │      CURLOPT_HTTPAUTH    => CURLAUTH_BASIC,
 │      CURLOPT_USERPWD     => "user:token",
 │      CURLOPT_SSL_VERIFYPEER => false,      ← ⚠️
 │      CURLOPT_SSL_VERIFYHOST => false       ← ⚠️
 │  ])                                                                        :92-101
 ├─ status 'error' | 'remote_error' → ['status'=>0,'errors'=>[message]]       :103-105
 └─ return $response['data'] ?? []                                            :107
```

## 4. Akış — `getSsoUrl()` ve `redirect()` (`:113-…`)

```
getSsoUrl($target, $email = null)
 ├─ $target = strtolower($target)                                            :115
 ├─ !isset(CpanelData::DESTINATIONS[$target])
 │    → ['success'=>false, 'message'=>"Geçersiz hedef: '{$target}'"]          :116-121
 └─ CpanelData::DESTINATIONS[$target] ile SSO oturumu üretir                :…

redirect($target, $email = null)
 └─ getSsoUrl() → başarılıysa header('Location: …') + exit                 :170
```

## 5. Yapılandırma ve sabitler (ölçülen)

`CpanelData` (tümü ölçüldü):

| Sabit | Değer |
|---|---|
| `DEFAULT_QUOTA` | `1024` (**MB**) |
| `DESTINATIONS` | **7 hedef** |

`DESTINATIONS` içeriği (ölçülen tam liste):

| Anahtar | Port | Yol |
|---|---|---|
| `cpanel` | 2083 | `/frontend/jupiter/index.html` |
| `filemanager` | 2083 | `/frontend/jupiter/filemanager/index.html` |
| `phpmyadmin` | 2083 | `/3rdparty/phpMyAdmin/index.php` |
| `email` | 2083 | `/frontend/jupiter/mail/pops/index.html` |
| `domains` | 2083 | `/frontend/jupiter/domains/index.html` |
| `cron` | 2083 | `/frontend/jupiter/cron/index.html` |
| `webmail` | **2096** | `/3rdparty/roundcube/index.php` |

Her anahtar `{port, path, title, message, sub_message}` taşır.

| Diğer | Değer | Kaynak |
|---|---|---|
| UAPI portu | `2083` (Sabit, yapılandırılamaz) | `:90` |
| Webmail portu | `2096` | `CpanelData` |
| Bağlantı zaman aşımı | `connect_timeout: 5`, `timeout: 15` sn | `:93-94` |
| Kimlik doğrulama | HTTP Basic (`CURLAUTH_BASIC`) | `:96` |
| `isConfigured()` geçersiz saydığı yer tutucu | `str_contains($host, 'your-cpanel-host')` | `:74` |

## 6. Tuzaklar ve kurallar (kodda görülen — ölçülmediği belirtilmiş)

1. **Tembel kimlik yükleme bilinçli (`:26-35` yorumu, B-1):** `afterBoot()`
   **hiçbir sırı okumaz**. Kimlik bilgileri ilk gerçek kullanımda
   `credentials()` içinde çözülür. Gerekçe: "sır dosyası olmayan siteler bu
   provider'ı boot edebilir, çünkü cPanel işlevi çağrılana kadar dosya zorunlu
   değildir."
2. **fail-closed, sessiz fallback yok (`:41`, `:83`):** `Secrets::section('cpanel')`
   bölümü yoksa/eksikse `RuntimeException` fırlatır.
   **Tuzak:** `isConfigured()` kontrolü `credentials()` **sonrasında** gelir
   (`:84` → `:86`), yani kimlik yoksa istisna, kimlik var ama geçersizse
   hata dizisi döner.
3. **⛔ SSL doğrulaması KAPALI (`:98-99`):**
   `CURLOPT_SSL_VERIFYPEER => false` ve `CURLOPT_SSL_VERIFYHOST => false`.
   Bu, Basic kimlik bilgilerinin (user:token) **TLS el sıkışması olmadan**
   gönderilmesi anlamına gelir. Kodda gerekçe yorumu **yoktur**.
   *(Bu çalışmada düzeltilmedi — belge salt okunurdur. Öneri: en az
   `CURLOPT_SSL_VERIFYPEER => true` + CA doğrulaması; üretim öncesi
   karar patronundur.)*
4. **`CPanelService::call()` imzası `CPanelProvider::call()` ile aynı** ama
   `bridge()` üzerinden delegasyon yapar — yani **iki katman** vardır ve
   parametreler birebir aynıdır.
5. **`CPanelDomainsHandler` 24 satırdır ve tek metottur** — `list()` yalnız
   `Email`/`Domains` modülünü çağırır. Alan adı **oluşturma/silme** yoktur
   (salt okunur).
6. **`CpanelData::DEFAULT_QUOTA` MB cinsindendir** (`:19` yorumu) ve
   `CPanelMailHandler::create()` için `$quota = 0` varsayılanı kullanır —
   yani `0` geçildiğinde provider **1024'e düşmez**, `0` gönderir. Düşürme
   mantığı `CPanelMailHandler` içinde değil `CpanelData` tarafındadır
   (ölçülmedi).
7. **`CPanelProvider` kayıt haritasında YOK.** `logicMap` içinde yalnız
   `services['cpanel']` var; `CPanelProvider` `bridge()` ile **elle** alınır.
   Ölçüldü: `handler('cpanelMail')` ve `handler('cpanelDomains')` **çözülüyor**,
   ama `provider('cpanel')` araması için kayıt yoktur.

## 7. Örnek (gerçek koddan)

```php
// Core/Services/Hosting/Providers/CPanelProvider.php:43-57  (tembel + fail-closed)
private function credentials(): array
{
    if ($this->host !== null && $this->user !== null && $this->token !== null) {
        return ['host' => $this->host, 'user' => $this->user, 'token' => $this->token];
    }

    // Fail-closed: dosya yoksa/eksikse burada RuntimeException fırlatılır.
    $c = Secrets::section('cpanel');

    $this->host  = $c['host'];
    $this->user  = $c['user'];
    $this->token = $c['token'];

    return $c;
}
```

```php
// Ölçülen çıktı — CpanelData sabitleri (reflection ile, sır dosyasına dokunulmadan)
//   CpanelData::DEFAULT_QUOTA = 1024
//   CpanelData::DESTINATIONS  → 7 anahtar: cpanel, filemanager, phpmyadmin,
//                               email, domains, cron, webmail
```

## 8. İlgili belgeler

* [Core/Services genel](README.md) · [System](System.md) · [Gatekeepers](Gatekeepers.md)
* [Core/System/Config.md](../System/Config.md) (`Secrets` okuyucusu) ·
  [Core/Http/Engine.md](../Http/Engine.md) (`RemoteRequest`/cURL) ·
  [Core/Base/Services.md](../Base/Services.md) (`BaseProvider`)
* [Kavram: yapılandırma](../../kavramlar/02-yapilandirma.md) · [Açık sorular §1.14](../../acik-sorular.md)