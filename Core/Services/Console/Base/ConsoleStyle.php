<?php

namespace Rbn\Framework\Core\Services\Console\Base;

class ConsoleStyle
{
    private const COLORS = [
        'default' => "\033[0m",
        'black'   => "\033[0;30m",
        'red'     => "\033[0;31m",
        'green'   => "\033[0;32m",
        'yellow'  => "\033[0;33m",
        'blue'    => "\033[0;34m",
        'magenta' => "\033[0;35m",
        'cyan'    => "\033[0;36m",
        'white'   => "\033[0;37m",
        'bold'    => "\033[1m",
        'underline' => "\033[4m",
        'bg_red'    => "\033[41m",
        'bg_green'  => "\033[42m",
        'bg_blue'   => "\033[44m"
    ];

    public static function header(string $title): void
    {
        echo "\n " . self::COLORS['yellow'] . self::COLORS['bold'] . " $title " . self::COLORS['default'] . "\n";
        echo " " . str_repeat('=', strlen($title) + 2) . "\n\n";
    }

    public static function info(string $msg): void
    {
        echo " " . self::COLORS['blue'] . "ℹ" . self::COLORS['default'] . " $msg\n";
    }

    public static function success(string $msg): void
    {
        echo " " . self::COLORS['green'] . "✔" . self::COLORS['default'] . " $msg\n";
    }

    public static function error(string $msg): void
    {
        echo " " . self::COLORS['red'] . "✘" . self::COLORS['default'] . " $msg\n";
    }

    public static function warning(string $msg): void
    {
        echo " " . self::COLORS['yellow'] . "⚠" . self::COLORS['default'] . " $msg\n";
    }

    public static function table(array $headers, array $data): void
    {
        if (empty($data)) {
            self::warning("Veri bulunamadı.");
            return;
        }

        $widths = [];
        foreach ($headers as $header) {
            $widths[$header] = strlen($header);
        }

        foreach ($data as $row) {
            foreach ($headers as $header) {
                $val = (string)($row[$header] ?? '');
                $widths[$header] = max($widths[$header], strlen($val));
            }
        }

        $line = "+-" . implode("-+-", array_map(fn($w) => str_repeat('-', $w), $widths)) . "-+";

        echo "\n" . $line . "\n";
        echo "| " . implode(" | ", array_map(fn($h) => self::COLORS['cyan'] . str_pad($h, $widths[$h]) . self::COLORS['default'], $headers)) . " |\n";
        echo $line . "\n";

        foreach ($data as $row) {
            echo "| " . implode(" | ", array_map(fn($h) => str_pad((string)($row[$h] ?? ''), $widths[$h]), $headers)) . " |\n";
        }
        echo $line . "\n\n";
    }

    public static function block(string $msg, string $style = 'info'): void
    {
        $color = match ($style) {
            'success' => self::COLORS['bg_green'],
            'error'   => self::COLORS['bg_red'],
            default   => self::COLORS['bg_blue']
        };

        $content = "  $msg  ";
        $len = strlen($content);
        $pad = str_repeat(' ', $len);

        echo "\n" . $color . self::COLORS['white'] . $pad . self::COLORS['default'] . "\n";
        echo $color . self::COLORS['white'] . $content . self::COLORS['default'] . "\n";
        echo $color . self::COLORS['white'] . $pad . self::COLORS['default'] . "\n\n";
    }
}
