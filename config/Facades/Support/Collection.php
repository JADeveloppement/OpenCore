<?php

/**
 * Nom du Fichier : Collection.php
 * Projet : JADCoreEngine
 * @category   Framework
 * @package    JADeveloppement
 * @author     Jalal AISSAOUI <jalal.aissaoui@outlook.com>
 * @copyright  2024-2026 JADeveloppement
 * @license    https://opensource.org/licenses/MIT MIT License
 * @link       https://jadeveloppement.fr
 */

namespace Config\Facades\Support;

use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;
use Countable;
use JsonSerializable;
use Traversable;

class Collection implements IteratorAggregate, Countable, JsonSerializable, ArrayAccess
{
    protected array $items;

    public function __construct(array $items = [])
    {
        $this->items = is_array($items) ? $items : [$items];
    }

    /**
     * Transforme chaque élément de la collection
     */
    public function map(callable $callback): self
    {
        return new static(array_map($callback, $this->items));
    }

    /**
     * Filtre la collection selon un critère
     */
    public function filter(callable $callback): self
    {
        return new static(array_values(array_filter($this->items, $callback)));
    }

    /**
     * Récupère le premier élément
     */
    public function first()
    {
        return $this->items[0] ?? null;
    }

    /**
     * Calcule la somme d'une propriété spécifique
     */
    public function sum(string $key): float
    {
        return array_reduce($this->items, function ($acc, $item) use ($key) {
            $val = is_object($item) ? ($item->$key ?? 0) : ($item[$key] ?? 0);
            return $acc + (float) $val;
        }, 0);
    }

    /**
     * Retourne des éléments uniques basés sur une clé
     */
    public function unique(string $key): self
    {
        $exists = [];
        return $this->filter(function ($item) use ($key, &$exists) {
            $val = is_object($item) ? ($item->$key ?? null) : ($item[$key] ?? null);
            if (in_array($val, $exists))
                return false;
            $exists[] = $val;
            return true;
        });
    }

    /**
     * Trie la collection (ex: $col->sortBy('date_invoice', 'desc'))
     */
    public function sortBy(string $key, string $direction = 'asc'): self
    {
        $items = $this->items;
        usort($items, function ($a, $b) use ($key, $direction) {
            $valA = is_object($a) ? $a->$key : $a[$key];
            $valB = is_object($b) ? $b->$key : $b[$key];

            if ($direction === 'asc') {
                return $valA <=> $valB;
            }
            return $valB <=> $valA;
        });

        return new static($items);
    }

    /**
     * Extrait une seule colonne de la collection
     */
    public function pluck(string $key): self
    {
        return $this->map(function ($item) use ($key) {
            return is_object($item) ? ($item->$key ?? null) : ($item[$key] ?? null);
        });
    }

    /**
     * Interfaces obligatoires pour la rétro-compatibilité
     */
    public function count(): int
    {
        return count($this->items);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    public function jsonSerialize(): array
    {
        return $this->items;
    }

    public function all(): array
    {
        return $this->items;
    }

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

    public function groupBy(string $key): self
    {
        $grouped = [];
        foreach ($this->items as $item) {
            $val = is_object($item) ? $item->$key : $item[$key];
            $grouped[$val][] = $item;
        }
        return new static($grouped);
    }

    public function isEmpty(): bool
    {
        return empty($this->items);
    }
}