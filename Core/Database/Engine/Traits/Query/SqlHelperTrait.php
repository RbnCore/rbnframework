<?php

namespace Rbn\Framework\Core\Database\Engine\Traits\Query;

/**
 * SqlHelperTrait - The SQL Construction Toolbox 🧰🛰️⚓
 * 
 * Provides helper methods for formatting and wrapping SQL identifiers.
 * This is the "Swiss Army Knife" for advanced SQL construction. 🎻✨
 */
trait SqlHelperTrait
{
    /**
     * Expert: Wrap column/table names safely in backticks 🏎️
     *
     * [D-06] BACKTICK KACISI: daha once parca dogrudan `` `{$p}` `` ile
     * sariliyordu; kullanici kontrollu bir sutun adindaki backtick tirnagi
     * kapatip SQL'i kirabiliyordu (``orderBy('id`) -- ')`` -> ``ORDER BY `id`) -- ` ASC``).
     *
     * NEDEN "RED" DEGIL DE "KACIS"? Backtick ile sarilmis bir tanimlayici
     * MySQL'de ICERDEKII tum karakterleri (noktali virgul, `--`, yorum, bosluk)
     * veri olarak ele alir; tek yapilacak backtick'leri `str_replace('`','``',...)`
     * ile ikilemektir. Bu yuzden supheli karakter iceren adi reddetmeye
     * GEREK YOKTUR ve meşru caglilar hicbir kirilma yasamaz (geri uyumluluk).
     * Noktali `tablo.sutun` bicimi parca parca sarilir, boyle korunur.
     *
     * Ham ifade gerekiyorsa `orderByRaw()` / `whereRaw()` vardir (kasitli ham yol).
     */
    protected function wrapColumn(string $column): string
    {
        if ($column === '*' || strpos($column, '(') !== false || $this->isAliased($column)) {
            return $column;
        }
 
        $parts = explode('.', $column);
        return implode('.', array_map(
            fn($p) => $p === '*' ? '*' : '`' . str_replace('`', '``', $p) . '`',
            $parts
        ));
    }

    /**
     * Safely wrap a table name 🧱
     */
    protected function wrapTable(string $table): string
    {
        return "`{$table}`";
    }

    /**
     * Check if a string contains an alias (e.g., "users AS u") 🕵️‍♂️
     */
    protected function isAliased(string $value): bool
    {
        return preg_match('/\s+AS\s+/i', $value) === 1;
    }

    /**
     * Extract the alias from an aliased string 🏹
     */
    protected function extractAlias(string $value): ?string
    {
        if (preg_match('/\s+AS\s+(.+)$/i', $value, $matches)) {
            return trim($matches[1], '` ');
        }
        return null;
    }

    /**
     * Create a string of named placeholders for a list of values 🧬
     * e.g., ":p1, :p2, :p3"
     */
    protected function parameterize(array $values): string
    {
        $placeholders = [];
        foreach ($values as $val) {
            $key = 'p' . count($this->params);
            $placeholders[] = ":{$key}";
            $this->params[$key] = $val;
        }
        return implode(', ', $placeholders);
    }
}
