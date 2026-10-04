# Bilinen Sınırlar ve Bilinçli Kararlar

> **Bu dosya neden var:** Bazı güvenlik ve yapılandırma tercihleri bilerek
> "daha sıkı" yapılmadı. Hepsi ölçülmüş, riski kabul edilmiş kararlardır. Bu
> liste, bir kurulumun **ne yapmadığını** baştan söyler; böylece "bu bir hata mı,
> yoksa tasarım mı?" sorusu ikinci bir güvenlik taraması yapılmadan yanıtlanır.

> **Bu bir hata listesi değildir.** Buradaki her madde bilinçli bir karardır ve
> kaldırılmaması kararlaştırılmıştır. Bir maddenin kaldırılması istenirse
> önce bu dosya güncellenir.

- **Dil:** Türkçe (iç). Her maddenin altında tek cümlelik İngilizcesi vardır.
- **Kapsam:** Yalnız **kullanıcıya/operatöre** görünen sonuçlar yazılır.
  İç ayrıntı, dosya-satır, ajan, proje veya müşteri adı **yoktur**.
- **Bu dosya bir sözleşme değildir:** Madde değişirse değişiklik kaydı
  `CHANGELOG.md`, yükseltme adımları `UPGRADING.md` dosyasındadır.

---

## Ağ ve IP katmanı

- **IP güvenlik katmanı varsayılan olarak yalnız gözlem yapar** (`log_only`):
  kararı günlüğe yazar, isteği engellemez.
  *The IP security layer is observation-only by default (`log_only`): it logs
  its decision instead of blocking the request.*

- **`X-Forwarded-For` başlığına güvenilmez.** Bir ters vekil (proxy) arkasındaki
  gerçek ziyaretçi adresi doğru görünmeyebilir; adres kısıtlaması olan
  özellikler vekil arkasında beklenenden katı davranabilir.
  *`X-Forwarded-For` is not trusted: behind a reverse proxy the real visitor
  address may be inaccurate, so address-restricted features can behave more
  strictly than expected.*

- **Dış API için izin verilen adres listesi boş bırakılırsa tüm adreslere izin
  verilir.** Bu, geriye uyum için bilinçlidir; her istemciyi ayrı ayrı
  tanımlamak yerine tek bir anahtar yeterli görülmüştür.
  *An empty external-API origin allowlist means every origin is allowed; this is
  deliberate for backward compatibility.*

- **Makine API istekleri yalnız anahtarla doğrulanır.** Ek bir imza
  (HMAC) zorunlu değildir.
  *Machine API requests are authenticated by key only; an additional signature
  (HMAC) is not required.*

## Parolalar, sırlar ve erişim

- **Veritabanı ve e-posta parolaları tek dosyada düz metin tutulur** ve
  düzenli parola değişimi yapılmaz.
  *Database and mail passwords are kept in plain text in a single file and are
  not rotated regularly.*

- **Yönetim paneli erişimi için tam yetkili bir API anahtarı saklanır.**
  *A full-privilege API token is stored for control-panel access.*

- **Geliştirici hesapları kapatılamaz/pasifleştirilemez**; hesabı geçici olarak
  kapatmak yerine parolası değiştirilir.
  *Developer accounts cannot be disabled; the password is changed instead.*

- **Yönetim panelindeki başarısız giriş denemeleri veritabanında düz metin
  tutulur** (kimlik bilgisi değil, olay kaydı).
  *Failed sign-in attempts in the control panel are stored in plain text in the
  database (they are audit records, not credentials).*

## Parola kuralları

- **Parola kuralları yıl biçiminde dört haneli sayıları kabul eder**
  (örneğin bir kuruluş adındaki `2024`). Bu, güvenliği düşürmez; kurallar zaten
  dört haneli rakam dizilerini bugün de reddetmektedir.
  *Password rules accept four-digit year-like numbers (e.g. `2024` in a company
  name); this does not weaken the rules, which already reject four-digit
  digit strings.*

- **Zengin metin alanlarındaki HTML etiketleri varsayılan olarak kaldırılmaz.**
  *HTML tags in rich-text fields are not stripped by default.*

## İç yapı ve bakım kolaylığı

- **Metot imzaları kısaltılmaz.** İmza belirsizliği bilinçli olarak kabul
  edilir; metotları yeniden adlandırmak geriye uyumu kıracağı için yapılmaz.
  *Method signatures are not shortened; the signature ambiguity is accepted on
  purpose because renaming methods would break compatibility.*

- **Eski Türkçe metot adları korunur.** Yeni adlar İngilizce olsa da mevcut
  adlar topluca değiştirilmez; bu, çok sayıda projenin gereksiz yere
  kırılmasını önler.
  *Legacy method names are kept; new names are English but existing names are
  not renamed in bulk, which avoids breaking many projects unnecessarily.*

- **Bazı yardımcı metotlar bilinçli olarak "ölü" bırakılmıştır** ve üstteki
  metotların gölgesinde kalır. Silinmezler.
  *Some helper methods are deliberately left unused and shadowed by the methods
  above them; they are not removed.*

- **Servis adı tanımı ve nitelik değerleri her okumada yeniden çözülür.**
  Ön bellek eklenmemiştir; ölçülen kazanç ihmal edilebilir düzeydedir.
  *Service-name definitions and attribute values are resolved on every read; no
  cache was added because the measured gain is negligible.*

- **Sınıf düzeyinde alan/nitelik tanımı yapılmaz.** Bu, PHP'nin erişim
  kuralları gereği bilinçli bir seçimdir.
  *Attributes are not defined at class level; this is a deliberate choice
  required by PHP access rules.*

- **Yapılandırma nesneleri süreç boyunca bir kez üretilip saklanır.**
  *Configuration objects are created once and cached for the process.*

- **Boş proje verisi için erken bir uyarı günlüğü yazılmaz** (gözlemlenebilir
  bir etkisi yoktur).
  *No early warning log is written for empty project data; it has no observable
  effect.*

- **Model sınıfı arayüzü iki kez bildirir.** Bu, PHP'nin nitelik önceliğini
  doğru çözmesi için gereklidir ve kasıtlıdır.
  *The model class declares its interface twice on purpose so that PHP resolves
  trait precedence correctly.*

- **Tip zorlaması katmanlar arasında gevşetilmez ve sıkılaştırılmaz.**
  *Type coercion is neither loosened nor tightened across layers.*

## Sıralama davranışı

- **Boş değerler (null) liste başında sıralanır**, metinlerden önce. Sonuç
  deterministiktir; "null'lar sona" davranışı bir ürün kararı olarak daha sonra
  değerlendirilebilir.
  *Empty (null) values sort before strings; the result is deterministic.
  Sorting nulls last is a product decision that may be revisited.*

## Lisanslama

- **Lisans doğrulaması veritabanı kaydına dayanır.** Çevrimdışı (imzalı)
  doğrulama kullanılmaz.
  *Licence validation relies on the database record; offline signature
  verification is not used.*

- **Masaüstü uygulamalar için lisans/onay tablosu tasarlanmadı ve
  uygulanmadı.**
  *The desktop application licence/approval table was designed but not
  implemented.*

## Tasarım sözleşmeleri

- **CSS yapısı sözleşme değildir.** Sürümler arasında değişebilir ve
  değişecektir; projeler kendi stillerini taşımak zorundadır.
  *CSS structure is not a contract; it may change between releases and projects
  are expected to carry their own styles.*

---

## Bu listeye nasıl bir madde eklenir?

1. Madde **kullanıcıya/operatöre** görünen bir sonuç olmalı (ölçülen davranış),
   iç teşhis ayrıntısı değil.
2. Madde bir **bilinçli karar** olmalı; "henüz yapılmadı" olanlar buraya
   girmez (onlar için yol haritası geçerlidir).
3. Aynı maddenin Türkçe ve İngilizce cümlesi birlikte yazılır.
