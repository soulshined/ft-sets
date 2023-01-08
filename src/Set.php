<?php

namespace FT\Sets;

use Countable;
use FT\Reflection\Type;
use InvalidArgumentException;
use Iterator;
use ReflectionClass;
use Traversable;

class Set implements Countable, Iterator, Traversable {
    private $index = 0;
    protected $hashtable = [];
    private readonly bool $silently_ignore_null;

    public function __construct(array $elements = [], bool $silently_ignore_null = false)
    {
        $this->silently_ignore_null = $silently_ignore_null;
        $this->addAll($elements);
    }

    private function __construct_internal(array $that_hashtable) : Set {
        $set = new Set;

        foreach ($that_hashtable as $key => $value)
            $set->hashtable[$key] = $value;

        return $set;
    }

    // region iterator
    public function rewind(): void
    {
        $this->index = 0;
    }

    public function current(): mixed
    {
        return array_values(array_slice($this->hashtable, $this->index, 1, true))[0];
    }

    public function key(): mixed
    {
        return $this->index;
    }

    public function next(): void
    {
        ++$this->index;
    }

    public function valid(): bool
    {
        return $this->index < $this->size();
    }
    // endregion iterator

    public function add(mixed $element) : bool
    {
        if (is_null($element) && !$this->silently_ignore_null)
            throw new InvalidArgumentException("Set elements can not be null");
        else if (is_null($element)) return false;

        $hash = strval(static::hash($element));
        if (key_exists($hash, $this->hashtable))
            return false;

        $this->hashtable[$hash] = $element;
        return true;
    }

    public function addAll(array $array) {
        foreach ($array as $value)
            $this->add($value);
    }

    public function remove(mixed $element) : bool
    {
        if (is_null($element)) return false;

        $hash = strval(static::hash($element));
        if (key_exists($hash, $this->hashtable)) {
            $index = array_search($hash, array_keys($this->hashtable));
            $e = array_splice($this->hashtable, $index, 1);
            return !empty($e);
        }

        return false;
    }

    public function removeAll(array $array) {
        foreach ($array as $value)
            $this->remove($value);
    }

    public function clear()
    {
        $this->hashtable = [];
    }

    /**
     * @return int the index of the first matched element or `-1` if one is not found
     */
    public function indexOf(array | string | int | float | bool | Comparable $value): int
    {
        if ($this->isEmpty()) return -1;

        $hash = strval(static::hash($value));
        $keys = array_keys($this->hashtable);

        for ($i=0; $i < count($keys); $i++)
            if ($hash == $keys[$i]) return $i;

        return -1;
    }

    /**
     * The union of two sets A and B is the set of elements which are in A, in B, or in both A and B
     */
    public function union(Set $b) : Set
    {
        if ($b->isEmpty()) return $this;
        if ($this->isEmpty()) return $b;

        return new Set(array_merge($this->toArray(), $b->toArray()));
    }

    /**
     * The intersection of two sets A and B is the set of all objects that are members of both the sets A and B
     */
    public function intersection(Set $b) : Set
    {
        if ($b->isEmpty() || $this->isEmpty())
            return new Set;

        $out = [];

        foreach ($b->hashtable as $key => $value) {
            if (!key_exists($key, $this->hashtable)) continue;

            $out[] = $value;
        }

        return $this->__construct_internal($out);
    }

    /**
     * The difference of two sets A and B is the set of all objects that are in B but not in A
     */
    public function difference(Set $b) : Set
    {
        if ($this->isEmpty()) return $b;

        $out = [];

        foreach ($b->hashtable as $key => $value) {
            if (key_exists($key, $this->hashtable)) continue;

            $out[] = $value;
        }

        return $this->__construct_internal($out);
    }

    /**
     * A subset predicate validates all elements of this exist in B
     */
    public function subset(Set $b) : bool
    {
        if ($this->isEmpty() || $b->isEmpty()) return false;

        foreach ($this->hashtable as $key => $value)
            if (!key_exists($key, $b->hashtable)) return false;

        return true;
    }

    /**
     * @return mixed removes the last element of the Set and returns it or `null` if one does not exist
     */
    public function pop(): mixed
    {
        $element = array_splice($this->hashtable, -1);
        if (empty($element)) return null;

        return array_values($element)[0];
    }

    /**
     * @return mixed removes the first element of the Set and returns it or `null` if one does not exist
     */
    public function shift(): mixed
    {
        $element = array_splice($this->hashtable, 0, 1);
        if (empty($element)) return null;

        return array_values($element)[0];
    }

    /**
     * @return mixed the last element of the Set or `null` if one does not exist
     */
    public function last(): mixed
    {
        $last = array_key_last($this->hashtable);
        if (is_null($last)) return null;

        return $this->hashtable[$last];
    }

    /**
     * @return mixed the first element of the Set or `null` if one does not exist
     */
    public function first(): mixed
    {
        $first = array_key_first($this->hashtable);
        if (is_null($first)) return null;

        return $this->hashtable[$first];
    }

    public function isEmpty() : bool
    {
        return empty($this);
    }

    public function size(): int
    {
        return count($this->hashtable);
    }

    /**
     * @alias for size()
     */
    public function count(): int
    {
        return $this->size();
    }

    public function equals(Set $b) : bool
    {
        return join(array_keys($this->hashtable))
           === join(array_keys($b->hashtable));
    }

    // region HELPERS

    /**
     * @param mixed $element element to query. Data type is irrelevant and nested objects/arrays are taken into consideration
     * @return true|false if this set contains the given element
     */
    public function contains(mixed $element): bool {
        if (is_null($element)) return false;

        return key_exists(strval(static::hash($element)), $this->hashtable);
    }

    public function filter(callable | SetPredicate $predicate) : Set {
        return new Set(array_filter(array_values($this->hashtable), $predicate));
    }

    /**
     * @return true if any of the elements in this Set match the given predicate
     */
    public function anyMatch(callable | SetPredicate $predicate) : bool {
        foreach ($this as $value)
            if ($predicate($value) === true) return true;

        return false;
    }

    /**
     * @return true only if all of the elements in this Set match the given predicate
     */
    public function allMatch(callable | SetPredicate $predicate) : bool {
        foreach ($this as $value)
            if ($predicate($value) === false) return false;

        return true;
    }

    /**
     * @return true only if none of the elements in this Set match the given predicate
     */
    public function noneMatch(callable | SetPredicate $predicate) : bool {
        foreach ($this as $value)
            if ($predicate($value) === true) return false;

        return true;
    }

    /**
     * @return mixed `n` randomly selected elements from this Set. If quantity is `1` then the value is returned, otherwise an array of values are returned. If quantity is `1` and the Set is empty then `null` is returned
     */
    public function choose(int $quantity = 1): mixed
    {
        if ($quantity < 1 || $this->isEmpty()) {
            if ($quantity === 1) return null;
            return [];
        }
        if ($quantity >= $this->size()) return $this->toArray();

        if ($quantity === 1) {
            $key = array_rand($this->hashtable);
            return $this->hashtable[$key];
        }

        $result = [];
        foreach (array_rand($this->hashtable, $quantity) as $key)
            $result[] = $this->hashtable[$key];
        return $result;
    }

    /**
     * @return ?string the closest word in the Set compared to the given query or null if the levenshtein distance is greater than 10
     */
    public function levenshtein(string $query) : ?string {
        if ($this->isEmpty()) return null;

        $strings = $this->filter(fn ($i) => is_string($i));

        if (empty($strings)) return null;
        if ($strings->size() === 1) return $this->toArray()[0];

        $closest = -1;

        foreach ($strings as $s) {
            $lev = levenshtein($query, $s);

            if ($lev === 0) return $query;

            if ($lev < 11 && ($lev <= $closest || $closest < 0)) {
                $candidate = $s;
                $closest = $lev;
            }
        }

        return $closest === -1 ? null : $candidate;
    }

    protected static function hash(mixed $value)
    {
        $hash = 7;
        switch (gettype($value)) {
            case 'boolean':
            case 'integer':
                $hash = 31 * $hash + intval($value);
                break;
            case 'double':
                $hash = 31 * $hash + floatval($value);
                break;
            case 'array':
                foreach ($value as $v)
                    $hash += static::hash($v);
                break;
            case 'string':
                $hash = 31 * $hash + hexdec(md5($value));
                break;
            case 'object':
                if (get_class($value) === 'stdClass') {
                    foreach (get_object_vars($value) as $v)
                        $hash += static::hash($v);

                    break;
                }

                $cls = new Type(new ReflectionClass(get_class($value)));

                foreach ($cls->properties as $pd)
                    $hash += static::hash($pd->get_value($value));

                break;
        }

        return $hash;
    }
    // endregion HELPERS

    // region TRANSFORMS
    /**
     * @param array $keys if provided the values of this set will be combined to the keys provided using `array_combine()`
     */
    public function toArray(array $keys = []) : array
    {
        if (!empty($keys))
            return array_combine($keys, array_values($this->hashtable));

        return array_values($this->hashtable);
    }

    public function map(callable $callable): Set
    {
        return new Set(
            array_map($callable, array_values($this->hashtable))
        );
    }

    /**
     * @param int $depth defaults to only flatten the first level of elements. To flatten all levels use `-1`
     */
    public function flatten(int $depth = 1) : Set {
        return new Set(Utils::flatten($this->toArray(), $depth));
    }
    // endregion TRANSFORMS

}

?>