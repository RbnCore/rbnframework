[English](README.en.md) | **Türkçe**

# RBN Core Framework

**Sürüm:** `0.9.4` · **Lisans:** MIT · **PHP:** 8.3+

RBN Core Framework, tek bir çekirdeğin altında **birden çok bağımsız PHP projesini** barındıran,
çok kiracılı (multi-tenant) mimariye sahip, hafif bir uygulama çatısıdır. Yönlendirme, şablon
işleme, veritabanı katmanı, güvenlik kalkanı, görev zamanlayıcı ve bir komut satırı aracı (CLI)
kendi içinde gelir; dışarıdan yalnız üç Composer paketi kullanır.

> Bu depo, framework'ün **kaynak kodudur**. Bir uygulamanın kendisi değildir.

---

## İçindekiler

- [Gereksinimler](#gereksinimler)
- [Kurulum](#kurulum)
- [Sır yönetimi (secrets.php)](#sır-yönetimi-secretsphp)
- [Klasör haritası](#klasör-haritası)
- [Komut satırı aracı: `rbn`](#komut-satırı-aracı-rbn)
- [Güvenlik](#güvenlik)
- [Sürüm ve yükseltme](#sürüm-ve-yükseltme)
- [Lisans ve atıf](#lisans-ve-atıf)
- [Katkı](#katkı)

---

## Gereksinimler

| Bileşen | Sürüm / Gereklilik |
|---|---|
| PHP | 8.3 veya üzeri (Attribute API kullanılır; 8.3 üzerinde geliştirilir ve test edilir) |
| PHP Eklentileri | `pdo`, `pdo_mysql`, `mbstring`, `curl`, `openssl`, `zip` |
| Composer | 2.x |
| Veritabanı | MySQL / MariaDB |

Composer bağımlılıkları (`composer.json`):

- `vlucas/phpdotenv` — `.env` okuma
- `phpmailer/phpmailer` — e-posta gönderimi
- `iyzico/iyzipay-php` — ödeme

---

## Kurulum

```bash
git clone https://github.com/RbnCore/rbnframework.git
cd rbnframework
composer install
```

Sonraki zorunlu adım: sır dosyasını oluşturmak (aşağıya bakın).

---

## Sır yönetimi (`secrets.php`)

Framework'teki tüm sırlar (veritabanı, SMTP, cPanel, uygulama anahtarı, servis API anahtarları)
**tek bir dosyadan** okunur:

```
Core/System/Config/Secrets/secrets.php
```

Bu dosya depoya **asla** girmez; `.gitignore` ile dışlanmıştır. Şablonu kopyalayın:

```bash
cp Core/System/Config/Secrets/secrets.example.php Core/System/Config/Secrets/secrets.php
chmod 600 Core/System/Config/Secrets/secrets.php
```

Şablondaki `CHANGE_ME` değerlerini kendi bilgilerinizle doldurun. Dosyadaki bölümler:

| Bölüm | İçerik |
|---|---|
| `master_db` | `host`, `port`, `name`, `user`, `pass` |
| `smtp` | `enabled`, `host`, `port`, `secure`, `user`, `pass`, `from_address`, `from_name` |
| `cpanel` | `host`, `user`, `token` |
| `app_key` | Uygulama anahtarı (64 karakterlik onaltılık metin) |
| `api` | Servis başına anahtar/değer (`Secrets::api('iyzico')` ile okunur) |

Ayrıntılı açıklama: `Core/System/Config/Secrets/README.md`

**Davranış:** Okuyucu *fail-closed* çalışır — dosya yoksa, bozuksa, zorunlu bir alan eksikse veya
içinde `CHANGE_ME` kaldıysa **açık hata** fırlatır; sessiz varsayılan değere düşmez. Hata mesajlarında
hiçbir sır değeri yazılmaz, yalnız dosya/bölüm/alan adı yazar.

Canlı sunucuya kurulumda sıralama şöyledir: **önce kod**, sonra `secrets.php` dosyasını sunucuda
elle oluşturun. Yükseltme adımları için `.github/UPGRADING.md`.

---

## Klasör haritası

```
rbnframework/
├── Core/                    # Çekirdek (çıkarılamaz)
│   ├── Base/                # Component, Controller, Service, Model temelleri
│   ├── Database/            # PDO yönetimi, sorgu kurucular, şema
│   ├── Http/                # Request, Response, doğrulama, güvenlik kalkanı
│   ├── Render/              # Şablon motoru, düzen (layout) çözümleyici
│   ├── Routes/              # Yönlendirici, eşleştirici, dağıtıcı
│   ├── Services/            # CLI (rbn), cron ve görev yöneticileri
│   ├── Support/             # Tanım sabitleri, yardımcılar, köprüler
│   └── System/              # Kernel, keşif motoru, yollar, yapılandırma, depolar
├── Bundles/                 # Çıkarılabilir paketler
│   ├── RbnSuite/            # RbnAdmin, RbnAuth, RbnStudio
│   └── Internal/            # Backstage, Syshub, Webhub
├── Packages/                # Küçük, tek iş yapan servis paketleri
│   │                        # RbnApi, RbnEmail, RbnFile, RbnUtility (ve diğerleri)
├── Resources/               # Assets, şablonlar ve veri dosyaları
├── vendor/                  # Composer bağımlılıkları (depoya girmez)
└── rbn                      # Komut satırı aracı giriş noktası
```

Bu harita kodda `Core/Support/Definitions/System/FolderMatrix.php` içinde tanımlıdır; uygulama
(proje) tarafındaki karşılık da aynı dosyanın `PROJECT` sabitindedir.

---

## Komut satırı aracı: `rbn`

Framework'in CLI aracı `rbnframework/rbn` dosyasıdır; `php rbn <komut>` ile çalışır.

```bash
php rbn                              # komut listesini gösterir
php rbn system:doctor                # sistem sağlığı, Master DB ve projeler için teşhis
php rbn project:list                 # sistemdeki projeleri listeler
php rbn make:controller AdimKontrol  # Controller/Model/Request/Migration üretir
php rbn migrate                      # bekleyen migration'ları çalıştırır
php rbn migrate:status               # migration durumunu gösterir
php rbn cache:clear                  # sistem ve proje önbelleğini temizler
php rbn logs:clear                   # log dosyalarını temizler
php rbn system:cron                  # zamanlanmış görevleri çalıştırır
```

Komutların tamamı için `php rbn` (list) çıktısına bakın.

**Global bayraklar:**

- `--project=<anahtar>` — komutu belirli bir proje bağlamında çalıştırır
- `--master` — Master (sistem) veritabanı bağlamında çalıştırır

> `master:migrate` **asla** otomatik çalışmaz; yalnızca elle verilir:
> `php rbn master:migrate`.

---

## Güvenlik

Bir güvenlik açığı bulduğunuzu **herkese açık bir issue olarak açmayın**. Gizli bildirim
kanalları, desteklenen sürümler ve koordineli açıklama süreci için:

**→ [.github/SECURITY.md](.github/SECURITY.md)**

---

## Sürüm ve yükseltme

- Değişiklik günlüğü: [.github/CHANGELOG.md](.github/CHANGELOG.md)
- Zorunlu yükseltme adımları ve geri alma: [.github/UPGRADING.md](.github/UPGRADING.md)

Bu belgeler Türkçedir.

Atıf için `CITATION.cff` dosyasına bakabilirsiniz.

---

## Lisans ve atıf

Bu proje **MIT** lisansıyla dağıtılmaktadır. Tam metin: [LICENSE](LICENSE).

Kısaca: kullanabilir, değiştirebilir, dağıtabilirsiniz. Tek koşul — **telif ve lisans bildirimini
korumaktır** (kopyaladığınız her nüshada `LICENSE` metni kalsın).

Bu çalışma üzerine kendi işinizi kurarsanız, RbnBilisim / RbnCore'den bahsetmeniz memnuniyetle
karşılanır; bu isteğe bağlıdır, zorunlu değildir.

---

## Katkı

Katkılar memnuniyetle karşılanır:

1. Önce bir **issue** açın; neyi değiştirmek istediğinizi ve nedenini yazın.
2. Mümkünse küçük, tek konulu bir değişiklik (pull request) gönderin.
3. Gizli bir güvenlik bulgusu için issue **açmayın** — bkz. [SECURITY.md](.github/SECURITY.md).