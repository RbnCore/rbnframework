# Güvenlik Politikası / Security Policy

> **Bu dosya neden var:** Framework'te bulunan bir güvenlik açığının **gizli ve zamanında**
> bize nasıl ulaştırılacağını, hangi sürümlerin güvenlik güncellemesi aldığını ve ne zaman
> duyurulacağını tanımlar. Bu politika, framework'ün nasıl *kullanıldığıyla* değil,
> framework'ün **güvenliğiyle** ilgilidir; uygulama/ayar tarafındaki güvenlik sorumluluğu
> site sahibine aittir.

> **This file:** how to privately report a security vulnerability found in the framework,
> which versions receive security fixes, and when disclosure happens.

> Bilinçli olarak **daha sıkı yapılmayan** güvenlik/yapılandırma tercihleri:
> [KNOWN-LIMITATIONS.md](KNOWN-LIMITATIONS.md) · Deliberately *not* tightened decisions:
> [KNOWN-LIMITATIONS.md](KNOWN-LIMITATIONS.md)

---

## Desteklenen sürümler / Supported versions

| Sürüm | Durum | Güvenlik yamaları |
|---|---|---|
| 0.9.x | **Desteklenir** | Evet — en güncel 0.9.x alınır |
| 0.8.x ve öncesi | **Desteklenmez** | Hayır — güncelleme gerekir |

| Version | Status | Security fixes |
|---|---|---|
| 0.9.x | **Supported** | Yes — use the latest 0.9.x |
| 0.8.x and older | **Unsupported** | No — upgrade required |

**Not:** Bu tablo SemVer 0.x yükseltme stratejisidir. İlk yayın `0.9.0` olacaktır; yayınlandığı
anda tablo güncellenir.

**Note:** This follows the SemVer 0.x upgrade strategy. The first release will be `0.9.0`; this
table is updated when it ships.

---

## Güvenlik açığı bildirme / Reporting a vulnerability

### Nereye / Where

İki kanal vardır; **ikisinden biri yeterlidir**:

There are two channels; **either one is enough**:

1. **GitHub — "Report a vulnerability"** (private vulnerability reporting). Depo →
   **Security** sekmesi → **Report a vulnerability**. Bu kanal, bildiriminizi yalnız proje
   ekiplerinin gördüğü gizli bir alana düşer ve herkese açık issue oluşturmaz.
   *(Bu özellik repoda etkinleştirilmelidir.)*

   **GitHub — "Report a vulnerability"** (private vulnerability reporting). Repository →
   **Security** tab → **Report a vulnerability**. Your report lands in a private area visible
   only to the maintainers; no public issue is created. *(This setting must be enabled in the
   repository.)*

2. **E-posta / Email:** `info@rbncore.tr` — konu satırına `SECURITY` yazın.

   **Email:** `info@rbncore.tr` — put `SECURITY` in the subject line.

Bildirim **hiçbir üçüncü tarafa** (forum, sosyal medya, genel sohbet) iletilmemelidir.

Never disclose the report through a third party (forums, social media, public chats).

### Nasıl / How

Açıklamanız **güvenli kanal** üzerinden, kod veya sömürme adımı/payload içeren en küçük örnekle
birlikte gönderilmelidir. Rapor; neyin etkilendiğini, nasıl yeniden üretileceğini ve önerilen
düzeltmeyi içermelidir.

Send your description through the secure channel above, with the smallest possible example that
includes the code or a proof of concept. Include what is affected, how to reproduce it, and your
suggested fix.

### Ne yapılmamalı / What not to do

- ❌ **Açık (public) issue ile bildirme** — issue herkese açıktır ve anında sömürülebilir hale gelir.
- ❌ Bulguyu önce blog, sosyal medya veya topluluk kanalında paylaşmak.
- ❌ Başka bir sistemde (başka alan adı, başka dağıtım) bulunduğu düşünülen açığı geçiştirmek;
  yine bildirin.

- ❌ **Reporting through a public issue** — issues are public and may be exploited immediately.
- ❌ Disclosing the finding first on a blog, social media or a community channel.
- ❌ Assuming a finding in another system (a different domain, a different distribution) is "not
  ours" and ignoring it; report it anyway.

### Yanıt süresi / Response time (hedef — taahhüt değildir / target — not a commitment)

Aşağıdaki süreler **hedef** olarak yazılmıştır, bağlayıcı taahhüt değildir.

The times below are **targets**, not binding commitments.

| Aşama / Stage | Hedef / Target |
|---|---|
| Bildirim alındı, ön kabul / Report received, acknowledged | Birkaç gün içinde / We aim to acknowledge within a few days |
| Düzeltme yayınlandı / Fix released | 30 gün içinde, ön koşullu onay verilirse daha erken / Within 30 days, earlier if a coordinated release is agreed |
| Bilgilendirme / Credit | Düzeltme yayınlandıktan sonra, bildiren kişinin isteğine göre / After release, at the reporter's request |

Ön koşullu erken yayın: düzeltme hazır olduğunda bildiren kişiye en fazla **10 gün** süre verilir;
süre dolmadan yayınlanabilir. Zorunlu bekleme süresi yoktur.

Early coordinated release: once a fix is ready, the reporter is given at most **10 days**; the fix
may ship afterwards even if no reply arrives. There is no mandatory waiting period.

### Koordineli ifşa / Coordinated disclosure

1. Açık alındıktan sonra düzeltme **önce** hazırlanır ve framework'ü kullanan her kuruluma
   yayınlanır; kısmi yayın "düzeltilmiş" sayılmaz.
2. Yayın tamamlanmadan ayrıntı (teknik kök neden, PoC, sömürme adımları, dosya/satır bilgisi)
   yayınlanmaz.
3. Yayınlandıktan sonra önlemler ve sürüm geçmişi kaydı güncellenir.
4. Bildiren kişi, teknik ayrıntıyı yayınlanmadan önce görme hakkına sahiptir.

1. After the report, the fix is prepared first and released to every installation using the
   framework; a partial release does not count as fixed.
2. Until the release is complete, details (root cause, PoC, exploitation steps, file/line
   references) are not published.
3. After the release, mitigations and the version history are updated.
4. The reporter may review the technical details before publication.

---

## Kapsam dışı / Out of scope

- Uygulama, proje veya barındırma ayarları kaynaklı sorunlar (site sahibinin sorumluluğunda)
- Üçüncü taraf paketlerin (`composer.json` `require` listesi) kendi açıkları — doğrudan
  paketin deposuna bildirilmelidir
- Zararsız kabul edilen, yalnız teorik olan veya kanıtlanamayan bulgular
- Spamlama, otomatik tarama trafiği, "hangi siteler bu framework'i kullanıyor" türü talepler

- Problems in applications, projects or hosting configuration (the site owner's responsibility)
- Vulnerabilities in third-party packages listed under `require` in `composer.json` — report
  those to the package's own repository
- Findings that are harmless, purely theoretical or not reproducible
- Spam, automated scanning traffic, and requests to enumerate sites running this framework

---

## Bu sürümde neler değişti / What changed in this version

Ayrıntı: [CHANGELOG.md](CHANGELOG.md). Güvenlik girdileri tarafsız yazılır; sömürme adımı, payload,
PoC ve dosya/satır ayrıntısı bu depoda yayınlanmaz.

Details: [CHANGELOG.md](CHANGELOG.md). Security entries are written neutrally; exploitation
steps, payloads, PoCs and file/line references are not published in this repository.

- Kaynak koda gömülü veritabanı ve SMTP parolaları çıkarıldı; sırlar tek dosyadan, "güvenli kapalı"
  biçimde okunuyor.
- Onarım planındaki diğer güvenlik kalemleri ayrı yama görevlerinde ilerlemektedir.

- Database and SMTP passwords embedded in source code were removed; secrets are read from a single
  file in a fail-closed manner.
- The remaining items of the remediation plan are progressing in separate patch tasks.

## Yükseltme / Upgrading

Zorunlu yükseltme adımları ve geri alma: [UPGRADING.md](UPGRADING.md). Özellikle 0.9.0 için sır
dosyasının **önce** oluşturulması zorunludur.

Mandatory upgrade steps and rollback: [UPGRADING.md](UPGRADING.md). For 0.9.0 the secrets file must
be created **first**.