<?php
/**
 * RBN Framework 3.0: High-Performance Database Engine 🎻⚓
 */
namespace Rbn\Framework\Core\Database\Engine\Traits;

use PDO;
use Rbn\Framework\Core\Support\Contracts\Database\DbProviderInterface;
use Rbn\Framework\Core\Database\Engine\Providers\MySqlProvider;

/**
 * ConnectionTrait - The Multi-Connection Hub 🛰️
 * 
 * Manages active connection states and specialized database providers.
 */
trait ConnectionTrait
{
    /**
     * Taninmasi gereken baglanti adlari [B-24 · FW-GECE-BASE-DOGRULA]
     *
     * `MySqlProvider` yalniz `database_master` ve `database_common`'i ayirt ediyor;
     * listede olmayan her ad `default` -> `database_project` kumesine dusuyor.
     * [FW-ALTYAPI-1 / B-03] Bu liste artik **GECERLILIK KAYNAGI**: asagidaki
     * `normalizeConnectionName()` dogrulamayi bu listeye dayandirir ve
     * tanimli olmayan bir adi sessizce kabul etmez.
     *
     * @var string[]
     */
    private const KNOWN_CONNECTIONS = ['database_project', 'database_master', 'database_common'];

    /**
     * [B-03] Gecersiz (eski) ad -> gecerli ad eslemesi.
     *
     * `QueryBuilder::$connectionName` ve `BaseModel::$connection` varsayilan
     * degeri `'default'`; `MySqlProvider` ise `'default'`'i zaten
     * `database_project` kumesine esliyordu. Yani **ucun farkli varsayilan**
     * ayni hedefe gidiyordu (tablo B-03). Burada tek ada indiriliyor; boylece
     * `default` ile `database_project` **AYNI saglayici nesnesini** paylasir
     * (ayni veritabanina iki ayri PDO acilmaz, ifade onbellegi de bolusur).
     *
     * @var array<string,string>
     */
    private const CONNECTION_ALIASES = [
        'default' => 'database_project',
    ];

    /** @var DbProviderInterface[] Active connection providers */
    protected array $connections = [];

    /** @var string The currently active connection name */
    protected string $activeConnection = 'database_project';

    /**
     * [B-03/B-05] Baglanti adini TEK gecerli ada indirger — sessizce degil 🛡️
     *
     * ONCEDEN: uc farkli varsayilan (`database_project` / `default` /
     * `$connectionName`) ve `MySqlProvider`'in `match()` blogunda **bilinmeyen
     * her adi sessizce** `database_project`'e dusurmesi. Cok kiracili ortamda
     * proje A'nin verisi proje B'ye gidebiliyordu.
     *
     * SIMDI:
     *   - `'default'` -> `database_project` (geriye uyumlu, B-03),
     *   - tanimli ad -> aynen,
     *   - **digeri -> istisna** (fail-closed, B-05).
     *
     * @throws \InvalidArgumentException Tanimsiz baglanti adi.
     */
    public function normalizeConnectionName(string $name): string
    {
        $ad = strtolower(trim($name));

        $ad = self::CONNECTION_ALIASES[$ad] ?? $ad;

        if (!in_array($ad, self::KNOWN_CONNECTIONS, true)) {
            throw new \InvalidArgumentException(sprintf(
                'Bilinmeyen veritabani baglanti adi: "%s". Gecerli adlar: %s.',
                $name,
                implode(', ', self::KNOWN_CONNECTIONS)
            ));
        }

        return $ad;
    }

    /**
     * Switch or get a named connection 🛰️
     */
    public function connection(?string $name = null): self|string
    {
        if ($name === null) {
            return $this->activeConnection;
        }

        $this->activeConnection = $name;
        return $this;
    }

    /**
     * Runs a callback on a named connection and restores the previous one 🔒🧬
     *
     * [B-24 · FW-KARAR-2-B] Save/restore (kaydet/geri al) deseninin TEK
     * yazılışı. `connection()` DEĞİŞTİRİLMEDİ; bu yeni bir yardımcıdır.
     *
     * ESKİ desen (elle, üç satır + `try/finally`):
     *     $onceki = (string) $db->connection();
     *     $db->connection('database_master');
     *     try { ... } finally { $db->connection($onceki); }
     *
     * DAVRANIS BIREBIR AYNI: geri alma `finally` içindedir, yani istisna
     * olsa da aktif bağlantı eski haline döner; istisna YUTULMAZ.
     * Kapsamlar iç içe kullanılabilir (her kapsam kendi `$onceki`'sini
     * yığınlar). `Database` bir singleton olduğu için bu, çağıranın
     * bağlantısını sızdırmaz.
     *
     * [FW-ALTYAPI-1 / B-02] Bu metot artik **TEK TUTARLI API**: sorgu yolu
     * (`QueryBuilder`) yönlendirme için global `connection()` çağırmak yerine
     * BUNU kullanır; yönlendirme yapılır VE geri alınır.
     *
     * @param string   $name   Hedef bağlantı adı (örn. `database_master`)
     * @param callable $islem  Yalnız hedef bağlantı üzerinde çalışacak iş
     * @return mixed            İşin dönüş değeri AYNEN döner
     */
    public function connectionScoped(string $name, callable $islem): mixed
    {
        $onceki = (string) $this->activeConnection;
        $this->activeConnection = $name;

        try {
            return $islem();
        } finally {
            $this->activeConnection = $onceki;
        }
    }

    /**
     * Get the current PDO instance 🧬
     */
    public function getPdo(): PDO
    {
        return $this->getProvider($this->activeConnection)->getPdo();
    }

    /** @var array<string, bool> Tracking active resolutions to prevent loops 🛡️ */
    protected array $resolving = [];

    /**
     * Get the underlying Provider for a connection 🧱
     */
    public function getProvider(string $name): DbProviderInterface
    {
        // Elle kaydedilmis saglayici (orn. `setPdo()` koprurusu) AYEN korunur:
        // anahtar ham adiyla tutulur, normallestirme UYGULANMAZ.
        if (isset($this->connections[$name])) {
            return $this->connections[$name];
        }

        // [FW-ALTYAPI-1 / B-03 + B-05] GORUNURLUK, DOĞRULAMADAN **ONCE**:
        // gecersiz sayilan bir ad ilk kez uretilmek istendiginde hatirlatilir.
        // (Onceki turda bu satir dogrulama SONRASINDAydi; fail-closed eklendiginde
        // istisna once firlatiyor ve log HICBIR ZAMAN yazilmiyordu — yani
        // "sessiz kabul" yerine "sessiz RED" olmustu, o da gorunurluk kaybi.)
        // Gurultu onlemi: kayit zaten varsa buraya GIRILMEZ.
        $hamAd = strtolower(trim($name));
        if (!in_array($hamAd, self::KNOWN_CONNECTIONS, true) && !isset(self::CONNECTION_ALIASES[$hamAd])) {
            error_log('[RBN] BILINMEYEN DB baglanti adi sessizce kabul edildi: "'
                . $name . '" -> "database_project" olarak eslendi'
                . ' (aktif="' . $this->activeConnection . '")');
        }

        // Tek gecerli ada indir (`'default'` -> `database_project`, digerleri
        // ISTISNA). Artik `$name` gecerli bir ad; onceden oldugu gibi
        // `new MySqlProvider($name)` uretilir ve kayit anahtari da odur.
        $name = $this->normalizeConnectionName($name);

        if (isset($this->connections[$name])) {
            return $this->connections[$name];
        }

        // [RBN 3.5] RECURSION GUARD 🛡️⚓⚖️
        if (isset($this->resolving[$name])) {
            // NOT: metin interpolasyonu (`"{$name}"`) KULLANILMAZ — kabul
            // testlerinin govde cikarici (`kabul_fonksiyonGovdesi`) su parcalar-
            // sayacini kaçirir ve metod govdesini yarida keser. Birlesim ayni
            // metni verir.
            throw new \Rbn\Framework\Core\Support\Exceptions\PreflightException(
                'Döngüsel Bağımlılık Tespit Edildi! [' . $name . ']',
                "Veritabanı bağlantısı oluşturulurken tekrar bir veritabanı sorgusu tetiklendi.",
                "Lütfen Boot aşamasındaki servislerinizin veya modellerinizin veritabanı bağımlılıklarını kontrol edin."
            );
        }

        $this->resolving[$name] = true;

        try {
            // Default to MySqlProvider for all RBN connections
            $this->connections[$name] = new MySqlProvider($name);
        } finally {
            unset($this->resolving[$name]);
        }

        return $this->connections[$name];
    }

    /**
     * Manual PDO injection (For testing or legacy compatibility) 🏗️
     */
    public function setPdo(PDO $pdo, string $name = 'default'): void
    {
        // Simple bridge provider for manual PDO
        $bridge = new class($pdo) implements DbProviderInterface {
            private $pdo;
            public function __construct($pdo) { $this->pdo = $pdo; }
            public function getPdo(): PDO { return $this->pdo; }
            public function beginTransaction(): bool { return $this->pdo->beginTransaction(); }
            public function commit(): bool { return $this->pdo->commit(); }
            public function rollback(): bool { return $this->pdo->rollBack(); }
            public function inTransaction(): bool { return $this->pdo->inTransaction(); }
            public function getLastInsertId(): int { return (int)$this->pdo->lastInsertId(); }
        };

        $this->connections[$name] = $bridge;
        $this->activeConnection = $name;
    }

    /**
     * Reconnects a database connection by clearing its provider and statement cache 🔄🔌
     */
    public function reconnect(?string $name = null): void
    {
        $connName = $name ?: $this->activeConnection;
        unset($this->connections[$connName]);
        self::$stmtCache = [];
    }
}
