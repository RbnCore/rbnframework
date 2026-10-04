<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Base\Data\Traits\Model\Engine;

use Rbn\Framework\Core\System\Config\Config;

/**
 * MassAssignmentTrait - Toplu atama güvenliği + kiracı izolasyonu 🛡️🔑⚓
 *
 * FW-BASE-1 (T1 + T2 + T3) tek motoru. `BaseModel` bu trait'i `ActionModelTrait`
 * üzerinden alır; model dosyaları yalnızca `$fillable` / `$guarded` tanımlar.
 *
 * GERİ UYUMLULUK KİLİDİ (asıl tasarım ilkesi):
 *   "Model bu özelliği TANIMLAMIYORSA davranış DEĞİŞMEZ."
 *   Her iki bayrak da `Config::get(...)` ile açılır ve VARSAYILAN KAPALIDIR;
 *   kapalıyken motor hiçbir alanı süzmez, hiçbir kaydı değiştirmez.
 *
 * @property-read string $fillable
 * @property-read string $guarded
 */
trait MassAssignmentTrait
{
    /** @var array[] Beyaz liste: yalnız bu alanlar yazılır. */
    protected array $fillable = [];

    /** @var array[] Kara liste: bu alanlar yazılamaz. */
    protected array $guarded = [];

    /** @var string|null `writeAsProject()` kaçış kapısının zorladığı proje anahtarı. */
    protected ?string $forcedProjectKey = null;

    /**
     * FW-BASE-2 (T4): `authorizeFields()` ile AÇIKça izin verilen korumalı alanlar.
     *
     * @var string[]
     */
    protected array $authorizedFields = [];

    /** Toplu atama motorunun config anahtarı (FW-BASE-2 T4 sonrası: varsayılan `on`). */
    public const MASS_ASSIGNMENT_FLAG = 'security.mass_assignment';

    /**
     * Korumalı alan LOG-ONLY sayacının config anahtarı (varsayılan: `off`).
     *
     * T2 ayrı bir anahtardır: ölçüm (log-only) ile zorlama (`mass_assignment`)
     * birbirinden bağımsız açılıp kapatılabilir.
     */
    public const PROTECTED_FIELD_LOG_FLAG = 'security.protected_field_log';

    /**
     * GLOBAL korumalı alan listesi — YALNIZ `warning` logu için (T2).
     *
     * [PATRON KARARI 2] `status` ve `is_active` bilinçli olarak YOKTUR: altı
     * meşru çağıran onları yazıyor, listeye almak kırılma üretir.
     * [PATRON KARARI 1] Bu liste `$fillable`/`$guarded` TANIMLAMAYAN modellere
     * ZORLAMA olarak UYGULANMAZ; yalnız ölçüm/log katmanında görünür.
     *
     * @var string[]
     */
    public const PROTECTED_FIELDS = [
        'role',
        'is_admin',
        'password',
        'password_hash',
        'license_key',
        'project_key',
        'user_id',
    ];

    /* ==========================================================================
       [ T1 ]  BEYAZ / KARA LİSTE SÜZGECİ
       ========================================================================== */

    /**
     * Toplu atama süzgeci 🛡️
     *
     * Tasarım ilkesi: "Model bu özelliği TANIMLAMIYORSA davranış DEĞİŞMEZ."
     * - bayrak `off`  -> hiçbir süzme yapılmaz
     * - `$fillable` tanımlıysa beyaz liste uygulanır
     * - `$guarded`  tanımlıysa kara liste uygulanır
     * - ikisi birden tanımlıysa `$fillable` beyaz listesi ÖNCE uygulanır
     *   (baskın), `$guarded` yalnız kalan kümede kara liste görevi görür.
     */
    protected function applyMassAssignmentGuard(array $data): array
    {
        if ($this->massAssignmentMode() !== 'on') {
            return $data;
        }
        if ($this->fillable !== []) {
            $data = array_intersect_key($data, array_flip($this->fillable));
        }
        if ($this->guarded !== []) {
            // FW-BASE-2 T4: `authorizeFields()` ile yetki verilmiş alanlar kara
            // listeden ÇIKARILIR. Kalan korumalı alanlar düşer.
            $guard = array_values(array_diff($this->guarded, $this->authorizedFields));
            if ($guard !== []) {
                $data = array_diff_key($data, array_flip($guard));
            }
        }
        return $data;
    }

    /**
     * YETKİLİ YAZMA KAPISI — korumalı alana yazmayı bilerek açar 🎫
     *
     * FW-BASE-2 (T4). `writeAsProject()` (kiracı izolasyonu) kardeşidir:
     * ikisi de **klon** döndürür, kaynak modeli değiştirmez ve her çağrı
     * `security` kanalına `MASS_ASSIGNMENT_AUTHORIZED_WRITE` olarak DÜŞER.
     *
     * Kullanım kuralı (Anayasa §10 — isim ne yaptığını söyler):
     *   - yetki metodu **istemi ALAN DEĞERİNİ DEĞİL, KARARINI** kabul eder
     *     (`setRole(int $id, string $role)`, `updateStatus(int $id, string $status)`);
     *   - alan listesi sabittir, `request`/`$_POST` içinden GELMEZ.
     *
     * @param string[] $fields Bu klon için yazılabilir sayılacak korumalı alanlar.
     */
    public function authorizeFields(array $fields): self
    {
        $clone = clone $this;
        $clone->authorizedFields = array_values(array_unique($fields));

        try {
            $this->logs()->channel('security')->notice('MASS_ASSIGNMENT_AUTHORIZED_WRITE', [
                'model'  => static::class,
                'fields' => $clone->authorizedFields,
            ]);
        } catch (\Throwable $e) {
            // Log yazılamazsa yazma ENGELLENMEZ — kapı yine de açıktır.
        }

        return $clone;
    }

    /**
     * BEYAZ LİSTEYİ AÇIKÇA UYGULA — süzgeçten GEÇEN yollar için 🎯
     *
     * FW-BASE-3 (T5). `create()`/`update()`/`save()` süzgeci zaten kendiliğinden
     * uygular. Ancak `model->query()->insert()/update()` yolları `CrudModelTrait`
     * içinden GEÇMEZ; orada süzgeç çalışmaz. Bu metot, süzgeçten geçen bir
     * repository'nin beyaz listeyi **kendi çağırması** için açıktır.
     *
     * Geri uyumluluk kilidi aynen korunur: `$fillable` tanımlı değilse veya
     * bayrak `off` ise veri DOKUNULMAZ.
     *
     * @param array $data Ham (süzülmemiş) veri.
     * @return array `$fillable` ile budanmış veri.
     */
    public function filterFillable(array $data): array
    {
        if ($this->fillable === [] || $this->massAssignmentMode() !== 'on') {
            return $data;
        }

        $beyaz = array_flip(array_values($this->fillable));

        return array_intersect_key($data, $beyaz);
    }

    /**
     * BEYAZ LİSTEYİ DIŞARIYA AÇ — yalnız ÖLÇMEK için 📋
     *
     * FW-BASE-3 (T5): `CrudControllerTrait` ham istek verisini bu listeye
     * bağlar. Liste BOŞ dönerse "bu model beyaz listesi TANIMLAMIYOR"
     * demektir ve çağıran taraf **davranışı DEĞİŞTİRMEZ** (T1 kilidi).
     *
     * @return string[] Yazılabilir alan adları.
     */
    public function getFillableFields(): array
    {
        return array_values($this->fillable);
    }

    /**
     * Toplu atama bayrağını normalize eder 🏷️     *
     * Yalnız tam `on`/`enforce` zorlamadır; `log_only` süzme yapmaz.
     * FW-BASE-2 (T4) sonrası **varsayılan `on`**: beş güvenlik modeli
     * korumalı alanlarını tanımlar, kalan modeller tanımlamadığı için
     * süzme kendilerine UYGULANMAZ (geri uyumluluk kilidi korunur).
     * `security.mass_assignment=false|off|log_only` ile geri alınabilir.
     */
    protected function massAssignmentMode(): string
    {
        $flag = Config::get(self::MASS_ASSIGNMENT_FLAG, 'on');
        if (!is_string($flag)) {
            return 'off';
        }
        $flag = strtolower(trim($flag));
        return in_array($flag, ['on', 'enforce'], true) ? 'on' : 'off';
    }

    /* ==========================================================================
       [ T2 ]  KORUMALI ALAN LOG-ONLY SAYACI
       ========================================================================== */

    /**
     * Korumalı alan log-only sayacı AÇIK mı? 📊
     *
     * Yalnız tam `on` sayacı çalıştırır; `off`/`log_only`/okunamayan değer
     * sayacı KAPALI sayar. FW-BASE-2 (T4) sayacı AÇIK bıraktı: zorlama
     * başladıktan sonra da "hangi korumalı alan ne sıklıkla geliyor" ölçümü
     * kaybolmasın (T2 ölçümü 18 sitede 0 tetiklenmişti).
     */
    protected function protectedFieldLogEnabled(): bool
    {
        $flag = Config::get(self::PROTECTED_FIELD_LOG_FLAG, 'on');
        return is_string($flag) && strtolower(trim($flag)) === 'on';
    }

    /**
     * Toplu atamada korumalı alan geldiyse LOG-ONLY `warning` düşer 📜
     *
     * DAVRANIŞ DEĞİŞMEZ: yalnız ölçüm. Log satırı **değer taşımaz** — sadece
     * model sınıfı, işlem türü ve alan ADLARI yazılır (parola sızıntısı riski
     * kapatılmıştır). Sayaç dosyadan okunur: `rg -c 'MASS_ASSIGNMENT_BLOCK'
     * <Storage/logs/security>` — yeni tablo/migration gerektirmez.
     *
     * @param array  $data      Gelen ham veri (süzme ÖNCESİ).
     * @param string $operation `create` | `update` | `insert`
     */
    protected function logProtectedFieldAttempt(array $data, string $operation): void
    {
        if (!$this->protectedFieldLogEnabled()) {
            return;
        }

        $fields = array_values(array_intersect(self::PROTECTED_FIELDS, array_keys($data)));
        if ($fields === []) {
            return;
        }

        try {
            $this->logs()->channel('security')->warning('MASS_ASSIGNMENT_BLOCK', [
                'model'     => static::class,
                'operation' => $operation,
                'fields'    => $fields,
                'mode'      => 'log_only',
            ]);
        } catch (\Throwable $e) {
            // Sayaç yazılamazsa YAZMA ENGELLENMEZ (ölçüm katmanı veri değiştirmez).
        }
    }

    /* ==========================================================================
       [ T3 / B-20 ]  KİRACİ İZOLASYONU + KAÇIŞ KAPISI
       ========================================================================== */

    /**
     * Tek meşru proje anahtarı kaçış kapısı 🔑🎫
     *
     * `withoutProjectScope()` yalnız OKUMA içindir; bu metot YAZMA içindir ve
     * her çağrısı `security` kanalına `notice` olarak DÜŞÜLÜR (FW-BASE-1 T3).
     * Klon döndürür: kaynak model değişmez.
     */
    public function writeAsProject(string $projectKey): self
    {
        $clone = clone $this;
        $clone->forcedProjectKey = $projectKey;
        // Kapsam filtresi de düşer: `project_key` zaten açıkça veriliyor ve
        // GLOBAL satırlar da hedeflenebilir olmalı (ShieldSettingsRepository).
        $clone->scoped = false;

        try {
            $this->logs()->channel('security')->notice('MASS_ASSIGNMENT_PROJECT_OVERRIDE', [
                'model'       => static::class,
                'project_key' => $projectKey,
            ]);
        } catch (\Throwable $e) {
            // Log yazılamazsa yazma ENGELLENMEZ — kapı yine de açıktır.
        }

        return $clone;
    }

    /**
     * B-20 (FW-BASE-1 T3): `project_key` HER ZAMAN sunucu bağlamından yazılır.
     *
     * Kullanıcının gönderdiği `project_key` DEĞERİ YOK SAYILIR; tek istisna
     * `writeAsProject()` ile açıkça açılan ve loglanan kaçış kapısıdır.
     * `scoped` olmayan modellerde HİÇBİR ŞEY yapılmaz (geri uyumluluk).
     */
    protected function applyProjectKeyScope(array $data): array
    {
        if (!empty($this->forcedProjectKey)) {
            $data['project_key'] = $this->forcedProjectKey;
            return $data;
        }

        if (empty($this->scoped) || !method_exists($this, 'getActiveProjectKey')) {
            return $data;
        }

        // [FW-ALTYAPI-3 / H · G4] Yazma yolu da OKUMA yoluyla AYNI anahtari
        // kullanir: `withProjectScope($X)` ile secilen kiraci, sorguda da
        // yazmada da gecerlidir. Boylece `withProjectScope()->create([...])`
        // cift filtreye dusmez.
        if (method_exists($this, 'resolveScopeProjectKey')) {
            $kapsam = $this->resolveScopeProjectKey();
            // Baglam COZULEMEDIYSE (`default` sentineli — design §4.6) hicbir
            // deger YAZILMAZ: `'default'` ASLA project_key olmaz.
            //
            // OLCULEN GERCEK: 7 yerel veritabaninda 74 kolonda
            // `project_key='default'` satir YOKTUR; yani bu deger ne okumada
            // ise yarar (her zaman 0 satir) ne yazmada (gorusmez, sahipsiz satir).
            //
            // KAPSAM DISI BIR SÜRÜM BULUNDU (olcum: `fw_base3_t5_crud_girisi`
            // A1 kirmizi oldu): eski kod cagiranin `project_key` degerini
            // yazma kisiminda EZDI, yani baglam cozulmedigi halde `default`
            // yaziyordu. Artik cagiranin degeri korunur, yoksa hicbir sey
            // yazilmaz -> satir hicbir kiracinin kapsamina girmez (fail-CLOSED).
            if ($kapsam === null || $kapsam === ''
                || $kapsam === \Rbn\Framework\Core\Base\Data\BaseModel::SCOPE_KEY_UNRESOLVED) {
                if (empty($data['project_key'])) {
                    unset($data['project_key']);
                }
                return $data;
            }
            $data['project_key'] = $kapsam;
            return $data;
        }

        $data['project_key'] = $this->getActiveProjectKey();
        return $data;
    }

    /**
     * MERKEZİ proje anahtarı yardımcısı — "sadece boşsa doldur" seması 🔑
     *
     * FW-BASE-1 T3: B-20 düzeltmesi öncesi `empty($data['project_key'])` deseni
     * altı ayrı yerde tekrar ediyordu. Bu TEK merkez onları toplar; kapsam
     * DIŞI (scoped olmayan) modeller için davranış BİREBİR korunur.
     *
     * @param string|null $projectKey Boşsa aktif bağlam kullanılır.
     */
    public static function fillProjectKeyIfMissing(array $data, ?string $projectKey = null): array
    {
        if (empty($data['project_key'])) {
            $data['project_key'] = ($projectKey !== null && $projectKey !== '')
                ? $projectKey
                : active_project_key();
        }
        return $data;
    }
}