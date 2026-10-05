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
 * Surum ELLE yazilmaz; sayaci `Version::next()` hesaplar.
 */
class VersionHandlers extends BaseCommand
{
    public function getCommands(): array
    {
        return [
            'version:check' => ['desc' => 'Sürüm tutarlılığını denetler (SALT-OKUNUR, çıkış kodu döner)', 'method' => 'versionCheck'],
            'version:next'  => ['desc' => 'Proje sürümünü Version::next() ile artırır (varsayılan: kuru koşu; --apply yazar)', 'method' => 'versionNext'],
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

        /* --- 3. CITATION.cff (kopya; kaynaktan OKUNMALI) --- */
        $citation = $this->citationSurumu();
        $satirlar[] = $this->satir('copy', 'CITATION.cff', $citation, $citation === $fw);
        $sapma += ($citation === $fw) ? 0 : 1;

        /* --- 4. CHANGELOG "Son surum" basligi --- */
        $changelog = $this->changelogSurumu();
        $satirlar[] = $this->satir('copy', 'CHANGELOG.md (Son sürüm)', $changelog, $changelog === $fw);
        $sapma += ($changelog === $fw) ? 0 : 1;

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

    private function citationSurumu(): string
    {
        return $this::ciktiAl('/CITATION.cff', '/^version:\s*(\S+)/m');
    }

    private function changelogSurumu(): string
    {
        return $this::ciktiAl('/.github/CHANGELOG.md', '/\*\*Son sürüm:\*\*\s*`([^`]+)`/u');
    }

    /** Klasorde tek bir satiri regex ile okur (dosya yoksa boş string). */
    private static function ciktiAl(string $goreceKalanYol, string $desen): string
    {
        try {
            $kok = \Rbn\Framework\Core\System\Paths\Paths::frameworkRoot();
            $dosya = $kok . $goreceKalanYol;
            if (!is_file($dosya)) {
                return '(dosya yok)';
            }
            $ham = (string) file_get_contents($dosya);
            if (preg_match($desen, $ham, $m) === 1) {
                return (string) $m[1];
            }

            return '(bulunamadı)';
        } catch (\Throwable) {
            return '(okunamadı)';
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
