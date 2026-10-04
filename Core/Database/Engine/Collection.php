<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Database\Engine;

use Rbn\Framework\Core\Support\Contracts\Collections\CollectionInterface;
use ArrayAccess;
use Countable;
use IteratorAggregate;
use ArrayIterator;
use JsonSerializable;
use Traversable;

/**
 * Collection - The Database & Data Manipulation Engine 🔱🧬
 * 
 * RBN 3.5: High-Performance, fluent, and memory-safe database data wrapper.
 * Located directly under Core\Database\Engine alongside DatabaseEngine and QueryBuilder.
 */
class Collection implements CollectionInterface, ArrayAccess, Countable, IteratorAggregate, JsonSerializable
{
    /** @var array The underlying items in the collection */
    protected array $items = [];

    /**
     * Create a new collection.
     */
    public function __construct($items = [])
    {
        $this->items = $this->getArrayableItems($items);
    }

    /**
     * Convert items to array if they aren't.
     */
    protected function getArrayableItems($items): array
    {
        if (is_array($items)) {
            return $items;
        } elseif ($items instanceof self) {
            return $items->all();
        }

        return (array) $items;
    }

    /**
     * static factory method 🚀
     */
    public static function make($items = []): self
    {
        return new static($items);
    }

    /* --- CollectionInterface Implementation --- */

    public function all(): array
    {
        return $this->items;
    }

    /**
     * [D-14] TEK ALAN ERİŞİM YOLU.
     *
     * Önceden `where()`/`pluck()`/`groupBy()`/`sortBy()` doğrudan `$item[$key]`
     * ile dizi erişimi yapıyordu; hydrate edilmiş olmayan düz nesne
     * (`stdClass`) öğede `Error: Cannot use object of type stdClass as array`
     * veriyordu. Sıra: ArrayAccess nesnesi → dizi → nesne özelliği → getter →
     * (hiçbiri yoksa) null.
     *
     * DİZİ öğelerde sonuç BİREBİR aynıdır (davranış değişikliği yok).
     */
    protected function itemGet($item, string $key)
    {
        if (is_array($item)) {
            return $item[$key] ?? null;
        }
        if ($item instanceof ArrayAccess) {
            return $item->offsetExists($key) ? $item[$key] : null;
        }
        if (is_object($item)) {
            if (isset($item->{$key})) {
                return $item->{$key};
            }
            if (property_exists($item, $key)) {
                return $item->{$key};
            }
            $getter = 'get' . str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $key)));
            if (method_exists($item, $getter)) {
                return $item->{$getter}();
            }
        }
        return null;
    }

    /**
     * Advanced Filter (Where) 🔍
     */
    public function where(string $key, $operator, $value = null): self
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        return $this->filter(function($item) use ($key, $operator, $value) {
            $retrieved = $this->itemGet($item, $key);

            switch ($operator) {
                case '=':
                case '==':  return $retrieved == $value;
                case '===': return $retrieved === $value;
                case '!=':
                case '<>':  return $retrieved != $value;
                case '!==': return $retrieved !== $value;
                case '<':   return $retrieved < $value;
                case '<=':  return $retrieved <= $value;
                case '>':   return $retrieved > $value;
                case '>=':  return $retrieved >= $value;
                default:    return $retrieved == $value;
            }
        });
    }

    public function map(callable $callback): self
    {
        return new static(array_map($callback, $this->items));
    }

    public function filter(callable $callback): self
    {
        return new static(array_filter($this->items, $callback));
    }

    public function pluck(string $key): self
    {
        return $this->map(function($item) use ($key) {
            return $this->itemGet($item, $key);
        });
    }

    public function sortBy(string $key): self
    {
        $items = $this->items;
        usort($items, function($a, $b) use ($key) {
            return $this->itemGet($a, $key) <=> $this->itemGet($b, $key);
        });

        return new static($items);
    }

    public function sortByDesc(string $key): self
    {
        $items = $this->items;
        usort($items, function($a, $b) use ($key) {
            return $this->itemGet($b, $key) <=> $this->itemGet($a, $key);
        });

        return new static($items);
    }

    /**
     * [D-15] null/eksik anahtar artık DİZİ İNDİKSİ olarak kullanılmıyor.
     *
     * Önceden `$results[$item[$key] ?? null][] = $item;` idi: PHP 8.5'te
     * `Using null as an array offset is deprecated` uyarısı üretiyordu.
     * Anahtar artık `(string)` olarak normalleştirilir; null/eksik alan `''`
     * grubuna gider (gruplama KAYBI olmaz, yalnız uyarı ve tip belirsizliği gider).
     */
    public function groupBy(string $key): self
    {
        $results = [];
        foreach ($this->items as $item) {
            $grup = (string) ($this->itemGet($item, $key) ?? '');
            $results[$grup][] = $item;
        }

        return new static($results);
    }

    /**
     * [D-13] `reset(...) ?: null` idi: ilk eleman `0`, `''`, `'0'` veya `false`
     * ise **yanlışlıkla null** dönüyordu (sayaç okuyan kodlarda sessiz veri kaybı).
     */
    public function first()
    {
        if ($this->items === []) {
            return null;
        }
        return reset($this->items);
    }

    /** [D-13] `last()` için aynı falsy kaybı. */
    public function last()
    {
        if ($this->items === []) {
            return null;
        }
        return end($this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }

    /**
     * Merge the collection with a given array or collection. ✨
     */
    public function merge($items): self
    {
        return new static(array_merge($this->items, $this->getArrayableItems($items)));
    }

    /**
     * Add an item to the collection (Alias for push). ✨
     */
    public function add($item): self
    {
        return $this->push($item);
    }

    /**
     * Push an item onto the end of the collection. ✨
     */
    public function push($item): self
    {
        $this->items[] = $item;
        return $this;
    }

    /**
     * [D-49] ÖZYİNELİ NORMALİZASYON.
     *
     * Önceden düz `$this->items` dönüyordu: iç içe **Collection** nesneleri
     * diziye çevrilmiyordu (JSON'a `{}` olarak giriyordu) ve iç içe diziler
     * normalize edilmiyordu.
     *
     * KAPSAM BİLİNÇLİ DAR: yalnız `Collection` (ve `ArrayAccess` olmayan düz
     * dizi) öğeleri özyinelemeli düzleştirilir. Model nesneleri **dokunulmaz**
     * — onların `toArray()`/hydrasyon davranışı bu sınıfın sorumluluğu değildir
     * ve çağıran kod (`ContentQueryTrait` vb.) nesneleri bekliyor olabilir.
     */
    public function toArray(): array
    {
        $sonuc = [];
        foreach ($this->items as $anahtar => $oge) {
            if ($oge instanceof self) {
                $sonuc[$anahtar] = $oge->toArray();
            } elseif (is_array($oge)) {
                $sonuc[$anahtar] = $this->normalizeArray($oge);
            } else {
                $sonuc[$anahtar] = $oge;
            }
        }

        return $sonuc;
    }

    /** [D-49] `toArray()` iç çağrısı: dizinin içindeki Collection/ dizileri düzleştirir. */
    protected function normalizeArray(array $dizi): array
    {
        $sonuc = [];
        foreach ($dizi as $anahtar => $oge) {
            if ($oge instanceof self) {
                $sonuc[$anahtar] = $oge->toArray();
            } elseif (is_array($oge)) {
                $sonuc[$anahtar] = $this->normalizeArray($oge);
            } else {
                $sonuc[$anahtar] = $oge;
            }
        }

        return $sonuc;
    }

    /* --- PHP Native Interfaces --- */

    public function offsetExists($offset): bool
    {
        return isset($this->items[$offset]);
    }

    public function offsetGet($offset): mixed
    {
        return $this->items[$offset] ?? null;
    }

    public function offsetSet($offset, $value): void
    {
        if (is_null($offset)) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    public function offsetUnset($offset): void
    {
        unset($this->items[$offset]);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    /** [D-49] `json_encode()` de iç içe Collection'ları düzleştirilmiş olarak verir. */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
