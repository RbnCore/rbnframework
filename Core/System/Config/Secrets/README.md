# Secrets - Sirların TEK Dosyası

Sırlar **TEK** dosyadan okunur: `Core/System/Config/Secrets/secrets.php`
(okuyucu: `Core/System/Config/Secrets.php`, fail-closed).

Şablon: `secrets.example.php` — kopyalayıp `secrets.php` adıyla kaydedin,
`CHANGE_ME` yerlerini doldurun, `chmod 600` yapın. Başka sır dosyası YOKTUR.

İçindekiler: `master_db` (host/port/name/user/pass), `smtp`, `cpanel`,
`app_key`, `api` (her servis için anahtar => değer), `app` (ortam bayrağı
`environment` + çalışma ayarları; opsiyonel, okuyucu `Secrets::app()`).

**Ortam değişkeni yoktur:** framework işletim sistemi ortam değişkeni okumaz;
ortam bayrağı dahil bütün anahtarlar bu dosyadadır. `app.environment`
yazılmazsa `production` kabul edilir ve bir kez loglanır. Yerel geliştirme
makinesinde `'app' => ['environment' => 'development']` yazın.

Projelerin kendi veritabanı **ayrıdır**: her proje `project-settings.php`
dosyasından okur, framework sır dosyasına girmez.

Gerçek `secrets.php` `.gitignore`'ludur; depoya asla commit edilmez.

## Okuyucu teknikleri (Config klasör yapısı ile uyumlu)

- **`Secrets`** (`Core/System/Config/Secrets.php`) TEK okuyucudur ve
  **fail-closed** calisir: dosya yok / bozuk / zorunlu anahtar eksik /
  `CHANGE_ME` / zorunlu bos deger durumlarinda **acik hata** firlatir;
  sessiz `root` / `''` fallback **yoktur**.
- **Hata mesajlarinda HICBIR sır degeri yazilmaz** - yalniz dosya/bolum/anahtar adi.
- Okuyucu kernel/bootstrap asamasinda calistigi icin ortak servis / IoC / `Paths`
  kullanmaz; yolu `__DIR__` ile goreceli olarak cozer.
- **LAZY:** her dosya ayri onbelleklenir ve yalniz ilk istendiginde okunur. Bu
  yuzden cPanel kullanmayan siteler/komutlar `cpanel` bolumu olmadan **acilir**;
  yalniz bir cPanel islevi cagrilinca fail-closed hata verir.
- Bu klasor icin **SSOT istisnasi** gecerlidir: kaynak
  `Definitions/DbProfiles/` (yalniz sabit) + kucuk okuyucu
  `Engine/Database/DbProfileResolver.php` / `Engine/Database/SmtpProfileResolver.php`.
- Yeni hizmet eklemek icin ikinci sır dosyasi acmaya **gerek yoktur**:
  `secrets.php` icindeki `api` bolumune anahtar + satir ekleyin, okuyan kod
  `Secrets::api('iyzico')` ile dogrular (bos deger kabul edilmez).

## Canli sunucu

Sır dosyalari **paketlerle gelmez**; yayin sirasinda sunucuda **elle**
olusturulurlar. Yayin sirasi:

1. Once **kod** yuku.
2. Sonra `secrets.php` dosyasini sunucuda olusturun (`chmod 600`).
3. Canlida `app` bolumunu ekleyin: `'app' => ['environment' => 'production']`
   (yazilmazsa da production kabul edilir, ama log uyarisi her surecte bir kez dusar).

Bolumlu tek dosya gecisi operatoru "once `cpanel-secrets.php` gelsin"
zorunlulugundan MUAF kilar (once kod, sonra dosya guvenli siradir).
Bkz. `.github/UPGRADING.md` 0.9.0 Adim 7.