<?php

namespace Rbn\Framework\Core\Support\Bridges\Helpers\Library;


/**
 * ColorHelper - Renk dönüşümü ve palet yönetimi
 */
class ColorHelper
{
    /**
     * HEX kodunu HSL (Hue, Saturation, Lightness) dizisine çevirir.
     */
    public function hexToHsl(string $hex): array
    {
        $hex = str_replace('#', '', $hex);

        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $l = ($max + $min) / 2;

        if ($max == $min) {
            $h = $s = 0;
        } else {
            $d = $max - $min;
            $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);

            switch ($max) {
                case $r:
                    $h = ($g - $b) / $d + ($g < $b ? 6 : 0);
                    break;
                case $g:
                    $h = ($b - $r) / $d + 2;
                    break;
                case $b:
                    $h = ($r - $g) / $d + 4;
                    break;
            }
            $h /= 6;
        }

        return [
            'h' => round($h * 360),
            's' => round($s * 100),
            'l' => round($l * 100)
        ];
    }

    /**
     * Verilen ana (ve opsiyonel ikincil) renkten CSS değişkenleri bloğu üretir.
     */
    public function generateThemeStyles(string $primaryHex, ?string $secondaryHex = null): string
    {
        $p = $this->hexToHsl($primaryHex);

        // Eğer ikincil renk yoksa, ana rengin daha koyu/doygun bir versiyonunu üret
        if (!$secondaryHex) {
            $s = [
                'h' => $p['h'],
                's' => min(100, $p['s'] + 10),
                'l' => max(0, $p['l'] - 15)
            ];
        } else {
            $s = $this->hexToHsl($secondaryHex);
        }

        $style = "\n    <!-- RBN THEME ENGINE: ACTIVE -->\n";
        $style .= "    <style>\n";
        $style .= "        :root, [data-theme], body {\n";
        $style .= "            --rbn-theme-h: {$p['h']};\n";
        $style .= "            --rbn-theme-s: {$p['s']}%;\n";
        $style .= "            --rbn-theme-l: {$p['l']}%;\n";
        $style .= "            --theme-h: {$p['h']};\n";
        $style .= "            --theme-s: {$p['s']}%;\n";
        $style .= "            --theme-l: {$p['l']}%;\n";
        $style .= "            --rbn-primary: {$primaryHex};\n";
        $style .= "            --theme-600: {$primaryHex};\n";
        $style .= "            --theme-500: {$primaryHex};\n";
        $style .= "            --theme-400: {$primaryHex};\n";
        $style .= "            --theme-rgb: " . $this->hexToRgbString($primaryHex) . ";\n";
        $style .= "            --theme-gradient: linear-gradient(135deg, {$primaryHex}, " . $this->hslToHex($s['h'], $s['s'], $s['l']) . ");\n";
        $style .= "        }\n";
        $style .= "    </style>\n";
        $style .= "    <!-- RBN THEME ENGINE: END -->\n";

        return $style;
    }

    /**
     * HEX to RGB String (255, 255, 255 format)
     */
    private function hexToRgbString(string $hex): string
    {
        $hex = str_replace('#', '', $hex);
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        return "{$r}, {$g}, {$b}";
    }

    /**
     * HSL values to HEX (Helper for gradient generation)
     */
    public function hslToHex($h, $s, $l): string
    {
        $h /= 360;
        $s /= 100;
        $l /= 100;

        if ($s == 0) {
            $r = $g = $b = $l;
        } else {
            $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
            $p = 2 * $l - $q;
            $r = $this->hue2rgb($p, $q, $h + 1 / 3);
            $g = $this->hue2rgb($p, $q, $h);
            $b = $this->hue2rgb($p, $q, $h - 1 / 3);
        }

        return sprintf("#%02x%02x%02x", round($r * 255), round($g * 255), round($b * 255));
    }

    private function hue2rgb($p, $q, $t)
    {
        if ($t < 0)
            $t += 1;
        if ($t > 1)
            $t -= 1;
        if ($t < 1 / 6)
            return $p + ($q - $p) * 6 * $t;
        if ($t < 1 / 2)
            return $q;
        if ($t < 2 / 3)
            return $p + ($q - $p) * (2 / 3 - $t) * 6;
        return $p;
    }
}
