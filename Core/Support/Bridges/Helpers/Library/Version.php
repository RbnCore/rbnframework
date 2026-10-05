<?php
declare(strict_types=1);

namespace Rbn\Framework\Core\Support\Bridges\Helpers\Library;

/**
 * Version - Standart sürüm biçimi ve sayaç kuralı (A.B.C).
 *
 * Kural: `.agents/rules/versioning.md` (patron kararı 2026-10-05)
 *   - Biçim: `A.B.C` (üç sayı, noktayla ayrılmış). Başka biçim GEÇERSİZ.
 *   - A: `0` … sınırsız (`9`'dan sonra `10`, `11`…)
 *   - B ve C: `0` … `9` (asla `10` olmaz)
 *   - Başlangıç sürümü: `0.1.1`
 *   - Sonraki sürüm TEK sayaç kuralı:
 *       1. C < 9 ise            → C + 1            (0.1.1 → 0.1.2)
 *       2. C = 9 ve B < 9 ise   → C = 0, B + 1     (0.7.9 → 0.8.0)
 *       3. C = 9 ve B = 9 ise   → C = 0, B = 0, A + 1 (0.9.9 → 1.0.0, 9.9.9 → 10.0.0)
 *   - Geçerli desen: `^(0|[1-9][0-9]*)\.[0-9]\.[0-9]$`
 *   - Karşılaştırma: sayısal, soldan sağa (A, sonra B, sonra C).
 *
 * Sınıf durumsuzdur (statik): hiçbir özellik tutmaz, `new` gerektirmez.
 * Sürüm numarası elle artırılmaz; her yayın `next()` sonucunu alır
 * (CHANGELOG başlığı ve etiket aynı değeri taşır).
 */
class Version
{
    /** Geçerli sürüm biçiminin tek tanımı (versioning.md §3). */
    public const PATTERN = '/^(0|[1-9][0-9]*)\.[0-9]\.[0-9]$/';

    /** Yeni uygulamaların başlangıç sürümü (versioning.md §1). */
    public const INITIAL = '0.1.1';

    /**
     * Sürüm biçimi geçerli mi?
     * `0.10.0`, `1.2.10`, `1.2.3.4`, `v1.2.3`, `01.2.3`, `1.2` → false
     */
    public static function isValid(string $version): bool
    {
        return (bool) preg_match(self::PATTERN, $version);
    }

    /**
     * Sürümü `{major, minor, patch}` dizisine ayırır.
     *
     * @return array{major:int,minor:int,patch:int}
     * @throws \InvalidArgumentException Geçersiz sürüm biçiminde.
     */
    public static function parse(string $version): array
    {
        self::assertValid($version);

        [$major, $minor, $patch] = explode('.', $version);

        return [
            'major' => (int) $major,
            'minor' => (int) $minor,
            'patch' => (int) $patch,
        ];
    }

    /**
     * Sayılan sonraki sürüm (tek sayaç kuralı).
     *
     * 0.1.1 → 0.1.2 · 0.7.9 → 0.8.0 · 0.9.9 → 1.0.0 · 9.9.9 → 10.0.0
     *
     * @throws \InvalidArgumentException Geçersiz sürüm biçiminde.
     */
    public static function next(string $version): string
    {
        $v = self::parse($version);

        if ($v['patch'] < 9) {
            $v['patch']++;
        } elseif ($v['minor'] < 9) {
            $v['minor']++;
            $v['patch'] = 0;
        } else {
            $v['major']++;
            $v['minor'] = 0;
            $v['patch'] = 0;
        }

        // Kural kendi içinde tutarlıdır; yine de çıktı doğrulanır (savunma).
        $sonuc = $v['major'] . '.' . $v['minor'] . '.' . $v['patch'];
        self::assertValid($sonuc);

        return $sonuc;
    }

    /**
     * İki sürümü sayısal karşılaştırır (soldan sağa: A, sonra B, sonra C).
     * `0.9.9 < 1.0.0 < 10.0.0`
     *
     * @return int -1, 0 veya 1
     * @throws \InvalidArgumentException Geçersiz sürüm biçiminde.
     */
    public static function compare(string $a, string $b): int
    {
        $x = self::parse($a);
        $y = self::parse($b);

        foreach (['major', 'minor', 'patch'] as $alan) {
            if ($x[$alan] !== $y[$alan]) {
                return $x[$alan] < $y[$alan] ? -1 : 1;
            }
        }

        return 0;
    }

    /** Yeni uygulamaların başlangıç sürümü: `0.1.1`. */
    public static function initial(): string
    {
        return self::INITIAL;
    }

    /** @throws \InvalidArgumentException */
    private static function assertValid(string $version): void
    {
        if (!self::isValid($version)) {
            throw new \InvalidArgumentException(
                'Geçersiz sürüm biçimi: "' . $version . '" (beklenen: ' . self::PATTERN . ')'
            );
        }
    }
}