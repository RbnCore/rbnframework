<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Services\Console\Handlers;

use Rbn\Framework\Core\Base\Services\BaseService;
use Rbn\Framework\Core\Services\Console\Base\BaseCommand;
use Rbn\Framework\Core\Services\Console\Base\ConsoleStyle;
use Rbn\Framework\Core\Support\Bridges\Helpers\Library\Version;
use Rbn\Framework\Core\Support\Definitions\System\FrameworkIdentity;

/**
 * VersionHandlers - Surum denetim komutlari 🔢
 *
 * [FW-SURUMLEME-2] Kurallar `.agents/rules/versioning.md` icinde; bu iki
 * komut o kuralin **yazilabilir** yuzudur:
 *
 *   - `rbn version:check`  -> SALT-OKUNUR denetim. Framework surumu ile
 *     CHANGELOG/CITATION/etiket ve master DB'deki proje/uygulama surumlerinin
 *     gecerliligini tablo olarak yazar. Hicbir dosya/veritabani YAZMAZ.
 *     Cikis kodu: `0` = temiz, `1` = sapma var.
 *   - `rbn version:next <project_key>` -> proje surumunu `Version::next()`
 *     ile hesaplar. VARSAYILAN KURU KOSUDUR; yalniz `--apply` ile yazar
 *     (yazma yalniz repository uzerinden olur, Anayasa §8).
 *
 *   - `rbn version:framework [--to=A.B.C] [--apply]` -> FRAMEWORK surumunu
 *     TEK komutta yukseltir. Kaynak `FrameworkIdentity::FRAMEWORK_VERSION`;
 *     kopyalarin TAM listesi `frameworkKopyalari()` icindedir (CITATION,
 *     composer.json (+ composer.lock content-hash), CHANGELOG "Son sürüm" +
 *     `## [A.B.C]` basligi,
 *     docs "Yayın" damgalari). `version:check` ayni listeyi denetler.
 *
 * Surum ELLE yazilmaz; sayaci `Version::next()` hesaplar.
 */
class VersionHandlers extends BaseCommand
{
    public function getCommands(): array
    {
        return [
            'version:check' => ['desc' => 'Sürüm tutarlılığını denetler (SALT-OKUNUR, çıkış kodu döner)', 'method' => 'versionCheck'],
            'version:next'  => ['desc' => 'Proje sürümünü Version::next() ile artırır (varsayılan: kuru koşu; --apply yazar)', 'method' => 'versionNext'],
            'version:framework' => ['desc' => 'Framework sürümünü TEK komutta yükseltir: FrameworkIdentity + CITATION + composer(.lock) + CHANGELOG + docs (kuru koşu; --apply yazar; --to=A.B.C)', 'method' => 'versionFramework'],
        ];
    }

    /* ==================================================================
       SALT-OKUNUR DENETIM
       ================================================================== */

    public function versionCheck(array $params): void
    {
        $sapma = 0;
        $satirlar = [];

        /* --- 1. Framework surumu (kaynak: FrameworkIdentity) --- */
        $fw = (string) FrameworkIdentity::FRAMEWORK_VERSION;
        $satirlar[] = $this->satir('framework', 'FrameworkIdentity::FRAMEWORK_VERSION', $fw, Version::isValid($fw));
        $sapma += $this->say($fw, Version::isValid($fw));

        /* --- 2. Bilesen surumleri --- */
        foreach ([
            'FRAMEWORK_CLI_VERSION' => FrameworkIdentity::FRAMEWORK_CLI_VERSION,
            'SHIELD_VERSION'        => FrameworkIdentity::SHIELD_VERSION,
            'ADMIN_VERSION'         => FrameworkIdentity::ADMIN_VERSION,
            'AUTH_VERSION'          => FrameworkIdentity::AUTH_VERSION,
        ] as $sabit => $deger) {
            $satirlar[] = $this->satir('component', $sabit, (string) $deger, Version::isValid((string) $deger));
            $sapma += $this->say((string) $deger, Version::isValid((string) $deger));
        }

        /* --- 3-4. Kopyalar (TEK liste: frameworkKopyalari) --- */
        foreach ($this->kopyaDurumu($fw) as $k) {
            $satirlar[] = $this->satir('copy', $k['ad'], $k['deger'], $k['tamam']);
            $sapma += $k['tamam'] ? 0 : 1;
        }

        /* --- 5. Master DB: proje ve uygulama surumleri --- */
        foreach ($this->masterSurumleri() as $r) {
            $satirlar[] = $r;
            $sapma += $r['valid'] === true ? 0 : 1;
        }

        ConsoleStyle::header('🔢 Sürüm Denetimi (version:check)');
        ConsoleStyle::table(
            ['kaynak', 'alan', 'deger', 'sonuc'],
            array_map(static fn(array $r): array => [
                'kaynak' => $r['kaynak'],
                'alan'   => $r['alan'],
                'deger'  => $r['deger'],
                'sonuc'  => $r['valid'] === true ? 'OK' : 'SAPMA',
            ], $satirlar)
        );

        if ($sapma === 0) {
            $this->success('Tüm sürümler tutarlı ve geçerli (A.B.C).');
        } else {
            $this->warning(sprintf(
                '%d sapma bulundu. Sayıyı elle yazmak yerine `Version::next()` kullanın; '
                . 'proje için: `rbn version:next <project_key> --apply`.',
                $sapma
            ));
        }

        exit($sapma === 0 ? 0 : 1);
    }

    /* ==================================================================
       SAYAÇ (varsayilan kuru koşu)
       ================================================================== */

    public function versionNext(array $params): void
    {
        $options = $this->parseOptions($params);
        $apply = isset($options['apply']);

        $projectKey = '';
        foreach ($params as $p) {
            if (is_string($p) && !str_starts_with($p, '-')) {
                $projectKey = trim($p);
                break;
            }
        }
        $projectKey = $projectKey !== '' ? $projectKey : (string) ($options['project'] ?? '');

        if ($projectKey === '') {
            $this->error('Proje anahtarı zorunludur. Kullanım: rbn version:next <project_key> [--apply]');
            exit(1);
        }

        $service = BaseService::get()->service('masterProjects');
        if (!$service || !method_exists($service, 'nextVersion')) {
            $this->error('Master projeler servisi bulunamadı.');
            exit(1);
        }

        $sonuc = $service->nextVersion($projectKey, $apply);

        ConsoleStyle::header('🔢 Proje Sürüm Sayacı (version:next)');
        if (($sonuc['errors'] ?? []) !== []) {
            foreach ($sonuc['errors'] as $hata) {
                $this->error((string) $hata);
            }
            exit(1);
        }

        $tablo = [[
            'project_key' => (string) $sonuc['project_key'],
            'mevcut'     => (string) $sonuc['current'],
            'sonraki'    => (string) $sonuc['next'],
            'yazildi'    => $sonuc['written'] === true ? 'evet' : 'hayir (kuru koşu)',
        ]];
        ConsoleStyle::table(['project_key', 'mevcut', 'sonraki', 'yazildi'], $tablo);

        if ($sonuc['written'] === true) {
            $this->success('Sürüm yazıldı (keşif önbelleği temizlendi).');
        } else {
            $this->info('Kuru koşu: hiçbir şey yazılmadı. Yazmak için `--apply` ekleyin.');
        }

        exit(0);
    }

    /* ==================================================================
       FRAMEWORK SURUMU (tek komut; varsayilan kuru koşu)
       ================================================================== */

    public function versionFramework(array $params): void
    {
        $options = $this->parseOptions($params);
        $apply = isset($options['apply']);
        $mevcut = (string) FrameworkIdentity::FRAMEWORK_VERSION;
        $hedef = trim((string) ($options['to'] ?? ''));
        $hedef = $hedef !== '' ? $hedef : Version::next($mevcut);

        ConsoleStyle::header('🔢 Framework Sürümü (version:framework)');
        if (!Version::isValid($hedef) || Version::compare($hedef, $mevcut) <= 0) {
            $this->error(sprintf('Geçersiz hedef "%s": A.B.C olmalı ve %s sürümünden büyük olmalı.', $hedef, $mevcut));
            exit(1);
        }

        $bugun = date('Y-m-d');
        $tablo = [];
        $yazilacak = [];
        foreach ($this->frameworkKopyalari() as $kopya) {
            foreach ($this->kopyaDosyalari($kopya) as $dosya) {
                // Ayni dosyada birden cok kopya olabilir (CHANGELOG, docs): kurallar zincirlenir.
                $ham = $yazilacak[$dosya] ?? (string) file_get_contents($dosya);
                $yeni = $this->kopyaYaz($kopya, $ham, $mevcut, $hedef, $bugun);
                $degisti = $yeni !== $ham;
                $tablo[] = [
                    'dosya'   => $this->goreceYol($dosya),
                    'mevcut'  => $mevcut,
                    'sonraki' => $hedef,
                    'durum'   => $degisti ? ($apply ? 'yazildi' : 'yazilacak') : 'degisiklik yok',
                ];
                if ($degisti) {
                    $yazilacak[$dosya] = $yeni;
                }
            }
        }

        // composer.json `version` composer.lock content-hash'ine girer: kilit AYNI komutta yenilenir.
        $kok = \Rbn\Framework\Core\System\Paths\Paths::frameworkRoot();
        $composerDosya = $kok . '/composer.json';
        $kilitDosya = $kok . '/composer.lock';
        if (isset($yazilacak[$composerDosya]) && is_file($kilitDosya)) {
            $kilit = (string) file_get_contents($kilitDosya);
            $yeniKilit = (string) preg_replace(
                '/"content-hash":\s*"[0-9a-f]{32}"/',
                '"content-hash": "' . self::composerIcerikHash($yazilacak[$composerDosya]) . '"',
                $kilit,
                1
            );
            if ($yeniKilit !== $kilit) {
                $yazilacak[$kilitDosya] = $yeniKilit;
                $tablo[] = ['dosya' => 'composer.lock (content-hash)', 'mevcut' => $mevcut, 'sonraki' => $hedef, 'durum' => $apply ? 'yazildi' : 'yazilacak'];
            }
        }
        ConsoleStyle::table(['dosya', 'mevcut', 'sonraki', 'durum'], $tablo);

        if (!$apply) {
            $this->info(sprintf('Kuru koşu: %d dosya yazılacak. Yazmak için `--apply` ekleyin.', count($yazilacak)));
            exit(0);
        }
        foreach ($yazilacak as $dosya => $icerik) {
            file_put_contents($dosya, $icerik);
        }

        // Yazilan kopyalari diskteki YENI kaynakla yeniden denetle (sabit bu surecte eski kalir).
        $sapma = 0;
        foreach ($this->kopyaDurumu($hedef) as $k) {
            $sapma += $k['tamam'] ? 0 : 1;
            if (!$k['tamam']) {
                $this->warning(sprintf('Sapma: %s = %s', $k['ad'], $k['deger']));
            }
        }
        if ($sapma > 0) {
            exit(1);
        }
        $this->success(sprintf('Framework %s -> %s: %d dosya yazıldı. CHANGELOG [%s] bölümünü doldurmayı unutmayın.', $mevcut, $hedef, count($yazilacak), $hedef));
        exit(0);
    }

    /**
     * Framework surumunun TUM kopyalari — TEK liste. Yeni bir kopya eklenirse
     * buraya yazilir; hem `version:framework` (yazma) hem `version:check`
     * (denetim) bu listeyi kullanir. Elle guncellenen yer kalmaz.
     *
     * @return list<array{ad:string, yol:string, desen:string, zorunlu:bool, tur?:string}>
     */
    private function frameworkKopyalari(): array
    {
        return [
            ['ad' => 'FrameworkIdentity::FRAMEWORK_VERSION', 'yol' => 'Core/Support/Definitions/System/FrameworkIdentity.php',
             'desen' => "/FRAMEWORK_VERSION = '(?<v>[^']+)'/", 'zorunlu' => true],
            ['ad' => 'CITATION.cff version', 'yol' => 'CITATION.cff', 'desen' => '/^version:\s*(?<v>\S+)/m', 'zorunlu' => true, 'tur' => 'citation'],
            // `version` composer.lock content-hash'ine girer; version:framework kilidi de yeniler.
            ['ad' => 'composer.json version', 'yol' => 'composer.json', 'desen' => '/"version"\s*:\s*"(?<v>[^"]+)"/', 'zorunlu' => true],
            ['ad' => 'CHANGELOG.md Son sürüm', 'yol' => '.github/CHANGELOG.md', 'desen' => '/\*\*Son sürüm:\*\*\s*`(?<v>[^`]+)`/u', 'zorunlu' => true, 'tur' => 'son-surum'],
            ['ad' => 'CHANGELOG.md ## [A.B.C]', 'yol' => '.github/CHANGELOG.md', 'desen' => '/^## \[(?<v>\d+\.\d+\.\d+)\]/m', 'zorunlu' => true, 'tur' => 'baslik'],
            ['ad' => 'docs **Yayın:** damgaları', 'yol' => 'docs/**.md', 'desen' => '/\*\*Yayın:\*\*\s*(?<v>\d+\.\d+\.\d+)/u', 'zorunlu' => false],
            ['ad' => 'docs Yayın tabanı damgaları', 'yol' => 'docs/**.md', 'desen' => '/Yayın tabanı:\*\*\s*(?<v>\d+\.\d+\.\d+)/u', 'zorunlu' => false],
        ];
    }

    /** @return list<string> mutlak dosya yollari */
    private function kopyaDosyalari(array $kopya): array
    {
        $kok = \Rbn\Framework\Core\System\Paths\Paths::frameworkRoot();
        if (!str_ends_with($kopya['yol'], '/**.md')) {
            $dosya = $kok . '/' . $kopya['yol'];
            return is_file($dosya) ? [$dosya] : [];
        }
        $dizin = $kok . '/' . substr($kopya['yol'], 0, -strlen('/**.md'));
        if (!is_dir($dizin)) {
            return [];
        }
        $liste = [];
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dizin, \FilesystemIterator::SKIP_DOTS));
        foreach ($it as $f) {
            if ($f->isFile() && strtolower($f->getExtension()) === 'md'
                && preg_match($kopya['desen'], (string) file_get_contents($f->getPathname())) === 1) {
                $liste[] = $f->getPathname();
            }
        }
        sort($liste);

        return $liste;
    }

    private function kopyaYaz(array $kopya, string $ham, string $mevcut, string $hedef, string $bugun): string
    {
        $tur = $kopya['tur'] ?? '';
        if ($tur === 'baslik') {
            // Eski baslik TARIHCEDIR, silinmez; yeni surum basligi en uste eklenir.
            if (preg_match('/^## \[' . preg_quote($hedef, '/') . '\]/m', $ham) === 1) {
                return $ham;
            }
            return (string) preg_replace('/^## \[/m', '## [' . $hedef . '] - ' . $bugun . "\n\n## [", $ham, 1);
        }
        if ($tur === 'son-surum') {
            // Eski ozet yeni surume ait degildir; satir surum + tarih olarak yeniden yazilir.
            return (string) preg_replace('/^\*\*Son sürüm:\*\*.*$/mu', '**Son sürüm:** `' . $hedef . '` (' . $bugun . ')', $ham, 1);
        }
        $yeni = (string) preg_replace_callback($kopya['desen'], static function (array $m) use ($hedef): string {
            return str_replace($m['v'], $hedef, $m[0]);
        }, $ham);
        if ($tur === 'citation') {
            $yeni = (string) preg_replace('/^date-released:\s*"[^"]*"/m', 'date-released: "' . $bugun . '"', $yeni);
        }

        return $yeni;
    }

    /** @return list<array{ad:string, deger:string, tamam:bool}> */
    private function kopyaDurumu(string $beklenen): array
    {
        $sonuc = [];
        foreach ($this->frameworkKopyalari() as $kopya) {
            $dosyalar = $this->kopyaDosyalari($kopya);
            if ($dosyalar === []) {
                $sonuc[] = ['ad' => $kopya['ad'], 'deger' => '(yok)', 'tamam' => !$kopya['zorunlu']];
                continue;
            }
            $farkli = [];
            $bulunan = 0;
            foreach ($dosyalar as $dosya) {
                preg_match_all($kopya['desen'], (string) file_get_contents($dosya), $m);
                $degerler = ($kopya['tur'] ?? '') === 'baslik' ? array_slice($m['v'], 0, 1) : $m['v'];
                $bulunan += count($degerler);
                if ($degerler === [] && $kopya['zorunlu']) {
                    $farkli[] = $this->goreceYol($dosya) . '=(bulunamadı)';
                }
                foreach ($degerler as $v) {
                    if ($v !== $beklenen) {
                        $farkli[] = $this->goreceYol($dosya) . '=' . $v;
                    }
                }
            }
            $sonuc[] = [
                'ad'    => $kopya['ad'] . (count($dosyalar) > 1 ? ' (' . count($dosyalar) . ' dosya)' : ''),
                'deger' => $farkli !== [] ? implode(', ', array_slice($farkli, 0, 3))
                    : ($bulunan === 0 ? '(alan yok)' : $beklenen),
                'tamam' => $farkli === [],
            ];
        }
        $sonuc[] = $this->kilitDurumu();

        return $sonuc;
    }

    /**
     * Composer'in `Locker::getContentHash()` algoritmasinin aynisi: ilgili
     * anahtarlar + config.platform, ksort, json_encode(0), md5.
     */
    private static function composerIcerikHash(string $composerJson): string
    {
        $icerik = json_decode($composerJson, true);
        if (!is_array($icerik)) {
            return '';
        }
        $ilgili = [];
        foreach (array_intersect(
            ['name', 'version', 'require', 'require-dev', 'conflict', 'replace', 'provide',
             'minimum-stability', 'prefer-stable', 'repositories', 'extra'],
            array_keys($icerik)
        ) as $anahtar) {
            $ilgili[$anahtar] = $icerik[$anahtar];
        }
        if (isset($icerik['config']['platform'])) {
            $ilgili['config']['platform'] = $icerik['config']['platform'];
        }
        ksort($ilgili);

        return md5((string) json_encode($ilgili, 0));
    }

    /** composer.lock content-hash composer.json ile uyumlu mu? */
    private function kilitDurumu(): array
    {
        $kok = \Rbn\Framework\Core\System\Paths\Paths::frameworkRoot();
        $kilit = is_file($kok . '/composer.lock') ? (string) file_get_contents($kok . '/composer.lock') : '';
        preg_match('/"content-hash":\s*"([0-9a-f]{32})"/', $kilit, $m);
        $beklenen = self::composerIcerikHash((string) @file_get_contents($kok . '/composer.json'));
        $var = $m[1] ?? '(yok)';

        return ['ad' => 'composer.lock content-hash', 'deger' => $var, 'tamam' => $var === $beklenen];
    }

    private function goreceYol(string $dosya): string
    {
        $kok = rtrim(str_replace('\\', '/', \Rbn\Framework\Core\System\Paths\Paths::frameworkRoot()), '/') . '/';

        return str_replace($kok, '', str_replace('\\', '/', $dosya));
    }

    /* ==================================================================
       YARDIMCILAR
       ================================================================== */

    /** @return array{valid:bool, deger:string} */
    private function masterSurumleri(): array
    {
        $satirlar = [];

        try {
            $db = \Rbn\Framework\Core\Database\Database::getInstance()->connection('database_master');

            $projeler = $db->raw('SELECT project_key, version FROM projects ORDER BY id');
            foreach ($projeler as $p) {
                $v = trim((string) ($p['version'] ?? ''));
                $satirlar[] = $this->satir('db:projects', (string) $p['project_key'], $v, Version::isValid($v));
            }

            if ($this->tabloVarMi($db, 'applications')) {
                $uygulamalar = $db->raw('SELECT app_key, current_version, min_version FROM applications ORDER BY id');
                foreach ($uygulamalar as $a) {
                    $v = trim((string) ($a['current_version'] ?? ''));
                    $satirlar[] = $this->satir(
                        'db:applications',
                        (string) $a['app_key'],
                        $v,
                        $v === '' || Version::isValid($v)
                    );
                }
            }
        } catch (\Throwable $e) {
            $satirlar[] = [
                'kaynak' => 'db:master',
                'alan'   => 'baglanti',
                'deger'  => 'HATA: ' . $e->getMessage(),
                'valid'  => false,
            ];
        }

        return $satirlar;
    }

    private function tabloVarMi(object $db, string $tablo): bool
    {
        try {
            $satir = $db->rawFirst(
                'SELECT COUNT(*) AS adet FROM information_schema.tables
                 WHERE table_schema = DATABASE() AND table_name = ?',
                [$tablo]
            );

            return ((int) ($satir['adet'] ?? 0)) > 0;
        } catch (\Throwable) {
            return false;
        }
    }

    /** @return array{kaynak:string, alan:string, deger:string, valid:bool} */
    private function satir(string $kaynak, string $alan, string $deger, bool $valid): array
    {
        return ['kaynak' => $kaynak, 'alan' => $alan, 'deger' => $deger, 'valid' => $valid];
    }

    /** Gecersiz deger icin sifir disi doner (sayac icin). */
    private function say(string $deger, bool $valid): int
    {
        if ($valid) {
            return 0;
        }
        $this->warning(sprintf('Geçersiz sürüm: "%s" — bu değeri `Version::next()` ile mi çıkardın?', $deger));

        return 1;
    }
}
