<?php

declare(strict_types=1);

/**
 * keys.php — `external-api` anahtar yönetimi CLI 🔑🗂️
 *
 * [FW-APIGUARD · TASARIM GOREV 2 · 2026-10-03 · team member]
 *
 * PATRON KARARI 5: yönetim ÖNCE CLI, panel YOK.
 * PATRON KARARI 2: anahtar `sha256:<hex>` olarak saklanır.
 * PATRON KARARI 3: düz metin `api_key` dalı geri uyum için AÇIK kalır —
 *   bu komut onu SILMEZ, yalnız `keys` bölümünü yönetir.
 *
 * KULLANIM:
 *   php Core/Http/Security/bin/keys.php list
 *   php Core/Http/Security/bin/keys.php create --scope=blog,faq --rate=60/60 --ttl=2592000
 *   php Core/Http/Security/bin/keys.php revoke <key_id>
 *   php Core/Http/Security/bin/keys.php rotate <key_id>
 *
 * GÜVENLİK:
 *   - Anahtar DEĞERİ yalnız `create`/`rotate` çıktısında BİR KEZ gösterilir;
 *     sonra yalnız hash saklanır.
 *   - `list` çıktısında değer YOKTUR (yalnız kimlik + kapsam + durum).
 *   - Komut `external-api.php` dosyasını gerçek PHP kaynağı olarak yeniden
 *     yazar; bu yüzden dosya başında yorum/format korunmaz. Yedek alınmadan
 *     çalıştırmayın: `create`/`revoke`/`rotate` yazmadan önce `.bak` alır.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Bu arac sadece CLI uzerinden calisir.\n");
}

// Autoload yolu: `bin/` -> `Security/` -> `Http/` -> `Core/` -> framework kok.
//
// [ANAYASA / FW-096-D8] Bu dosya ORTAM DEGISKENI OKUMAZ: framework'te ortam
// degiskeni mekanizmasi yoktur; ayar ve sirlarin TEK kaynagi
// `Core/System/Config/Secrets/secrets.php` (okuyucu `Secrets`). Konumlar bu
// yuzden dosya YOLUNDAN turetilir (deterministik).
$frameworkKok = dirname(__DIR__, 4);
$vendorAdaylari = [
    $frameworkKok . '/vendor/autoload.php',
    dirname($frameworkKok) . '/vendor/autoload.php',
];
$autoload = null;
foreach ($vendorAdaylari as $v) {
    if (is_file($v)) {
        $autoload = $v;
        break;
    }
}
if ($autoload === null) {
    fwrite(STDERR, "HATA: composer autoload.php bulunamadi ({$frameworkKok}/vendor).\n");
    exit(2);
}
require_once $autoload;

use Rbn\Framework\Core\Http\Security\MachineApiKeyStore;

$argv = $_SERVER['argv'] ?? [];
array_shift($argv);
$komut = (string) (array_shift($argv) ?? 'help');

/** `--anahtar=deger` / `--anahtar deger` ayristirir. */
function argAyikla(array $argv, string $ad, ?string $varsayilan = null): ?string
{
    foreach ($argv as $i => $a) {
        if ($a === "--{$ad}") {
            return (string) ($argv[$i + 1] ?? '');
        }
        if (str_starts_with((string) $a, "--{$ad}=")) {
            return substr((string) $a, strlen($ad) + 3);
        }
    }
    return $varsayilan;
}

/** Proje anahtarini `--proje` veya mevcut proje anahtarindan alir. */
$proje = argAyikla($argv, 'proje') ?: (function_exists('project_key') ? (string) project_key() : '');
if ($proje === '') {
    fwrite(STDERR, "HATA: proje belirtilmedi. Kullanim: --proje=<proje-anahtari>\n");
    exit(2);
}

// Workspace koku: varsayilan framework kokunun UST dizini (`<workspace>/rbnframework`).
// Test/ozel kurulum icin `--workspace=` bayragiyla verilebilir (ortam degiskeni DEGIL).
$root = rtrim(str_replace('\\', '/', dirname(__DIR__, 4)), '/');
$hedef = argAyikla($argv, 'workspace');
$hedef = $hedef !== null && $hedef !== ''
    ? rtrim(str_replace('\\', '/', $hedef), '/')
    : rtrim(str_replace('\\', '/', dirname($root)), '/');
$dosya = $hedef . '/projects/' . $proje . '/Core/Config/external-api.php';

if (!is_file($dosya)) {
    fwrite(STDERR, "HATA: ayar dosyasi bulunamadi: {$dosya}\n");
    exit(2);
}

/** Ayar dosyasini okur (dizi dondurur). */
$ayarOku = static function (string $dosya): array {
    $cfg = require $dosya;
    return is_array($cfg) ? $cfg : [];
};

/** Ayar dosyasini yazar (var_export + PHP dosya bicimi). */
$ayarYaz = static function (string $dosya, array $cfg): void {
    $php = "<?php\n\ndeclare(strict_types=1);\n\n"
        . "// FW-APIGUARD: `keys` bolumu CLI ile yonetilir (Core/Http/Security/bin/keys.php).\n"
        . "// Anahtar DEGERI burada saklanmaz; yalniz `sha256:<hex>` hash'i tutulur.\n"
        . "// Anahtar degeri yalniz `create`/`rotate` ciktisinda BIR KEZ gosterilir.\n"
        . "return " . var_export($cfg, true) . ";\n";
    // Geri alma yolu: yazmadan once yedek alinir.
    if (!is_file($dosya . '.bak')) {
        @copy($dosya, $dosya . '.bak');
    }
    file_put_contents($dosya, $php);
};

$cfg = $ayarOku($dosya);
$keys = MachineApiKeyStore::normalizeDefinitions($cfg['keys'] ?? null);
$mevcutIdler = array_map(static fn(array $k): string => (string) $k['id'], $keys);

switch ($komut) {
    case 'list':
        echo "Proje: {$proje}\n";
        echo "Dosya: {$dosya}\n";
        echo str_repeat('-', 72) . "\n";
        if ($keys === []) {
            echo "(keys bolumu yok — geri uyum: duz metin `api_key` dali kullanilir)\n";
        } else {
            printf("%-22s %-24s %-18s %s\n", 'KEY_ID', 'SCOPES', 'DURUM', 'RATE');
            foreach ($keys as $k) {
                $durum = MachineApiKeyStore::isRevoked($k) ? 'IPTAL'
                    : (MachineApiKeyStore::isExpired($k) ? 'SURESI DOLMUS' : 'AKTIF');
                printf(
                    "%-22s %-24s %-18s %s\n",
                    (string) $k['id'],
                    implode(',', (array) $k['scopes']) ?: '-',
                    $durum,
                    $k['rate'] ? sprintf('%d/%ds', $k['rate']['max'], $k['rate']['per']) : '-'
                );
            }
        }
        echo str_repeat('-', 72) . "\n";
        echo "NOT: anahtar DEGERI gosterilmez (yalniz hash saklanir).\n";
        break;

    case 'create':
        $scope = MachineApiKeyStore::normalizeScopes(argAyikla($argv, 'scope') ?: []);
        $rateArg = argAyikla($argv, 'rate');
        $rate = null;
        if ($rateArg !== null && $rateArg !== '') {
            $parca = explode('/', $rateArg);
            $rate = ['max' => (int) $parca[0], 'per' => (int) ($parca[1] ?? 60)];
        }
        $bodyMax = argAyikla($argv, 'body-max');
        $ttl = (int) (argAyikla($argv, 'ttl') ?: 0);
        $id = MachineApiKeyStore::generateId(
            $scope[0] ?? 'default',
            $mevcutIdler
        );
        $u = MachineApiKeyStore::generate();
        $keys[] = [
            'id'      => $id,
            'hash'    => $u['hash'],
            'scopes'  => $scope,
            'rate'    => $rate,
            'body_max' => ($bodyMax !== null && (int) $bodyMax > 0) ? (int) $bodyMax : null,
            'expires_at' => $ttl > 0 ? date('c', time() + $ttl) : null,
            'revoked_at' => null,
            'label'   => (string) (argAyikla($argv, 'label') ?? ''),
        ];
        $cfg['keys'] = array_values(array_filter(
            $keys,
            static fn(array $k): bool => !empty($k['id'])
        ));
        $ayarYaz($dosya, $cfg);
        echo "Anahtar olusturuldu: {$id}\n";
        echo "Kapsam : " . (implode(',', $scope) ?: '-') . "\n";
        echo "Sure   : " . ($ttl > 0 ? $ttl . ' sn' : 'sinirsiz') . "\n";
        echo "DEGER  : {$u['plain']}\n";
        echo "!! Bu deger SADECE BIR KEZ gosterilir. Simdi kopyalayin; kaybolursa\n";
        echo "   yeni anahtar olusturmaniz gerekir (hash geri dondurulemez).\n";
        break;

    case 'revoke':
    case 'rotate':
        $hedefId = (string) (array_shift($argv) ?? '');
        if ($hedefId === '') {
            fwrite(STDERR, "HATA: key_id gerekli. Kullanim: {$komut} <key_id>\n");
            exit(2);
        }
        $bulundu = false;
        $yeniDeger = null;
        foreach ($keys as $i => $k) {
            if ((string) $k['id'] !== $hedefId) {
                continue;
            }
            $bulundu = true;
            if ($komut === 'revoke') {
                $keys[$i]['revoked_at'] = date('c');
            } else {
                // rotate: eski anahtar iptal + ayni kapsam/sure ile yeni anahtar.
                $keys[$i]['revoked_at'] = date('c');
                $u = MachineApiKeyStore::generate();
                $yeniDeger = $u['plain'];
                $yeniId = MachineApiKeyStore::generateId((string) ($k['scopes'][0] ?? 'default'), $mevcutIdler);
                $mevcutIdler[] = $yeniId;
                $keys[] = [
                    'id'      => $yeniId,
                    'hash'    => $u['hash'],
                    'scopes'  => (array) $k['scopes'],
                    'rate'    => $k['rate'],
                    'body_max' => $k['body_max'],
                    'expires_at' => $k['expires_at'],
                    'revoked_at' => null,
                    'label'   => (string) $k['label'],
                    'rotated_from' => $hedefId,
                ];
            }
            break;
        }
        if (!$bulundu) {
            fwrite(STDERR, "HATA: anahtar bulunamadi: {$hedefId}\n");
            exit(2);
        }
        $cfg['keys'] = array_values(array_filter($keys, static fn(array $k): bool => !empty($k['id'])));
        $ayarYaz($dosya, $cfg);
        if ($komut === 'revoke') {
            echo "Anahtar iptal edildi: {$hedefId}\n";
            echo "Not: iptal TEK SATIRDIR ve geri alinamaz; yeniden olusturmaniz gerekir.\n";
        } else {
            echo "Eski anahtar iptal edildi: {$hedefId}\n";
            echo "Yeni anahtar kimligi : " . end($keys)['id'] . "\n";
            echo "DEGER  : {$yeniDeger}\n";
            echo "!! Bu deger SADECE BIR KEZ gosterilir.\n";
            echo "   Guvenli gecis: yeni anahtari ajana verin, en az 48 saat sonra\n";
            echo "   eskisinin basarili kullanimini dogrulayin.\n";
        }
        break;

    case 'help':
    default:
        echo "Kullanim:\n";
        echo "  php keys.php list\n";
        echo "  php keys.php create --scope=blog,faq --rate=60/60 --ttl=2592000 [--body-max=65536]\n";
        echo "  php keys.php revoke <key_id>\n";
        echo "  php keys.php rotate <key_id>\n";
        echo "\nOrtak bayraklar: --proje=<proje-anahtari>  (varsayilan: aktif proje)\n";
        echo "               --workspace=<kok>  (varsayilan: framework kokunun ust dizini)\n";
        echo "NOT: bu arac ortam degiskeni OKUMAZ (FW-ENV-KAYIT-160); konumlar dosya\n";
        echo "     yolundan turetilir.\n";
        if ($komut !== 'help') {
            fwrite(STDERR, "HATA: bilinmeyen komut: {$komut}\n");
            exit(2);
        }
        break;
}