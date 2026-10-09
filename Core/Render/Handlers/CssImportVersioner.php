<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Render\Handlers;

/**
 * CssImportVersioner — CSS `@import` zincirini motorun sürümleme biçimiyle işaretler 🔗
 *
 * KÖK NEDEN (FW-H51): varlık proxy'si CSS'i `max-age=31536000` ile sunar; ana dosya
 * URL'i `=v<mtime>` ile değişir ama içindeki `@import url('x.css')` satırları sürümsüz
 * kalır. Tarayıcı import edilen dosyayı 1 yıl saklar → yeni tasarım görünmez.
 *
 * ÇÖZÜM (tek nokta): iki işi aynı sınıf yapar, böylece biri ötekinden sapmaz:
 *  - `rewrite()`   : sunulan CSS'teki göreli `@import` yollarına `=v<mtime>` ekler.
 *  - `chainMtime()`: dosyanın VE tüm import zincirinin en yeni mtime'ı. Ana dosyanın
 *                    URL sürümü bundan üretilir; alt dosya değişince ana URL de değişir.
 *
 * Dış URL'lere (`http:`, `//`, `data:`) ve zaten `=v` taşıyanlara DOKUNULMAZ.
 * Döngüsel import güvenli (ziyaret kümesi + derinlik sınırı).
 */
final class CssImportVersioner
{
    private const MAX_DEPTH = 8;

    /** `@import url('x.css')`, `@import url(x.css)`, `@import "x.css"` */
    private const PATTERN = '/@import\s+(?:url\(\s*([\'"]?)([^\'")\s]+)\1\s*\)|([\'"])([^\'"]+)\3)/i';

    /** Dosyanın ve import zincirinin en yeni değişiklik zamanı. */
    public static function chainMtime(string $physicalPath, int $depth = 0, array &$seen = []): int
    {
        $real = realpath($physicalPath);
        if ($real === false || isset($seen[$real])) {
            return 0;
        }
        $seen[$real] = true;

        $max = (int) filemtime($real);
        if ($depth >= self::MAX_DEPTH || strtolower(pathinfo($real, PATHINFO_EXTENSION)) !== 'css') {
            return $max;
        }

        $css = @file_get_contents($real);
        if ($css === false || preg_match_all(self::PATTERN, $css, $m) < 1) {
            return $max;
        }

        foreach (self::targets($m) as $rel) {
            $child = self::locate($real, $rel);
            if ($child !== null) {
                $max = max($max, self::chainMtime($child, $depth + 1, $seen));
            }
        }

        return $max;
    }

    /** Sunulacak CSS içeriğindeki göreli import yollarını sürümler. */
    public static function rewrite(string $css, string $physicalPath): string
    {
        $out = preg_replace_callback(self::PATTERN, static function (array $m) use ($physicalPath): string {
            $rel = ($m[2] ?? '') !== '' ? $m[2] : ($m[4] ?? '');
            $child = self::isLocal($rel) ? self::locate($physicalPath, $rel) : null;
            if ($child === null) {
                return $m[0];
            }
            $v = self::chainMtime($child);
            // Sürüm, sorgu/hash'ten ÖNCE yola eklenir: `x.css=v123?a=b` değil `x.css=v123`.
            return str_replace($rel, self::withVersion($rel, $v), $m[0]);
        }, $css);

        return $out ?? $css;
    }

    private static function targets(array $m): array
    {
        $list = [];
        foreach ($m[2] as $i => $a) {
            $rel = $a !== '' ? $a : ($m[4][$i] ?? '');
            if ($rel !== '' && self::isLocal($rel)) {
                $list[] = $rel;
            }
        }
        return $list;
    }

    private static function isLocal(string $rel): bool
    {
        return $rel !== ''
            && !preg_match('#^(?:[a-z][a-z0-9+.-]*:|//)#i', $rel)
            && !str_contains($rel, '=v');
    }

    private static function withVersion(string $rel, int $v): string
    {
        $suffix = '';
        if (preg_match('/^([^?#]*)([?#].*)$/', $rel, $p)) {
            [$rel, $suffix] = [$p[1], $p[2]];
        }
        return $rel . '=v' . $v . $suffix;
    }

    private static function locate(string $fromFile, string $rel): ?string
    {
        $rel = preg_replace('/[?#].*$/', '', $rel) ?? $rel;
        $path = dirname($fromFile) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
        $real = realpath($path);
        // Kök dışına kaçış: yalnız .css ve var olan dosya.
        if ($real === false || !is_file($real) || strtolower(pathinfo($real, PATHINFO_EXTENSION)) !== 'css') {
            return null;
        }
        return $real;
    }
}
